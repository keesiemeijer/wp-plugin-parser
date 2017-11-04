<?php
namespace keesiemeijer\WP_Plugin_Parser;

// Composer autoloading.
$composer = file_exists( WP_PLUGIN_PARSER_PLUGIN_DIR . 'vendor/autoload.php' );

if ( $composer ) {

	require WP_PLUGIN_PARSER_PLUGIN_DIR . 'vendor/autoload.php';

	new Admin_Page();
	
	// Add the admin page for this plugin.
	// add_action( 'admin_menu', __NAMESPACE__ . '\\enqueue__scripts' );
	// add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue__scripts' );

	// // Add the calendar shortcode.
	// add_shortcode( 'pt_calendar', __NAMESPACE__ . '\\add_calendar_shortcode' );

}