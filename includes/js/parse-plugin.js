( function( $ ) {
	wp = wp || {};
	var parser = plugin_parser || {};

	var form = $( '#wp-plugin-parser-form' );
	var submit = $( '#wp-plugin-parser-submit', form );
	var htmlAjax = $( '#wp-plugin-parser-ajax-results' );
	var processing = false;
	var pluginName = '';
	var wpAdminBar = 0;
	var total = 0;

	if ( !wp || !parser || !form.length || !submit.length || !htmlAjax.length ) {
		return;
	}

	var backLink = $( '<a href="' + parser.plugin_url + '">' + parser.backlink + '</a>' );
	backLink.addClass( 'wp-plugin-parser-backlink' );

	// Remove server side rendered results (if they exists)
	$( '#wp-plugin-parser-results' ).empty();
	$( '.error,.updated' ).hide();

	var template = wp.template( "wp-plugin-parser-ajax-results-template" );
	var templateData = defaultTemplateData();

	// Calculate wp adminbar offset
	$( document ).ready( function() {
		wpAdminBar = $( '#wpadminbar' ).length ? 32 : 0;
	} );

	// Show form
	$( document ).on( 'click', 'a.wp-plugin-parser-backlink', function( e ) {
		e.preventDefault();
		resetForm();
		form.show();
	} );

	// Start Parsing
	submit.on( 'click', function( e ) {
		e.preventDefault();

		if ( processing ) {
			return;
		}

		processing = true;
		resetForm();
		form.hide();

		pluginName = $( "select#plugins", form ).find( ':selected' ).text();
		templateData.header = parser.load_files;
		templateData.content = parser.notice;
		updateTemplate();

		var pos = htmlAjax.offset();
		$( 'html,body' ).animate( {
			scrollTop: pos.top - wpAdminBar
		}, 1 );

		// Reset checkboxes (needed for serialize())
		var checkBoxes = $( form ).find( 'input:checkbox' );
		checkBoxes.prop( 'value', '' );
		checkBoxes.filter( ':checked' ).prop( 'value', 'on' );

		wp.ajax.send( 'parse_files', {
			data: {
				form: form.serialize(),
				nonce: parser.file_nonce,
			}
		} ).done( function( response ) {
			total = response.total;

			// Add timeout for small plugins
			setTimeout( function() {
				templateData.header = addPluginName( parser.start );
				templateData.content = updateParseMessage( '1' );
				updateTemplate();
				parseUses();
			}, 2000 );

		} ).fail( function( response ) {
			displayErrors( response );
		} );
	} );

	// Parse functions classes and methods
	function parseUses() {
		wp.ajax.send( 'parse_uses', {
			data: { nonce: parser.uses_nonce }
		} ).done( function( response ) {
			templateData.content = updateParseMessage( response.count );
			updateTemplate();

			if ( response.done ) {
				// Add timeout for small plugins
				setTimeout( function() {
					parseWordPressUses();
				}, 2000 );
			} else {
				parseUses();
			}
		} ).fail( function( response ) {
			displayErrors( response );
		} );
	}

	// Parse WordPress data
	function parseWordPressUses() {
		wp.ajax.send( 'parse_wp_uses', {
			data: { nonce: parser.wp_uses_nonce }
		} ).done( function( response ) {
			displayResults();
		} ).fail( function( response ) {
			displayErrors( response );
		} );
	}

	function displayResults() {
		wp.ajax.send( 'display_results', {
			data: { nonce: parser.display_nonce }
		} ).done( function( response ) {
			processing = false;
			$( '.wp-plugin-parser-spinner' ).hide();

			templateData.content = response.content;
			var icon = '<span style="color:green;" class="dashicons dashicons-yes"></span>'
			templateData.subheader = icon + parser.finished;
			updateTemplate();

			$( '.wp-plugin-parser-plugin', htmlAjax ).hide();
			$( '#wp-plugin-parser-subheader', htmlAjax ).append( " (", $( backLink ), ")" );
		} ).fail( function( response ) {
			displayErrors( response );
		} );
	}

	function displayErrors( response ) {
		processing = false;
		templateData.errors = parser.error;
		templateData.content = backLink.prop( 'outerHTML' );

		if ( typeof response.errors === 'undefined' ) {
			updateTemplate();
			return;
		}

		if ( !response.errors.length ) {
			updateTemplate();
			return;
		}

		templateData.errors = response.errors.join( '<br/>' );
		updateTemplate();
	}

	function resetForm() {
		htmlAjax.empty();
		templateData = defaultTemplateData();
	}

	function defaultTemplateData() {
		return {
			header: '',
			subheader: '',
			errors: '',
			content: '',
			plugin: '',
		}
	}

	function updateTemplate() {
		templateData.plugin = addPluginName( parser.plugin );
		htmlAjax.html( template( templateData ) );
		if ( processing ) {
			$( '.wp-plugin-parser-spinner', htmlAjax ).show();
		}
	}

	function addPluginName( string ) {
		var plugin = pluginName || '';
		return string.replace( '%s', '<strong>' + plugin + '</strong>' );
	}

	function updateParseMessage( count ) {
		if ( !total ) {
			return '';
		}

		var content = parser.parse_file;
		return content.replace( '%1$d', count ).replace( '%2$d', total );
	}
} )( jQuery );