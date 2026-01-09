<?php
/**
 * Plugin Name: NFC Hub
 * Plugin URI: https://github.com/yourusername/nfc-hub
 * Description: Create Elementor-editable NFC landing pages with secure analytics tracking. Manage review links, payment links, and social actions with real-time tracking.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nfc-hub
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'NFCHUB_VERSION', '1.0.0' );
define( 'NFCHUB_PLUGIN_FILE', __FILE__ );
define( 'NFCHUB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NFCHUB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NFCHUB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Fixed allowlist of action keys for tracking.
 * Security: Only these actions can be logged. Prevents arbitrary action injection.
 */
define( 'NFCHUB_ALLOWED_ACTIONS', array(
	'page_view',
	'google_review_click',
	'facebook_review_click',
	'facebook_follow_click',
	'payment_trust_click',
	'payment_consult_click',
	'payment_invoice_click',
) );

/**
 * Main plugin class
 */
class NFC_Hub {

	/**
	 * Single instance of the class
	 *
	 * @var NFC_Hub
	 */
	private static $instance = null;

	/**
	 * Get single instance
	 *
	 * @return NFC_Hub
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files
	 */
	private function load_dependencies() {
		require_once NFCHUB_PLUGIN_DIR . 'includes/class-db.php';
		require_once NFCHUB_PLUGIN_DIR . 'includes/class-cpt.php';
		require_once NFCHUB_PLUGIN_DIR . 'includes/class-rewrite.php';
		require_once NFCHUB_PLUGIN_DIR . 'includes/class-meta.php';
		require_once NFCHUB_PLUGIN_DIR . 'includes/class-rest-public.php';
		require_once NFCHUB_PLUGIN_DIR . 'includes/class-rest-admin.php';
		require_once NFCHUB_PLUGIN_DIR . 'includes/class-admin-ui.php';
		require_once NFCHUB_PLUGIN_DIR . 'includes/class-elementor-init.php';
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		// Activation hook
		register_activation_hook( NFCHUB_PLUGIN_FILE, array( $this, 'activate' ) );

		// Deactivation hook
		register_deactivation_hook( NFCHUB_PLUGIN_FILE, array( $this, 'deactivate' ) );

		// Initialize components
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// Enqueue front-end tracking script
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Create database table
		NFCHub_DB::create_table();

		// Register CPT and flush rewrite rules
		NFCHub_CPT::register();
		NFCHub_Rewrite::add_rules();
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Initialize plugin components
	 */
	public function init() {
		// Initialize CPT
		NFCHub_CPT::init();

		// Initialize rewrite rules
		NFCHub_Rewrite::init();

		// Initialize meta fields
		NFCHub_Meta::init();

		// Initialize REST API endpoints
		NFCHub_REST_Public::init();
		NFCHub_REST_Admin::init();

		// Initialize admin UI
		NFCHub_Admin_UI::init();

		// Initialize Elementor integration
		NFCHub_Elementor_Init::init();
	}

	/**
	 * Enqueue front-end tracking script on NFC Hub pages only
	 */
	public function enqueue_frontend_scripts() {
		// Only load on nfchub_page posts
		if ( ! is_singular( 'nfchub_page' ) ) {
			return;
		}

		$post_id = get_the_ID();

		// Enqueue tracking script
		wp_enqueue_script(
			'nfchub-tracker',
			NFCHUB_PLUGIN_URL . 'assets/js/tracker.js',
			array(),
			NFCHUB_VERSION,
			true
		);

		// Localize script with minimal data (security: no sensitive info)
		wp_localize_script(
			'nfchub-tracker',
			'nfcHubTracker',
			array(
				'apiUrl'     => rest_url( 'nfchub/v1/event' ),
				'pageId'     => absint( $post_id ),
				'pageSlug'   => get_post_field( 'post_name', $post_id ),
				'nonce'      => wp_create_nonce( 'wp_rest' ), // For REST API
			)
		);
	}
}

/**
 * Initialize the plugin
 */
function nfchub_init() {
	return NFC_Hub::get_instance();
}

// Start the plugin
nfchub_init();
