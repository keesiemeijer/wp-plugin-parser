<?php
namespace keesiemeijer\WP_Plugin_Parser;

class Admin_Page {

	public $plugins;
	public $compat;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	public function add_admin_menu() {
		$hook = add_submenu_page(
			'tools.php',
			__( 'WP Plugin Parser', 'wp-plugin-parser' ),
			__( 'WP Plugin Parser', 'wp-plugin-parser' ),
			'manage_options',
			'wp-plugin-parser',
			array( $this, 'admin_menu' )
		);
	}

	public function get_database_settings() {
		$defaults = get_default_settings();
		$settings = get_option( 'wp_plugin_parser_settings' );
		$settings = is_array( $settings ) ? $settings : $defaults;

		return array_merge( $defaults, $settings );
	}

	private function get_admin_settings() {

		$old_settings = $this->get_database_settings();
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return $this->apply_settings_filters( $old_settings );
		}

		check_admin_referer( 'wp_plugin_parser_nonce', 'security' );

		$plugin = $this->get_requested_plugin();
		$settings = array(
			'plugin_file'         => $plugin,
			'plugin_name'         => $plugin ? $this->plugins[ $plugin ]['Name'] : '',
			'exclude_dirs'        => isset( $_POST['exclude_dirs'] ) ? $_POST['exclude_dirs'] : '',
			'blacklist_functions' => isset( $_POST['blacklist_functions'] ) ? $_POST['blacklist_functions'] : '',
			'wp_only'             => isset( $_POST['wp_only'] ) ? 'on' : '',
			'exclude_strict'      => isset( $_POST['exclude_strict'] ) ? 'on' : '',
			'php_version'         => isset( $_POST['php_version'] ) ?  $_POST['php_version'] : '7.0',
			'check_version'       => isset( $_POST['check_version'] ) ? 'on' : '',
		);

		$settings = $this->apply_settings_filters( $settings );

		if ( $old_settings != $settings ) {
			update_option( 'wp_plugin_parser_settings', $settings );
		}

		// Parse the plugin from the request.
		$settings['parse_request'] = true;

		return $settings;
	}

	public function admin_menu() {
		$errors           = '';
		$notice           = '';
		$warnings         = 0;
		$file_count       = 0;
		$parsed_files     = array();
		$compat           = '';
		$parse_errors     = '';
		$compat_errors    = '';
		$compatible      = true;
		$plugin_url       = admin_url( 'tools.php?page=wp-plugin-parser' );
		$this->plugins    = get_plugins();

		$settings = $this->get_admin_settings();
		$request  = isset( $settings['parse_request'] );

		if ( $request ) {
			$parser = new File_Parser( $settings );
			$errors = implode( '<br/>', $parser->get_log( 'errors' ) );

			if ( ! $errors ) {
				$parsed_files = $parser->parse_plugin_uses();
				$parse_errors = implode( '<br/>', $parser->get_log( 'parse' ) );
			}

			if ( ! $errors && $settings['check_version'] ) {
				$compat = $parser->parse_php_compatibility();

				$compat_errors = implode( '<br/>', $parser->get_log( 'compat' ) );
				if ( ! $compat_errors ) {
					$compatible = ! preg_match( '/(\d*) ERRORS?/i', $compat );
				}
			}
		}

		$exclude_dirs_str = implode( ', ', $settings['exclude_dirs'] );
		$blacklist_str    = implode( ', ', $settings['blacklist_functions'] );
		$php_versions     = get_php_versions();

		if ( $request && $parsed_files ) {
			$file_count = count( $parsed_files );
			$uses       = new Parse_Uses( $parsed_files );
			$results    = $uses->get_uses();
			$wp_uses    = new Parse_WP_Uses( $results );
			$wp_results = $wp_uses->get_uses();

			$blacklisted = get_blacklisted( $results, $settings['blacklist_functions'] );
			$deprecated  = array(
				'functions' => get_deprecated( $wp_results, 'functions' ),
				'classes'   => get_deprecated( $wp_results, 'classes' ),
			);

			if ( $blacklisted || $wp_results['deprecated'] ) {
				// Moves blacklisted and deprecated to the top.
				$results = sort_results( $results, $deprecated, $blacklisted );
			}

			$show_construct_info = ! empty( $results['constructs'] );
			if ( $show_construct_info && $settings['wp_only'] ) {
				$show_construct_info = array_filter( $results['constructs'], function( $val ) use ( $blacklisted ) {
						return in_array( $val, $blacklisted );
					} );
				$show_construct_info = ! empty( $show_construct_info );
			}

			$warnings = (int) $wp_results['deprecated'] + count( $blacklisted );
			$notice = sprintf( __( 'Parsed plugin: %s', 'wp-plugin-parser' ), $settings['plugin_name'] );
		}

		$warnings = ! $compatible ? $warnings + 1 : $warnings;

		// Display admin form and results
		include 'partials/admin-form.php';
	}


	private function apply_settings_filters( $settings ) {
		foreach ( array( 'exclude_dirs', 'blacklist_functions' ) as $type ) {
			if ( ! isset( $settings[ $type ] ) ) {
				$settings[ $type ] = array();
				continue;
			}

			if ( is_string( $settings[ $type ] ) ) {
				$settings[ $type ] = explode( ',', $settings[ $type ] );
				$settings[ $type ] = array_filter( array_unique( array_map( 'trim', $settings[ $type ] ) ) );
			}

			$settings[ $type ] = apply_filters( "wp_plugin_parser_{$type}", $settings[ $type ] );
			$settings[ $type ] = array_filter( array_unique( array_map( 'trim', $settings[ $type ] ) ) );
		}

		// Add user directories after default excluded directories.
		$exclude_dirs             = get_default_exclude_dirs();
		$user_dirs                = array_diff( $settings['exclude_dirs'], $exclude_dirs );
		$settings['exclude_dirs'] = array_unique( array_merge( $exclude_dirs, $user_dirs ) );

		// Remove single '/' directory because nothing will be parsed.
		$key = array_search( '/', $settings['exclude_dirs'] );
		if ( false !== $key ) {
			unset( $settings['exclude_dirs'][ $key ] );
		}

		return $settings;
	}


	private function plugin_exists( $plugin ) {
		if ( ! ( $this->plugins && is_array( $this->plugins ) ) ) {
			return false;
		}

		if ( in_array( $plugin, array_keys( $this->plugins ) ) ) {
			// Check if plugin name exists.
			return isset( $this->plugins[ $plugin ]['Name'] );
		}

		return false;
	}

	private function get_requested_plugin() {
		if ( ! ( isset( $_POST['wp_plugin_parser'] ) && isset( $_POST['plugins'] ) ) ) {
			return '';
		}

		return $this->plugin_exists( $_POST['plugins'] ) ? $_POST['plugins']: '';
	}
}
