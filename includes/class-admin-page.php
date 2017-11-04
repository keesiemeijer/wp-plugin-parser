<?php
namespace keesiemeijer\WP_Plugin_Parser;

class Admin_Page {

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

	public function get_settings() {
		$defaults = array(
			'plugin' => '',
			'exclude_dirs' => array(),
			'wp_only' => '',
		);

		$settings = get_option( 'wp_plugin_parser_settings' );
		if ( ! is_array( $settings ) ) {
			$settings = $defaults;
		}
		$settings = array_merge( $defaults, $settings );

		return $settings;
	}

	public function admin_menu() {
		$requested_plugin = false;
		$parsed           = false;
		$errors           = '';
		$parsed_files     = array();

		$plugins = get_plugins();
		unset( $plugins['wp-plugin-parser/wp-plugin-parser.php'] );

		if ( ! ( $plugins && is_array( $plugins ) ) ) {
			$errors .= __( 'Could not find plugins', 'wp-plugin-parser' ) . '<br/>';
		}

		$old_settings = $this->get_settings();
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'wp_plugin_parser_nonce', 'security' );
			$requested_plugin = $this->get_requested_plugin( $plugins );
			$settings = array(
				'plugin' => $requested_plugin,
				'exclude_dirs' => $this->get_excluded_dirs(),
				'wp_only' => ( isset( $_POST['wp_only'] ) ? 'on' : '' ),
			);

			if ( $old_settings != $settings ) {
				update_option( 'wp_plugin_parser_settings', $settings );
			}
		} else {
			$settings = $old_settings;
		}

		if ( $requested_plugin ) {
			$plugins_dir = trailingslashit( dirname( WP_PLUGIN_PARSER_PLUGIN_DIR ) );

			if ( ! is_readable( $plugins_dir . $settings['plugin'] ) ) {
				$errors .= __( 'Could not read plugin files', 'wp-plugin-parser' ) . '<br/>';
			} else {
				$plugin_dir = trailingslashit( dirname( $plugins_dir . $settings['plugin'] ) );

				if ( $plugins_dir === $plugin_dir ) {
					// Single file plugins.
					$plugin_dir = $plugins_dir . $settings['plugin'];
				}

				$parsed_files = $this->parse_files( $plugin_dir, $settings['exclude_dirs'] );
				if ( is_string( $parsed_files ) ) {
					$errors .= $parsed_files . '<br/>';
				}
			}
		}

		if ( $errors ) {
			include 'partials/admin-errors.php';
		} else {
			$exclude_dirs_str = implode( ', ', $settings['exclude_dirs'] );
			if ( $parsed_files ) {
				$uses             = new Parse_Uses( $parsed_files );
				$results          = $uses->get_uses();
				$wp_uses          = new Parse_WP_Uses( $results );
				$wp_results       = $wp_uses->get_uses();
				$parsed           = true;
			}

			include 'partials/admin-form.php';
		}
	}

	public function get_excluded_dirs() {
		$dirs = array();
		if ( isset( $_POST['exclude_dir'] ) && $_POST['exclude_dir'] ) {
			$dirs = explode( ',', $_POST['exclude_dir'] );
			$dirs = array_filter( array_unique( array_map( 'trim', $dirs ) ) );
		}

		return $dirs;
	}

	public function get_requested_plugin( $plugins ) {
		if ( ! ( isset( $_POST['wp_parser_cleanup'] ) && isset( $_POST['plugins'] ) ) ) {
			return false;
		}

		if ( ! in_array( $_POST['plugins'], array_keys( $plugins ) ) ) {
			return false;
		}

		return $_POST['plugins'];
	}

	public function parse_files( $path, $exclude_dirs ) {
		$root    = trailingslashit( $path );
		$is_file = is_file( $path );
		$files   = $is_file ? array( $path ) : \WP_Parser\get_wp_files( $path );
		$path    = $is_file ? dirname( $path ) : $path;

		if ( $files instanceof \WP_Error ) {
			return __( 'Could not parse files', 'wp-plugin-parser' );
		}

		foreach ( $files as $key => $file ) {
			foreach ( $exclude_dirs as $dir ) {
				if ( 0 === strpos( $file, $root . $dir . '/' ) ) {
					unset( $files[ $key ] );
				}
			}
		}

		if(!function_exists('\WP_Parser\parse_files')) {
			return __('WP Parser not found', 'wp-plugin-parser');
		}

		return  \WP_Parser\parse_files( $files, $path );
	}
}
