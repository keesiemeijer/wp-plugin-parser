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
			'root'                => $plugin ? $this->get_plugin_root( $plugin ) : '',
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
		$parsed_uses      = array();
		$blacklisted      = array();
		$deprecated       = array();
		$compat           = '';
		$uses_errors      = '';
		$compat_errors    = '';
		$compatible       = true;
		$plugin_url       = admin_url( 'tools.php?page=wp-plugin-parser' );
		$this->plugins    = get_plugins();
		$settings         = $this->get_admin_settings();
		$request          = isset( $settings['parse_request'] );
		$exclude_dirs_str = implode( ', ', $settings['exclude_dirs'] );
		$blacklist_str    = implode( ', ', $settings['blacklist_functions'] );
		$php_versions     = get_php_versions();

		if ( $request ) {
			$file_parser = new File_Parser( $settings );
			$files  = $file_parser->get_files();
			$errors = $this->get_errors( $file_parser );

			if ( ! $errors && $files ) {
				$uses_parser = new Uses_Parser( $files, $settings['root'] );
				$parsed_uses = $uses_parser->get_uses();
				$blacklisted = $uses_parser->get_blacklisted( $settings['blacklist_functions'] );
				$uses_errors = $this->get_errors( $uses_parser );
			}

			if ( ! $errors && $files && $settings['check_version'] ) {
				$compat_parser = new PHP_Compat_Parser( $files, $settings['php_version'] );
				$compat        = $compat_parser->get_compat();
				$compat_errors = $this->get_errors( $compat_parser );
				if ( ! $compat_errors ) {
					$compatible = ! preg_match( '/(\d*) ERRORS?/i', $compat );
				}
			}
		}

		if ( $request && $parsed_uses ) {
			$file_count = count( $parsed_uses );
			$wp_parser  = new WP_Uses_Parser( $parsed_uses );
			$wp_uses    = $wp_parser->get_uses();

			$deprecated  = array(
				'functions' => $wp_parser->get_deprecated( 'functions' ),
				'classes'   => $wp_parser->get_deprecated( 'classes' ),
			);

			if ( $blacklisted || $wp_uses['deprecated'] ) {
				// Moves blacklisted and deprecated to the top.
				$parsed_uses = sort_results( $parsed_uses, $deprecated, $blacklisted );
			}

			$show_construct_info = ! empty( $parsed_uses['constructs'] );
			if ( $show_construct_info && $settings['wp_only'] ) {
				$show_construct_info = array_filter( $parsed_uses['constructs'], function( $val ) use ( $blacklisted ) {
						return in_array( $val, $blacklisted );
					} );
				$show_construct_info = ! empty( $show_construct_info );
			}

			$warnings = (int) $wp_uses['deprecated'] + count( $blacklisted );
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

	function get_errors( $obj ) {
		return implode( '<br/>', $obj->logger->get_log() );
	}

	private function get_plugin_root( $plugin ) {
		if ( ! $this->plugin_exists( $plugin ) ) {
			return '';
		}

		$plugins_dir = trailingslashit( dirname( WP_PLUGIN_PARSER_PLUGIN_DIR ) );
		$root        = trailingslashit( dirname( $plugins_dir . $plugin ) );

		if ( $plugins_dir === $root ) {
			// Single file plugins (not in a directory).
			$root = $plugins_dir . $plugin;
		}

		return $root;
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
