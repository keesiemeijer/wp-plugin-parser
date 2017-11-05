<div class="wrap">
	<h1><?php _e( 'WP Plugin Parser', 'wp-plugin-parser' ); ?></h1>
	<p>
		<?php _e( 'Parse plugins to see what WP functions and classes are used.', 'wp-plugin-parser' ); ?>
	</p>
	<hr>
	<form method="post" action="">
		<?php wp_nonce_field( 'wp_plugin_parser_nonce', 'security' ); ?>
		<table class='form-table'>
			<tr>
				<th scope='row'>
					<label for="exclude_dir">
						<?php _e( 'Exclude directories', 'wp-plugin-parser' ); ?>:
					</label>
				</th>
				<td>
					<input type="text" id="exclude_dir" name="exclude_dir" value="<?php echo $exclude_dirs_str; ?>" />
					<p class="description">
						<?php _e( 'A comma separated list of directories you want to exclude when parsing.', 'wp-plugin-parser' ); ?><br/>
						<?php _e( 'Use paths starting from the root plugin directory. (e.g. vendor, build, tests)', 'wp-plugin-parser' ); ?><br/>
					</p>

				</td>
			</tr>
			<tr>
				<th scope='row'>
					<label for="plugins">
						<?php _ex( 'Plugin', 'Plugin form label text', 'wp-plugin-parser' ); ?>:
					</label>
				</th>
				<td>
					<select id="plugins" name="plugins">
						<?php foreach ( $plugins as $plugin => $data ) : ?>
							<option value='<?php echo $plugin; ?>'<?php selected( $settings['plugin'], $plugin ); ?>><?php echo $data['Name']; ?></option>";
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php _e( 'The plugin you want to parse.', 'wp-plugin-parser' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope='row'>
				</th>
				<td>
					<label for="wp_only">
						<input id="wp_only" type="checkbox" name="wp_only" value="" <?php checked( $settings['wp_only'], 'on' ); ?>>
						<?php _e( 'Display WordPress functions only', 'wp-plugin-parser' ); ?>
					</label>

				</td>
			</tr>
		</table>
		<input id="wp_plugin_parser" class="button button-primary" name="wp_plugin_parser" value="Parse Plugin" type="submit">
	</form><br/>
	<?php
		if ( $parsed ) {
			include 'results.php';
		}
	?>
</div>
