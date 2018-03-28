<?php
namespace keesiemeijer\WP_Plugin_Parser;

class File_Parser {

	/**
	 * Log.
	 *
	 * @var array
	 */
	public $logger;

	/**
	 * Settings to parse for files.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * PHP file path's from a directory.
	 *
	 * @var array
	 */
	private $files;

	public function __construct( $settings ) {
		$this->init( $settings );
	}

	/**
	 * Set up properties and get the PHP file path's from a directory.
	 *
	 * @param array $settings Settings.
	 */
	public function init( $settings ) {
		$defaults = array(
			'root'           => '',
			'exclude_dirs'   => array(),
			'exclude_strict' => false,
		);

		$settings = is_array( $settings ) ? $settings : array();
		$this->settings = array_merge( $defaults, (array) $settings );

		$this->logger = new Logger();
		$this->files  = null;

		$this->files_init();
	}

	/**
	 * Public function to get the PHP file path's of a directory.
	 *
	 * @return array Array with PHP file path's.
	 */
	public function get_files() {
		return $this->files ? $this->files : array();
	}

	/**
	 * Get al the PHP file path's from a directory.
	 *
	 * Sets the class properties files and path.
	 *
	 * @return false|void Returns false if files are not found.
	 */
	private function files_init() {
		if ( ! $this->settings['root'] ) {
			$this->logger->log( __( "Root directory not found", 'wp-plugin-parser' ) );
			return false;
		}

		$path    = $this->settings['root'];
		$is_file = is_file( $path );

		if ( ! is_readable( $path ) ) {
			if ( $is_file ) {
				$this->logger->log( sprintf( __( 'Could not read file %s', 'wp-plugin-parser' ), '<code>' . $path . '</code>'  ) );
			} else {
				$this->logger->log( sprintf( __( 'Could not read directory %s', 'wp-plugin-parser' ), '<code>' . $path . '</code>' ) );
			}

			return false;
		}

		$files      = $is_file ? array( $path ) : $this->itarate_files( $path );
		$this->root = $is_file ? dirname( $path ) : $path;

		if ( $files instanceof \WP_Error ) {
			$this->logger->log( $files->get_error_message() );
			return false;
		}

		$this->files = $files;
	}

	/**
	 * Get all PHP files in a directory.
	 *
	 * @param string $path     Path to root directory.
	 * @param array  $settings Settings.
	 * @return array           Array with PHP file path's/
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

				// Exclude directories found in the root directory.
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
