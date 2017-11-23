<?php
namespace keesiemeijer\WP_Plugin_Parser;

$composer = file_exists( WP_PLUGIN_PARSER_PLUGIN_DIR . 'vendor/autoload.php' );

if ( $composer && is_admin() ) {
	require WP_PLUGIN_PARSER_PLUGIN_DIR . 'vendor/autoload.php';

	new Admin_Page();
}
