<?php
/**
 * Plugin Name:     WP Plugin Parser
 * Plugin URI:      https://github.com/keesiemeijer/wp-plugin-parser
 * Description:     Parse plugins to see what functions, classes and methods they use.
 * Author:          keesiemeijer
 * Author URI:      https://github.com/keesiemeijer/
 * Text Domain:     wp-plugin-parser
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         WP_Plugin_Parser
 */

if ( ! defined( 'WP_PLUGIN_PARSER_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_PARSER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( version_compare( phpversion(), '5.3', '>=' ) ) {
	require WP_PLUGIN_PARSER_PLUGIN_DIR . 'includes/install.php';
}