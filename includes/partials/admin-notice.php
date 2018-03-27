<?php if ( ! $errors && $notice ) : ?>
	<div class="updated">
		<p>
			<?php echo $notice; ?>
			<?php if ( ! $warnings ) : ?>
				 (<a href='<?php echo $plugin_url; ?>#parse-results'><?php _e( 'See parse results', 'wp-plugin-parser' ); ?></a>)
			<?php endif; ?> 
		</p>
	</div>
<?php endif; ?>

<?php if ( ! $errors && $warnings ) : ?>
	<div class="error">
		<p>
			<?php printf( _n( 'This plugin generates %d warning', 'This plugin generates %d warnings', $warnings, 'wp-plugin-parser' ), $warnings ); ?> 
			(<a href='<?php echo $plugin_url; ?>#parse-results'><?php _e( 'See parse results', 'wp-plugin-parser' ); ?></a>)
		</p>
	</div>
<?php endif; ?>

<?php if ( $errors ) : ?>
	<div class="error">
		<p>
			<?php _e('This plugin generated errors while retrieving files', 'wp-plugin-parser'); ?><br/><br/>
			<?php echo $errors; ?>
		</p>
	</div>
<?php endif; ?>

<?php if ( ! $errors && $parse_errors ) : ?>
	<div class="error">
		<p>
			<?php _e('This plugin generated errors while parsing files', 'wp-plugin-parser'); ?><br/><br/>
			<?php echo $parse_errors; ?>
		</p>
	</div>
<?php endif; ?>

<?php if ( ! $errors && $compat_errors ) : ?>
	<div class="error">
		<p>
			<?php _e('This plugin generated errors while checking PHP compatibility', 'wp-plugin-parser'); ?><br/><br/>
			<?php echo $compat_errors; ?>
		</p>
	</div>
<?php endif; ?>
