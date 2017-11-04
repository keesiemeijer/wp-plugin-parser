<?php
namespace keesiemeijer\WP_Plugin_Parser;


class Parse_WP_Uses {

	private $uses;

	/**
	 * Constructor
	 */
	public function __construct( $uses ) {
		$this->uses = array(
			'functions' => array(),
			'classes' => array(),
			'max_version' => '',
			'deprecated' => 0,
		);

		if ( ! empty( $uses['functions'] ) ) {
			$wp_functions = $this->get_json( 'functions' );
			$this->set_wp_uses( $uses, $wp_functions, 'functions' );
		}

		if ( ! empty( $uses['classes'] ) ) {
			$wp_classes = $this->get_json( 'classes' );
			$this->set_wp_uses( $uses, $wp_classes, 'classes' );
		}
	}

	public function get_uses() {
		return $this->uses;
	}

	private function get_json( $type ) {
		$file = WP_PLUGIN_PARSER_PLUGIN_DIR . 'json-files/' . $type . '.json';
		if ( ! is_readable( $file ) ) {
			return array();
		}

		$content = json_decode( file_get_contents( $file ), true );

		return isset( $content['content'] ) ? $content['content'] : array();
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
