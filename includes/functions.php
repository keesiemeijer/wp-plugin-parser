<?php
namespace keesiemeijer\WP_Plugin_Parser;

function get_blacklisted( $results, $blacklisted ) {
	if ( ! $blacklisted || ! ( isset( $results['functions'] ) && $results['functions'] ) ) {
		return array();
	}

	$blacklisted_functions = array();
	foreach ( $results['functions'] as $function ) {
		if ( in_array( $function, $blacklisted ) ) {
			$blacklisted_functions[] = $function;
		}
	}

	return $blacklisted_functions;
}

function get_deprecated( $results, $use_type ) {
	if ( ! isset( $results[ $use_type ] ) ) {
		return array();
	}

	$deprecated = wp_list_filter( $results[ $use_type ], array( 'deprecated' => true ) );
	$titles = $deprecated ? wp_list_pluck( $deprecated, 'title' ) : array();

	return $titles;
}

function sort_results( $results, $deprecated, $blacklisted ) {
	$warnings = array();

	foreach ( array( 'functions', 'classes' ) as $use_type ) {
		$sort = array();

		if ( ! isset( $results[ $use_type ] ) ) {
			continue;
		}

		$names = array();
		if ( isset( $deprecated[ $use_type ] ) && $deprecated[ $use_type ] ) {
			$names = $deprecated[ $use_type ];
		}

		if ( ('functions' === $use_type) && is_array( $blacklisted ) ) {
			$names = array_values( array_unique( array_merge( $names, $blacklisted ) ) );
		}

		foreach ( $results[ $use_type ] as $key => $type ) {
			if ( in_array( $type , $names ) ) {
				$sort[] = $type;
				unset( $results[ $use_type ][ $key ] );
			}
		}

		if ( $sort ) {
			sort( $sort );
			$results[ $use_type ] = array_merge( $sort, $results[ $use_type ] );
			$warnings[] = $use_type;
		}

		$results[ $use_type ] = array_values( $results[ $use_type ] );
	}

	if ( $warnings ) {
		$results['warnings'] = $warnings;
	}

	return $results;
}

function parse_files( $path, $exclude_dirs ) {
	$root    = trailingslashit( $path );
	$is_file = is_file( $path );
	$files   = $is_file ? array( $path ) : \WP_Parser\get_wp_files( $path );
	$path    = $is_file ? dirname( $path ) : $path;

	if ( $files instanceof \WP_Error ) {
		return __( 'Could not parse files', 'wp-plugin-parser' );
	}

	foreach ( $files as $key => $file ) {
		foreach ( $exclude_dirs as $dir ) {
			if ( 0 === strpos( $file, $root . $dir . '/' ) ) {
				unset( $files[ $key ] );
			}
		}
	}

	if ( ! function_exists( '\WP_Parser\parse_files' ) ) {
		return __( 'WP Parser not found', 'wp-plugin-parser' );
	}

	return  \WP_Parser\parse_files( $files, $path );
}
