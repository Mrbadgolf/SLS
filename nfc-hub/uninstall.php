<?php
/**
 * Uninstall Script
 *
 * Runs when the plugin is uninstalled.
 * Cleans up database tables, options, and post meta.
 *
 * @package NFCHub
 */

// Exit if not called from WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete custom table
$table_name = $wpdb->prefix . 'nfchub_events';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// Delete all nfchub_page posts and their meta
$posts = get_posts(
	array(
		'post_type'      => 'nfchub_page',
		'posts_per_page' => -1,
		'post_status'    => 'any',
	)
);

foreach ( $posts as $post ) {
	wp_delete_post( $post->ID, true );
}

// Delete all post meta associated with the plugin
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_nfchub_%'" );

// Delete plugin options (if any are added in the future)
delete_option( 'nfchub_version' );

// Clear any cached data
wp_cache_flush();
