<?php
namespace keesiemeijer\WP_Plugin_Parser;

class Uses_Parser {

	private $uses;
	public $logger;

	public function __construct() {
		$this->parse_init();
	}

	public function parse_init() {
		$this->logger = new Logger();
		$this->uses = array(
			'functions' => array(),
			'methods'   => array(),
			'classes'   => array(),
			'constructs' => array(),
		);
	}

	public function parse( $files, $root ) {
		$parsed_files = $this->parse_uses( $files, $root );

		if ( $parsed_files instanceof \WP_Error ) {
			$this->logger->log( $parsed_files->get_error_message() );
			return false;
		}

		if ( $parsed_files ) {
			$this->get_file_data( $parsed_files );
		}
	}

	/**
	 * Parse PHP files for functions and classes.
	 *
	 * @return array Parsed files.
	 */
	public function parse_uses( $files, $root ) {
		if ( ! function_exists( '\WP_Parser\parse_files' ) ) {
			$this->logger->log( __( 'WP Parser not installed', 'wp-plugin-parser' ) );
			return false;
		}

		if ( ! $files || ! $root ) {
			$this->logger->log( __( 'No files found to parse', 'wp-plugin-parser' ) );
			return false;
		}

		// Parse files and catch errors.
		ob_start();
		$parsed_files = \WP_Parser\parse_files( $files, $root );
		$error        = ob_get_contents();
		ob_end_clean();

		if ( $error ) {
			return new \WP_Error(
				'parser_error',
				sprintf( 'The parsed plugin contains errors. %s', $error )
			);
		}

		return $parsed_files;
	}

	public function get_uses() {
		return $this->uses;
	}

	public function get_blacklisted( $blacklisted ) {

		if ( ! is_array( $blacklisted ) ) {
			return array();
		}

		$functions = array_filter( $this->uses['functions'], function( $value ) use ( $blacklisted ) {
				return in_array( $value, $blacklisted );
			} );

		return array_values( $functions );
	}

	private function get_file_data( $parsed_files ) {
		foreach ( $parsed_files as $key => $file ) {
			if ( isset( $file['uses'] ) ) {
				$this->add_uses( $file );
			}

			foreach ( array( 'functions', 'classes', ) as $type ) {
				if ( ! isset( $file[ $type ] ) ) {
					continue;
				}

				$this->get_data( $file[ $type ], $type );
			}

			$this->get_language_constructs( $file );
		}

		$this->sanitize_use_types();
	}

	private function get_data( $nodes, $type ) {
		foreach ( $nodes as $node ) {

			if ( 'classes' === $type ) {
				$this->uses['classes'][] = $this->sanitize_name( $node['name'], $type );
				if ( isset( $node['extends'] ) && $node['extends'] ) {
					$this->uses['classes'][] = $this->sanitize_name( $node['extends'], $type );
				}
				if ( isset( $node['methods'] ) ) {
					$this->get_data( $node['methods'], 'methods' );
				} else {
					continue;
				}
			}

			if ( ! isset( $node['uses'] ) ) {
				continue;
			}

			$this->add_uses( $node );
		}
	}

	private function get_language_constructs( $file ) {
		if ( ! ( isset( $file['root'] ) && isset( $file['path'] ) ) ) {
			return;
		}

		$path = trailingslashit( $file['root'] ) . $file['path'];
		if ( ! is_readable( $path ) ) {
			return;
		}

		$content = file_get_contents( $path );

		preg_match_all( "/(^|\W)(eval|die|exit)\s*\(/", $content, $matches );
		if ( ! ( isset( $matches[2] ) && $matches[2] ) ) {
			return;
		}

		$constructs = array_unique( $matches[2] );
		$this->uses['constructs'] = $constructs;
		$this->uses['functions'] = array_merge( $this->uses['functions'], $constructs );
	}

	private function add_uses( $node ) {

		foreach ( array( 'functions', 'classes', 'methods' ) as $uses_type ) {

			if ( ! isset( $node['uses'][ $uses_type ] ) ) {
				continue;
			}

			$names = $this->get_name( $node['uses'][ $uses_type ], $uses_type );
			switch ( $uses_type ) {
				case 'functions':
					$this->uses['functions'] = array_merge( $this->uses['functions'], $names );
					break;
				case 'classes':
					$this->uses['classes'] = array_merge( $this->uses['classes'], $names );
					break;
				case 'methods':
					$classes = wp_list_pluck( $node['uses']['methods'], 'class' );

					foreach ( $classes as $classname ) {
						$this->uses['classes'][] = $this->sanitize_name( $classname, 'classes' );
					}
					$this->uses['methods'] = array_merge( $this->uses['methods'], $names );
					break;
			}
		}
	}

	private function get_name( $nodes, $type ) {
		$names = array();
		foreach ( $nodes as $node ) {
			$name = $this->sanitize_name( $node['name'], $type );
			if ( ! $name ) {
				continue;
			}

			if ( 'methods' === $type ) {
				$classname = $this->sanitize_name( $node['class'], 'classes' );
				if ( ! $classname ) {
					continue;
				}

				$names[] = $classname . '::' . $name;
			} else {
				$names[] = $name;
			}
		}

		return $names;
	}

	private function sanitize_name( $name, $context = '' ) {
		if ( ! is_string( $name ) ) {
			return '';
		}

		$name = trim( $name );
		if ( 0 === strpos( $name, '$' ) ) {
			return '';
		}

		if ( 'classes' === $context ) {
			$name = trim( $name, '\\' );
		}

		return $name;
	}

	private function sanitize_use_types() {
		foreach ( array( 'functions', 'classes', 'methods' ) as $type ) {
			$this->uses[ $type ] = array_filter( array_unique( $this->uses[ $type ]  ) );
			sort( $this->uses[ $type ] );
		}
	}
}
