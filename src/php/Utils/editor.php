<?php
/**
 * Functions for loading the code editor.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Utils;

use Code_Snippets\Settings\Settings_Fields;
use function Code_Snippets\Settings\get_setting;
use function Code_Snippets\Settings\get_settings_values;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Script and style handle for the code editor.
 */
const CODE_EDITOR_HANDLE = 'code-snippets-code-editor';

/**
 * Determine whether the current user wants a syntax-highlighting code editor.
 *
 * Honours the "Syntax Highlighting" option on the user's profile screen, which
 * falls back to a plain textarea when disabled.
 *
 * @return bool
 */
function is_code_editor_enabled(): bool {
	return ! ( is_user_logged_in() && 'false' === wp_get_current_user()->syntax_highlighting );
}

/**
 * Build the code editor options from the saved editor settings.
 *
 * @param array<string, mixed> $extra_atts Attributes to override the saved ones.
 *
 * @return array<string, mixed> Editor options, keyed by the name each editor setting field declares.
 */
function get_code_editor_settings( array $extra_atts = [] ): array {
	$atts = [
		'matchBrackets' => true,
		'lint'          => true,
		'direction'     => 'ltr',
	];

	$plugin_settings = get_settings_values();
	$field_definitions = Settings_Fields::get_field_definitions();

	foreach ( $field_definitions['editor'] as $field_id => $field ) {
		// The 'codemirror' setting field specifies the name of the attribute.
		$atts[ $field['codemirror'] ] = $plugin_settings['editor'][ $field_id ];
	}

	$atts = array_merge( $atts, $extra_atts );
	$atts = apply_filters( 'code_snippets_codemirror_atts', $atts );

	// Ensure number values are not formatted as strings.
	foreach ( [ 'indentUnit', 'tabSize', 'fontSize' ] as $number_att ) {
		if ( isset( $atts[ $number_att ] ) ) {
			$atts[ $number_att ] = intval( $atts[ $number_att ] );
		}
	}

	foreach ( [ 'indentWithTabs', 'lineWrapping', 'foldGutter', 'lineNumbers', 'autoCloseBrackets', 'highlightSelectionMatches', 'styleActiveLine', 'matchBrackets', 'lint' ] as $boolean_att ) {
		$atts[ $boolean_att ] = ! empty( $atts[ $boolean_att ] );
	}

	return $atts;
}

/**
 * Make the editor configuration available to a script.
 *
 * @param string               $handle   Handle of the script that creates code editors.
 * @param array<string, mixed> $settings Editor options, as returned by get_code_editor_settings().
 *
 * @return void
 */
function add_code_editor_config( string $handle, array $settings ) {
	unset( $settings['fontSize'] );

	$config = [
		'enabled'  => is_code_editor_enabled(),
		'settings' => $settings,
	];

	wp_add_inline_script( $handle, 'var CODE_SNIPPETS_EDITOR = ' . wp_json_encode( $config ) . ';', 'before' );
}

/**
 * Load the code editor.
 *
 * @param array<string, mixed> $extra_atts Pass a list of attributes to override the saved ones.
 *
 * @return void
 */
function enqueue_code_editor( array $extra_atts = [] ) {
	$settings = get_code_editor_settings( $extra_atts );

	wp_register_style( CODE_EDITOR_HANDLE, false, [], PLUGIN_VERSION );
	wp_enqueue_style( CODE_EDITOR_HANDLE );

	if ( isset( $settings['fontSize'] ) ) {
		wp_add_inline_style( CODE_EDITOR_HANDLE, ".cm-editor { font-size: {$settings['fontSize']}px; }" );
	}

	wp_enqueue_script(
		CODE_EDITOR_HANDLE,
		plugins_url( 'dist/editor.js', PLUGIN_FILE ),
		[ 'wp-hooks' ],
		PLUGIN_VERSION,
		[ 'in_footer' => true ]
	);

	add_code_editor_config( CODE_EDITOR_HANDLE, $settings );
	enqueue_code_editor_theme();
}

/**
 * Load what a script needs to show read-only code previews.
 *
 * @param string $handle Handle of the script that shows the previews.
 *
 * @return void
 */
function enqueue_code_preview_editor( string $handle ): void {
	add_code_editor_config( $handle, get_code_editor_settings() );
	enqueue_code_editor_theme();
}

/**
 * Load the configured editor theme.
 *
 * @return void
 */
function enqueue_code_editor_theme(): void {
	$theme = get_setting( 'editor', 'theme' );

	if ( 'default' !== $theme ) {
		wp_enqueue_style(
			'code-snippets-editor-theme-' . $theme,
			plugins_url( "dist/editor-themes/$theme.css", PLUGIN_FILE ),
			[],
			PLUGIN_VERSION
		);
	}
}

/**
 * Retrieve a list of the available editor themes.
 *
 * @return array<string> The available themes.
 */
function get_editor_themes(): array {
	static $themes = null;

	if ( ! is_null( $themes ) ) {
		return $themes;
	}

	$themes = array();
	$themes_dir = plugin_dir_path( PLUGIN_FILE ) . 'dist/editor-themes/';

	$theme_files = glob( $themes_dir . '*.css' );

	foreach ( $theme_files as $theme ) {
		$theme = str_replace( $themes_dir, '', $theme );
		$theme = str_replace( '.css', '', $theme );
		$themes[] = $theme;
	}

	return $themes;
}
