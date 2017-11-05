<?php
namespace keesiemeijer\WP_Plugin_Parser;

class Parse_Uses {

	public $uses;

	public function __construct( $files ) {
		$this->uses = array(
			'functions' => array(),
			'methods'   => array(),
			'classes'   => array(),
		);

		$this->get_file_data( $files );
	}

	public function get_uses() {
		return $this->uses;
	}

	private function get_file_data( $files ) {

		foreach ( $files as $key => $file ) {
			foreach ( array( 'uses', 'functions', 'classes' ) as $type ) {
				if ( ! isset( $file[ $type ] ) ) {
					continue;
				}
				$this->get_data( $file[ $type ], $type );
			}
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
			$this->uses[ $type ] = array_unique( $this->uses[ $type ]  );
			sort( $this->uses[ $type ] );
		}
	}
}
