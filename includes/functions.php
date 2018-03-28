<?php
namespace keesiemeijer\WP_Plugin_Parser;

/**
 * Get default directories to exclude.
 *
 * These directories are usualy from third party
 *
 * @return array Array with default directories to exclude.
 */
function get_default_exclude_dirs() {
	return apply_filters( 'wp_plugin_parser_exclude_default_dirs', array(
			'vendor',
			'node_modules',
			'.git',
		) );
}

/**
 * Get default directories to exclude.
 *
 * These directories are usualy from third party
 *
 * @return array Array with default directories to exclude.
 */
function get_php_versions() {
	return array(
		'5.2',
		'5.3',
		'5.4',
		'5.5',
		'5.6',
		'7.0',
	);
}

/**
 * Get default admin page settings.
 *
 * @return array Array with default admin page settings.
 */
function get_default_settings() {
	return array(
		'root'                => '',
		'plugin_file'         => '',
		'plugin_name'         => '',
		'exclude_dirs'        => array(),
		'blacklist_functions' => array(),
		'wp_only'             => '',
		'exclude_strict'      => '',
		'php_version'         => '7.0',
		'check_version'       => '',
	);
}

/**
 * Sort parsed results.
 *
 * Sort deprecated and blacklisted and move them to the top.
 *
 * @param array $results     Parse results.
 * @param array $deprecated  Array with deprecated function or classes.
 * @param array $blacklisted Array with blacklisted functions.
 * @return array Sorted parse results.
 */
function sort_results( $results, $deprecated, $blacklisted ) {
	$warning_types = array();

	foreach ( array( 'functions', 'classes' ) as $type ) {
		$warnings_sort = array();

		if ( ! isset( $results[ $type ] ) ) {
			continue;
		}

		$warning_type = array();
		if ( isset( $deprecated[ $type ] ) && $deprecated[ $type ] ) {
			$warning_type = $deprecated[ $type ];
		}

		if ( ( 'functions' === $type ) && is_array( $blacklisted ) ) {
			$warning_type = array_values( array_unique( array_merge( $warning_type, $blacklisted ) ) );
		}

		foreach ( $results[ $type ] as $key => $value ) {
			if ( in_array( $value , $warning_type ) ) {
				// Function or class is deprecated or blacklisted.
				$warnings_sort[] = $value;
				unset( $results[ $type ][ $key ] );
			}
		}

		if ( $warnings_sort ) {
			// Sort deprecated and blacklisted and move them to the front.
			sort( $warnings_sort );
			$results[ $type ] = array_merge( $warnings_sort, $results[ $type ] );
			$warning_types[] = $type;
		}

		$results[ $type ] = array_values( $results[ $type ] );
	}

	if ( $warning_types ) {
		$results['warnings'] = $warning_types;
	}

	return $results;
}