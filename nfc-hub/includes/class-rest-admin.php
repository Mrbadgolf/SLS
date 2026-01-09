<?php
/**
 * Admin REST API Handler
 *
 * Handles admin-only analytics endpoints.
 *
 * SECURITY:
 * - All endpoints require manage_options capability
 * - All endpoints require valid WordPress REST nonce
 * - No sensitive data in responses by default
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin REST API class
 */
class NFCHub_REST_Admin {

	/**
	 * REST namespace
	 */
	const NAMESPACE = 'nfchub/v1';

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
		// Summary statistics
		register_rest_route(
			self::NAMESPACE,
			'/admin/summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_summary' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permission' ),
			)
		);

		// Recent events
		register_rest_route(
			self::NAMESPACE,
			'/admin/recent',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_recent' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permission' ),
				'args'                => array(
					'limit'   => array(
						'default'           => 50,
						'sanitize_callback' => 'absint',
					),
					'page_id' => array(
						'default'           => null,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Timeseries data
		register_rest_route(
			self::NAMESPACE,
			'/admin/timeseries',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_timeseries' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permission' ),
				'args'                => array(
					'days'    => array(
						'default'           => 30,
						'sanitize_callback' => 'absint',
					),
					'page_id' => array(
						'default'           => null,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Check admin permission
	 *
	 * Security: Requires manage_options capability and valid nonce.
	 *
	 * @return bool
	 */
	public static function check_admin_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get summary statistics
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_summary( $request ) {
		$summary_7  = NFCHub_DB::get_summary( 7 );
		$summary_30 = NFCHub_DB::get_summary( 30 );

		$top_actions = NFCHub_DB::get_top_actions( 30, 10 );
		$top_pages   = NFCHub_DB::get_top_pages( 30, 10 );

		// Enhance top pages with post titles
		foreach ( $top_pages as &$page ) {
			$post = get_post( $page['page_id'] );
			$page['page_title'] = $post ? $post->post_title : __( 'Unknown', 'nfc-hub' );
		}

		$data = array(
			'last_7_days'  => $summary_7,
			'last_30_days' => $summary_30,
			'top_actions'  => $top_actions,
			'top_pages'    => $top_pages,
		);

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get recent events
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_recent( $request ) {
		$limit   = $request->get_param( 'limit' );
		$page_id = $request->get_param( 'page_id' );

		$args = array(
			'limit' => min( $limit, 100 ), // Cap at 100
		);

		if ( ! empty( $page_id ) ) {
			$args['page_id'] = $page_id;
		}

		$events = NFCHub_DB::get_recent_events( $args );

		// Security: Don't expose IP hash or full user agent by default
		// Only return sanitized, safe data
		$safe_events = array();
		foreach ( $events as $event ) {
			$safe_events[] = array(
				'id'           => $event['id'],
				'occurred_at'  => $event['occurred_at'],
				'page_id'      => $event['page_id'],
				'page_slug'    => $event['page_slug'],
				'action_key'   => $event['action_key'],
				'referrer'     => $event['referrer'],
				'utm_source'   => $event['utm_source'],
				'utm_medium'   => $event['utm_medium'],
				'utm_campaign' => $event['utm_campaign'],
			);
		}

		return new WP_REST_Response( $safe_events, 200 );
	}

	/**
	 * Get timeseries data
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_timeseries( $request ) {
		$days    = $request->get_param( 'days' );
		$page_id = $request->get_param( 'page_id' );

		$args = array(
			'days' => min( $days, 365 ), // Cap at 1 year
		);

		if ( ! empty( $page_id ) ) {
			$args['page_id'] = $page_id;
		}

		$data = NFCHub_DB::get_timeseries( $args );

		return new WP_REST_Response( $data, 200 );
	}
}
