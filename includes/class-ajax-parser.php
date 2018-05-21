<?php
namespace keesiemeijer\WP_Plugin_Parser;

/**
 * Class to handle user submitted content preview.
 */
class Parser_Ajax {
	public $expire;
	public $fail;

	/**
	 * Initializer
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'do_init' ) );
	}

	/**
	 * Handles adding hooks to enable ajax parsing.
	 */
	public function do_init() {
		$this->expire = HOUR_IN_SECONDS * 4;
		$this->fail   = __( 'Parsing failed.', 'wp-plugin-parser' );

		// Ajax actions to process request.
		add_action( "wp_ajax_parse_files", array( $this, "parse_files" ) );
		add_action( "wp_ajax_parse_uses", array( $this, "parse_uses" ) );
		add_action( "wp_ajax_parse_wp_uses", array( $this, "parse_wp_uses" ) );
		add_action( "wp_ajax_display_results", array( $this, "display_results" ) );

		// Enqueue scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ), 11 );

		// Add Javasctipt template in the footer.
		add_action( "admin_footer", array( $this, 'parse_template' ) );
	}


	/**
	 * Add a template for Javascript to the footer.
	 */
	function parse_template() {
		include_once plugin_dir_path( __FILE__ ) . "/partials/parse-template.php";
	}

	/**
	 * Enqueues scripts and styles.
	 */
	public function scripts_and_styles() {

		$file = plugins_url( 'js/parse-plugin.js', __FILE__ );
		wp_enqueue_script( 'parse_plugin', $file , array( 'jquery', 'wp-util' ), false, true );
		wp_localize_script( 'parse_plugin', 'plugin_parser', array(
				'plugin_url'    => admin_url( 'tools.php?page=wp-plugin-parser' ),
				'file_nonce'    => wp_create_nonce( 'parse_files_nonce' ),
				'uses_nonce'    => wp_create_nonce( 'parse_uses_nonce' ),
				'wp_uses_nonce' => wp_create_nonce( 'parse_wp_uses_nonce' ),
				'display_nonce' => wp_create_nonce( 'display_results_nonce' ),
				'parse_file'    => __( 'Parsing file %1$d of %2$d...', 'wp-plugin-parser' ),
				'start'         => __( 'Parsing plugin', 'wp-plugin-parser' ),
				'load_files'    => __( 'Loading plugin files', 'wp-plugin-parser' ),
				'finished'      => __( 'Finished parsing!', 'wp-plugin-parser' ),
				'backlink'      => __( 'Back to plugin form', 'wp-plugin-parser' ),
				'plugin'        => __( 'Plugin: %s', 'wp-plugin-parser' ),
				'error'         => __( 'Something went wrong while parsing this plugin', 'wp-plugin-parser' ),
				'notice'        => __( 'Loading files can take some time', 'wp-plugin-parser' ),
			) );
	}

	function get_defaults() {
		return array(
			'total'         => 0,
			'count'         => 0,
			'is_compatible' => true,
			'settings'      => array(),
			'files'         => array(),
			'compat'        => array(),
		);
	}

	public function delete_transients() {
		delete_transient( 'wp_plugin_parser_ajax' );
		delete_transient( 'wp_plugin_parser_ajax_uses' );
		delete_transient( 'wp_plugin_parser_ajax_wp_uses' );
	}

	/**
	 * Ajax action to get the plugin files and admin settings.
	 */
	public function parse_files( ) {
		check_ajax_referer( 'parse_files_nonce', 'nonce' );

		// Delete the transients before a new request.
		$this->delete_transients();

		$defaults = get_default_settings();

		if ( ! ( isset( $_POST['form'] ) && $_POST['form'] ) ) {
			$errors = $this->fail . ' ' . __( 'Missing form settings.', 'wp-plugin-parser' );
			wp_send_json_error( array( 'errors' => array( $errors ) ) );
		}

		// Parse the admin form fields.
		wp_parse_str( $_POST['form'], $form_data );
		$form_data = array_merge( $defaults, $form_data );

		$plugin      = isset( $form_data['plugins'] ) ? $form_data['plugins'] : '';
		$plugins_dir = trailingslashit( dirname( WP_PLUGIN_PARSER_PLUGIN_DIR ) );
		$root        = trailingslashit( dirname( $plugins_dir . $plugin ) );
		$plugin_data = get_plugin_data( $plugins_dir . $plugin );
		$plugin_name = isset( $plugin_data['Name'] ) ? $plugin_data['Name'] : '';

		if ( $plugins_dir === $root ) {
			// Single file plugins (not in a directory).
			$root = $plugins_dir . $plugin;
		}

		$form_data['root']        = $root;
		$form_data['plugin_file'] = $plugin;
		$form_data['plugin_name'] = $plugin_name;
		$form_data                = apply_settings_filters( $form_data );
		
		$file_parser = new File_Parser();
		$file_parser->parse($form_data);

		$files  = $file_parser->get_files();
		$errors = $file_parser->logger->get_log();
		$total  = count( $files );

		if ( $errors ) {
			array_unshift( $errors , $this->fail );
			wp_send_json_error( array( 'errors' => $errors ) );
		}

		// Save admin settings.
		$settings     = array_intersect_key( $form_data, $defaults );
		$old_settings = get_database_settings();
		if ( $old_settings != $settings ) {
			update_option( 'wp_plugin_parser_settings', $settings );
		}

		$data = array(
			'settings' => $form_data,
			'files'    => $files,
			'total'    => $total,
		);

		$data = array_merge( $this->get_defaults(), $data );

		set_transient( 'wp_plugin_parser_ajax', $data, $this->expire );

		wp_send_json_success( array( 'total' => $total, 'option' => $option ) );
	}

	function parse_uses() {
		check_ajax_referer( 'parse_uses_nonce', 'nonce' );

		$settings = get_transient( 'wp_plugin_parser_ajax' );
		if ( ! is_array( $settings ) ) {
			$this->delete_transients();
			$errors = $this->fail . ' ' . __( 'Missing settings.', 'wp-plugin-parser' );
			wp_send_json_error( array( 'errors' => array( $errors ) ) );
		}

		$settings = array_merge( $this->get_defaults(), $settings );
		$form     = is_array( $settings['settings'] ) ? $settings['settings'] : '';
		$files    = is_array( $settings['files'] ) ? $settings['files'] : '';
		$total    = absint( $settings['total'] );

		if ( ! ( $form && $files && $total ) ) {
			$this->delete_transients();
			$errors = $this->fail . ' ' . __( 'Missing plugin file data.', 'wp-plugin-parser' );
			wp_send_json_error( array( 'errors' => array( $errors ) ) );
		}

		$uses          = get_transient( 'wp_plugin_parser_ajax_uses' );
		$uses          = is_array( $uses ) ? $uses : array();
		$blacklisted   = isset( $uses['blacklisted'] ) ? $uses['blacklisted'] : array();
		$count         = absint( $settings['count'] );
		$file          = isset( $files[ $count ] ) ? $files[ $count ] : '';
		$form          = array_merge( get_default_settings(), $form );
		$errors        = array();
		$parsed_uses   = array();
		$is_compatible = true;

		if ( ! $file ) {
			$errors[] = __( 'Error: Could not find file to parse', 'wporg-developer' );
		}

		if ( ! $errors && $file ) {
			$parser = new Uses_Parser();
			$parser->parse( array( $file ), $form['root'] );

			$parsed_uses     = $parser->get_uses();
			$blacklisted_new = $parser->get_blacklisted( $form['blacklist_functions'] );
			$blacklisted     = array_merge( $blacklisted, $blacklisted_new );
			$blacklisted     = array_unique( $blacklisted );
			$errors          = $parser->logger->get_log();
		}

		if ( ! $errors && $parsed_uses ) {
			$uses = $this->merge_settings( $uses, $parsed_uses );
		}

		if ( ! $errors && $file && $form['check_version'] ) {
			$compat_parser = new PHP_Compat_Parser();
			$compat_parser->parse( array( $file ), $form['php_version'] );

			$compat = $compat_parser->get_compat();
			$errors = $compat_parser->logger->get_log();
			if ( ! $errors ) {
				$is_compatible = ! preg_match( '/(\d*) ERRORS?/i', $compat );
			}
		}

		if ( $errors ) {
			$this->delete_transients();
			array_unshift( $errors , $this->fail );
			wp_send_json_error( array( 'errors' => $errors ) );
		}

		$parser_data = array(
			'settings' => $form,
			'files'    => $files,
			'count'    => ++$count,
			'total'    => $total,
		);

		$parser_data = array_merge( $this->get_defaults(), $parser_data );
		if ( ! $is_compatible ) {
			$parser_data['compat'][]      = $compat;
			$parser_data['is_compatible'] = false;
		}
		set_transient( 'wp_plugin_parser_ajax', $parser_data, $this->expire );

		$data_uses = array(
			'blacklisted' => $blacklisted,
			'uses'        => $uses,
		);
		set_transient( 'wp_plugin_parser_ajax_uses', $data_uses, $this->expire );

		$data = array(
			'count'  => $parser_data['count'],
			'done'   => false,
		);

		if ( $total === $count ) {
			$data['done'] = true;
		}

		wp_send_json_success( $data );
	}

	function parse_wp_uses() {
		check_ajax_referer( 'parse_wp_uses_nonce', 'nonce' );

		$uses = get_transient( 'wp_plugin_parser_ajax_uses' );
		if ( ! is_array( $uses ) ) {
			$this->delete_transients();
			$errors = $this->fail . ' ' . __( 'Missing parsed files data.', 'wp-plugin-parser' );
			wp_send_json_error( array( 'errors' => array( $errors ) ) );
		}

		$parsed_wp_uses = array();
		$deprecated     = array();
		$parsed_uses    = isset( $uses['uses'] ) ? $uses['uses'] : array();
		$blacklisted    = isset( $uses['blacklisted'] ) ? $uses['blacklisted'] : array();
		$warnings       = isset( $parsed_uses['warnings'] ) ? $parsed_uses['warnings'] : array();

		if ( $parsed_uses ) {
			$wp_parser = new WP_Uses_Parser();
			$wp_parser->parse( $parsed_uses );
			$parsed_wp_uses = $wp_parser->get_uses();

			$deprecated  = array(
				'functions' => $wp_parser->get_deprecated( 'functions' ),
				'classes'   => $wp_parser->get_deprecated( 'classes' ),
			);

			if ( $blacklisted || $parsed_wp_uses['deprecated'] ) {
				// Moves blacklisted and deprecated to the top.
				// Adds warnings to $parsed_uses object.
				$parsed_uses = sort_results( $parsed_uses, $deprecated, $blacklisted );
				if ( isset( $parsed_uses['warnings'] ) && $parsed_uses['warnings'] ) {
					$parsed_uses['warnings'] = array_merge( $warnings, $parsed_uses['warnings'] );
					$parsed_uses['warnings'] = array_unique( $parsed_uses['warnings'] );
				}
			}
		}

		$data_uses = array(
			'blacklisted'   => $blacklisted,
			'uses'          => $parsed_uses,
		);
		set_transient( 'wp_plugin_parser_ajax_uses', $data_uses, $this->expire );

		$data_wp_uses = array(
			'uses'       => $parsed_wp_uses,
			'deprecated' => $deprecated,
		);
		set_transient( 'wp_plugin_parser_ajax_wp_uses', $data_wp_uses, $this->expire );

		$data = array(
			'finished_parsing' => true,
		);

		wp_send_json_success( $data );
	}

	function display_results() {
		check_ajax_referer( 'display_results_nonce', 'nonce' );

		$ajax    = get_transient( 'wp_plugin_parser_ajax' );
		$uses    = get_transient( 'wp_plugin_parser_ajax_uses' );
		$wp_uses = get_transient( 'wp_plugin_parser_ajax_wp_uses' );

		// Delete the transients after the request.
		$this->delete_transients();

		if ( ! ( is_array( $ajax ) && is_array( $uses ) && is_array( $wp_uses ) ) ) {
			$errors = $this->fail . ' ' . __( 'No parsed data found.', 'wp-plugin-parser' );
			wp_send_json_error( array( 'errors' => array( $errors ) ) );
		}

		$blacklisted = isset( $uses['blacklisted'] ) ? $uses['blacklisted'] : array();
		$deprecated  = isset( $wp_uses['deprecated'] ) ? $wp_uses['deprecated'] : array();
		$parsed_uses = isset( $uses['uses'] ) ? $uses['uses'] : array();
		$wp_uses     = isset( $wp_uses['uses'] ) ? $wp_uses['uses'] : array();
		$file_count  = isset( $ajax['total'] ) ? $ajax['total'] : 0;
		$settings    = isset( $ajax['settings'] ) ? $ajax['settings'] : array();

		if ( ! $parsed_uses || ! $wp_uses || ! $settings || ! $deprecated ) {
			$errors = $this->fail . ' ' . __( 'No parsed results found.', 'wp-plugin-parser' );
			wp_send_json_error( array( 'errors' => array( $errors ) ) );
		}

		$errors        = '';
		$uses_errors   = '';
		$compat_errors = '';
		$is_compatible = isset( $ajax['is_compatible'] ) ? $ajax['is_compatible'] : true;
		$compat        = isset( $ajax['compat'] ) ? $ajax['compat'] : array();
		$settings      = array_merge( get_default_settings(), $settings );

		$show_construct_info = ! empty( $parsed_uses['constructs'] );
		if ( $show_construct_info && $settings['wp_only'] ) {
			$show_construct_info = array_filter( $parsed_uses['constructs'], function( $val ) use ( $blacklisted ) {
					return in_array( $val, $blacklisted );
				} );
			$show_construct_info = ! empty( $show_construct_info );
		}


		ob_start();
		include 'partials/results.php';
		include 'partials/parse-results.php';
		if ( $settings['check_version'] && $compat ) {
			$compat = implode( '<br/>', $compat );
			include 'partials/compat.php';
		}

		$content = ob_get_clean();

		wp_send_json_success( array( 'content' => $content ) );
	}

	function merge_settings( $settings, $new_settings, $name = 'uses' ) {
		$settings = isset( $settings[ $name ] ) ? $settings[ $name ] : array();

		foreach ( $new_settings as $key => $new_setting ) {

			if ( isset( $settings[ $key ] ) ) {
				$settings[ $key ] = array_merge( $settings[ $key ], $new_settings[ $key ] );
			} else {
				$settings[ $key ] = $new_settings[ $key ];
			}
			$settings[ $key ] = array_unique( $settings[ $key ] );
			sort( $settings[ $key ] );
		}

		return $settings;
	}
}
