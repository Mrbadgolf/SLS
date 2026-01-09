<?php
/**
 * Custom Post Type Handler
 *
 * Registers the nfchub_page custom post type with Elementor support.
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Post Type class
 */
class NFCHub_CPT {

	/**
	 * Post type slug
	 */
	const POST_TYPE = 'nfchub_page';

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register custom post type
	 */
	public static function register() {
		$labels = array(
			'name'                  => _x( 'NFC Pages', 'Post Type General Name', 'nfc-hub' ),
			'singular_name'         => _x( 'NFC Page', 'Post Type Singular Name', 'nfc-hub' ),
			'menu_name'             => __( 'NFC Pages', 'nfc-hub' ),
			'name_admin_bar'        => __( 'NFC Page', 'nfc-hub' ),
			'archives'              => __( 'NFC Page Archives', 'nfc-hub' ),
			'attributes'            => __( 'NFC Page Attributes', 'nfc-hub' ),
			'parent_item_colon'     => __( 'Parent NFC Page:', 'nfc-hub' ),
			'all_items'             => __( 'All NFC Pages', 'nfc-hub' ),
			'add_new_item'          => __( 'Add New NFC Page', 'nfc-hub' ),
			'add_new'               => __( 'Add New', 'nfc-hub' ),
			'new_item'              => __( 'New NFC Page', 'nfc-hub' ),
			'edit_item'             => __( 'Edit NFC Page', 'nfc-hub' ),
			'update_item'           => __( 'Update NFC Page', 'nfc-hub' ),
			'view_item'             => __( 'View NFC Page', 'nfc-hub' ),
			'view_items'            => __( 'View NFC Pages', 'nfc-hub' ),
			'search_items'          => __( 'Search NFC Page', 'nfc-hub' ),
			'not_found'             => __( 'Not found', 'nfc-hub' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'nfc-hub' ),
			'featured_image'        => __( 'Featured Image', 'nfc-hub' ),
			'set_featured_image'    => __( 'Set featured image', 'nfc-hub' ),
			'remove_featured_image' => __( 'Remove featured image', 'nfc-hub' ),
			'use_featured_image'    => __( 'Use as featured image', 'nfc-hub' ),
			'insert_into_item'      => __( 'Insert into NFC page', 'nfc-hub' ),
			'uploaded_to_this_item' => __( 'Uploaded to this NFC page', 'nfc-hub' ),
			'items_list'            => __( 'NFC pages list', 'nfc-hub' ),
			'items_list_navigation' => __( 'NFC pages list navigation', 'nfc-hub' ),
			'filter_items_list'     => __( 'Filter NFC pages list', 'nfc-hub' ),
		);

		$args = array(
			'label'               => __( 'NFC Page', 'nfc-hub' ),
			'description'         => __( 'NFC landing pages with action tracking', 'nfc-hub' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions', 'elementor' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-smartphone',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
			'show_in_rest'        => true, // Required for Elementor
			'rewrite'             => false, // We handle custom rewrite rules separately
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Get action configuration
	 *
	 * Returns the configuration for all available actions.
	 *
	 * @return array
	 */
	public static function get_action_config() {
		return array(
			'google_review'    => array(
				'label'       => __( 'Google Review', 'nfc-hub' ),
				'action_key'  => 'google_review_click',
				'icon'        => 'fab fa-google',
				'default_label' => __( 'Leave Google Review', 'nfc-hub' ),
			),
			'facebook_review'  => array(
				'label'       => __( 'Facebook Review', 'nfc-hub' ),
				'action_key'  => 'facebook_review_click',
				'icon'        => 'fab fa-facebook',
				'default_label' => __( 'Leave Facebook Review', 'nfc-hub' ),
			),
			'facebook_follow'  => array(
				'label'       => __( 'Facebook Follow', 'nfc-hub' ),
				'action_key'  => 'facebook_follow_click',
				'icon'        => 'fab fa-facebook',
				'default_label' => __( 'Follow on Facebook', 'nfc-hub' ),
			),
			'payment_trust'    => array(
				'label'       => __( 'Trust Payment', 'nfc-hub' ),
				'action_key'  => 'payment_trust_click',
				'icon'        => 'fas fa-credit-card',
				'default_label' => __( 'Pay Trust', 'nfc-hub' ),
			),
			'payment_consult'  => array(
				'label'       => __( 'Consultation Payment', 'nfc-hub' ),
				'action_key'  => 'payment_consult_click',
				'icon'        => 'fas fa-credit-card',
				'default_label' => __( 'Pay Consultation Fee', 'nfc-hub' ),
			),
			'payment_invoice'  => array(
				'label'       => __( 'Invoice Payment', 'nfc-hub' ),
				'action_key'  => 'payment_invoice_click',
				'icon'        => 'fas fa-file-invoice-dollar',
				'default_label' => __( 'Pay Invoice', 'nfc-hub' ),
			),
		);
	}
}
