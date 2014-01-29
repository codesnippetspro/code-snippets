<?php

/**
 * This file handles the code snippets settings admin menu
 * @package Code_Snippets
 */

/**
 * Register the setting sub-menu
 *
 * @since 2.0
 * @access private
 *
 * @uses add_submenu_page() To register a sub-menu
 */
function code_snippets_add_settings_menu() {

	add_submenu_page(
		code_snippets_get_menu_slug(),
		__( 'Snippets Settings', 'code-snippets' ),
		__( 'Settings', 'code-snippets' ),
		get_snippets_cap(),
		code_snippets_get_menu_slug( 'settings' ),
		'code_snippets_render_settings_menu'
	);
}

add_action( 'admin_menu', 'code_snippets_add_settings_menu' );
add_action( 'network_admin_menu', 'code_snippets_add_settings_menu' );

/**
 * Displays the settings menu
 *
 * @since 2.0
 */
function code_snippets_render_settings_menu() {
	require plugin_dir_path( __FILE__ ) . 'views/settings.php';
}

function code_snippets_settings_init() {
	register_setting( 'code-snippets', 'code_snippets_settings', 'code_snippets_settings_validate' );

	add_settings_section(
		'code-snippets-editor',
		__( 'Code Editor', 'code-snippets' ),
		'__return_empty_string',
		'code-snippets'
	);

	add_settings_field(
		'code_snippets_codemirror_theme',
		__( 'Theme', 'code-snippets' ),
		'code_snippets_codemirror_theme_select_field',
		'code-snippets',
		'code-snippets-editor'
	);
}

add_action( 'admin_init', 'code_snippets_settings_init' );

function code_snippets_codemirror_theme_select_field() {
	$options = get_option( 'code_snippets_settings' );

	echo '<select id="code_snippets_setting_codemirror_theme" name="code_snippets_settings[codemirror_theme]" />';

	$themes = array(
		'default',
		'3024-day',
		'3024-night',
		'ambiance-mobile',
		'ambiance',
		'base16-dark',
		'base16-light',
		'blackboard',
		'cobalt',
		'eclipse',
		'elegant',
		'erlang-dark',
		'lesser-dark',
		'mbo',
		'midnight',
		'monokai',
		'neat',
		'night',
		'paraiso-dark',
		'paraiso-light',
		'rubyblue',
		'solarized',
		'the-matrix',
		'tomorrow-night-eighties',
		'twilight',
		'vibrant-ink',
		'xq-dark',
		'xq-light',
	);

	foreach ( $themes as $theme ) {
		printf ( '<option value="%1$s"%2$s>%1$s</option>', $theme, selected( $theme, $options['codemirror_theme'] ) );
	}

	echo '</select>';
}

function code_snippets_settings_validate( $input ) {
	$output['codemirror_theme'] = $input['codemirror_theme'];

	/* Add an updated message */
	add_settings_error(
		'code-snippets-settings-notices',
		'settings-saved',
		__( 'Settings saved.', 'code-snippets' ),
		'updated'
	);

	return $output;
}
