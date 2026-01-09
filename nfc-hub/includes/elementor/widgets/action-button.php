<?php
/**
 * Elementor Widget: NFCHub Action Button
 *
 * Displays a single action button with automatic tracking.
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action Button widget
 */
class NFCHub_Elementor_Widget_Action_Button extends \Elementor\Widget_Base {

	/**
	 * Get widget name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'nfchub-action-button';
	}

	/**
	 * Get widget title
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'NFCHub Action Button', 'nfc-hub' );
	}

	/**
	 * Get widget icon
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-button';
	}

	/**
	 * Get widget categories
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'nfc-hub' );
	}

	/**
	 * Register widget controls
	 */
	protected function register_controls() {
		// Content Section
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'nfc-hub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$actions = NFCHub_CPT::get_action_config();
		$options = array();

		foreach ( $actions as $key => $config ) {
			$options[ $config['action_key'] ] = $config['label'];
		}

		$this->add_control(
			'action_key',
			array(
				'label'   => __( 'Action', 'nfc-hub' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $options,
				'default' => 'google_review_click',
			)
		);

		$this->add_control(
			'label_override',
			array(
				'label'       => __( 'Custom Label', 'nfc-hub' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'Leave empty to use default label', 'nfc-hub' ),
			)
		);

		$this->add_control(
			'open_new_tab',
			array(
				'label'        => __( 'Open in New Tab', 'nfc-hub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'nfc-hub' ),
				'label_off'    => __( 'No', 'nfc-hub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_icon',
			array(
				'label'        => __( 'Show Icon', 'nfc-hub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'nfc-hub' ),
				'label_off'    => __( 'No', 'nfc-hub' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->end_controls_section();

		// Style Section
		$this->start_controls_section(
			'style_section',
			array(
				'label' => __( 'Button Style', 'nfc-hub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .nfchub-action-button',
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Text Color', 'nfc-hub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .nfchub-action-button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_bg_color',
			array(
				'label'     => __( 'Background Color', 'nfc-hub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .nfchub-action-button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .nfchub-action-button',
			)
		);

		$this->add_control(
			'button_border_radius',
			array(
				'label'      => __( 'Border Radius', 'nfc-hub' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .nfchub-action-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => __( 'Padding', 'nfc-hub' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .nfchub-action-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_align',
			array(
				'label'     => __( 'Alignment', 'nfc-hub' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => __( 'Left', 'nfc-hub' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'nfc-hub' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'nfc-hub' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .nfchub-action-button-wrapper' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output
	 */
	protected function render() {
		$settings   = $this->get_settings_for_display();
		$action_key = $settings['action_key'];
		$post_id    = get_the_ID();

		// Get action config
		$actions       = NFCHub_CPT::get_action_config();
		$action_config = null;

		foreach ( $actions as $key => $config ) {
			if ( $config['action_key'] === $action_key ) {
				$action_config = $config;
				$meta_key      = $key;
				break;
			}
		}

		if ( ! $action_config ) {
			return;
		}

		// Get action data from meta
		$enabled = NFCHub_Meta::get_meta( $post_id, "enable_{$meta_key}", false );
		$url     = NFCHub_Meta::get_meta( $post_id, "{$meta_key}_url", '' );
		$label   = NFCHub_Meta::get_meta( $post_id, "{$meta_key}_label", '' );

		// Use custom label if provided
		if ( ! empty( $settings['label_override'] ) ) {
			$label = $settings['label_override'];
		} elseif ( empty( $label ) ) {
			$label = $action_config['default_label'];
		}

		// Check if action is enabled
		$is_enabled = $enabled && ! empty( $url );

		// In editor mode, always show a placeholder
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			if ( ! $is_enabled ) {
				echo '<div class="nfchub-action-button-wrapper">';
				echo '<span class="nfchub-placeholder" style="display: inline-block; padding: 12px 24px; background: #f0f0f0; border: 2px dashed #ccc; color: #666;">';
				echo esc_html( $label ) . ' (' . esc_html__( 'Disabled - Configure in NFC Hub Actions', 'nfc-hub' ) . ')';
				echo '</span>';
				echo '</div>';
				return;
			}
		} else {
			// On front-end, don't render if disabled
			if ( ! $is_enabled ) {
				return;
			}
		}

		// Render button
		$target = 'yes' === $settings['open_new_tab'] ? '_blank' : '_self';

		echo '<div class="nfchub-action-button-wrapper">';
		echo '<a href="' . esc_url( $url ) . '" class="nfchub-action-button" data-nfchub-action="' . esc_attr( $action_key ) . '" target="' . esc_attr( $target ) . '" rel="noopener noreferrer">';

		if ( 'yes' === $settings['show_icon'] && ! empty( $action_config['icon'] ) ) {
			echo '<i class="' . esc_attr( $action_config['icon'] ) . '" style="margin-right: 8px;"></i>';
		}

		echo esc_html( $label );
		echo '</a>';
		echo '</div>';
	}

	/**
	 * Render widget output in the editor (content template)
	 */
	protected function content_template() {
		// JS template for live editing (optional - can be enhanced)
	}
}
