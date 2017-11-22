<div class="wrap">
	<h1><?php _e( 'WP Plugin Parser', 'wp-plugin-parser' ); ?></h1>
	<p>
		<?php _e( 'Parse plugins to see what PHP, WP or plugin functions and classes are used.', 'wp-plugin-parser' ); ?>
	</p>
	<?php if ( $notice ) : ?>
		<div class="updated">
			<p>
				<?php echo $notice; ?>
			</p>
		</div>
	<?php endif; ?>
	<?php if ( $warnings ) : ?>
		<div class="error">
			<p>
				<?php printf(_n('%d warning found for this plugin', '%d warnings found for this plugin', $warnings, 'wp-plugin-parser'), $warnings); ?>
			</p>
		</div>
	<?php endif; ?>
	<hr>
	<form method="post" action="">
		<?php wp_nonce_field( 'wp_plugin_parser_nonce', 'security' ); ?>
		<table class='form-table'>
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
					<label for="exclude_dirs">
						<?php _e( 'Exclude directories', 'wp-plugin-parser' ); ?>:
					</label>
				</th>
				<td>
					<input type="text" class="regular-text" id="exclude_dirs" name="exclude_dirs" value="<?php echo $exclude_dirs_str; ?>" />
					<p class="description">
						<?php _e( 'A comma separated list of directories you want to exclude when parsing.', 'wp-plugin-parser' ); ?><br/>
						<?php _e( 'Use paths starting from the root plugin directory. (e.g. vendor, build, tests)', 'wp-plugin-parser' ); ?><br/>
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
						<p class="description">
							<?php _e( 'Blacklisted functions (see below) are displayed regardless of this setting.', 'wp-plugin-parser' ); ?><br/>

						</p>
					</label>

				</td>
			</tr>
			<tr>
				<th scope='row'>
					<label for="blacklist_functions">
						<?php _e('Blacklist Functions', 'wp-plugin-parser'); ?>
					</label>
				</th>
				<td>
					<textarea name="blacklist_functions" rows="4" cols="50" id="blacklist_functions" class="large-text code"><?php echo esc_textarea( $blacklist_str ); ?></textarea>
					<p class="description">
						<?php _e( 'A comma separated list of functions that are dangerous (e.g. exec, eval, base64_decode)', 'wp-plugin-parser' ); ?><br>
						<?php _e( 'Warnings are displayed if these functions are used in a plugin', 'wp-plugin-parser' ); ?>
					</p>
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
