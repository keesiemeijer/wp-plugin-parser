<div class="wrap">
	<h1><?php _e( 'WP Plugin Parser', 'wp-plugin-parser' ); ?></h1>
	<p>
		<?php _e( 'Parse plugins to see what PHP, WP or plugin functions and classes are used.', 'wp-plugin-parser' ); ?>
	</p>
    <?php include 'admin-notice.php'; ?>
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
						<?php foreach ( $this->plugins as $plugin => $data ) : ?>
							<option value='<?php echo $plugin; ?>'<?php selected( $settings['plugin_file'], $plugin ); ?>><?php echo $data['Name']; ?></option>";
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
					<input type="text" class="large-text" id="exclude_dirs" name="exclude_dirs" value="<?php echo $exclude_dirs_str; ?>" />
					<p class="description">
						<?php _e( 'A comma separated list of directories you want to exclude when parsing.', 'wp-plugin-parser' ); ?><br/>
						<?php _e( 'Use paths starting from the root plugin directory. (e.g. build, tests, lib)', 'wp-plugin-parser' ); ?><br/>
						<?php _e( 'The <code>vendor</code>, <code>node_modules</code> and <code>.git</code> directories are excluded by default.', 'wp-plugin-parser' ); ?><br/>
					</p>

				</td>
			</tr>
			<tr>
				<th scope='row'>
				</th>
				<td>
					<label for="exclude_strict">
						<input id="exclude_strict" type="checkbox" name="exclude_strict" value="" <?php checked( $settings['exclude_strict'], 'on' ); ?>>
						<?php _e( 'Exclude everywhere', 'wp-plugin-parser' ); ?>
						<p class="description">
							<?php _e( "Exclude the directories (defined above) also if found in a plugin sub directory", 'wp-plugin-parser' ); ?><br/>
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
						<?php _e( 'A comma separated list of (dangerous) functions that should trigger a warning (e.g. exec, base64_decode)', 'wp-plugin-parser' ); ?><br>
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
							<?php _e( 'Blacklisted functions (see above) are displayed regardless of this setting.', 'wp-plugin-parser' ); ?><br/>

						</p>
					</label>

				</td>
			</tr>
			<tr>
				<th scope='row'>
					<?php _e( 'PHP version', 'Plugin form label text', 'wp-plugin-parser' ); ?>:
				</th>
				<td>
					<label for="check_version">
						<input id="check_version" type="checkbox" name="check_version" value="" <?php checked( $settings['check_version'], 'on' ); ?>>
						<?php _e( 'Test if the plugin is compatible with PHP version', 'wp-plugin-parser' ); ?>
					</label>
					
					<label for="php_version">
					<select id="php_version" name="php_version">
						<?php foreach ( $php_versions as $version ) : ?>
							<option value='<?php echo $version; ?>'<?php selected( $settings['php_version'], $version ); ?>><?php echo $version; ?></option>";
						<?php endforeach; ?>
					</select>
					</label>
				</td>
			</tr>
		</table>
		<input id="wp_plugin_parser" class="button button-primary" name="wp_plugin_parser" value="Parse Plugin" type="submit">
	</form><br/>
	<?php
		if($request) {
			include 'results.php';
		
			if ( $parsed_files && ! $parse_errors ) {
				include 'parse-results.php';
			}

			if( $settings['check_version'] && $compat && ! $compat_errors) {
				include 'compat.php';
			}
		}
	?>
</div>
