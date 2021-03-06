<?php

// Uninstall script.

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit ();
}

global $wpdb;

if ( is_multisite() ) {
	global $wpdb;
	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
	if ( $blogs ) {
		foreach ( (array) $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			delete_option( 'wp_plugin_parser_settings' );
			delete_transient( 'wp_plugin_parser_ajax' );
			delete_transient( 'wp_plugin_parser_ajax_uses' );
			delete_transient( 'wp_plugin_parser_ajax_wp_uses' );
		}
		restore_current_blog();
	}
} else {
	delete_option( 'wp_plugin_parser_settings' );
	delete_transient( 'wp_plugin_parser_ajax' );
	delete_transient( 'wp_plugin_parser_ajax_uses' );
	delete_transient( 'wp_plugin_parser_ajax_wp_uses' );
}
