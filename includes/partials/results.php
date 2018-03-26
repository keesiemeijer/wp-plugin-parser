<hr>
<h2 id="parse-results">Parse Results</h2>
<ul style="font-size: 14px;">
	<li>
		<?php printf( __( 'Plugin: %s', 'wp-plugin-parser' ), '<strong>' . $settings['plugin_name'] . '</strong>' ) ; ?>
	<li>

	<li>
		<?php printf( __( 'Parsed PHP files: %s', 'wp-plugin-parser' ), $file_count ) ; ?>
	<li>

	<?php if ( $wp_results['max_version'] ) : ?>
	<li>
		<?php printf( __( 'Requires at least version: %s', 'wp-plugin-parser' ), $wp_results['max_version'] ); ?>
	</li>
	<?php endif; ?>

	<?php if ( ! isset( $results['warnings'] ) ) : ?>
	<li style="margin-top:1em;">
		<span style="color:green;" class="dashicons dashicons-yes"></span><?php _e( 'No deprecated or blacklisted functions or classes found', 'wp-plugin-parser' ); ?>
	</li>
	<?php endif; ?>

	<?php if ( 0 < $wp_results['deprecated'] ) : ?>

		<?php if(isset($deprecated['functions']) && $deprecated['functions'] ) : ?>
				<li style="color: red;">
					<span class="dashicons dashicons-no"></span>
					<?php
						/* translators: %d: Number of deprecated functions */
						printf( _n( 'Warning: This plugin uses %d deprecated function', 'Warning: This plugin uses %d deprecated functions', count( $deprecated['functions'] ), 'wp-plugin-parser' ), count( $deprecated['functions']) );
					?>
				</li>
		<?php endif; ?>

		<?php if( isset( $deprecated['classes'] ) && $deprecated['classes'] ) : ?>
				<li style="color: red;">
					<span class="dashicons dashicons-no"></span>
					<?php
						/* translators: %d: Number of deprecated functions */
						printf( _n( 'Warning: This plugin uses %d deprecated class', 'Warning: This plugin uses %d deprecated classes', count( $deprecated['classes'] ), 'wp-plugin-parser' ), count( $deprecated['classes']) );
					?>
				</li>
		<?php endif; ?>

	<?php endif; ?>

	<?php if ( 0 < count( $blacklisted ) ) : ?>
	<li style="color: red;">
		<span class="dashicons dashicons-no"></span>
		<?php
			/* translators: %d: Number of blacklisted functions */
			printf( _n( 'Warning: This plugin uses %d blacklisted function', 'Warning: This plugin uses %d blacklisted functions', count( $blacklisted ), 'wp-plugin-parser' ), count( $blacklisted ) );
		?>
	</li>
	<?php endif; ?>

</ul>

<?php
foreach ( array( 'functions', 'classes', 'methods' ) as $use_type ) :
	$names             = array();
	$use_type_slug     = ('methods' === $use_type) ? '' : $use_type . '/';
	$has_warnings      = isset( $results['warnings'] ) && in_array( $use_type, $results['warnings'] );
	$has_constructs    = false;
	$has_since         = false;

	if ( ! empty( $wp_results[ $use_type ] ) ) {
			$names     = wp_list_pluck( $wp_results[ $use_type ], 'title' );
			$has_since = array_filter( wp_list_pluck( $wp_results[ $use_type ], 'since' ) );
			$has_since = ! empty( $has_since );
	} else {
		if ( empty( $results[ $use_type ] ) ) {
			continue;
		}

		if ( $settings['wp_only'] ) {
			if ( ! $has_warnings ) {
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
	<div style="margin-top: 1em;">
		<hr>
		<h3><?php echo  ucfirst( $use_type ); ?></h3>

		<?php if ( $show_construct_info && ('functions' === $use_type ) ) : ?>
		<p>
			<strong><?php _e('Notice', 'wp-plugin-parser') ?></strong>: 
			<?php
				$construct_count = count( $results['constructs'] );
			    $link_text =  _n( 'PHP language construct', 'PHP language constructs', $construct_count, 'wp-plugin-parser' );
				$link = '<a href="https://secure.php.net/manual/en/reserved.keywords.php">' . $link_text . '</a>';

				/* translators: 1: Number of PHP language constructs found, 2:link to PHP language construncts page */
				printf( _n( ' %1$d %2$s found.', '%1$d %2$s found.', count( $results['constructs'] ), 'wp-plugin-parser' ), count( $results['constructs'] ), $link );
			?>
		</p>
		<?php endif; ?>

		<table class="widefat fixed">
			 <thead>
				<tr>
					<th scope='col'>
						<?php echo  ucfirst( $single ); ?>
					</th>

					<?php if( $has_since ) : ?>
						<th scope='col'>
							<?php _e( 'WordPress Since', 'wp-plugin-parser' ); ?>
						</th>
					<?php endif; ?>

					<?php if( $has_warnings ) : ?>
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
						<a href="https://developer.wordpress.org/reference/<?php echo $use_type_slug; ?><?php echo $wp_type['slug']; ?>"><?php echo $type; ?></a>
						<?php else : ?>
							<?php
							echo $type;
							if( ( 'functions' === $use_type ) && in_array( $type, $results['constructs'] ) ) {
								echo ' <span>*</span>';
								$has_constructs = true;
							}
							?>
						<?php endif; ?>
					</td>

					<?php if( $has_since ) : ?>
						<td>
							<?php if ( $wp_type ) : ?>
								<?php echo $wp_type['since']; ?>
							<?php endif; ?>
						</td>
					<?php endif; ?>

					<?php if( $has_warnings ) : ?>
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

		<?php if( $has_constructs ) : ?>
		<p>
			* <?php _e( 'PHP language construct', 'wp-plugin-parser'); ?>.
		</p>
		<?php endif; ?>

	</div>
<?php endforeach; ?>
