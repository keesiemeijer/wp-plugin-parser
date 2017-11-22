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
		//delete_option( 'wp_plugin_parser_settings' );
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

		$plugins = get_plugins();
		unset( $plugins['wp-plugin-parser/wp-plugin-parser.php'] );

		if ( ! ( $plugins && is_array( $plugins ) ) ) {
			$errors .= __( 'Could not find plugins', 'wp-plugin-parser' ) . '<br/>';
		}

		$old_settings = $this->get_settings();
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'wp_plugin_parser_nonce', 'security' );
			$requested_plugin = $this->get_requested_plugin( $plugins );
			$plugin_name = isset( $plugins[ $requested_plugin ]['Name'] );
			$settings = array(
				'plugin'              => $requested_plugin,
				'plugin_name'         => $plugin_name ? $plugins[ $requested_plugin ]['Name'] : '',
				'exclude_dirs'        => isset( $_POST['exclude_dirs'] ) ? $_POST['exclude_dirs'] : '',
				'blacklist_functions' => isset( $_POST['blacklist_functions'] ) ? $_POST['blacklist_functions'] : '',
				'wp_only'             => isset( $_POST['wp_only'] ) ? 'on' : '',
			);

			$settings = $this->apply_filters( $settings );

			if ( $old_settings != $settings ) {
				update_option( 'wp_plugin_parser_settings', $settings );
			}
		} else {
			$settings = $this->apply_filters( $old_settings );
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
			$blacklist_str = implode( ', ', $settings['blacklist_functions'] );
			$warnings = 0;
			if ( $parsed_files ) {
				$uses             = new Parse_Uses( $parsed_files );
				$results          = $uses->get_uses();
				$wp_uses          = new Parse_WP_Uses( $results );
				$wp_results       = $wp_uses->get_uses();
				$parsed           = true;

				$blacklisted      = $this->get_blacklisted( $results, $settings['blacklist_functions'] );
				$deprecated       = array(
					'functions' => $this->get_deprecated( $wp_results, 'functions' ),
					'classes'   => $this->get_deprecated( $wp_results, 'classes' ),
				);

				if ( $blacklisted || $wp_results['deprecated'] ) {
					$results = $this->sort( $results, $deprecated, $blacklisted );
				}

				$warnings = (int) $wp_results['deprecated'] + count( $blacklisted );
				$notice = sprintf( __( 'Parsed plugin: %s', 'wp-plugin-parser' ), $settings['plugin_name'] );
				$notice .= " (<a href='{$plugin_url}#parse-results' >";
				$notice .= __( 'See results below', 'wp-plugin-parser' ) . '</a>)';
			}

			include 'partials/admin-form.php';
		}
	}


	public function apply_filters( $settings ) {
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

	public function get_requested_plugin( $plugins ) {
		if ( ! ( isset( $_POST['wp_plugin_parser'] ) && isset( $_POST['plugins'] ) ) ) {
			return false;
		}

		if ( ! in_array( $_POST['plugins'], array_keys( $plugins ) ) ) {
			return false;
		}

		return $_POST['plugins'];
	}

	public function get_blacklisted( $results, $blacklisted ) {
		if ( ! $blacklisted || ! ( isset( $results['functions'] ) && $results['functions'] ) ) {
			return array();
		}

		$blacklisted_functions = array();
		foreach ( $results['functions'] as $function ) {
			if ( in_array( $function, $blacklisted ) ) {
				$blacklisted_functions[] = $function;
			}
		}

		return $blacklisted_functions;
	}

	public function get_deprecated( $results, $use_type ) {
		if ( ! isset( $results[ $use_type ] ) ) {
			return array();
		}

		$deprecated = wp_list_filter( $results[ $use_type ], array( 'deprecated' => true ) );
		$titles = $deprecated ? wp_list_pluck( $deprecated, 'title' ) : array();

		return $titles;
	}

	public function sort( $results, $deprecated, $blacklisted ) {
		$warnings = array();

		foreach ( array( 'functions', 'classes' ) as $use_type ) {
			$sort = array();

			if ( ! isset( $results[ $use_type ] ) ) {
				continue;
			}

			$names = array();
			if ( isset( $deprecated[ $use_type ] ) && $deprecated[ $use_type ] ) {
				$names = $deprecated[ $use_type ];
			}

			if ( 'functions' === $use_type ) {
				$names = array_values( array_unique( array_merge( $names, $blacklisted ) ) );
			}

			foreach ( $results[ $use_type ] as $key => $type ) {
				if ( in_array( $type , $names ) ) {
					$sort[] = $type;
					unset( $results[ $use_type ][ $key ] );
				}
			}

			if ( $sort ) {
				sort( $sort );
				$results[ $use_type ] = array_merge( $sort, $results[ $use_type ] );
				$warnings[] = $use_type;
			}

			$results[ $use_type ] = array_values( $results[ $use_type ] );
		}

		if ( $warnings ) {
			$results['warnings'] = $warnings;
		}

		return $results;
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

		if ( ! function_exists( '\WP_Parser\parse_files' ) ) {
			return __( 'WP Parser not found', 'wp-plugin-parser' );
		}

		return  \WP_Parser\parse_files( $files, $path );
	}
}
