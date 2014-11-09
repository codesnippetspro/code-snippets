<?php

function code_snippets_editor_settings_preview_assets( $hook ) {

	/* Only load on the settings page */
	if ( code_snippets_get_menu_hook( 'settings' ) !== $hook ) {
		return;
	}

	/* Enqueue scripts for the editor preview */
	code_snippets_enqueue_codemirror();

	/* Enqueue ALL themes */
	$themes_dir = plugin_dir_path( CODE_SNIPPETS_FILE ) . 'vendor/codemirror/theme/';
	$themes = glob( $themes_dir . '*.css' );

	foreach ( $themes as $theme ) {
		$theme = str_replace( $themes_dir, '', $theme );
		$theme = str_replace( '.css', '', $theme );

		wp_enqueue_style(
			'code-snippets-codemirror-theme-' . $theme,
			plugins_url( "vendor/codemirror/theme/$theme.css", CODE_SNIPPETS_FILE ),
			array( 'code-snippets-codemirror' )
		);
	}

	/* Enqueue jQuery */
	wp_enqueue_script( 'jquery' );
}

add_action( 'admin_enqueue_scripts', 'code_snippets_editor_settings_preview_assets' );

/**
 * Render a theme select field
 */
function code_snippets_codemirror_theme_select_field() {
	$settings = get_option( 'code_snippets_settings' );
	$saved_value = $settings['editor']['theme'];

	echo '<select name="code_snippets_settings[editor][theme]">';

	echo '<option value="default"' . selected( 'default', $saved_value, false ) . '>default</option>';

	/* Fetch all theme CSS files */
	$themes_dir = plugin_dir_path( CODE_SNIPPETS_FILE ) . 'vendor/codemirror/theme/';
	$themes = glob( $themes_dir . '*.css' );

	/* Print dropdown entry for each theme */
	foreach ( $themes as $theme ) {

		/* Extract theme name from path */
		$theme = str_replace( $themes_dir, '', $theme );
		$theme = str_replace( '.css', '', $theme );

		/* Skip mobile themes */
		if ( 'ambiance-mobile' === $theme ) {
			continue;
		}

		printf (
			'<option value="%1$s"%2$s>%1$s</option>',
			$theme,
			selected( $theme, $saved_value, false )
		);
	}

	echo '</select>';
}

function code_snippets_settings_editor_preview() {
	$example_content = 'function example_replace_admin_footer_text( $footer_text ) {

	if ( ! is_network_admin() ) {

		$footer_text = str_replace(
			__( \'Thank you for creating with <a href="http://wordpress.org/">WordPress</a>.\' ),
			sprintf ( __( \'Thank you for visiting <a href="%1$s">%2$s</a>.\' ), get_home_url(), get_bloginfo( \'name\' ) ),
			$footer_text
		);

	}

	return $footer_text;
}

add_filter( \'admin_footer_text\', \'example_replace_admin_footer_text\' );';

	$atts = array(
		'mode' => 'text/x-php',
		'value' => $example_content
	);

	?>

	<div style="max-width: 800px" id="code_snippets_editor_preview"></div>

	<script>
	(function( $ ) {
		"use strict";

		$(function() {

			// Load CodeMirror
			var atts = <?php echo code_snippets_get_editor_atts( $atts, true ); ?>;
			var editor = CodeMirror(document.getElementById('code_snippets_editor_preview'), atts);

			// Dynamically change editor settings

			$( 'select[name="code_snippets_settings[editor][theme]"]' ).change( function () {
				editor.setOption( 'theme', $(this).val() );
			} );

			$( 'input[name="code_snippets_settings[editor][wrap_lines]"]' ).change( function () {
				editor.setOption( 'lineWrapping', $(this).is(':checked') );
			} );

			$( 'input[name="code_snippets_settings[editor][line_numbers]"]' ).change( function () {
				editor.setOption( 'lineNumbers', $(this).is(':checked') );
			} );

			$( 'input[name="code_snippets_settings[editor][indent_with_tabs]"]' ).change( function () {
				editor.setOption( 'indentWithTabs', $(this).is(':checked') );
			} );

			$( 'input[name="code_snippets_settings[editor][indent_unit]"]' ).change( function () {
				editor.setOption( 'indentUnit', $(this).val() );
			} );

			$( 'input[name="code_snippets_settings[editor][tab_size]"]' ).change( function () {
				editor.setOption( 'tabSize', $(this).val() );
			} );

			$( 'input[name="code_snippets_settings[editor][auto_close_brackets]"]' ).change( function () {
				editor.setOption( 'autoCloseBrackets', $(this).is(':checked') );
			} );

			$( 'input[name="code_snippets_settings[editor][highlight_selection_matches]"]' ).change( function () {
				editor.setOption( 'highlightSelectionMatches', $(this).is(':checked') );
			} );
		});

	}(jQuery));
	</script>

	<?php
}
