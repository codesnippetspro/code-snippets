<?php
/**
 * This file registers the settings.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Settings;

use Code_Snippets\Client\Welcome_Client;
use Code_Snippets\Controller\Cloud_Search_Controller;
use function add_action;
use function Code_Snippets\clean_snippets_cache;
use function Code_Snippets\flush_versioned_cache_groups;
use function Code_Snippets\code_snippets;
use function Code_Snippets\Utils\add_self_option;
use function Code_Snippets\Utils\get_self_option;
use function Code_Snippets\Utils\update_self_option;
use const Code_Snippets\CACHE_GROUP;

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
	$settings = wp_cache_get( CACHE_KEY, CACHE_GROUP );
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

	wp_cache_set( CACHE_KEY, $settings, CACHE_GROUP );
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
 * Update a single setting to a new value.
 *
 * @param string $section   ID of the section the setting belongs to.
 * @param string $field     ID of the setting field.
 * @param mixed  $new_value Setting value. Expected to not be SQL-escaped.
 *
 * @return bool False if value was not updated. True if value was updated.
 */
function update_setting( string $section, string $field, $new_value ): bool {
	$settings = get_settings_values();

	$settings[ $section ][ $field ] = $new_value;

	wp_cache_set( CACHE_KEY, $settings, CACHE_GROUP );
	return update_self_option( are_settings_unified(), OPTION_NAME, $settings );
}

/**
 * Render the fields of a settings section, with group headings.
 *
 * Mirrors the core `do_settings_fields()`, adding a full-width heading row
 * above any field carrying a `group_heading` argument. Tabs are long enough
 * that unbroken rows are hard to scan.
 *
 * @param string $page    Settings page slug.
 * @param string $section Settings section identifier.
 *
 * @return void
 */
function do_settings_fields_with_headings( string $page, string $section ): void {
	global $wp_settings_fields;

	if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
		return;
	}

	$seen_headings = [];

	foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {
		$heading = $field['args']['group_heading'] ?? '';

		// A heading is skipped if an identical one has already been drawn, so
		// that a group whose first field is absent does not repeat it.
		if ( $heading && ! in_array( $heading, $seen_headings, true ) ) {
			$seen_headings[] = $heading;
			printf(
				'<tr class="settings-group-heading"><th colspan="2" scope="colgroup">%s</th></tr>',
				esc_html( $heading )
			);
		}

		$class = empty( $field['args']['class'] ) ? '' : ' class="' . esc_attr( $field['args']['class'] ) . '"';

		echo '<tr' . $class . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.

		// A field with no title, such as a notice rendered by a callback, spans
		// the row rather than leaving an empty header cell for a screen reader.
		if ( '' === (string) $field['title'] ) {
			echo '<td colspan="2">';
			call_user_func( $field['callback'], $field['args'] );
			echo '</td></tr>';
			continue;
		}

		if ( ! empty( $field['args']['label_for'] ) ) {
			printf(
				'<th scope="row"><label for="%s">%s</label></th>',
				esc_attr( $field['args']['label_for'] ),
				esc_html( $field['title'] )
			);
		} else {
			printf( '<th scope="row">%s</th>', esc_html( $field['title'] ) );
		}

		echo '<td>';
		call_user_func( $field['callback'], $field['args'] );
		echo '</td></tr>';
	}
}

/**
 * Retrieve the settings sections
 *
 * @return array<string, string> Settings sections.
 */
function get_settings_sections(): array {
	// Tabs are grouped by task rather than by storage section. Values still
	// save into their original sections; see Settings_Layout.
	$sections = Settings_Layout::get_available_tabs();

	return apply_filters( 'code_snippets_settings_sections', $sections );
}

/**
 * Register settings sections, fields, etc
 */
function register_plugin_settings() {
	if ( ! get_self_option( are_settings_unified(), OPTION_NAME ) ) {
		add_self_option( are_settings_unified(), OPTION_NAME, Settings_Fields::get_default_values() );
	}

	$current_settings = get_settings_values();

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

	// Register settings fields. The tab a field appears under comes from the
	// layout, while the section it saves into stays exactly as it was.
	$definitions = Settings_Fields::get_field_definitions();
	$descriptions = Settings_Layout::get_descriptions();
	$headings = Settings_Layout::get_group_headings();

	foreach ( get_settings_sections() as $tab_id => $tab_name ) {
		foreach ( Settings_Layout::get_visible_fields( $tab_id, $current_settings ) as $entry ) {
			list( $section_id, $field_id ) = $entry;
			$field = $definitions[ $section_id ][ $field_id ];

			// Reword the settings whose description names the control without
			// saying what it buys you.
			if ( isset( $descriptions[ $field_id ] ) ) {
				$field['desc'] = $descriptions[ $field_id ];
			}

			$field_object = new Setting_Field( $section_id, $field_id, $field );

			$field_args = isset( $headings[ $tab_id ][ $field_id ] )
				? [ 'group_heading' => $headings[ $tab_id ][ $field_id ] ]
				: [];

			add_settings_field(
				$field_id,
				$field['name'],
				[ $field_object, 'render' ],
				'code-snippets',
				$tab_id,
				$field_args
			);
		}
	}

	$editor_preview = new Editor_Preview();

	// Add editor preview as a field.
	add_settings_field(
		'editor_preview',
		__( 'Editor Preview', 'code-snippets' ),
		[ $editor_preview, 'render' ],
		'code-snippets',
		'editing'
	);

	Version_Switch::init();
}

add_action( 'admin_init', __NAMESPACE__ . '\\register_plugin_settings' );

/**
 * Determine whether a setting field should be rendered.
 *
 * @param array<string, mixed>                    $field    Field definition.
 * @param array<string, array<string,mixed>>      $settings Current settings values.
 * @param array<string, array<string,mixed>>|null $input Optional raw input values.
 *
 * @return bool
 */
function should_render_setting_field( array $field, array $settings, ?array $input = null ): bool {
	if ( empty( $field['show_if'] ) || ! is_array( $field['show_if'] ) ) {
		return true;
	}

	$show_if = array_merge(
		[
			'section' => '',
			'field'   => '',
			'value'   => true,
		],
		$field['show_if']
	);

	$section = is_string( $show_if['section'] ) ? $show_if['section'] : '';
	$field_id = is_string( $show_if['field'] ) ? $show_if['field'] : '';
	$expected = $show_if['value'];

	if ( '' === $section || '' === $field_id ) {
		return true;
	}

	$actual = null;

	if ( is_array( $input ) && isset( $input[ $section ] ) && is_array( $input[ $section ] ) && array_key_exists( $field_id, $input[ $section ] ) ) {
		$actual = $input[ $section ][ $field_id ];
	} elseif ( isset( $settings[ $section ] ) && array_key_exists( $field_id, $settings[ $section ] ) ) {
		$actual = $settings[ $section ][ $field_id ];
	}

	if ( is_bool( $expected ) ) {
		if ( is_bool( $actual ) ) {
			return $actual === $expected;
		}

		return ( 'on' === $actual ) === $expected;
	}

	return $actual === $expected;
}

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
		Welcome_Client::clear_cache();
		Cloud_Search_Controller::clear_caches();
		clean_snippets_cache( code_snippets()->db->get_table_name( false ) );

		if ( is_multisite() ) {
			clean_snippets_cache( code_snippets()->db->get_table_name( true ) );
		}

		// Deleting known keys cannot reach data written by a different version
		// of the plugin, which is what needs clearing before a rollback, so the
		// versioned groups (current, previous and legacy) all go too.
		flush_versioned_cache_groups( (string) get_option( 'code_snippets_cache_version', '' ) );

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
	wp_cache_delete( CACHE_KEY, CACHE_GROUP );
	$result = process_settings_actions( $input );

	if ( ! is_null( $result ) ) {
		return $result;
	}

	$settings = get_settings_values();
	$updated = false;

	// Don't directly loop through $input as it does not include as deselected checkboxes.
	foreach ( Settings_Fields::get_field_definitions() as $section_id => $fields ) {
		foreach ( $fields as $field_id => $field ) {
			if ( ! should_render_setting_field( $field, $settings, $input ) ) {
				continue;
			}

			// Fetch the corresponding input value from the posted data.
			$input_value = $input[ $section_id ][ $field_id ] ?? null;
			$stored_value = $settings[ $section_id ][ $field_id ] ?? null;

			// Attempt to sanitize the setting value.
			$sanitized_value = sanitize_setting_value( $field, $input_value );

			if ( ! is_null( $sanitized_value ) && $stored_value !== $sanitized_value ) {
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

		do_action( 'code_snippets/settings_updated', $settings, $input );
	}

	return $settings;
}
