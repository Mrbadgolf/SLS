<?php
/**
 * Elementor Dynamic Tag: Action Enabled
 *
 * Returns whether an action is enabled (for conditional display).
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action Enabled dynamic tag
 */
class NFCHub_Dynamic_Tag_Action_Enabled extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Get tag name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'nfchub-action-enabled';
	}

	/**
	 * Get tag title
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Action Enabled', 'nfc-hub' );
	}

	/**
	 * Get tag group
	 *
	 * @return string
	 */
	public function get_group() {
		return 'nfc-hub';
	}

	/**
	 * Get tag categories
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	/**
	 * Register controls
	 */
	protected function register_controls() {
		$actions = NFCHub_CPT::get_action_config();
		$options = array();

		foreach ( $actions as $key => $config ) {
			$options[ $key ] = $config['label'];
		}

		$this->add_control(
			'action',
			array(
				'label'   => __( 'Action', 'nfc-hub' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $options,
				'default' => 'google_review',
			)
		);
	}

	/**
	 * Render tag
	 */
	public function render() {
		$action  = $this->get_settings( 'action' );
		$post_id = get_the_ID();

		$enabled = NFCHub_Meta::get_meta( $post_id, "enable_{$action}", false );
		$url     = NFCHub_Meta::get_meta( $post_id, "{$action}_url", '' );

		// Action is enabled only if toggle is on AND URL is set
		$is_enabled = $enabled && ! empty( $url );

		echo $is_enabled ? 'yes' : 'no';
	}
}
