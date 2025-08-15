<?php
/**
 * This file registers the settings.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Settings;

use Code_Snippets\Client\Welcome_API;
use function add_action;
use function Code_Snippets\clean_snippets_cache;
use function Code_Snippets\code_snippets;
use function Code_Snippets\Utils\add_self_option;
use function Code_Snippets\Utils\get_self_option;

const CACHE_KEY = 'code_snippets_settings';
const OPTION_GROUP = 'code-snippets';
const OPTION_NAME = 'code_snippets_settings';

/**
 * Returns 'true' if plugin settings are unified on a multisite installation
 * under the Network Admin settings menu
 *
 * This option is controlled by the "Enable administration menus" setting on the Network Settings menu
 *
 * @return bool
 */
function are_settings_unified(): bool {
	if ( ! is_multisite() ) {
		return false;
	}

	$menu_perms = get_site_option( 'menu_items', [] );
	return empty( $menu_perms['snippets_settings'] );
}

/**
 * Retrieve the setting values from the database.
 *
 * If a setting does not exist in the database, the default value will be returned.
 *
 * @return array<string, array<string, mixed>>
 */
function get_settings_values(): array {
	$settings = wp_cache_get( CACHE_KEY );
	if ( $settings ) {
		return $settings;
	}

	$settings = Settings_Fields::get_default_values();
	$saved = get_self_option( are_settings_unified(), OPTION_NAME, [] );

	// Deep merge the saved settings with the default values.
	foreach ( $settings as $section => $section_fields ) {
		if ( isset( $saved[ $section ] ) ) {
			$settings[ $section ] = array_replace( $section_fields, $saved[ $section ] );
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
function get_setting( string $section, string $field ) {
	$settings = get_settings_values();

	return $settings[ $section ][ $field ] ?? null;
}

/**
 * Retrieve the settings sections
 *
 * @return array<string, string> Settings sections.
 */
function get_settings_sections(): array {
	$sections = array(
		'general' => __( 'General', 'code-snippets' ),
		'editor'  => __( 'Code Editor', 'code-snippets' ),
		'debug'   => __( 'Debug', 'code-snippets' ),
	);

	return apply_filters( 'code_snippets_settings_sections', $sections );
}

/**
 * Register settings sections, fields, etc
 */
function register_plugin_settings() {
	if ( ! get_self_option( are_settings_unified(), OPTION_NAME ) ) {
		add_self_option( are_settings_unified(), OPTION_NAME, Settings_Fields::get_default_values() );
	}

	// Register the setting.
	register_setting(
		OPTION_GROUP,
		OPTION_NAME,
		[ 'sanitize_callback' => __NAMESPACE__ . '\\sanitize_settings' ]
	);

	// Register settings sections.
	foreach ( get_settings_sections() as $section_id => $section_name ) {
		add_settings_section( $section_id, $section_name, '__return_empty_string', 'code-snippets' );
	}

	// Register settings fields.
	foreach ( Settings_Fields::get_field_definitions() as $section_id => $fields ) {
		foreach ( $fields as $field_id => $field ) {
			$field_object = new Setting_Field( $section_id, $field_id, $field );
			add_settings_field( $field_id, $field['name'], [ $field_object, 'render' ], 'code-snippets', $section_id );
		}
	}

	$editor_preview = new Editor_Preview();

	// Add editor preview as a field.
	add_settings_field(
		'editor_preview',
		__( 'Editor Preview', 'code-snippets' ),
		[ $editor_preview, 'render' ],
		'code-snippets',
		'editor'
	);
}

add_action( 'admin_init', __NAMESPACE__ . '\\register_plugin_settings' );

/**
 * Sanitize a single setting value.
 *
 * @param array<string, mixed> $field       Setting field information.
 * @param mixed                $input_value User input setting value, or null if missing.
 *
 * @return mixed Sanitized setting value, or null if unset.
 */
function sanitize_setting_value( array $field, $input_value ) {
	switch ( $field['type'] ) {

		case 'checkbox':
			return 'on' === $input_value;

		case 'number':
			return intval( $input_value );

		case 'select':
			$select_options = array_map( 'strval', array_keys( $field['options'] ) );
			return in_array( strval( $input_value ), $select_options, true ) ? $input_value : null;

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
		case 'hidden':
			return trim( sanitize_text_field( $input_value ) );

		case 'callback':
			return isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ?
				call_user_func( $field['sanitize_callback'], $input_value ) :
				null;

		default:
			return null;
	}
}

/**
 * Process settings actions.
 *
 * @param array $input Provided settings input.
 *
 * @return array|null New $input value to return, or null to continue with settings update process.
 */
function process_settings_actions( array $input ): ?array {

	if ( isset( $input['reset_settings'] ) ) {
		add_settings_error(
			OPTION_NAME,
			'settings_reset',
			__( 'All settings have been reset to their defaults.', 'code-snippets' ),
			'updated'
		);

		delete_option( 'code_snippets_cloud_settings' );
		return [];
	}

	if ( isset( $input['debug']['database_update'] ) ) {
		code_snippets()->db->create_or_upgrade_tables();

		add_settings_error(
			OPTION_NAME,
			'database_update_done',
			__( 'Successfully performed database table upgrade.', 'code-snippets' ),
			'updated'
		);
	}

	if ( isset( $input['debug']['reset_caches'] ) ) {
		Welcome_API::clear_cache();
		clean_snippets_cache( code_snippets()->db->get_table_name( false ) );

		if ( is_multisite() ) {
			clean_snippets_cache( code_snippets()->db->get_table_name( true ) );
		}

		add_settings_error(
			OPTION_NAME,
			'snippet_caches_reset',
			__( 'Successfully reset snippets caches.', 'code-snippets' ),
			'updated'
		);
	}

	return null;
}

/**
 * Validate the settings
 *
 * @param array<string, array<string, mixed>> $input The received settings.
 *
 * @return array<string, array<string, mixed>> The validated settings.
 */
function sanitize_settings( array $input ): array {
	wp_cache_delete( CACHE_KEY );
	$result = process_settings_actions( $input );

	if ( ! is_null( $result ) ) {
		return $result;
	}

	$settings = get_settings_values();
	$updated = false;

	// Don't directly loop through $input as it does not include as deselected checkboxes.
	foreach ( Settings_Fields::get_field_definitions() as $section_id => $fields ) {
		foreach ( $fields as $field_id => $field ) {

			// Fetch the corresponding input value from the posted data.
			$input_value = $input[ $section_id ][ $field_id ] ?? null;

			// Attempt to sanitize the setting value.
			$sanitized_value = sanitize_setting_value( $field, $input_value );

			if ( ! is_null( $sanitized_value ) && $settings[ $section_id ][ $field_id ] !== $sanitized_value ) {
				$settings[ $section_id ][ $field_id ] = $sanitized_value;
				$updated = true;
			}
		}
	}

	// Add an updated message.
	if ( $updated ) {
		add_settings_error(
			OPTION_NAME,
			'settings-saved',
			__( 'Settings saved.', 'code-snippets' ),
			'updated'
		);
	}

	return $settings;
}
