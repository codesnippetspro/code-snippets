<?php
/**
 * This file registers the settings
 *
 * @package    Code_Snippets
 * @subpackage Settings
 */

namespace Code_Snippets\Settings;

const NS = __NAMESPACE__ . '\\';

const CACHE_KEY = 'code_snippets_settings';

/**
 * Add a new option for either the current site or the current network
 *
 * @param bool   $network Whether to add a network-wide option.
 * @param string $option  Name of option to add. Expected to not be SQL-escaped.
 * @param mixed  $value   Option value, can be anything. Expected to not be SQL-escaped.
 *
 * @return bool False if the option was not added. True if the option was added.
 */
function add_self_option( $network, $option, $value ) {
	return $network ? add_site_option( $option, $value ) : add_option( $option, $value );
}

/**
 * Retrieves an option value based on an option name from either the current site or the current network
 *
 * @param bool   $network Whether to get a network-wide option.
 * @param string $option  Name of option to retrieve. Expected to not be SQL-escaped.
 * @param mixed  $default Optional value to return if option doesn't exist. Default false.
 *
 * @return mixed Value set for the option.
 */
function get_self_option( $network, $option, $default = false ) {
	return $network ? get_site_option( $option, $default ) : get_option( $option, $default );
}

/**
 * Update the value of an option that was already added on the current site or the current network
 *
 * @param bool   $network Whether to update a network-wide option.
 * @param string $option  Name of option. Expected to not be SQL-escaped.
 * @param mixed  $value   Option value. Expected to not be SQL-escaped.
 *
 * @return bool False if value was not updated. True if value was updated.
 */
function update_self_option( $network, $option, $value ) {
	return $network ? update_site_option( $option, $value ) : update_option( $option, $value );
}

/**
 * Returns 'true' if plugin settings are unified on a multisite installation
 * under the Network Admin settings menu
 *
 * This option is controlled by the "Enable administration menus" setting on the Network Settings menu
 *
 * @return bool
 */
function are_settings_unified() {
	if ( ! is_multisite() ) {
		return false;
	}

	$menu_perms = get_site_option( 'menu_items', array() );

	return empty( $menu_perms['snippets_settings'] );
}

/**
 * Retrieve the setting values from the database.
 * If a setting does not exist in the database, the default value will be returned.
 *
 * @return array
 */
function get_settings_values() {

	// Check if the settings have been cached.
	$settings = wp_cache_get( CACHE_KEY );
	if ( $settings ) {
		return $settings;
	}

	/* Begin with the default settings */
	$settings = get_default_settings();

	/* Retrieve saved settings from the database */
	$saved = get_self_option( are_settings_unified(), 'code_snippets_settings', array() );

	/* Replace the default field values with the ones saved in the database */
	foreach ( $settings as $section => $fields ) {
		if ( isset( $saved[ $section ] ) ) {
			$settings[ $section ] = array_replace( $fields, $saved[ $section ] );
		}
	}

	wp_cache_set( CACHE_KEY, $settings );

	return $settings;
}

/**
 * Retrieve an individual setting field value
 *
 * @param string $section ID of the section the setting belongs to.
 * @param string $field   ID of the setting field.
 *
 * @return mixed
 */
function get_setting( $section, $field ) {
	$settings = get_settings_values();

	return $settings[ $section ][ $field ];
}

/**
 * Update a single setting to a new value.
 *
 * @param string $section   ID of the section the setting belongs to.
 * @param string $field     ID of the setting field.
 * @param mixed  $new_value Setting value. Expected to not be SQL-escaped.
 *
 * @return bool False if value was not updated. True if value was updated.
 */
function update_setting( $section, $field, $new_value ) {
	$settings = get_settings_values();

	$settings[ $section ][ $field ] = $new_value;

	wp_cache_set( CACHE_KEY, $settings );
	return update_self_option( are_settings_unified(), 'code_snippets_settings', $settings );
}

/**
 * Retrieve the settings sections
 *
 * @return array
 */
function get_settings_sections() {
	$sections = array(
		'general'            => __( 'General', 'code-snippets' ),
		'description_editor' => __( 'Description Editor', 'code-snippets' ),
		'editor'             => __( 'Code Editor', 'code-snippets' ),
	);

	return apply_filters( 'code_snippets_settings_sections', $sections );
}

/**
 * Register settings sections, fields, etc
 */
function register_plugin_settings() {

	if ( are_settings_unified() ) {

		if ( ! get_site_option( 'code_snippets_settings', false ) ) {
			add_site_option( 'code_snippets_settings', get_default_settings() );
		}
	} else {

		if ( ! get_option( 'code_snippets_settings', false ) ) {
			add_option( 'code_snippets_settings', get_default_settings() );
		}
	}

	/* Register the setting */
	register_setting(
		'code-snippets',
		'code_snippets_settings',
		array( 'sanitize_callback' => NS . 'sanitize_settings' )
	);

	/* Register settings sections */
	$sections = get_settings_sections();

	if ( ! get_setting( 'general', 'enable_description' ) ) {
		unset( $sections['description_editor'] );
	}

	foreach ( $sections as $section_id => $section_name ) {
		add_settings_section( $section_id, $section_name, null, 'code-snippets' );
	}

	/* Register settings fields */
	foreach ( get_settings_fields() as $section_id => $fields ) {
		foreach ( $fields as $field_id => $field ) {
			$field_object = new Setting_Field( $section_id, $field_id, $field );
			add_settings_field( $field_id, $field['name'], [ $field_object, 'render' ], 'code-snippets', $section_id );
		}
	}

	/* Add editor preview as a field */
	add_settings_field(
		'editor_preview',
		__( 'Editor Preview', 'code-snippets' ),
		NS . 'render_editor_preview',
		'code-snippets',
		'editor'
	);
}

add_action( 'admin_init', NS . 'register_plugin_settings' );

/**
 * Sanitize a single setting value.
 *
 * @param array $field       Setting field information.
 * @param mixed $input_value User input setting value, or null if missing.
 *
 * @return mixed Sanitized setting value, or null if unset.
 */
function sanitize_setting_value( $field, $input_value ) {
	switch ( $field['type'] ) {

		case 'checkbox':
			return 'on' === $input_value;

		case 'number':
			return absint( $input_value );

		case 'select':
			// phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse
			return in_array( $input_value, array_keys( $field['options'] ), false ) ? $input_value : null;

		case 'checkboxes':
			$results = [];

			if ( ! empty( $input_value ) ) {
				foreach ( $field['options'] as $option_id => $option_label ) {
					if ( isset( $input_value[ $option_id ] ) && 'on' === $input_value[ $option_id ] ) {
						$results[] = $option_id;
					}
				}
			}

			return $results;

		case 'text':
			return trim( sanitize_text_field( $input_value ) );

		default:
			return null;
	}
}

/**
 * Validate the settings
 *
 * @param array $input The received settings.
 *
 * @return array The validated settings.
 */
function sanitize_settings( array $input ) {
	$settings = get_settings_values();
	$updated = false;

	// Don't directly loop through $input as it does not include as deselected checkboxes.
	foreach ( get_settings_fields() as $section_id => $fields ) {
		foreach ( $fields as $field_id => $field ) {

			// Fetch the corresponding input value from the posted data.
			$input_value = isset( $input[ $section_id ][ $field_id ] ) ? $input[ $section_id ][ $field_id ] : null;

			// Attempt to sanitize the setting value.
			$sanitized_value = sanitize_setting_value( $field, $input_value );

			if ( ! is_null( $sanitized_value ) && $settings[ $section_id ][ $field_id ] !== $sanitized_value ) {
				$settings[ $section_id ][ $field_id ] = $sanitized_value;
				$updated = true;
			}
		}
	}

	wp_cache_delete( CACHE_KEY );

	// Add an updated message.
	if ( $updated ) {
		add_settings_error(
			'code-snippets-settings-notices',
			'settings-saved',
			__( 'Settings saved.', 'code-snippets' ),
			'updated'
		);
	}

	return $settings;
}
