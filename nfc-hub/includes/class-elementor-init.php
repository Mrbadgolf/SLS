<?php
/**
 * Elementor Integration Loader
 *
 * Initializes Elementor widgets and dynamic tags.
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor initialization class
 */
class NFCHub_Elementor_Init {

	/**
	 * Initialize
	 */
	public static function init() {
		// Check if Elementor is active
		add_action( 'elementor/init', array( __CLASS__, 'on_elementor_init' ) );

		// Register widgets
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );

		// Register dynamic tags (Elementor Pro)
		add_action( 'elementor/dynamic_tags/register', array( __CLASS__, 'register_dynamic_tags' ) );
	}

	/**
	 * On Elementor init
	 */
	public static function on_elementor_init() {
		// Create a category for our widgets
		\Elementor\Plugin::instance()->elements_manager->add_category(
			'nfc-hub',
			array(
				'title' => __( 'NFC Hub', 'nfc-hub' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * Register widgets
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 */
	public static function register_widgets( $widgets_manager ) {
		// Load widget files
		require_once NFCHUB_PLUGIN_DIR . 'includes/elementor/widgets/action-button.php';
		require_once NFCHUB_PLUGIN_DIR . 'includes/elementor/widgets/action-list.php';

		// Register widgets
		$widgets_manager->register( new \NFCHub_Elementor_Widget_Action_Button() );
		$widgets_manager->register( new \NFCHub_Elementor_Widget_Action_List() );
	}

	/**
	 * Register dynamic tags
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
	 */
	public static function register_dynamic_tags( $dynamic_tags_manager ) {
		// Load dynamic tag files
		require_once NFCHUB_PLUGIN_DIR . 'includes/elementor/dynamic-tags/action-url.php';
		require_once NFCHUB_PLUGIN_DIR . 'includes/elementor/dynamic-tags/action-label.php';
		require_once NFCHUB_PLUGIN_DIR . 'includes/elementor/dynamic-tags/action-enabled.php';

		// Register tag group
		$dynamic_tags_manager->register_group(
			'nfc-hub',
			array(
				'title' => __( 'NFC Hub', 'nfc-hub' ),
			)
		);

		// Register tags
		$dynamic_tags_manager->register( new \NFCHub_Dynamic_Tag_Action_URL() );
		$dynamic_tags_manager->register( new \NFCHub_Dynamic_Tag_Action_Label() );
		$dynamic_tags_manager->register( new \NFCHub_Dynamic_Tag_Action_Enabled() );
	}
}
