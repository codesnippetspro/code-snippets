<?php

/**
 * This file handles the editor preview setting
 *
 * @since 2.0
 * @package Code_Snippets
 */

/**
 * Load the CSS and JavaScript for the editor preview field
 *
 * @param string $hook The current page hook
 */
function code_snippets_editor_settings_preview_assets( $hook ) {
	$plugin = code_snippets();

	// only load on the settings page
	if ( $plugin->get_menu_hook( 'settings' ) !== $hook ) {
		return;
	}

	// enqueue scripts for the editor preview
	code_snippets_enqueue_editor();

	// enqueue all editor themes
	$themes = code_snippets_get_available_themes();

	foreach ( $themes as $theme ) {

		wp_enqueue_style(
			'code-snippets-editor-theme-' . $theme,
			plugins_url( "css/min/editor-themes/$theme.css", $plugin->file ),
			array( 'code-snippets-editor' ), $plugin->version
		);
	}

	// enqueue the menu scripts
	wp_enqueue_script(
		'code-snippets-settings-menu',
		plugins_url( 'js/min/settings.js', $plugin->file ),
		array( 'code-snippets-editor' ), $plugin->version, true
	);

	$setting_fields = code_snippets_get_settings_fields();
	$editor_fields = array();

	foreach ( $setting_fields['editor'] as $name => $field ) {
		if ( empty( $field['codemirror'] ) ) {
			continue;
		}

		$editor_fields[] = array(
			'name' => $name,
			'type' => $field['type'],
			'codemirror' => addslashes( $field['codemirror'] ),
		);
	}

	$inline_script = 'var code_snippets_editor_atts = ' . code_snippets_get_editor_atts( array(), true ) . ';';
	$inline_script .= "\n" . 'var code_snippets_editor_settings = ' . json_encode( $editor_fields ) . ';';

	wp_add_inline_script( 'code-snippets-settings-menu', $inline_script, 'before' );
}

add_action( 'admin_enqueue_scripts', 'code_snippets_editor_settings_preview_assets' );

/**
 * Render a theme select field
 *
 * @param array $atts
 */
function code_snippets_codemirror_theme_select_field( $atts ) {

	$saved_value = code_snippets_get_setting( $atts['section'], $atts['id'] );

	echo '<select name="code_snippets_settings[editor][theme]">';
	echo '<option value="default"' . selected( 'default', $saved_value, false ) . '>Default</option>';

	// print a dropdown entry for each theme
	foreach ( code_snippets_get_available_themes() as $theme ) {

		// skip mobile themes
		if ( 'ambiance-mobile' === $theme ) {
			continue;
		}

		printf(
			'<option value="%s"%s>%s</option>',
			$theme,
			selected( $theme, $saved_value, false ),
			ucwords( str_replace( '-', ' ', $theme ) )
		);
	}

	echo '</select>';
}

/**
 * Render the editor preview setting
 */
function code_snippets_settings_editor_preview() {
	echo '<div id="code_snippets_editor_preview"></div>';
}
