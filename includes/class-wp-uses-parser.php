<?php
namespace keesiemeijer\WP_Plugin_Parser;


class WP_Uses_Parser {

	private $uses;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->parse_init();
	}

	public function parse_init() {
		$this->uses = array(
			'functions' => array(),
			'classes' => array(),
			'methods' => array(),
			'max_version' => '',
			'deprecated' => 0,
		);
	}

	public function parse( $uses ) {
		if ( ! empty( $uses['functions'] ) ) {
			$wp_functions = $this->get_json( 'functions' );
			$this->set_wp_uses( $uses, $wp_functions, 'functions' );
		}

		if ( ! empty( $uses['classes'] ) ) {
			$wp_classes = $this->get_json( 'classes' );
			$this->set_wp_uses( $uses, $wp_classes, 'classes' );

			if ( ! empty( $uses['methods'] ) ) {
				$this->set_wp_uses_methods( $uses['methods'] );
			}
		}
	}

	public function get_uses() {
		return $this->uses;
	}

	/**
	 * Get deprecated functions.
	 *
	 * @param array  $uses Array with parse results.
	 * @param string $type 'functions' or 'classes'.
	 * @return array Array with deprecated function or class titles.
	 */
	public function get_deprecated( $type ) {
		if ( ! isset( $this->uses[ $type ] ) ) {
			return array();
		}

		$deprecated = wp_list_filter( $this->uses[ $type ], array( 'deprecated' => true ) );
		$titles = $deprecated ? wp_list_pluck( $deprecated, 'title' ) : array();

		return $titles;
	}

	private function get_json( $type ) {
		$file = WP_PLUGIN_PARSER_PLUGIN_DIR . 'json-files/' . $type . '.json';
		if ( ! is_readable( $file ) ) {
			return array();
		}

		$content = json_decode( file_get_contents( $file ), true );

		return isset( $content['content'] ) ? $content['content'] : array();
	}

	private function set_wp_uses_methods( $methods ) {
		if ( empty( $this->uses['classes'] ) ) {
			return;
		}

		foreach ( $methods as $method ) {
			$method_class = explode( '::', $method );
			if ( ! ( isset( $method_class[1] ) && $method_class[1] ) ) {
				continue;
			}

			$class = wp_list_filter( $this->uses['classes'], array( 'title' => $method_class[0] ) );
			$class = array_values( $class );
			if ( ! isset( $class[0] ) ) {
				continue;
			}

			// Not interested in the constructor method for WordPress methods.
			if ( '__construct' === $method_class[1] ) {
				continue;
			}

			$this->uses['methods'][] = array(
				'title' => $method,
				'class' =>  $class[0]['title'],
				'slug'  => 'classes/' . $class[0]['slug'] . '/' . sanitize_title( $method_class[1] ),
				'since' => '',
			);
		}
	}

	private function set_wp_uses( $uses, $wp_uses, $type ) {
		$names = wp_list_pluck( $wp_uses, 'title' );

		foreach ( $uses[ $type ] as $key => $function ) {
			if ( ! in_array( $function, $names ) ) {
				continue;
			}

			$index = array_search( $function, array_column( $wp_uses, 'title' ) );
			$result = $wp_uses[ $index ];
			$this->uses[ $type ][] = $result;

			if ( isset( $result['deprecated'] ) && $result['deprecated'] ) {
				$this->uses['deprecated'] = $this->uses['deprecated'] + 1;
			}

			if ( ! ( isset( $result['since'] ) && $result['since'] ) ) {
				continue;
			}

			if ( ! $this->uses['max_version'] ) {
				$this->uses['max_version'] = $result['since'];
			} else {
				if ( version_compare( $result['since'], $this->uses['max_version'], '>' ) ) {
					$this->uses['max_version'] = $result['since'];
				}
			}
		}
	}
}
