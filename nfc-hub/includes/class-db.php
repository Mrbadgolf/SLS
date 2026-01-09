<?php
/**
 * Database Handler
 *
 * Creates and manages the custom events table for analytics.
 *
 * Security:
 * - All queries use $wpdb->prepare() with placeholders
 * - IP addresses are hashed with salt (never stored raw)
 * - Strict validation of all inputs
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database class
 */
class NFCHub_DB {

	/**
	 * Table name (without prefix)
	 */
	const TABLE_NAME = 'nfchub_events';

	/**
	 * Get full table name with WordPress prefix
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Create database table
	 *
	 * Called on plugin activation.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			occurred_at DATETIME NOT NULL,
			page_id BIGINT(20) UNSIGNED NOT NULL,
			page_slug VARCHAR(200) NOT NULL,
			action_key VARCHAR(64) NOT NULL,
			ip_hash CHAR(64) NOT NULL,
			user_agent TEXT,
			referrer TEXT,
			utm_source VARCHAR(255),
			utm_medium VARCHAR(255),
			utm_campaign VARCHAR(255),
			utm_term VARCHAR(255),
			utm_content VARCHAR(255),
			meta_json LONGTEXT,
			PRIMARY KEY (id),
			KEY occurred_at (occurred_at),
			KEY page_id (page_id),
			KEY page_slug (page_slug),
			KEY action_key (action_key),
			KEY ip_hash (ip_hash)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert event
	 *
	 * Security: Uses prepared statements for all data.
	 *
	 * @param array $data Event data.
	 * @return int|false Insert ID or false on failure.
	 */
	public static function insert_event( $data ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// Prepare data with defaults
		$insert_data = array(
			'occurred_at'  => current_time( 'mysql', true ),
			'page_id'      => absint( $data['page_id'] ),
			'page_slug'    => sanitize_title( $data['page_slug'] ),
			'action_key'   => sanitize_key( $data['action_key'] ),
			'ip_hash'      => self::hash_ip( $data['ip'] ?? '' ),
			'user_agent'   => isset( $data['user_agent'] ) ? substr( sanitize_text_field( $data['user_agent'] ), 0, 500 ) : '',
			'referrer'     => isset( $data['referrer'] ) ? esc_url_raw( substr( $data['referrer'], 0, 500 ) ) : '',
			'utm_source'   => isset( $data['utm_source'] ) ? sanitize_text_field( substr( $data['utm_source'], 0, 255 ) ) : null,
			'utm_medium'   => isset( $data['utm_medium'] ) ? sanitize_text_field( substr( $data['utm_medium'], 0, 255 ) ) : null,
			'utm_campaign' => isset( $data['utm_campaign'] ) ? sanitize_text_field( substr( $data['utm_campaign'], 0, 255 ) ) : null,
			'utm_term'     => isset( $data['utm_term'] ) ? sanitize_text_field( substr( $data['utm_term'], 0, 255 ) ) : null,
			'utm_content'  => isset( $data['utm_content'] ) ? sanitize_text_field( substr( $data['utm_content'], 0, 255 ) ) : null,
			'meta_json'    => isset( $data['meta'] ) ? wp_json_encode( $data['meta'] ) : null,
		);

		$insert_format = array(
			'%s', // occurred_at
			'%d', // page_id
			'%s', // page_slug
			'%s', // action_key
			'%s', // ip_hash
			'%s', // user_agent
			'%s', // referrer
			'%s', // utm_source
			'%s', // utm_medium
			'%s', // utm_campaign
			'%s', // utm_term
			'%s', // utm_content
			'%s', // meta_json
		);

		$result = $wpdb->insert( $table_name, $insert_data, $insert_format );

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Hash IP address
	 *
	 * Security: IP addresses are hashed with a salt and never stored raw.
	 * This prevents PII exposure while still allowing rate limiting.
	 *
	 * @param string $ip IP address.
	 * @return string
	 */
	public static function hash_ip( $ip ) {
		// Use WordPress salt for hashing
		$salt = defined( 'NONCE_SALT' ) ? NONCE_SALT : 'nfchub-default-salt';
		return hash( 'sha256', $ip . $salt );
	}

	/**
	 * Get recent events
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_recent_events( $args = array() ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$defaults = array(
			'limit'     => 50,
			'offset'    => 0,
			'page_id'   => null,
			'action_key' => null,
		);

		$args = wp_parse_args( $args, $defaults );

		// Build WHERE clause
		$where = array( '1=1' );

		if ( ! empty( $args['page_id'] ) ) {
			$where[] = $wpdb->prepare( 'page_id = %d', absint( $args['page_id'] ) );
		}

		if ( ! empty( $args['action_key'] ) ) {
			$where[] = $wpdb->prepare( 'action_key = %s', sanitize_key( $args['action_key'] ) );
		}

		$where_sql = implode( ' AND ', $where );

		// Security: Use prepared statement with placeholders
		$sql = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY occurred_at DESC LIMIT %d OFFSET %d",
			absint( $args['limit'] ),
			absint( $args['offset'] )
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Get summary statistics
	 *
	 * @param int $days Number of days to look back.
	 * @return array
	 */
	public static function get_summary( $days = 7 ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// Security: Use prepared statement
		$sql = $wpdb->prepare(
			"SELECT
				COUNT(*) as total_events,
				COUNT(DISTINCT page_id) as unique_pages,
				COUNT(DISTINCT ip_hash) as unique_visitors
			FROM {$table_name}
			WHERE occurred_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
			absint( $days )
		);

		return $wpdb->get_row( $sql, ARRAY_A );
	}

	/**
	 * Get top actions
	 *
	 * @param int $days Number of days to look back.
	 * @param int $limit Number of results.
	 * @return array
	 */
	public static function get_top_actions( $days = 30, $limit = 10 ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// Security: Use prepared statement
		$sql = $wpdb->prepare(
			"SELECT
				action_key,
				COUNT(*) as count
			FROM {$table_name}
			WHERE occurred_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			GROUP BY action_key
			ORDER BY count DESC
			LIMIT %d",
			absint( $days ),
			absint( $limit )
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Get top pages
	 *
	 * @param int $days Number of days to look back.
	 * @param int $limit Number of results.
	 * @return array
	 */
	public static function get_top_pages( $days = 30, $limit = 10 ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// Security: Use prepared statement
		$sql = $wpdb->prepare(
			"SELECT
				page_id,
				page_slug,
				COUNT(*) as count
			FROM {$table_name}
			WHERE occurred_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			GROUP BY page_id, page_slug
			ORDER BY count DESC
			LIMIT %d",
			absint( $days ),
			absint( $limit )
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Get timeseries data
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_timeseries( $args = array() ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$defaults = array(
			'days'    => 30,
			'page_id' => null,
		);

		$args = wp_parse_args( $args, $defaults );

		// Build WHERE clause
		$where = array();
		$where[] = $wpdb->prepare( 'occurred_at >= DATE_SUB(NOW(), INTERVAL %d DAY)', absint( $args['days'] ) );

		if ( ! empty( $args['page_id'] ) ) {
			$where[] = $wpdb->prepare( 'page_id = %d', absint( $args['page_id'] ) );
		}

		$where_sql = implode( ' AND ', $where );

		// Security: Use prepared statement
		$sql = "SELECT
			DATE(occurred_at) as date,
			action_key,
			COUNT(*) as count
		FROM {$table_name}
		WHERE {$where_sql}
		GROUP BY DATE(occurred_at), action_key
		ORDER BY date DESC, action_key";

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Count events by IP hash in time window (for rate limiting)
	 *
	 * @param string $ip_hash IP hash.
	 * @param int    $minutes Time window in minutes.
	 * @return int
	 */
	public static function count_events_by_ip( $ip_hash, $minutes = 5 ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// Security: Use prepared statement
		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name}
			WHERE ip_hash = %s
			AND occurred_at >= DATE_SUB(NOW(), INTERVAL %d MINUTE)",
			$ip_hash,
			absint( $minutes )
		);

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Count events by page in time window (for rate limiting)
	 *
	 * @param int $page_id Page ID.
	 * @param int $hours Time window in hours.
	 * @return int
	 */
	public static function count_events_by_page( $page_id, $hours = 1 ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// Security: Use prepared statement
		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name}
			WHERE page_id = %d
			AND occurred_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)",
			absint( $page_id ),
			absint( $hours )
		);

		return (int) $wpdb->get_var( $sql );
	}
}
