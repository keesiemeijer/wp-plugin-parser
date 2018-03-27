<hr>
<h2 id="compat-results"><?php _e( 'PHP Compatibility Results', 'wp-plugin-parser' ); ?></h2>
<p>
	<?php printf( __( 'This plugin is not compatible with PHP version: %s', 'wp-plugin-parser' ), $settings['php_version'] ); ?>
</p>
<p>	
	<strong><?php _e('Note', 'wp-plugin-parser'); ?>:</strong>  
	<?php _e( "This tool cannot detect if a plugin provides (back) compatibility for this PHP version in some other way.", 'wp-plugin-parser' ); ?>
	<?php echo ' ' . __( "You'll need to inspect the plugin manually, to make sure it fails gracefully and doesn't produce fatal errors.", 'wp-plugin-parser' ); ?>
</p>
<p>
	<pre style="padding: 1em; background: #fff;">
		<?php echo $compat; ?>
	</pre>
</p>
  