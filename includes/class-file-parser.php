<?php
namespace keesiemeijer\WP_Plugin_Parser;

class File_Parser {

	private $log;
	private $settings;
	private $files;
	private $path;

	public function __construct( $settings ) {
		$this->init( $settings );
	}

	public function init( $settings ) {
		$this->log = array();
		$this->files = null;
		$this->path = null;
		$this->settings = array_merge( get_default_settings(), $settings );
		$this->files_init();
	}

	public function get_log( $key = 'errors' ) {
		if ( isset( $this->log[ $key ] ) ) {
			return $this->log[ $key ];
		}
		return array();
	}

	public function get_files() {
		if ( ! $this->files ) {
			$this->files_init();
		}

		return $this->files ? $this->files : array();
	}

	private function log( $msg = '', $key = 'errors' ) {
		$this->log[ $key ][] = $msg;
	}

	private function files_init() {
		if ( ! $this->settings['plugin_file'] ) {
			$this->log( __( "Requested plugin doesn't exist", 'wp-plugin-parser' ) );
			return false;
		}

		$plugins_dir = trailingslashit( dirname( WP_PLUGIN_PARSER_PLUGIN_DIR ) );
		$path        = trailingslashit( dirname( $plugins_dir . $this->settings['plugin_file'] ) );

		if ( ! is_readable( $plugins_dir . $this->settings['plugin_file'] ) ) {
			$this->log( __( 'Could not read file', 'wp-plugin-parser' ) );
			return false;
		}

		if ( $plugins_dir === $path ) {
			// Single file plugins (not in a directory).
			$path = $plugins_dir . $this->settings['plugin_file'];
		}

		$is_file    = is_file( $path );
		$files      = $is_file ? array( $path ) : $this->itarate_files( $path );
		$this->path = $is_file ? dirname( $path ) : $path;

		if ( $files instanceof \WP_Error ) {
			$this->log( __( "Could not find requested files", 'wp-plugin-parser' ) );
			return false;
		}

		$this->files = $files;
	}

	public function parse_php_compatibility() {
		if ( ! class_exists( 'PHPCompatibility\PHPCSHelper' ) ) {
			$this->log( __( 'PHPCompatibility not installed', 'wp-plugin-parser' ), 'compat' );
			return false;
		}

		if ( ! class_exists( 'PHP_CodeSniffer_CLI' ) ) {
			$this->log( __( 'PHP CodeSniffer not installed', 'wp-plugin-parser' ), 'compat' );
			return false;
		}
		$version = $this->settings['php_version'];
		$php_versions = get_php_versions();
		if ( ! in_array( $version, $php_versions ) ) {
			$this->log( __( 'Invalid PHP version used for compatibility check', 'wp-plugin-parser' ), 'compat' );
			return false;
		}

		if ( ! $this->files ) {
			$this->log( __( 'No files found for compatibility check', 'wp-plugin-parser' ), 'compat' );
			return false;
		}

		$args = array(
			'files'       => $this->files,
			'testVersion' => $version,
			'standard'    => 'PHPCompatibility',
			'reportWidth' => '9999',
			'extensions'  => array( 'php' ),
		);

		call_user_func( array( 'PHPCompatibility\PHPCSHelper', 'setConfigData' ), 'testVersion', $args['testVersion'], true );

		$cli  = new \PHP_CodeSniffer_CLI();

		ob_start();
		$cli->process( $args );
		$report = ob_get_clean();

		return $report;
	}

	public function parse_plugin_uses() {
		if ( ! function_exists( '\WP_Parser\parse_files' ) ) {
			$this->log( __( 'WP Parser not installed', 'wp-plugin-parser' ), 'parse' );
			return false;
		}


		if ( ! $this->files || ! $this->path ) {
			$this->log( __( 'No files found to parse', 'wp-plugin-parser' ), 'parse' );
			return false;
		}

		return  \WP_Parser\parse_files( $this->files, $this->path );
	}

	/**
	 * Get all PHP files in a plugin.
	 *
	 * @param string $path     Path to root directory of a plugin
	 * @param array  $settings Admin page settings.
	 * @return array           Array with PHP plugin file path's/
	 */
	private function itarate_files( $path ) {
		$settings    = $this->settings;
		$files       = array();
		$path        = trailingslashit( $path );
		$dirIterator = new \RecursiveDirectoryIterator( $path );

		// Filter the excluded directories out.
		$filterIterator = new \RecursiveCallbackFilterIterator( $dirIterator, function ( $current, $key, $iterator ) use ( $path, $settings ) {
				if ( $current->isFile() && ( 'php' !== $current->getExtension() ) ) {
					return false;
				}

				if ( ! $current->isDir() ) {
					return true;
				}

				// Exclude directories if found in path
				$directory_name = $current->getFilename();
				if ( $settings['exclude_strict'] && in_array( $directory_name, $settings['exclude_dirs'] ) ) {
					return false;
				}

				// Exclude directories found in root directory of a plugin.
				$current_path = $current->getPathname();
				foreach (  $settings['exclude_dirs'] as $dir ) {
					if ( ( $path . untrailingslashit( $dir ) ) === $current_path  ) {
						return false;
					}
				}

				return true;
			} );

		$iterableFiles  = new \RecursiveIteratorIterator( $filterIterator );
		try {
			foreach ( $iterableFiles as $file ) {
				if ( 'php' !== $file->getExtension() ) {
					continue;
				}

				$files[] = $file->getPathname();
			}
		} catch ( \UnexpectedValueException $exc ) {
			return new \WP_Error(
				'unexpected_value_exception',
				sprintf( 'Directory [%s] contained a directory we can not recurse into', $path )
			);
		}

		return $files;
	}
}
