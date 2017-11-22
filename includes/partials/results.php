<hr>
<h2 id="parse-results">Parse Results</h2>
<ul style="font-size: 14px;">
	<li>
		<?php printf( __( 'Plugin: %s', 'wp-plugin-parser' ), '<strong>' . $settings['plugin_name'] . '</strong' ) ; ?>
	<li>

	<?php if ( $wp_results['max_version'] ) : ?>
	<li>
		<?php printf( __( 'Requires at least version: %s', 'wp-plugin-parser' ), $wp_results['max_version'] ); ?>
	</li>
	<?php endif; ?>

	<?php if ( ! isset( $results['warnings'] ) ) : ?>
	<li>
		<?php _e( 'No deprecated or blacklisted functions or classes found', 'wp-plugin-parser' ); ?>
	</li>
	<?php endif; ?>

	<?php if ( 0 < $wp_results['deprecated'] ) : ?>
	<li style="color: red;">
		<?php
			/* translators: %d: Number of deprecated functions */
			printf( _n( 'Warning: This plugin uses %d deprecated function', 'Warning: This plugin uses %d deprecated functions', $wp_results['deprecated'], 'wp-plugin-parser' ), $wp_results['deprecated'] );
		?>
	</li>
	<?php endif; ?>
	<?php if ( 0 < count( $blacklisted ) ) : ?>
	<li style="color: red;">
		<?php
			/* translators: %d: Number of deprecated functions */
			printf( _n( 'Warning: This plugin uses %d blacklisted function', 'Warning: This plugin uses %d blacklisted functions', count( $blacklisted ), 'wp-plugin-parser' ), count( $blacklisted ) );
		?>
	</li>
	<?php endif; ?>


</ul>
<hr>
<?php
foreach ( array( 'functions', 'classes', 'methods' ) as $use_type ) :
	$names             = array();
	$use_type_warnings = isset( $results['warnings'] ) && in_array( $use_type, $results['warnings'] );

	if ( ! empty( $wp_results[ $use_type ] ) ) {
		$names = wp_list_pluck( $wp_results[ $use_type ], 'title' );
	} else {
		if ( empty( $results[ $use_type ] ) ) {
			continue;
		}

		if ( $settings['wp_only'] ) {
			if ( ! $use_type_warnings ) {
				continue;
			}
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
				<?php if( $use_type_warnings ) : ?>
					<th scope='col'>
						<?php _e( 'Warning', 'wp-plugin-parser' ); ?>
					</th>
				<?php endif; ?>
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

				if ( ! $wp_type && $settings['wp_only'] ) {
					if( ! in_array( $type, $blacklisted ) ) {
						continue;
					}
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
				<?php if( $use_type_warnings ) : ?>
					<td>
						<?php
							$separator = '';
							if ( $wp_type && $wp_type['deprecated'] ) :
								$separator = ' - ';
						?>
							<span style="color: red;"><strong><?php _e( 'deprecated', 'wp-plugin-parser' ); ?></strong></span>
						<?php endif; ?>
						<?php if ( ( $use_type = 'functions' ) && in_array( $type, $blacklisted ) ) : ?>
							<?php echo $separator; ?><span style="color: red;"><strong><?php _e( 'blacklisted', 'wp-plugin-parser' ); ?></strong></span>
						<?php endif; ?>
					</td>
				<?php endif; ?>
			</tr>
			<?php $i++; ?>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php endforeach; ?>
