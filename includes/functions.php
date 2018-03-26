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
 * Get default admin page settings.
 *
 * @return array Array with default admin page settings.
 */
function get_default_settings() {
	return array(
		'plugin_file'         => '',
		'plugin_name'         => '',
		'exclude_dirs'        => array(),
		'blacklist_functions' => array(),
		'wp_only'             => '',
		'exclude_strict'      => '',
	);
}

/**
 * Get blacklisted functions.
 *
 * @param array $results     Array with parse results.
 * @param array $blacklisted Array with blacklisted functions.
 * @return array Array with blacklisted functions found in the parse results.
 */
function get_blacklisted( $results, $blacklisted ) {
	if ( ! ( is_array( $blacklisted ) && is_array( $results['functions'] ) ) ) {
		return array();
	}

	$blacklisted_functions = array_filter( $results['functions'], function( $value ) use ( $blacklisted ) {
			return in_array( $value, $blacklisted );
		} );

	return array_values( $blacklisted_functions );
}

/**
 * Get deprecated functions.
 *
 * @param array  $results Array with parse results.
 * @param string $type    'functions' or 'classes'.
 * @return array Array with deprecated function or class titles.
 */
function get_deprecated( $results, $type ) {
	if ( ! isset( $results[ $type ] ) ) {
		return array();
	}

	$deprecated = wp_list_filter( $results[ $type ], array( 'deprecated' => true ) );
	$titles = $deprecated ? wp_list_pluck( $deprecated, 'title' ) : array();

	return $titles;
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

/**
 * Parse files for PHP functions, classes and class methods
 *
 * @param string $path     Path to plugin root directory or file
 * @param array  $settings Admin page settings.
 * @return array Array with parsed files.
 */
function parse_files( $path, $settings ) {
	if ( ! is_array( $settings ) ) {
		return __( 'Current settings not found', 'wp-plugin-parser' );
	}

	$settings = array_merge( get_default_settings(), $settings );
	$is_file  = is_file( $path );
	$files    = $is_file ? array( $path ) : get_plugin_files( $path, $settings );
	$path     = $is_file ? dirname( $path ) : $path;

	if ( $files instanceof \WP_Error ) {
		return __( 'Could not parse files', 'wp-plugin-parser' );
	}

	if ( ! function_exists( '\WP_Parser\parse_files' ) ) {
		return __( 'WP Parser not found', 'wp-plugin-parser' );
	}

	return  \WP_Parser\parse_files( $files, $path );
}

/**
 * Get all PHP files in a plugin.
 *
 * @param string $path     Path to root directory of a plugin
 * @param array  $settings Admin page settings.
 * @return array           Array with PHP plugin file path's/
 */
function get_plugin_files( $path, $settings ) {
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
