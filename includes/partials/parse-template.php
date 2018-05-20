<script type="text/html" id="tmpl-wp-plugin-parser-ajax-results-template">
<style type='text/css'>
.wp-plugin-parser-spinner {
			background: url("/wp-includes/images/spinner-2x.gif") no-repeat scroll 0 50%;
			-webkit-background-size: 20px 20px;
			background-size: 20px 20px;
			margin: 0 0.5em;
			padding-left: 20px;
		}
#wp-plugin-parser-plugin,
#wp-plugin-parser-subheader,
#wp-plugin-parser-errors,
.wp-plugin-parser-backlink {
	font-size: 14px;
}

#wp-plugin-parser-errors {
	color: red;
}
</style>
<div id="wp-plugin-parser-container" aria-hidden="true">
	<# if ( data.header.length ) { #>
		<h2 id="wp-plugin-parser-header">{{data.header}}<span class="wp-plugin-parser-spinner" style="display:none;"></span></h2>
	<# } #>

	<p id="wp-plugin-parser-plugin">{{{data.plugin}}}</p>

	<# if ( data.subheader.length ) { #>
		<p id="wp-plugin-parser-subheader">{{data.subheader}}</p>
	<# } #>

	<# if ( data.errors.length ) { #>
		<div><p id="wp-plugin-parser-errors">{{{data.errors}}}</p></div>
	<# } #>

	<div id="wp-plugin-parser-content">
		<# if ( data.content.length ) { #>
			<div>{{{data.content}}}</div>
		<# } #>
	</div>
</div>
</script>