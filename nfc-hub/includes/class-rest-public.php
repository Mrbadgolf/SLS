<?php
/**
 * Public REST API Handler
 *
 * Handles the public event tracking endpoint.
 *
 * SECURITY THREAT MODEL:
 * - Malicious actors may attempt to:
 *   1. Enumerate valid page slugs (mitigated by generic error messages)
 *   2. Spam events to fill database (mitigated by rate limiting)
 *   3. Inject malicious data (mitigated by strict validation and sanitization)
 *   4. Exfiltrate data via tracking (mitigated by not accepting URLs from client)
 *   5. Execute XSS via stored data (mitigated by sanitization on input and escaping on output)
 *
 * SECURITY MEASURES:
 * - Fixed allowlist of action_key values
 * - Strict validation: page must exist, slug must match, action must be enabled
 * - Rate limiting: 30 events per IP per 5 min, 300 events per page per hour
 * - Payload size limit: 4KB max
 * - No sensitive data in responses
 * - All queries use prepared statements
 * - Never accept destination URLs from client
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public REST API class
 */
class NFCHub_REST_Public {

	/**
	 * REST namespace
	 */
	const NAMESPACE = 'nfchub/v1';

	/**
	 * Maximum payload size (4KB)
	 */
	const MAX_PAYLOAD_SIZE = 4096;

	/**
	 * Rate limit: events per IP per time window
	 */
	const RATE_LIMIT_IP = 30;
	const RATE_LIMIT_IP_WINDOW = 5; // minutes

	/**
	 * Rate limit: events per page per time window
	 */
	const RATE_LIMIT_PAGE = 300;
	const RATE_LIMIT_PAGE_WINDOW = 1; // hours

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST routes
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/event',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'log_event' ),
				'permission_callback' => '__return_true', // Public endpoint
				'args'                => array(
					'page_id'      => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => function( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
					),
					'page_slug'    => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_title',
						'validate_callback' => function( $param ) {
							return is_string( $param ) && strlen( $param ) > 0 && strlen( $param ) < 200;
						},
					),
					'action_key'   => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => function( $param ) {
							return in_array( $param, NFCHUB_ALLOWED_ACTIONS, true );
						},
					),
					'referrer'     => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
					'utm_source'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'utm_medium'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'utm_campaign' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'utm_term'     => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'utm_content'  => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'meta'         => array(
						'required' => false,
						'type'     => 'object',
					),
				),
			)
		);
	}

	/**
	 * Log event
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function log_event( $request ) {
		// Security: Check payload size (prevent large payloads)
		$content_length = isset( $_SERVER['CONTENT_LENGTH'] ) ? intval( $_SERVER['CONTENT_LENGTH'] ) : 0;
		if ( $content_length > self::MAX_PAYLOAD_SIZE ) {
			return new WP_REST_Response(
				array( 'success' => false ),
				400
			);
		}

		// Get parameters (already sanitized by REST API)
		$page_id     = $request->get_param( 'page_id' );
		$page_slug   = $request->get_param( 'page_slug' );
		$action_key  = $request->get_param( 'action_key' );

		// Security: Verify page exists and is published
		$post = get_post( $page_id );
		if ( ! $post || NFCHub_CPT::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			// Generic error to prevent enumeration
			return new WP_REST_Response(
				array( 'success' => false ),
				400
			);
		}

		// Security: Verify slug matches page_id (prevent ID spoofing)
		if ( $post->post_name !== $page_slug ) {
			return new WP_REST_Response(
				array( 'success' => false ),
				400
			);
		}

		// Security: Verify action is enabled for this page (except page_view which is always allowed)
		if ( 'page_view' !== $action_key ) {
			if ( ! NFCHub_Meta::is_action_enabled( $page_id, $action_key ) ) {
				return new WP_REST_Response(
					array( 'success' => false ),
					400
				);
			}
		}

		// Get client IP (hash it before storing)
		$ip = self::get_client_ip();
		$ip_hash = NFCHub_DB::hash_ip( $ip );

		// Security: Rate limiting by IP
		$ip_event_count = NFCHub_DB::count_events_by_ip( $ip_hash, self::RATE_LIMIT_IP_WINDOW );
		if ( $ip_event_count >= self::RATE_LIMIT_IP ) {
			return new WP_REST_Response(
				array( 'success' => false ),
				429 // Too Many Requests
			);
		}

		// Security: Rate limiting by page
		$page_event_count = NFCHub_DB::count_events_by_page( $page_id, self::RATE_LIMIT_PAGE_WINDOW );
		if ( $page_event_count >= self::RATE_LIMIT_PAGE ) {
			return new WP_REST_Response(
				array( 'success' => false ),
				429
			);
		}

		// Get user agent
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';

		// Prepare event data
		$event_data = array(
			'page_id'      => $page_id,
			'page_slug'    => $page_slug,
			'action_key'   => $action_key,
			'ip'           => $ip, // Will be hashed in insert_event
			'user_agent'   => $user_agent,
			'referrer'     => $request->get_param( 'referrer' ),
			'utm_source'   => $request->get_param( 'utm_source' ),
			'utm_medium'   => $request->get_param( 'utm_medium' ),
			'utm_campaign' => $request->get_param( 'utm_campaign' ),
			'utm_term'     => $request->get_param( 'utm_term' ),
			'utm_content'  => $request->get_param( 'utm_content' ),
		);

		// Add meta if provided (sanitize)
		$meta = $request->get_param( 'meta' );
		if ( ! empty( $meta ) && is_array( $meta ) ) {
			// Security: Limit meta data
			$meta = array_slice( $meta, 0, 10 ); // Max 10 keys
			$meta = array_map( 'sanitize_text_field', $meta );
			$event_data['meta'] = $meta;
		}

		// Insert event
		$result = NFCHub_DB::insert_event( $event_data );

		if ( false === $result ) {
			return new WP_REST_Response(
				array( 'success' => false ),
				500
			);
		}

		return new WP_REST_Response(
			array( 'success' => true ),
			200
		);
	}

	/**
	 * Get client IP address
	 *
	 * Handles various proxy headers.
	 *
	 * @return string
	 */
	private static function get_client_ip() {
		// Check for proxy headers
		$headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = $_SERVER[ $header ];
				// If X-Forwarded-For has multiple IPs, take the first one
				if ( strpos( $ip, ',' ) !== false ) {
					$ips = explode( ',', $ip );
					$ip  = trim( $ips[0] );
				}
				// Validate IP
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}
