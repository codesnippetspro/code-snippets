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

	$settings['general'] = array(
		'activate_by_default' => false,
	);

	$settings['editor'] = array(
		'theme' => 'default',
		'tab_size' => 4,
		'wrap_lines' => true,
		'indent_unit' => 2,
		'line_numbers' => true,
		'indent_with_tabs' => true,
		'auto_close_brackets' => true,
		'highlight_selection_matches' => true,
	);

	return $settings;
}

/**
 * Retrieve the saved setting values
 * @return array
 */
function code_snippets_get_settings() {
	$saved = get_option( 'code_snippets_settings', array() );
	$default = code_snippets_get_default_settings();
	return wp_parse_args( $saved, $default );
}

/**
 * Retrieve a value from a saved setting
 * @return array
 */
function code_snippets_get_setting_value( $setting, $group = 'general' ) {
	$settings = code_snippets_get_settings();
	return $settings[ $group ][ $setting ];
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

	/* General settings section */

	add_settings_section(
		'code-snippets-general',
		__( 'General', 'code-snippets' ),
		'__return_empty_string',
		'code-snippets'
	);

	add_settings_field(
		'code_snippets_activate_by_default',
		__( 'Activate by Default', 'code-snippets' ),
		'code_snippets_checkbox_setting_field',
		'code-snippets',
		'code-snippets-general',
		array( 'activate_by_default', 'general',
		'Make the (de)activate button the default action when saving a snippet' )
	);

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
		'code_snippets_checkbox_setting_field',
		'code-snippets',
		'code-snippets-editor',
		array( 'indent_with_tabs', 'editor' )
	);

	add_settings_field(
		'code_snippets_tab_size',
		__( 'Tab Size', 'code-snippets' ),
		'code_snippets_number_setting_field',
		'code-snippets',
		'code-snippets-editor',
		array( 'tab_size', 'editor' )
	);

	add_settings_field(
		'code_snippets_indent_unit',
		__( 'Indent Unit', 'code-snippets' ),
		'code_snippets_number_setting_field',
		'code-snippets',
		'code-snippets-editor',
		array( 'indent_unit', 'editor' )
	);

	add_settings_field(
		'code_snippets_editor_wrap_lines',
		__( 'Wrap Lines', 'code-snippets' ),
		'code_snippets_checkbox_setting_field',
		'code-snippets',
		'code-snippets-editor',
		array( 'wrap_lines', 'editor' )
	);

	add_settings_field(
		'code_snippets_editor_line_numbers',
		__( 'Line Numbers', 'code-snippets' ),
		'code_snippets_checkbox_setting_field',
		'code-snippets',
		'code-snippets-editor',
		array( 'line_numbers', 'editor' )
	);

	add_settings_field(
		'code_snippets_editor_auto_close_brackets',
		__( 'Auto Close Brackets', 'code-snippets' ),
		'code_snippets_checkbox_setting_field',
		'code-snippets',
		'code-snippets-editor',
		array( 'auto_close_brackets', 'editor' )
	);

	add_settings_field(
		'code-snippets_editor_highlight_selection_matches',
		__( 'Highlight Selection Matches', 'code-snippets' ),
		'code_snippets_checkbox_setting_field',
		'code-snippets',
		'code-snippets-editor',
		array( 'highlight_selection_matches', 'editor' )
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

	/* Validate settings */

	/* Select boxes */
	$output['editor']['theme'] = $input['editor']['theme'];

	/* Number select boxes */
	$output['editor']['tab_size'] = absint( $input['editor']['tab_size'] );
	$output['editor']['indent_unit'] = absint( $input['editor']['indent_unit'] );

	/* Check boxes */
	$output['editor']['wrap_lines'] = ( 'on' === $input['editor']['wrap_lines'] );
	$output['editor']['line_numbers'] = ( 'on' === $input['editor']['line_numbers'] );
	$output['editor']['indent_with_tabs'] = ( 'on' === $input['editor']['indent_with_tabs'] );
	$output['editor']['auto_close_brackets'] = ( 'on' === $input['editor']['auto_close_brackets'] );
	$output['editor']['highlight_selection_matches'] = ( 'on' === $input['editor']['highlight_selection_matches'] );
	$output['general']['activate_by_default'] = ( 'on' === $input['general']['activate_by_default'] );

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
function code_snippets_checkbox_setting_field( $atts ) {
	$setting = $atts[0];
	$group = $atts[1];
	$label = isset( $atts[2] ) ? $atts[2] : '';

	$saved_value = code_snippets_get_setting_value( $setting, $group );
	$field_name = sprintf ( 'code_snippets_settings[%s][%s]', $group, $setting );

	if ( $label ) {
		echo '<label for="' . $field_name . '">';
	}

	printf (
		'<input type="checkbox" name="%s"%s>',
		$field_name,
		checked( $saved_value, true, false )
	);

	if ( $label ) {
		echo "\n" . $label . '</label>';
	}
}

/**
 * Render a number select field for an editor setting
 * @param string $setting The setting ID
 */
function code_snippets_number_setting_field( $atts ) {
	$setting = $atts[0];
	$group = $atts[1];

	$saved_value = code_snippets_get_setting_value( $setting, $group );

	printf (
		'<input type="number" name="code_snippets_settings[%1$s][%2$s]" value="%3$s">',
		$group, $setting, $saved_value
	);
}
