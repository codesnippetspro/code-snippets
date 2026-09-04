<?php

namespace Code_Snippets\Settings;

/**
 * Groups settings fields into tabs for display.
 *
 * Storage is deliberately left alone: every value stays in the section it has
 * always lived in, so `get_setting( 'general', … )` keeps working and nobody's
 * saved settings are orphaned. This class only decides which tab a field is
 * drawn under, and in what order.
 *
 * The tabs are grouped by what someone came to the page to do, rather than by
 * which release the setting arrived in. General had grown large enough that
 * whole features were sitting in the same flat list as small preferences.
 *
 * The map below names every field across both editions. Anything not present
 * in the running install is skipped, and a tab left with nothing in it does not
 * appear at all, so the free plugin shows fewer tabs than Pro without needing a
 * separate map.
 */
class Settings_Layout {

	/**
	 * Retrieve every tab, in display order, before availability is considered.
	 *
	 * @return array<string, string> Tab identifier keyed to its label.
	 */
	public static function get_tabs(): array {
		$tabs = [
			'editing'   => __( 'Editing', 'code-snippets' ),
			'running'   => __( 'Running', 'code-snippets' ),
			'insights'  => __( 'Insights', 'code-snippets' ),
			'library'   => __( 'Library', 'code-snippets' ),
			'interface' => __( 'Interface', 'code-snippets' ),
			'advanced'  => __( 'Advanced', 'code-snippets' ),
		];

		return apply_filters( 'code_snippets_settings_tabs', $tabs );
	}

	/**
	 * Retrieve the contents of every tab.
	 *
	 * Each entry is a `[ storage section, field identifier ]` pair. The storage
	 * section is the one the value is saved under and must not change; only the
	 * tab it appears in does.
	 *
	 * @return array<string, array<int, array{0: string, 1: string}>>
	 */
	public static function get_tab_contents(): array {
		$contents = [
			'editing'   => [
				[ 'general', 'enable_tags' ],
				[ 'general', 'enable_description' ],
				[ 'general', 'visual_editor_rows' ],
				[ 'editor', 'theme' ],
				[ 'editor', 'keymap' ],
				[ 'editor', 'font_size' ],
				[ 'editor', 'indent_with_tabs' ],
				[ 'editor', 'tab_size' ],
				[ 'editor', 'indent_unit' ],
				[ 'editor', 'line_numbers' ],
				[ 'editor', 'code_folding' ],
				[ 'editor', 'wrap_lines' ],
				[ 'editor', 'auto_close_brackets' ],
				[ 'editor', 'highlight_active_line' ],
				[ 'editor', 'highlight_selection_matches' ],
			],
			'running'   => [
				[ 'general', 'enable_flat_files' ],
				[ 'general', 'minify_output' ],
				[ 'general', 'activate_by_default' ],
			],
			'insights'  => [
				[ 'general', 'enable_performance_tracker' ],
				[ 'general', 'enable_security_scan' ],
				[ 'general', 'rescan_all_snippets' ],
			],
			'library'   => [
				[ 'general', 'max_revisions' ],
				[ 'general', 'preserve_on_delete' ],
				[ 'general', 'delete_revision_default' ],
				[ 'general', 'connect_cloud' ],
			],
			'interface' => [
				[ 'general', 'list_order' ],
				[ 'general', 'disable_prism' ],
				[ 'general', 'enable_admin_bar' ],
				[ 'general', 'admin_bar_snippet_limit' ],
				[ 'general', 'hide_upgrade_menu' ],
			],
			'advanced'  => [
				[ 'permissions', 'role_capabilities' ],
				[ 'version-switch', 'version_switcher' ],
				[ 'version-switch', 'refresh_versions' ],
				[ 'version-switch', 'version_warning' ],
				[ 'debug', 'reset_caches' ],
				[ 'debug', 'database_update' ],
				[ 'general', 'complete_uninstall' ],
			],
		];

		return apply_filters( 'code_snippets_settings_tab_contents', $contents );
	}

	/**
	 * Retrieve the fields of a tab that exist and should be shown.
	 *
	 * @param string                                   $tab_id   Tab identifier.
	 * @param array<string, array<string, mixed>>|null $settings Current setting values.
	 *
	 * @return array<int, array{0: string, 1: string}> Storage section and field pairs.
	 */
	public static function get_visible_fields( string $tab_id, ?array $settings = null ): array {
		$contents = self::get_tab_contents();

		if ( empty( $contents[ $tab_id ] ) ) {
			return [];
		}

		$definitions = Settings_Fields::get_field_definitions();
		$settings = null === $settings ? get_settings_values() : $settings;
		$visible = [];

		foreach ( $contents[ $tab_id ] as $entry ) {
			list( $section_id, $field_id ) = $entry;

			// Fields belonging to the other edition simply are not there.
			if ( ! isset( $definitions[ $section_id ][ $field_id ] ) ) {
				continue;
			}

			if ( ! should_render_setting_field( $definitions[ $section_id ][ $field_id ], $settings ) ) {
				continue;
			}

			$visible[] = $entry;
		}

		return $visible;
	}

	/**
	 * Retrieve the tabs that have something to show.
	 *
	 * @return array<string, string> Tab identifier keyed to its label.
	 */
	public static function get_available_tabs(): array {
		$settings = get_settings_values();
		$available = [];

		foreach ( self::get_tabs() as $tab_id => $label ) {
			if ( self::get_visible_fields( $tab_id, $settings ) ) {
				$available[ $tab_id ] = $label;
			}
		}

		return $available;
	}

	/**
	 * Retrieve the headings that break a tab into labelled groups.
	 *
	 * Keyed by tab, then by the field the heading is drawn above. A heading
	 * whose field is absent is skipped along with it.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_group_headings(): array {
		return [
			'editing'   => [
				'enable_tags' => __( 'Snippet fields', 'code-snippets' ),
				'theme'       => __( 'Code editor', 'code-snippets' ),
			],
			'running'   => [
				'enable_flat_files' => __( 'Execution', 'code-snippets' ),
			],
			'insights'  => [
				'enable_performance_tracker' => __( 'Performance', 'code-snippets' ),
				'enable_security_scan'       => __( 'Security', 'code-snippets' ),
			],
			'library'   => [
				'max_revisions' => __( 'Revisions', 'code-snippets' ),
				'connect_cloud' => __( 'Cloud', 'code-snippets' ),
			],
			'interface' => [
				'list_order'        => __( 'Snippets list', 'code-snippets' ),
				'enable_admin_bar'  => __( 'Admin bar', 'code-snippets' ),
				'hide_upgrade_menu' => __( 'Notices', 'code-snippets' ),
			],
			'advanced'  => [
				'role_capabilities' => __( 'Access', 'code-snippets' ),
				'version_switcher'  => __( 'Maintenance', 'code-snippets' ),
				'reset_caches'      => __( 'Maintenance', 'code-snippets' ),
			],
		];
	}

	/**
	 * Retrieve replacement descriptions for fields whose current wording names
	 * the setting without saying what it is for.
	 *
	 * @return array<string, string> Field identifier keyed to its description.
	 */
	public static function get_descriptions(): array {
		return [
			'enable_flat_files'          => __( 'Run snippets from files on disk instead of the database. Faster on most sites, and it lets you keep snippets in version control.', 'code-snippets' ),
			'minify_output'              => __( 'Strip whitespace from CSS and JavaScript snippets before they reach the browser, so pages load faster.', 'code-snippets' ),
			'enable_performance_tracker' => __( 'Measure how long each snippet takes to run. Timings appear on the snippets list, so you can find the slow one without guessing.', 'code-snippets' ),
			'enable_security_scan'       => __( 'Check PHP snippets for risky patterns. Anything worth a second look is flagged against the snippet on the list.', 'code-snippets' ),
			'max_revisions'              => __( 'Saves a copy each time a snippet changes, so you can compare versions and roll back a bad edit.', 'code-snippets' ),
			'preserve_on_delete'         => __( 'Keep the revision history after a snippet is deleted, so it can still be recovered.', 'code-snippets' ),
			'enable_admin_bar'           => __( 'Jump to any snippet from anywhere in the admin, and see which ones ran on the page you are looking at.', 'code-snippets' ),
			'reset_caches'               => __( 'Clears stored snippet data. Worth doing before switching to an older version of the plugin.', 'code-snippets' ),
		];
	}
}
