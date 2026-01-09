<?php
/**
 * Elementor Widget: NFCHub Action List
 *
 * Displays all enabled actions in a list.
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action List widget
 */
class NFCHub_Elementor_Widget_Action_List extends \Elementor\Widget_Base {

	/**
	 * Get widget name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'nfchub-action-list';
	}

	/**
	 * Get widget title
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'NFCHub Action List', 'nfc-hub' );
	}

	/**
	 * Get widget icon
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-bullet-list';
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

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'nfc-hub' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'vertical'   => __( 'Vertical', 'nfc-hub' ),
					'horizontal' => __( 'Horizontal', 'nfc-hub' ),
					'grid'       => __( 'Grid', 'nfc-hub' ),
				),
				'default' => 'vertical',
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'     => __( 'Columns', 'nfc-hub' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 4,
				'default'   => 2,
				'condition' => array(
					'layout' => 'grid',
				),
			)
		);

		$this->add_control(
			'show_icons',
			array(
				'label'        => __( 'Show Icons', 'nfc-hub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'nfc-hub' ),
				'label_off'    => __( 'No', 'nfc-hub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
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
				'selector' => '{{WRAPPER}} .nfchub-action-list-item a',
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Text Color', 'nfc-hub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .nfchub-action-list-item a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_bg_color',
			array(
				'label'     => __( 'Background Color', 'nfc-hub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .nfchub-action-list-item a' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .nfchub-action-list-item a',
			)
		);

		$this->add_control(
			'button_border_radius',
			array(
				'label'      => __( 'Border Radius', 'nfc-hub' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .nfchub-action-list-item a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .nfchub-action-list-item a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'item_spacing',
			array(
				'label'      => __( 'Item Spacing', 'nfc-hub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .nfchub-action-list.layout-vertical .nfchub-action-list-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .nfchub-action-list.layout-horizontal .nfchub-action-list-item' => 'margin-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .nfchub-action-list.layout-grid .nfchub-action-list-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$post_id  = get_the_ID();

		// Get all enabled actions
		$actions_data = NFCHub_Meta::get_actions_data( $post_id );

		// If no actions enabled, show placeholder in editor
		if ( empty( $actions_data ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="nfchub-placeholder" style="padding: 20px; background: #f0f0f0; border: 2px dashed #ccc; color: #666; text-align: center;">';
				echo esc_html__( 'No actions enabled. Configure actions in NFC Hub Actions metabox.', 'nfc-hub' );
				echo '</div>';
			}
			return;
		}

		// Determine layout classes
		$layout  = $settings['layout'];
		$columns = $settings['columns'] ?? 2;

		$list_classes = array( 'nfchub-action-list', 'layout-' . $layout );

		if ( 'grid' === $layout ) {
			$list_classes[] = 'columns-' . $columns;
		}

		$target = 'yes' === $settings['open_new_tab'] ? '_blank' : '_self';

		// Render action list
		echo '<div class="' . esc_attr( implode( ' ', $list_classes ) ) . '" style="list-style: none; margin: 0; padding: 0;">';

		foreach ( $actions_data as $action ) {
			echo '<div class="nfchub-action-list-item" style="display: inline-block; width: ' . ( 'grid' === $layout ? ( 100 / $columns ) . '%' : 'auto' ) . ';">';
			echo '<a href="' . esc_url( $action['url'] ) . '" class="nfchub-action-button" data-nfchub-action="' . esc_attr( $action['action_key'] ) . '" target="' . esc_attr( $target ) . '" rel="noopener noreferrer" style="display: block; text-decoration: none;">';

			if ( 'yes' === $settings['show_icons'] && ! empty( $action['icon'] ) ) {
				echo '<i class="' . esc_attr( $action['icon'] ) . '" style="margin-right: 8px;"></i>';
			}

			echo esc_html( $action['label'] );
			echo '</a>';
			echo '</div>';
		}

		echo '</div>';

		// Add inline CSS for layout
		?>
		<style>
			.nfchub-action-list.layout-vertical .nfchub-action-list-item {
				display: block;
				width: 100%;
			}
			.nfchub-action-list.layout-horizontal {
				display: flex;
				flex-wrap: wrap;
			}
			.nfchub-action-list.layout-horizontal .nfchub-action-list-item {
				display: inline-block;
				width: auto;
			}
			.nfchub-action-list.layout-grid {
				display: flex;
				flex-wrap: wrap;
			}
		</style>
		<?php
	}
}
