<?php
namespace keesiemeijer\WP_Plugin_Parser;

// We need to use the PHP_CS autoloader to access the Runner and Config.
require_once WP_PLUGIN_PARSER_PLUGIN_DIR . '/vendor/squizlabs/php_codesniffer/autoload.php';
require_once WP_PLUGIN_PARSER_PLUGIN_DIR . '/vendor/phpcompatibility/php-compatibility/PHPCompatibility/PHPCSHelper.php';

use \PHP_CodeSniffer\Runner;
use \PHP_CodeSniffer\Config;
use \PHP_CodeSniffer\Reporter;
use \PHP_CodeSniffer\Files\DummyFile;
use \PHPCompatibility\PHPCSHelper;

class PHP_Compat_Parser {

	public $compat;
	public $logger;

	public function __construct() {
		$this->parse_init();
	}

	public function parse_init() {
		$this->logger = new Logger();
		$this->compat = '';
	}

	public function parse( $files, $version ) {
		$this->compat = $this->parse_compatibility( $files, $version );
	}

	public function get_compat() {
		return $this->compat ? $this->compat : '';
	}

	/**
	 * Check if files are compatible with a specific PHP version
	 *
	 * @return string|false Compatibility report. Files are compatible if empty string.
	 *                                            False if an error occured.
	 */
	private function parse_compatibility( $files, $version ) {
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

		$runner = new Runner();

		$config_args = ['-s', '-p', '-n'];

		$runner->config = new Config( $config_args );

		$runner->config->extensions = array(
			'php' => 'PHP',
			'inc' => 'PHP',
		);

		$ignored = '.*/node_modules/.*,.*/vendor/.*,.*/assets/build/.*,.*/build/.*,.*/bin/.*';


		$runner->config->standards   = array( 'PHPCompatibility' );
		$runner->config->files       = $files;
		$runner->config->annotations = true;
		$runner->config->parallel    = 8;
		$runner->config->colors      = false;
		$runner->config->tabWidth    = 0;
		$runner->config->reportWidth = 110;
		$runner->config->interactive = false;
		$runner->config->cache       = false;
		$runner->config->ignored     = $ignored;

		$runner->init();

		// Ignoring warnings when generating the exit code.
		PHPCSHelper::setConfigData( 'ignore_warnings_on_exit', true, true );

		// Set minimum supported PHP version.
		PHPCSHelper::setConfigData( 'testVersion', $version , true );

		$runner->reporter = new Reporter( $runner->config );

		foreach ( $files as $file_path ) {
			$file       = new DummyFile( file_get_contents( $file_path ), $runner->ruleset, $runner->config );
			$file->path = $file_path;

			$runner->processFile( $file );
		}

		ob_start();
		$runner->reporter->printReports();
		$report = ob_get_clean();

		return $report;
	}
}
