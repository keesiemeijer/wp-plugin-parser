<?php
namespace keesiemeijer\WP_Plugin_Parser;

class PHP_Compat_Parser {

	public $compat;
	public $logger;

	public function __construct( $files, $version ) {
		$this->parse_init( $files, $version );
	}

	public function parse_init( $files, $version ) {
		$this->logger = new Logger();
		$this->compat = $this->parse_compatibility( $files, $version );
	}

	public function get_compat() {
		return $this->compat;
	}

	/**
	 * Check if files are compatible with a specific PHP version
	 *
	 * @return string|false Compatibility report. Files are compatible if empty string.
	 *                                            False if an error occured.
	 */
	private function parse_compatibility( $files, $version ) {
		if ( ! class_exists( 'PHPCompatibility\PHPCSHelper' ) ) {
			$this->logger->log( __( 'PHPCompatibility sniffs not installed', 'wp-plugin-parser' ), 'compat' );
			return false;
		}

		if ( ! class_exists( 'PHP_CodeSniffer_CLI' ) ) {
			$this->logger->log( __( 'PHP CodeSniffer not installed', 'wp-plugin-parser' ), 'compat' );
			return false;
		}

		$php_versions = get_php_versions();
		if ( ! in_array( $version, $php_versions ) ) {
			$this->logger->log( __( 'Invalid PHP version used for compatibility check', 'wp-plugin-parser' ), 'compat' );
			return false;
		}

		if ( ! $files ) {
			$this->logger->log( __( 'No files found for compatibility check', 'wp-plugin-parser' ), 'compat' );
			return false;
		}

		$args = array(
			'files'       => $files,
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
}
