<?php
/**
 * Rewrite Rules Handler
 *
 * Handles custom URL routing for /nfc/<slug> to nfchub_page posts.
 *
 * @package NFCHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrite rules class
 */
class NFCHub_Rewrite {

	/**
	 * Query var name
	 */
	const QUERY_VAR = 'nfchub_slug';

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
	}

	/**
	 * Add rewrite rules
	 *
	 * Creates the /nfc/<slug> URL structure.
	 */
	public static function add_rules() {
		// Add rewrite rule: /nfc/<slug> -> ?nfchub_slug=<slug>
		add_rewrite_rule(
			'^nfc/([^/]+)/?$',
			'index.php?' . self::QUERY_VAR . '=$matches[1]',
			'top'
		);
	}

	/**
	 * Add custom query var
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public static function add_query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Handle template redirect
	 *
	 * When /nfc/<slug> is accessed, find the matching nfchub_page post
	 * and set up WordPress to display it using the single template.
	 */
	public static function template_redirect() {
		global $wp_query;

		$slug = get_query_var( self::QUERY_VAR );

		// If not our custom URL, return
		if ( empty( $slug ) ) {
			return;
		}

		// Sanitize slug
		$slug = sanitize_title( $slug );

		// Find nfchub_page with this slug
		$args = array(
			'post_type'      => NFCHub_CPT::POST_TYPE,
			'name'           => $slug,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		);

		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			// No matching page found - trigger 404
			$wp_query->set_404();
			status_header( 404 );
			return;
		}

		// Found the post - set up the query
		$post = $posts[0];

		// Clear existing query
		$wp_query->init();

		// Set up post data
		$wp_query->is_single = true;
		$wp_query->is_singular = true;
		$wp_query->is_404 = false;
		$wp_query->post_count = 1;
		$wp_query->found_posts = 1;
		$wp_query->max_num_pages = 1;
		$wp_query->queried_object = $post;
		$wp_query->queried_object_id = $post->ID;
		$wp_query->posts = array( $post );
		$wp_query->post = $post;

		// Set up global post
		$GLOBALS['post'] = $post;
		setup_postdata( $post );

		// Load the template
		// WordPress will use the single-nfchub_page.php template if it exists,
		// or fall back to single.php, then index.php
		// Elementor will hook in and render its template if configured
	}
}
