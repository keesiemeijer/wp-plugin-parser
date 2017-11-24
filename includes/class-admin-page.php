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
			'plugin'              => '',
			'plugin_name'         => '',
			'exclude_dirs'        => array(),
			'blacklist_functions' => array(),
			'wp_only'             => '',
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
		$notice           = '';
		$warning          = '';
		$parsed_files     = array();
		$plugin_url       = admin_url( 'tools.php?page=wp-plugin-parser' );
		$plugins          = get_plugins();

		unset( $plugins['wp-plugin-parser/wp-plugin-parser.php'] );

		if ( ! ( $plugins && is_array( $plugins ) ) ) {
			$errors .= __( 'Could not find plugins', 'wp-plugin-parser' ) . '<br/>';
		}

		$old_settings = $this->get_settings();
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'wp_plugin_parser_nonce', 'security' );

			$requested_plugin = $this->get_requested_plugin( $plugins );
			$plugin_name      = isset( $plugins[ $requested_plugin ]['Name'] );
			$settings         = array(
				'plugin'              => $requested_plugin,
				'plugin_name'         => $plugin_name ? $plugins[ $requested_plugin ]['Name'] : '',
				'exclude_dirs'        => isset( $_POST['exclude_dirs'] ) ? $_POST['exclude_dirs'] : '',
				'blacklist_functions' => isset( $_POST['blacklist_functions'] ) ? $_POST['blacklist_functions'] : '',
				'wp_only'             => isset( $_POST['wp_only'] ) ? 'on' : '',
			);

			$settings = $this->apply_settings_filters( $settings );

			if ( $old_settings != $settings ) {
				update_option( 'wp_plugin_parser_settings', $settings );
			}
		} else {
			$settings = $this->apply_settings_filters( $old_settings );
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

				$parsed_files = parse_files( $plugin_dir, $settings['exclude_dirs'] );
				if ( is_string( $parsed_files ) ) {
					$errors .= $parsed_files . '<br/>';
				}
			}
		}

		if ( $errors ) {
			include 'partials/admin-errors.php';
		} else {
			$exclude_dirs_str = implode( ', ', $settings['exclude_dirs'] );
			$blacklist_str    = implode( ', ', $settings['blacklist_functions'] );
			$warnings         = 0;

			if ( $parsed_files ) {
				$uses             = new Parse_Uses( $parsed_files );
				$results          = $uses->get_uses();
				$wp_uses          = new Parse_WP_Uses( $results );
				$wp_results       = $wp_uses->get_uses();
				$parsed           = true;

				$blacklisted      = get_blacklisted( $results, $settings['blacklist_functions'] );
				$deprecated       = array(
					'functions' => get_deprecated( $wp_results, 'functions' ),
					'classes'   => get_deprecated( $wp_results, 'classes' ),
				);

				if ( $blacklisted || $wp_results['deprecated'] ) {
					$results = sort_results( $results, $deprecated, $blacklisted );
				}

				$show_construct_info = false;
				if(! empty($results['constructs']) ) {
					$show_construct_info = true;
					if( $settings['wp_only'] ) {
						$show_construct_info = false;
						foreach ( $results['constructs'] as $construct ) {
							if(in_array($construct, $blacklisted)) {
								$show_construct_info = true;
								break;
							}
						}
					}
				}

				$warnings = (int) $wp_results['deprecated'] + count( $blacklisted );
				$notice = sprintf( __( 'Parsed plugin: %s', 'wp-plugin-parser' ), $settings['plugin_name'] );
			}

			include 'partials/admin-form.php';
		}
	}


	public function apply_settings_filters( $settings ) {
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

		return $settings;
	}

	private function get_requested_plugin( $plugins ) {
		if ( ! ( isset( $_POST['wp_plugin_parser'] ) && isset( $_POST['plugins'] ) ) ) {
			return false;
		}

		if ( ! in_array( $_POST['plugins'], array_keys( $plugins ) ) ) {
			return false;
		}

		return $_POST['plugins'];
	}
}
