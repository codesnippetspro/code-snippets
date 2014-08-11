<?php

/**
 * This file handles the code snippets settings admin menu
 * @package Code_Snippets
 */

/**
 * Retrieve the default setting values
 * @return array
 */
function code_snippets_get_default_settings() {
	$settings = array();

	$settings['editor'] = array(
		'indent_with_tabs' => true,
		'theme'            => 'default',
		'wrap_lines'       => true,
		'indent_unit'      => 2,
		'tab_size'         => 4,
		'line_numbers'     => true,
		'auto_close_brackets' => true,
	);

	return $settings;
}

/**
 * Register settings sections, fields, etc
 */
function code_snippets_register_settings() {

	/* Create the option in the database */

	if ( ! get_option( 'code_snippets_settings' ) ) {
		add_option(	'code_snippets_settings',	code_snippets_get_default_settings() );
	}

	/* Register the setting */
	register_setting( 'code-snippets', 'code_snippets_settings', 'code_snippets_settings_validate' );

	/* Editor settings section */

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

	add_settings_field(
		'code_snippets_indent_with_tabs',
		__( 'Indent With Tabs', 'code-snippets' ),
		'code_snippets_editor_checkbox_setting',
		'code-snippets',
		'code-snippets-editor',
		array( 'indent_with_tabs' )
	);

	add_settings_field(
		'code_snippets_tab_size',
		__( 'Tab Size', 'code-snippets' ),
		'code_snippets_editor_number_setting',
		'code-snippets',
		'code-snippets-editor',
		array( 'tab_size' )
	);

	add_settings_field(
		'code_snippets_indent_unit',
		__( 'Indent Unit', 'code-snippets' ),
		'code_snippets_editor_number_setting',
		'code-snippets',
		'code-snippets-editor',
		array( 'indent_unit' )
	);

	add_settings_field(
		'code_snippets_editor_wrap_lines',
		__( 'Wrap Lines', 'code-snippets' ),
		'code_snippets_editor_checkbox_setting',
		'code-snippets',
		'code-snippets-editor',
		array( 'wrap_lines' )
	);

	add_settings_field(
		'code_snippets_editor_line_numbers',
		__( 'Line Numbers', 'code-snippets' ),
		'code_snippets_editor_checkbox_setting',
		'code-snippets',
		'code-snippets-editor',
		array( 'line_numbers' )
	);

	add_settings_field(
		'code_snippets_editor_auto_close_brackets',
		__( 'Auto Close Brackets', 'code-snippets' ),
		'code_snippets_editor_checkbox_setting',
		'code-snippets',
		'code-snippets-editor',
		array( 'auto_close_brackets' )
	);

	add_settings_field(
		'code_snippets_editor_preview',
		__( 'Editor Preview', 'code-snippets' ),
		'code_snippets_settings_editor_preview',
		'code-snippets',
		'code-snippets-editor'
	);
}

add_action( 'admin_init', 'code_snippets_register_settings' );

/**
 * Validate the settings
 * @param array $input
 * @return array
 */
function code_snippets_settings_validate( $input ) {

	/* Validate editor settings */
	$output['editor']['theme'] = $input['editor']['theme'];
	$output['editor']['tab_size'] = absint( $input['editor']['tab_size'] );
	$output['editor']['indent_unit'] = absint( $input['editor']['indent_unit'] );
	$output['editor']['wrap_lines'] = ( 'on' === $input['editor']['wrap_lines'] );
	$output['editor']['line_numbers'] = ( 'on' === $input['editor']['line_numbers'] );
	$output['editor']['indent_with_tabs'] = ( 'on' === $input['editor']['indent_with_tabs'] );
	$output['editor']['auto_close_brackets'] = ( 'on' === $input['editor']['auto_close_brackets'] );

	/* Add an updated message */
	add_settings_error(
		'code-snippets-settings-notices',
		'settings-saved',
		__( 'Settings saved.', 'code-snippets' ),
		'updated'
	);

	return $output;
}

/**
 * Render a checkbox field for an editor setting
 * @param string $setting The setting ID
 */
function code_snippets_editor_checkbox_setting( $atts ) {
	$setting = $atts[0];

	$settings = get_option( 'code_snippets_settings' );
	$saved_value = $settings['editor'][ $setting ];

	$default_settings = code_snippets_get_default_settings();
	$default_value = $default_settings['editor'][ $setting ];

	printf (
		'<input type="checkbox" name="code_snippets_settings[editor][%1$s]"%2$s>',
		$setting,
		checked( $saved_value, $default_value, false )
	);
}

/**
 * Render a number select field for an editor setting
 * @param string $setting The setting ID
 */
function code_snippets_editor_number_setting( $atts ) {
	$setting = $atts[0];
	$settings = get_option( 'code_snippets_settings' );
	$saved_value = $settings['editor'][ $setting ];

	printf (
		'<input type="number" name="code_snippets_settings[editor][%1$s]" value="%2$s">',
		$setting,	$saved_value
	);
}
