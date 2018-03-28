<?php
namespace keesiemeijer\WP_Plugin_Parser;

class Logger {

	private $logs;

	public function __construct(){
		$this->logs = array();
	}
	
	/**
	 * Public method to get log messages.
	 *
	 * @param string $key Type of log message. Default 'errors'.
	 * @return array      Array of log messages.
	 */
	public function get_log( $key = 'errors' ) {
		if ( isset( $this->logs[ $key ] ) ) {
			return $this->logs[ $key ];
		}
		return array();
	}

	/**
	 * Add messages tot the log.
	 *
	 * @param string $msg Log message.
	 * @param string $key Key to log messages under. Default 'errors'.
	 */
	public function log( $msg, $key = 'errors' ) {
		$this->logs[ $key ][] = $msg;
	}
}