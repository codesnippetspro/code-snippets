<?php
/**
 * Tests for the task-based settings layout.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Settings;

use Code_Snippets\Admin\Menus\Settings_Menu;
use Code_Snippets\UnitTestCase;
use ReflectionClass;

/**
 * Tabs only show fields that exist and apply; headings and labels render once and escaped.
 *
 * @group settings
 */
class Settings_Layout_Test extends UnitTestCase {

	/**
	 * Remove what a test registered.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $wp_settings_fields, $wp_settings_sections;

		unset( $wp_settings_fields[ Settings_Menu::SETTINGS_PAGE ]['layout-test'], $wp_settings_sections[ Settings_Menu::SETTINGS_PAGE ] );
		unset( $_REQUEST['section'] );
		remove_all_filters( 'code_snippets_settings_tab_contents' );
		remove_all_filters( 'code_snippets_settings_tabs' );
		parent::tear_down();
	}

	/**
	 * A field the definitions do not know, and one whose condition is not met, are left out.
	 *
	 * @return void
	 */
	public function test_undefined_and_hidden_fields_are_left_out(): void {
		add_filter(
			'code_snippets_settings_tab_contents',
			static function ( array $contents ): array {
				$contents['interface'][] = [ 'general', 'no_such_field' ];
				return $contents;
			}
		);

		$settings = Settings_Fields::get_default_values();

		$settings['general']['enable_admin_bar'] = false;
		$hidden                                  = Settings_Layout::get_visible_fields( 'interface', $settings );

		$settings['general']['enable_admin_bar'] = true;
		$shown                                   = Settings_Layout::get_visible_fields( 'interface', $settings );

		$this->assertNotContains( [ 'general', 'no_such_field' ], $shown, 'a field with no definition is skipped' );
		$this->assertNotContains( [ 'general', 'admin_bar_snippet_limit' ], $hidden, 'a field whose condition is not met is skipped' );
		$this->assertContains( [ 'general', 'admin_bar_snippet_limit' ], $shown );
		$this->assertContains( [ 'general', 'enable_admin_bar' ], $hidden, 'the field the condition depends on is always there' );
		$this->assertSame( [], Settings_Layout::get_visible_fields( 'no-such-tab', $settings ) );
	}

	/**
	 * A tab with nothing to show is not offered.
	 *
	 * @return void
	 */
	public function test_tabs_with_nothing_to_show_are_unavailable(): void {
		add_filter(
			'code_snippets_settings_tabs',
			static function ( array $tabs ): array {
				$tabs['empty'] = 'Empty';
				return $tabs;
			}
		);

		$available = Settings_Layout::get_available_tabs();

		$this->assertArrayHasKey( 'editing', $available );
		$this->assertArrayNotHasKey( 'empty', $available );
	}

	/**
	 * A group heading renders once for its group, escaped, and a labelable field gets a real label.
	 *
	 * @return void
	 */
	public function test_group_headings_render_once_and_escaped(): void {
		add_settings_section( 'layout-test', 'Layout test', '__return_empty_string', Settings_Menu::SETTINGS_PAGE );
		add_settings_field( 'first', 'First', '__return_null', Settings_Menu::SETTINGS_PAGE, 'layout-test', [ 'group_heading' => 'Group <b>one</b>' ] );
		add_settings_field(
			'second',
			'Second',
			'__return_null',
			Settings_Menu::SETTINGS_PAGE,
			'layout-test',
			[
				'group_heading' => 'Group <b>one</b>',
				'label_for'     => 'field-second',
			]
		);
		add_settings_field( 'third', 'Third', '__return_null', Settings_Menu::SETTINGS_PAGE, 'layout-test', [ 'group_heading' => 'Group two' ] );

		ob_start();
		do_settings_fields_with_headings( Settings_Menu::SETTINGS_PAGE, 'layout-test' );
		$html = (string) ob_get_clean();

		$this->assertSame( 1, substr_count( $html, 'Group &lt;b&gt;one&lt;/b&gt;' ), 'the heading is drawn once and escaped' );
		$this->assertStringNotContainsString( '<b>one</b>', $html );
		$this->assertStringContainsString( 'Group two', $html );
		$this->assertStringContainsString( '<label for="field-second">Second</label>', $html );
		$this->assertStringContainsString( '<th scope="row">First</th>', $html );
	}

	/**
	 * The current section is the requested one when it exists, else the default, else the first.
	 *
	 * @return void
	 */
	public function test_current_section_falls_back_sensibly(): void {
		global $wp_settings_sections;

		// Only the registered sections are read, so the menu's dependencies are not needed.
		$menu = ( new ReflectionClass( Settings_Menu::class ) )->newInstanceWithoutConstructor();

		$this->assertSame( 'anything', $menu->get_current_section( 'anything' ), 'with no sections the default is returned as given' );

		$wp_settings_sections[ Settings_Menu::SETTINGS_PAGE ] = [ // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test fixture, removed in tear_down.
			'editing' => [ 'id' => 'editing' ],
			'running' => [ 'id' => 'running' ],
		];

		$this->assertSame( 'running', $menu->get_current_section( 'running' ) );
		$this->assertSame( 'editing', $menu->get_current_section( 'no-such-section' ), 'an unknown default falls back to the first tab' );

		$_REQUEST['section'] = 'running';
		$this->assertSame( 'running', $menu->get_current_section() );

		$_REQUEST['section'] = '<script>bogus</script>';
		$this->assertSame( 'editing', $menu->get_current_section(), 'an invalid request value falls back to the first tab' );
	}
}
