<hr>
<h2>Parse Results</h2>
<?php if($wp_results['max_version']) : ?>

<p>
	<?php printf( __('This plugin "Requires at least" version: %s', 'wp-plugin-parser'), $wp_results['max_version'] ); ?>
</p>
<?php endif; ?>

<?php if( 0 < $wp_results['deprecated']) : ?>
<p style="color: red;">

	<?php
		/* translators: %d: Number of deprecated functions */
		printf( _n( 'Warning: This plugin uses %d deprecated function', 'Warning: This plugin uses %d deprecated functions', $wp_results['deprecated'], 'wp-plugin-parser' ), $wp_results['deprecated'] );
	?>
	
</p>
<?php else : ?>
	<?php _e('No deprecated functions or classes found', '') ?>
<?php endif; ?>

<?php
foreach ( array( 'functions', 'classes', 'methods' ) as $use_type ) :
	$names = array();
	if ( ! empty( $wp_results[ $use_type ] ) ) {
		$names = wp_list_pluck( $wp_results[ $use_type ], 'title' );
	} else {
		if ( empty( $results[ $use_type ] ) || $settings['wp_only'] ) {
			continue;
		}
	}

	if ( 'es' === substr( $use_type, -2, 2 ) ) {
		$single = substr( $use_type, 0, -2 );
	} else {
		$single = substr( $use_type, 0, -1 );
	}
	?>
	<h3><?php echo  ucfirst( $use_type ); ?></h3>
	<table class="widefat fixed">
		 <thead>
			<tr>
				<th scope='col'>
					<?php echo  ucfirst( $single ); ?>
				</th>
				<th scope='col'>
					<?php _e( 'WordPress Since', 'wp-plugin-parser' ); ?>
				</th>
				<th scope='col'>
					<?php _e( 'Deprecated', 'wp-plugin-parser' ); ?>
				</th>
			</tr>
		</thead>
		<tbody>
		<?php $i = 0; ?>
		<?php  foreach ( $results[ $use_type ] as $type ) : ?>
			<?php
			$wp_type = false;
			if ( in_array( $type, $names ) ) {
				$index = array_search( $type, array_column( $wp_results[ $use_type ], 'title' ) );
				$wp_type = $wp_results[ $use_type ][ $index ];
			}

			if(!$wp_type && $settings['wp_only']) {
				continue;
			}
			?>

			<tr<?php echo ( 0 === ( $i % 2 ) ) ? ' class="alternate"' : ''; ?>>
				<td>
					<?php if ( $wp_type ) : ?>
					<a href="https://developer.wordpress.org/reference/<?php echo $use_type; ?>/<?php echo $wp_type['slug']; ?>"><?php echo $type; ?></a>
					<?php else : ?>
						<?php echo $type; ?>
					<?php endif; ?>
				</td>
				<td>
					<?php if ( $wp_type ) : ?>
						<?php echo $wp_type['since']; ?>
					<?php endif; ?>
				</td>
				<td>
					<?php if ( $wp_type && $wp_type['deprecated'] ) : ?>
						<span style="color: red;"><strong>deprecated</strong></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php $i++ ?>
	<?php endforeach; ?>
		</tbody>
	</table>
<?php endforeach; ?>
