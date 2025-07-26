<?php
/**
 * This file handles the editor preview setting
 *
 * @since   2.0.0
 * @package Code_Snippets
 */

namespace Code_Snippets\Settings;

use Code_Snippets\Admin_Menu;
use function Code_Snippets\code_snippets;
use function Code_Snippets\enqueue_code_editor;
use function Code_Snippets\get_editor_themes;

/**
 * Retrieve the list of code editor themes.
 *
 * @return array<string, string> List of editor themes.
 */
function get_editor_theme_list(): array {
	$themes = [
		'default' => __( 'Default', 'code-snippets' ),
	];

	foreach ( get_editor_themes() as $theme ) {

		// Skip mobile themes.
		if ( '-mobile' === substr( $theme, -7 ) ) {
			continue;
		}

		$themes[ $theme ] = ucwords( str_replace( '-', ' ', $theme ) );
	}

	return $themes;
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
