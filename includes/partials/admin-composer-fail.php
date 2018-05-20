<div class="wrap">
<h1><?php _e( 'WP Plugin Parser', 'wp-plugin-parser' ); ?></h1>

<div class="error">
	<p>
		<?php _e( 'Missing plugin dependencies.', 'wp-plugin-parser' ); ?> 
		<?php _e( 'Please install dependencies with composer', 'wp-plugin-parser' ); ?><br/>
		<?php printf( __( 'See the %s', 'wp-plugin-parser' ), "<a href='{$install}'>" . __('installation instructions', 'wp-plugin-parser') . '</a>' ); ?> 
	</p>
</div>  
</div>
