<?php
/**
 * This file handles the editor preview setting
 *
 * @since   2.0.0
 * @package Code_Snippets
 */

namespace Code_Snippets\Settings;

use function Code_Snippets\enqueue_code_editor;
use function Code_Snippets\get_editor_themes;

/**
 * Load the CSS and JavaScript for the editor preview field
 */
function enqueue_editor_preview_assets() {
	$plugin = \Code_Snippets\code_snippets();

	enqueue_code_editor( 'php' );

	// Enqueue all editor themes.
	$themes = get_editor_themes();

	foreach ( $themes as $theme ) {

		if ( 'default' !== $theme ) {
			wp_enqueue_style(
				'code-snippets-editor-theme-' . $theme,
				plugins_url( "css/min/editor-themes/$theme.css", $plugin->file ),
				array( 'code-editor' ), $plugin->version
			);
		}
	}

	// Enqueue the menu scripts.
	wp_enqueue_script(
		'code-snippets-settings-menu',
		plugins_url( 'js/min/settings.js', $plugin->file ),
		[ 'code-editor' ], $plugin->version, true
	);

	// Extract the CodeMirror-specific editor settings.
	$setting_fields = get_settings_fields();
	$editor_fields = array();

	foreach ( $setting_fields['editor'] as $name => $field ) {
		if ( empty( $field['codemirror'] ) ) {
			continue;
		}

		$editor_fields[] = array(
			'name'       => $name,
			'type'       => $field['type'],
			'codemirror' => addslashes( $field['codemirror'] ),
		);
	}

	// Pass the saved options to the external JavaScript file.
	$inline_script = 'var code_snippets_editor_settings = ' . wp_json_encode( $editor_fields ) . ';';

	wp_add_inline_script( 'code-snippets-settings-menu', $inline_script, 'before' );
}

/**
 * Render a theme select field
 *
 * @param array $atts Field attributes.
 */
function render_editor_theme_select_field( $atts ) {

	$saved_value = get_setting( $atts['section'], $atts['id'] );

	echo '<select name="code_snippets_settings[editor][theme]">';

	// print a dropdown entry for each theme
	foreach ( get_editor_themes() as $theme ) {

		// skip mobile themes
		if ( 'ambiance-mobile' === $theme ) {
			continue;
		}

		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $theme ),
			selected( $theme, $saved_value, false ),
			esc_html( ucwords( str_replace( '-', ' ', $theme ) ) )
		);
	}

	echo '</select>';
}

/**
 * Render the editor preview setting
 */
function render_editor_preview() {
	$settings = get_settings_values();
	$settings = $settings['editor'];

	$indent_unit = absint( $settings['indent_unit'] );
	$tab_size = absint( $settings['tab_size'] );

	$n_tabs = $settings['indent_with_tabs'] ? floor( $indent_unit / $tab_size ) : 0;
	$n_spaces = $settings['indent_with_tabs'] ? $indent_unit % $tab_size : $indent_unit;

	$indent = str_repeat( "\t", $n_tabs ) . str_repeat( ' ', $n_spaces );

	$code = "add_filter( 'admin_footer_text', function ( \$text ) {\n\n" .
	        $indent . "\$site_name = get_bloginfo( 'name' );\n\n" .
	        $indent . '$text = "Thank you for visiting $site_name.";' . "\n" .
	        $indent . 'return $text;' . "\n" .
	        "} );\n";

	echo '<textarea id="code_snippets_editor_preview">', esc_textarea( $code ), '</textarea>';
}
