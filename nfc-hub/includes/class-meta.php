<?php
/**
 * Meta Fields Handler
 *
 * Manages post meta fields for nfchub_page posts.
 * Includes admin metabox with security checks.
 *
 * Security:
 * - Nonce verification on save
 * - Capability checks (current_user_can)
 * - Strict sanitization (esc_url_raw for URLs, sanitize_text_field for labels)
 * - Only allow http/https protocols for URLs
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta fields class
 */
class NFCHub_Meta {

	/**
	 * Meta key prefix
	 */
	const META_PREFIX = '_nfchub_';

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . NFCHub_CPT::POST_TYPE, array( __CLASS__, 'save_meta' ), 10, 2 );
	}

	/**
	 * Add metabox
	 */
	public static function add_meta_box() {
		add_meta_box(
			'nfchub_actions_meta',
			__( 'NFC Hub Actions', 'nfc-hub' ),
			array( __CLASS__, 'render_meta_box' ),
			NFCHub_CPT::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render metabox
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function render_meta_box( $post ) {
		// Add nonce for security
		wp_nonce_field( 'nfchub_save_meta', 'nfchub_meta_nonce' );

		$actions = NFCHub_CPT::get_action_config();

		echo '<div class="nfchub-meta-fields">';
		echo '<p class="description">' . esc_html__( 'Configure action URLs and labels. Only enabled actions with valid URLs will be displayed on the page.', 'nfc-hub' ) . '</p>';

		foreach ( $actions as $key => $config ) {
			$enabled = self::get_meta( $post->ID, "enable_{$key}", false );
			$url     = self::get_meta( $post->ID, "{$key}_url", '' );
			$label   = self::get_meta( $post->ID, "{$key}_label", '' );

			echo '<div class="nfchub-action-field" style="margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9;">';
			echo '<h3 style="margin-top: 0;">' . esc_html( $config['label'] ) . '</h3>';

			// Enable checkbox
			echo '<p>';
			echo '<label>';
			echo '<input type="checkbox" name="nfchub_enable_' . esc_attr( $key ) . '" value="1" ' . checked( $enabled, true, false ) . ' /> ';
			echo esc_html__( 'Enable this action', 'nfc-hub' );
			echo '</label>';
			echo '</p>';

			// URL field
			echo '<p>';
			echo '<label for="nfchub_url_' . esc_attr( $key ) . '">' . esc_html__( 'URL:', 'nfc-hub' ) . '</label><br>';
			echo '<input type="url" id="nfchub_url_' . esc_attr( $key ) . '" name="nfchub_url_' . esc_attr( $key ) . '" value="' . esc_attr( $url ) . '" style="width: 100%;" placeholder="https://example.com" />';
			echo '</p>';

			// Label field
			echo '<p>';
			echo '<label for="nfchub_label_' . esc_attr( $key ) . '">' . esc_html__( 'Button Label (optional):', 'nfc-hub' ) . '</label><br>';
			echo '<input type="text" id="nfchub_label_' . esc_attr( $key ) . '" name="nfchub_label_' . esc_attr( $key ) . '" value="' . esc_attr( $label ) . '" style="width: 100%;" placeholder="' . esc_attr( $config['default_label'] ) . '" />';
			echo '<small class="description">' . esc_html__( 'Leave empty to use default label.', 'nfc-hub' ) . '</small>';
			echo '</p>';

			echo '</div>';
		}

		echo '</div>';

		// Display NFC URL
		if ( 'publish' === $post->post_status ) {
			$nfc_url = home_url( '/nfc/' . $post->post_name );
			echo '<div class="nfchub-nfc-url" style="margin-top: 20px; padding: 15px; border: 2px solid #00a32a; background: #f0f6fc;">';
			echo '<h3>' . esc_html__( 'NFC Tag URL', 'nfc-hub' ) . '</h3>';
			echo '<p>' . esc_html__( 'Encode this URL to your NFC tag:', 'nfc-hub' ) . '</p>';
			echo '<p><strong><code style="font-size: 14px;">' . esc_html( $nfc_url ) . '</code></strong></p>';
			echo '<p><button type="button" class="button" onclick="navigator.clipboard.writeText(\'' . esc_js( $nfc_url ) . '\'); this.textContent=\'Copied!\';">' . esc_html__( 'Copy URL', 'nfc-hub' ) . '</button></p>';
			echo '</div>';
		}
	}

	/**
	 * Save meta data
	 *
	 * Security:
	 * - Verify nonce
	 * - Check autosave
	 * - Check user capabilities
	 * - Sanitize all inputs
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public static function save_meta( $post_id, $post ) {
		// Verify nonce
		if ( ! isset( $_POST['nfchub_meta_nonce'] ) || ! wp_verify_nonce( $_POST['nfchub_meta_nonce'], 'nfchub_save_meta' ) ) {
			return;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check user capabilities
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$actions = NFCHub_CPT::get_action_config();

		foreach ( $actions as $key => $config ) {
			// Save enabled state
			$enabled = isset( $_POST[ "nfchub_enable_{$key}" ] ) ? true : false;
			self::update_meta( $post_id, "enable_{$key}", $enabled );

			// Save and sanitize URL (security: only allow http/https)
			if ( isset( $_POST[ "nfchub_url_{$key}" ] ) ) {
				$url = $_POST[ "nfchub_url_{$key}" ];
				// Sanitize URL and ensure only http/https protocols
				$url = esc_url_raw( $url, array( 'http', 'https' ) );
				self::update_meta( $post_id, "{$key}_url", $url );
			}

			// Save and sanitize label
			if ( isset( $_POST[ "nfchub_label_{$key}" ] ) ) {
				$label = sanitize_text_field( $_POST[ "nfchub_label_{$key}" ] );
				self::update_meta( $post_id, "{$key}_label", $label );
			}
		}
	}

	/**
	 * Get meta value
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key (without prefix).
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_meta( $post_id, $key, $default = '' ) {
		$value = get_post_meta( $post_id, self::META_PREFIX . $key, true );
		return ( '' !== $value ) ? $value : $default;
	}

	/**
	 * Update meta value
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key (without prefix).
	 * @param mixed  $value Value to save.
	 */
	public static function update_meta( $post_id, $key, $value ) {
		update_post_meta( $post_id, self::META_PREFIX . $key, $value );
	}

	/**
	 * Get action data for a page
	 *
	 * Returns all action data for rendering on front-end.
	 * Only returns enabled actions with valid URLs.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_actions_data( $post_id ) {
		$actions = NFCHub_CPT::get_action_config();
		$data    = array();

		foreach ( $actions as $key => $config ) {
			$enabled = self::get_meta( $post_id, "enable_{$key}", false );
			$url     = self::get_meta( $post_id, "{$key}_url", '' );
			$label   = self::get_meta( $post_id, "{$key}_label", '' );

			// Only include if enabled and URL is valid
			if ( $enabled && ! empty( $url ) ) {
				$data[ $key ] = array(
					'enabled'    => true,
					'url'        => $url,
					'label'      => ! empty( $label ) ? $label : $config['default_label'],
					'action_key' => $config['action_key'],
					'icon'       => $config['icon'],
				);
			}
		}

		return $data;
	}

	/**
	 * Check if an action is enabled
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action_key Action key (e.g., 'google_review_click').
	 * @return bool
	 */
	public static function is_action_enabled( $post_id, $action_key ) {
		$actions = NFCHub_CPT::get_action_config();

		foreach ( $actions as $key => $config ) {
			if ( $config['action_key'] === $action_key ) {
				$enabled = self::get_meta( $post_id, "enable_{$key}", false );
				$url     = self::get_meta( $post_id, "{$key}_url", '' );
				return $enabled && ! empty( $url );
			}
		}

		return false;
	}
}
