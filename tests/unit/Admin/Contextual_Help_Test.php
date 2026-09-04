<?php

namespace Code_Snippets\Admin;

use Code_Snippets\AdminUnitTestCase;

/**
 * Tests for contextual help tabs.
 *
 * @group admin-help
 */
class Contextual_Help_Test extends AdminUnitTestCase {

	/**
	 * Every interactive admin page has an overview help tab.
	 *
	 * @dataProvider interactive_screens
	 *
	 * @param string $screen_name Contextual help screen name.
	 * @return void
	 */
	public function test_interactive_screens_have_an_overview_tab( string $screen_name ): void {
		set_current_screen( 'dashboard' );

		$contextual_help = new Contextual_Help( $screen_name );
		$contextual_help->load();

		$this->assertArrayHasKey( 'overview', get_current_screen()->get_help_tabs() );
	}

	/**
	 * Interactive admin screen names.
	 *
	 * @return array<string, array{string}>
	 */
	public function interactive_screens(): array {
		return [
			'manage'   => [ 'manage' ],
			'edit'     => [ 'edit' ],
			'insights'  => [ 'insights' ],
			'import'    => [ 'import' ],
			'settings'  => [ 'settings' ],
			'welcome'   => [ 'welcome' ],
		];
	}
}
