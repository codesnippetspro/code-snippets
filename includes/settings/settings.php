<?php

/**
 * This file registers the settings
 * @package Code_Snippets
 */

/**
 * Retrieve the default setting values
 * @return array
 */
function code_snippets_get_default_settings() {
	$fields = code_snippets_get_settings_fields();
	$defaults = array();

	foreach ( $fields as $section_id => $section_fields ) {
		$defaults[ $section_id ] = wp_list_pluck( $section_fields, 'default', 'id' );
	}

	return $defaults;
}

/*
 * Retrieve the setting values from the database.
 * If a setting does not exist in the database, the default value will be returned.
 * @return array
 */
function code_snippets_get_settings() {
	$saved = get_option( 'code_snippets_settings', array() );
	$default = code_snippets_get_default_settings();
	return wp_parse_args( $saved, $default );
}

/**
 * Retrieve an individual setting field value
 * @param string $section The ID of the section the setting belongs to
 * @param string $field The ID of the setting field
 * @return array
 */
function code_snippets_get_setting( $section, $field ) {
	$settings = code_snippets_get_settings();
	return $settings[ $section ][ $field ];
}

/**
 * Retrieve the settings sections
 * @return array
 */
function code_snippets_get_settings_sections() {
	$sections = array(
		'general' => __( 'General', 'code-snippets' ),
		'editor' => __( 'Editor', 'code-snippets' ),
	);

	return apply_filters( 'code_snippets_settings_sections', $sections );
}

/**
 * Retrieve the settings fields
 * @return array
 */
function code_snippets_get_settings_fields() {
	$settings = array();

	$settings['general'] = array(
		array(
			'id' => 'activate_by_default',
			'name' =>__( 'Activate by Default', 'code-snippets' ),
			'type' => 'checkbox',
			'label' => __( "Make the 'Save and Activate' button the default action when saving a snippet.", 'code-snippets' ),
			'default' => false,
		),
	);

	/* Editor settings section */

	$settings['editor'] = array(
		array(
			'id' => 'theme',
			'name' => __( 'Theme', 'code-snippets' ),
			'type' => 'codemirror_theme_select',
			'default' => 'default',
		),

		array(
			'id' => 'indent_with_tabs',
			'name' => __( 'Indent With Tabs', 'code-snippets' ),
			'type' => 'checkbox',
			'label' => __( 'Use hard tabs (not spaces) for indentation.', 'code-snippets' ),
			'default' => true,
		),

		array(
			'id' => 'tab_size',
			'name' => __( 'Tab Size', 'code-snippets' ),
			'type' => 'number',
			'label' => __( 'The width of a tab character.', 'code-snippets' ),
			'default' => 4,
		),

		array(
			'id' => 'indent_unit',
			'name' => __( 'Indent Unit', 'code-snippets' ),
			'type' => 'number',
			'label' => __( 'How many spaces a block should be indented.', 'code-snippets' ),
			'default' => 2
		),

		array(
			'id' => 'wrap_lines',
			'name' => __( 'Wrap Lines', 'code-snippets' ),
			'type' => 'checkbox',
			'label' => __( 'Whether the editor should scroll or wrap for long lines.', 'code-snippets' ),
			'default' => true,
		),

		array(
			'id' => 'line_numbers',
			'name' => __( 'Line Numbers', 'code-snippets' ),
			'type' => 'checkbox',
			'label' => __( 'Show line numbers to the left of the editor.', 'code-snippets' ),
			'default' => true,
		),

		array(
			'id' => 'auto_close_brackets',
			'name' => __( 'Auto Close Brackets', 'code-snippets' ),
			'type' => 'checkbox',
			'label' => __( 'Auto-close brackets and quotes when typed.', 'code-snippets' ),
			'default' => true,
		),

		array(
			'id' => 'highlight_selection_matches',
			'name' => __( 'Highlight Selection Matches', 'code-snippets' ),
			'label' => __( 'Highlight all instances of a currently selected word.', 'code-snippets' ),
			'type' => 'checkbox',
			'default' => true,
		),
	);

	return apply_filters( 'code_snippets_settings_fields', $settings );
}

/**
 * Register settings sections, fields, etc
 */
function code_snippets_register_settings() {

	/* Register the setting */
	register_setting( 'code-snippets', 'code_snippets_settings', 'code_snippets_settings_validate' );

	/* Register settings sections */
	foreach ( code_snippets_get_settings_sections() as $section_id => $section_name ) {
		add_settings_section(
			'code-snippets-' . $section_id,
			$section_name,
			'__return_empty_string',
			'code-snippets'
		);
	}

	/* Register settings fields */
	foreach ( code_snippets_get_settings_fields() as $section_id => $fields ) {

		foreach ( $fields as $field ) {
			add_settings_field(
				'code_snippets_' . $field['id'],
				$field['name'],
				"code_snippets_{$field['type']}_field",
				'code-snippets',
				'code-snippets-' . $section_id,
				array_merge( $field, array( 'section' => $section_id ) )
			);
		}

	}

	/* Add editor preview as a field */
	add_settings_field(
		'code_snippets_' . $field['id'],
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
function code_snippets_settings_validate( array $input ) {
	$settings = code_snippets_get_settings();
	$settings_fields = code_snippets_get_settings_fields();

	// Don't directly loop through $input as it does not include as deselected checkboxes
	foreach ( $settings_fields as $section_id => $fields ) {

		// Loop through fields
		foreach ( $fields as $field ) {
			$field_id = $field['id'];

			// Checkbox field
			if ( 'checkbox' === $field['type'] ) {

				$settings[ $section_id ][ $field_id ] = (
					isset( $input[ $section_id ][ $field_id ] ) &&
					'on' === $input[ $section_id ][ $field_id ]
				);

			// Number field
			} elseif ( 'number' == $field['type'] ) {
				$settings[ $section_id ][ $field_id ] = absint( $input[ $section_id ][ $field_id ] );

			// Other fields
			} else {
				$settings[ $section_id ][ $field_id ] = $input[ $section_id ][ $field_id ];
			}
		}
	}

	/* Add an updated message */
	add_settings_error(
		'code-snippets-settings-notices',
		'settings-saved',
		__( 'Settings saved.', 'code-snippets' ),
		'updated'
	);

	return $settings;
}
