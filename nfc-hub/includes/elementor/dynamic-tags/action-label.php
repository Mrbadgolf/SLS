<?php
/**
 * Elementor Dynamic Tag: Action Label
 *
 * Returns the label for a specific action.
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action Label dynamic tag
 */
class NFCHub_Dynamic_Tag_Action_Label extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Get tag name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'nfchub-action-label';
	}

	/**
	 * Get tag title
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Action Label', 'nfc-hub' );
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

		$actions = NFCHub_CPT::get_action_config();
		$label   = NFCHub_Meta::get_meta( $post_id, "{$action}_label", '' );

		// Use custom label or default
		if ( empty( $label ) && isset( $actions[ $action ] ) ) {
			$label = $actions[ $action ]['default_label'];
		}

		// Security: Escape text
		echo esc_html( $label );
	}
}
