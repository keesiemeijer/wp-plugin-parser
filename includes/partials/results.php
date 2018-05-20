<hr>
<h2 id="parse-results">Parse Results</h2>
<ul style="font-size: 14px;">
	<li class="wp-plugin-parser-plugin">
		<?php printf( __( 'Plugin: %s', 'wp-plugin-parser' ), '<strong>' . $settings['plugin_name'] . '</strong>' ) ; ?>
	<li>
	<?php if( ! $settings['check_version'] ) : ?>
		<li>
			<?php _e( 'Plugin not checked for PHP version compatibility', 'wp-plugin-parser' ); ?>
		<li>
	<?php endif; ?>

	<?php if( $errors || $uses_errors ) : ?>
		<li style="color: red;<?php echo $margin; ?>">
			<span class="dashicons dashicons-no"></span>
			<?php
				if( 0 === strpos( $uses_errors, 'The parsed plugin contains errors' ) ) {
					_e( 'Warning: The selected plugin is not parsed because it contains errors', 'wp-plugin-parser' );
				} else {
					_e( 'Warning: This plugin is not parsed for functions and classes because of parsing errors', 'wp-plugin-parser' );
				}
			?>
		</li>
		<?php $margin = ''; ?>
	<?php endif; ?>

	<?php if( $compat_errors ) : ?>
		<li style="color: red;<?php echo $margin; ?>">
		<span class="dashicons dashicons-no"></span>
			<?php _e( 'Warning: This plugin was not checked for PHP version compatibility because of errors', 'wp-plugin-parser' ); ?>
		</li>
	<?php endif; ?>

	<?php if ( $parsed_uses && ! $uses_errors ) : ?>
		<!-- Parse Results -->
		<li style="margin-top: 1em;">
			<?php printf( __( 'Parsed PHP files: %s', 'wp-plugin-parser' ), $file_count ) ; ?>
		<li>

		<?php if ( $wp_uses['max_version'] ) : ?>
			<li>
				<?php printf( __( 'Requires at least version: %s', 'wp-plugin-parser' ), $wp_uses['max_version'] ); ?>
			</li>
		<?php endif; ?>

		<?php $margin = ' margin-top: 1em;'; ?>
		<?php if ( ! isset( $parsed_uses['warnings'] ) ) : ?>
			<li style="<?php echo trim( $margin ); ?>">
				<span style="color:green;" class="dashicons dashicons-yes"></span><?php _e( 'No deprecated or blacklisted functions or classes found', 'wp-plugin-parser' ); ?>
			</li>
			<?php $margin = ''; ?>
		<?php endif; ?>

		<?php if ( 0 < $wp_uses['deprecated'] ) : ?>

			<?php if( isset( $deprecated['functions'] ) && $deprecated['functions'] ) : ?>
				<li style="color: red;<?php echo $margin; ?>">
					<span class="dashicons dashicons-no"></span>
					<?php
						/* translators: %d: Number of deprecated functions */
						printf( _n( 'Warning: This plugin uses %d deprecated function', 'Warning: This plugin uses %d deprecated functions', count( $deprecated['functions'] ), 'wp-plugin-parser' ), count( $deprecated['functions'] ) );
					?>
				</li>
				<?php $margin = ''; ?>
			<?php endif; ?>

			<?php if( isset( $deprecated['classes'] ) && $deprecated['classes'] ) : ?>
				<li style="color: red;<?php echo $margin; ?>">
					<span class="dashicons dashicons-no"></span>
					<?php
						/* translators: %d: Number of deprecated functions */
						printf( _n( 'Warning: This plugin uses %d deprecated class', 'Warning: This plugin uses %d deprecated classes', count( $deprecated['classes'] ), 'wp-plugin-parser' ), count( $deprecated['classes'] ) );
					?>
				</li>
				<?php $margin = ''; ?>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( 0 < count( $blacklisted ) ) : ?>
			<li style="color: red;<?php echo $margin; ?>">
				<span class="dashicons dashicons-no"></span>
				<?php
					/* translators: %d: Number of blacklisted functions */
					printf( _n( 'Warning: This plugin uses %d blacklisted function', 'Warning: This plugin uses %d blacklisted functions', count( $blacklisted ), 'wp-plugin-parser' ), count( $blacklisted ) );
				?>
			</li>
			<?php $margin = ''; ?>
		<?php endif; ?>
	<?php endif ?>

	<?php if( $settings['check_version'] && ! $errors && ! $compat_errors ) : ?>
		<!-- Copatibility results -->
		<?php if ( ! $is_compatible && $compat ) : ?>
			<li style="color: red;<?php echo $margin; ?>">
				<span class="dashicons dashicons-no"></span>
				<?php printf( __( 'Warning: This plugin is not compatible with PHP version: %s.', 'wp-plugin-parser' ), $settings['php_version'] ); ?>
				(<a href='<?php echo $plugin_url; ?>#compat-results'><?php _e( 'See compatibility results', 'wp-plugin-parser' ); ?></a>)
			</li>
			<?php $margin = ''; ?>
		<?php endif; ?>
		<?php if( $is_compatible ) : ?>
			<li>
				<span style="color:green;<?php echo $margin; ?>" class="dashicons dashicons-yes"></span>
				<?php printf( __( 'This plugin is compatible with PHP version %s', 'wp-plugin-parser' ), $settings['php_version'] ); ?>
			</li>
			<?php $margin = ''; ?>
		<?php endif; ?>
	<?php endif; ?>

</ul>