<?php
/**
 * Admin UI Handler
 *
 * Handles the admin analytics dashboard.
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI class
 */
class NFCHub_Admin_UI {

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add admin menu
	 */
	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=' . NFCHub_CPT::POST_TYPE,
			__( 'Analytics', 'nfc-hub' ),
			__( 'Analytics', 'nfc-hub' ),
			'manage_options',
			'nfchub-analytics',
			array( __CLASS__, 'render_analytics_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_admin_assets( $hook ) {
		// Only load on our analytics page
		if ( 'nfchub_page_nfchub-analytics' !== $hook ) {
			return;
		}

		// Enqueue CSS
		wp_enqueue_style(
			'nfchub-admin',
			NFCHUB_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			NFCHUB_VERSION
		);

		// Enqueue JS
		wp_enqueue_script(
			'nfchub-admin',
			NFCHUB_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			NFCHUB_VERSION,
			true
		);

		// Localize script with API data
		wp_localize_script(
			'nfchub-admin',
			'nfcHubAdmin',
			array(
				'apiUrl' => rest_url( NFCHub_REST_Admin::NAMESPACE ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Render analytics page
	 */
	public static function render_analytics_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nfc-hub' ) );
		}

		// Get all NFC pages for filter
		$pages = get_posts(
			array(
				'post_type'      => NFCHub_CPT::POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		?>
		<div class="wrap nfchub-analytics">
			<h1><?php esc_html_e( 'NFC Hub Analytics', 'nfc-hub' ); ?></h1>

			<!-- Filter -->
			<div class="nfchub-filter">
				<label for="nfchub-page-filter"><?php esc_html_e( 'Filter by Page:', 'nfc-hub' ); ?></label>
				<select id="nfchub-page-filter">
					<option value=""><?php esc_html_e( 'All Pages', 'nfc-hub' ); ?></option>
					<?php foreach ( $pages as $page ) : ?>
						<option value="<?php echo esc_attr( $page->ID ); ?>">
							<?php echo esc_html( $page->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button" id="nfchub-refresh-btn">
					<?php esc_html_e( 'Refresh', 'nfc-hub' ); ?>
				</button>
			</div>

			<!-- Summary Cards -->
			<div class="nfchub-summary">
				<div class="nfchub-card">
					<h3><?php esc_html_e( 'Last 7 Days', 'nfc-hub' ); ?></h3>
					<div class="nfchub-stat">
						<div class="nfchub-stat-item">
							<span class="nfchub-stat-label"><?php esc_html_e( 'Total Events', 'nfc-hub' ); ?></span>
							<span class="nfchub-stat-value" id="stat-7d-events">-</span>
						</div>
						<div class="nfchub-stat-item">
							<span class="nfchub-stat-label"><?php esc_html_e( 'Unique Pages', 'nfc-hub' ); ?></span>
							<span class="nfchub-stat-value" id="stat-7d-pages">-</span>
						</div>
						<div class="nfchub-stat-item">
							<span class="nfchub-stat-label"><?php esc_html_e( 'Unique Visitors', 'nfc-hub' ); ?></span>
							<span class="nfchub-stat-value" id="stat-7d-visitors">-</span>
						</div>
					</div>
				</div>

				<div class="nfchub-card">
					<h3><?php esc_html_e( 'Last 30 Days', 'nfc-hub' ); ?></h3>
					<div class="nfchub-stat">
						<div class="nfchub-stat-item">
							<span class="nfchub-stat-label"><?php esc_html_e( 'Total Events', 'nfc-hub' ); ?></span>
							<span class="nfchub-stat-value" id="stat-30d-events">-</span>
						</div>
						<div class="nfchub-stat-item">
							<span class="nfchub-stat-label"><?php esc_html_e( 'Unique Pages', 'nfc-hub' ); ?></span>
							<span class="nfchub-stat-value" id="stat-30d-pages">-</span>
						</div>
						<div class="nfchub-stat-item">
							<span class="nfchub-stat-label"><?php esc_html_e( 'Unique Visitors', 'nfc-hub' ); ?></span>
							<span class="nfchub-stat-value" id="stat-30d-visitors">-</span>
						</div>
					</div>
				</div>
			</div>

			<!-- Top Actions and Pages -->
			<div class="nfchub-top-stats">
				<div class="nfchub-card">
					<h3><?php esc_html_e( 'Top Actions (30 Days)', 'nfc-hub' ); ?></h3>
					<table class="widefat" id="nfchub-top-actions">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Action', 'nfc-hub' ); ?></th>
								<th><?php esc_html_e( 'Count', 'nfc-hub' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td colspan="2"><?php esc_html_e( 'Loading...', 'nfc-hub' ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>

				<div class="nfchub-card">
					<h3><?php esc_html_e( 'Top Pages (30 Days)', 'nfc-hub' ); ?></h3>
					<table class="widefat" id="nfchub-top-pages">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Page', 'nfc-hub' ); ?></th>
								<th><?php esc_html_e( 'Count', 'nfc-hub' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td colspan="2"><?php esc_html_e( 'Loading...', 'nfc-hub' ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Recent Events -->
			<div class="nfchub-card">
				<h3><?php esc_html_e( 'Recent Events', 'nfc-hub' ); ?></h3>
				<table class="widefat striped" id="nfchub-recent-events">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date/Time', 'nfc-hub' ); ?></th>
							<th><?php esc_html_e( 'Page', 'nfc-hub' ); ?></th>
							<th><?php esc_html_e( 'Action', 'nfc-hub' ); ?></th>
							<th><?php esc_html_e( 'Referrer', 'nfc-hub' ); ?></th>
							<th><?php esc_html_e( 'UTM Source', 'nfc-hub' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="5"><?php esc_html_e( 'Loading...', 'nfc-hub' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Auto-refresh notice -->
			<p class="description">
				<?php esc_html_e( 'Summary and recent events refresh every 10 seconds.', 'nfc-hub' ); ?>
			</p>
		</div>
		<?php
	}
}
