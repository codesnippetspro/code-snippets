<?php

/**
 * This file registers the settings
 * @package Code_Snippets
 */

require plugin_dir_path( __FILE__ ) . '/class-settings.php';

/**
 * Retrieve the default setting values
 * @return array
 */
function code_snippets_get_default_settings() {
	return Code_Snippets_Settings::get_defaults();
}

/**
 * Retrieve the settings fields
 * @return array
 */
function code_snippets_get_settings_fields() {
	return Code_Snippets_Settings::get_fields();
}


/*
 * Retrieve the setting values from the database.
 * If a setting does not exist in the database, the default value will be returned.
 * @return array
 */
function code_snippets_get_settings() {

	/* Check if the settings have been cached */
	if ( $settings = wp_cache_get( 'code_snippets_settings' ) ) {
		return $settings;
	}

	/* Begin with the default settings */
	$settings = Code_Snippets_Settings::get_defaults();

	/* Retrieve saved settings from the database */
	$saved = get_option( 'code_snippets_settings', array() );

	/* Replace the default field values with the ones saved in the database */
	if ( function_exists( 'array_replace_recursive' ) ) {

		/* Use the much more efficient array_replace_recursive() function in PHP 5.3 and later */
		$settings = array_replace_recursive( $settings, $saved );
	} else {

		/* Otherwise, do it manually */
		foreach ( $settings as $section => $fields ) {
			foreach ( $fields as $field => $value ) {

				if ( isset( $saved[ $section ][ $field ] ) ) {
					$settings[ $section ][ $field ] = $saved[ $section ][ $field ];
				}
			}
		}
	}

	wp_cache_set( 'code_snippets_settings', $settings );
	return $settings;
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
		'description_editor' => __( 'Description Editor', 'code-snippets' ),
		'editor' => __( 'Code Editor', 'code-snippets' ),
	);

	return apply_filters( 'code_snippets_settings_sections', $sections );
}

/**
 * Register settings sections, fields, etc
 */
function code_snippets_register_settings() {

	if ( ! get_option( 'code_snippets_settings', false ) ) {
		add_option( 'code_snippets_settings', code_snippets_get_default_settings() );
	}

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
	foreach ( Code_Snippets_Settings::get_fields() as $section_id => $fields ) {
		foreach ( $fields as $field ) {
			$atts = $field;
			$atts['section'] = $section_id;

			add_settings_field(
				'code_snippets_' . $field['id'],
				$field['name'],
				"code_snippets_{$field['type']}_field",
				'code-snippets',
				'code-snippets-' . $section_id,
				$atts
			);
		}
	}

	/* Add editor preview as a field */
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
function code_snippets_settings_validate( array $input ) {
	$settings = code_snippets_get_settings();
	$settings_fields = code_snippets_get_settings_fields();

	// Don't directly loop through $input as it does not include as deselected checkboxes
	foreach ( $settings_fields as $section_id => $fields ) {

		// Loop through fields
		foreach ( $fields as $field ) {
			$field_id = $field['id'];

			switch ( $field['type'] ) {

				case 'checkbox':
					$settings[ $section_id ][ $field_id ] =
						isset( $input[ $section_id ][ $field_id ] ) && 'on' === $input[ $section_id ][ $field_id ];
					break;

				case 'number':
					$settings[ $section_id ][ $field_id ] = absint( $input[ $section_id ][ $field_id ] );
					break;

				default:
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
