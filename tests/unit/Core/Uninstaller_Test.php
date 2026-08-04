<?php

namespace Code_Snippets\Core;

use Code_Snippets\REST_API\Snippets\Preferences_REST_Controller;
use Code_Snippets\UnitTestCase;
use function Code_Snippets\code_snippets;

/**
 * Tests for complete plugin uninstallation.
 */
class Uninstaller_Test extends UnitTestCase {

	/**
	 * Restore the snippets table after testing complete uninstallation.
	 *
	 * @return void
	 */
	public function tear_down() {
		code_snippets()->db->create_or_upgrade_tables();
		delete_option( Preferences_REST_Controller::INSIGHTS_CHART_VIEWS_OPTION );
		delete_option( 'code_snippets_settings' );

		parent::tear_down();
	}

	/**
	 * Complete uninstall removes saved Insights chart view preferences.
	 *
	 * @return void
	 */
	public function test_complete_uninstall_removes_insights_chart_view_preferences(): void {
		update_option(
			'code_snippets_settings',
			[
				'general' => [ 'complete_uninstall' => true ],
			]
		);
		update_option(
			Preferences_REST_Controller::INSIGHTS_CHART_VIEWS_OPTION,
			[
				'type'       => 'pie',
				'activation' => 'bar',
				'location'   => 'pie',
			]
		);

		( new Uninstaller() )->uninstall_plugin();

		$this->assertFalse( get_option( Preferences_REST_Controller::INSIGHTS_CHART_VIEWS_OPTION ) );
	}
}
