<?php

namespace Code_Snippets\Core;

use Code_Snippets\REST_API\Preferences\Insights_View_Rest_Controller;
use Code_Snippets\REST_API\Preferences\Snippet_View_REST_Controller;
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
		delete_option( Snippet_View_REST_Controller::OPTION_NAME );
		delete_option( Insights_View_Rest_Controller::OPTION_NAME );
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
			Insights_View_Rest_Controller::OPTION_NAME,
			[
				'type'       => 'pie',
				'activation' => 'bar',
				'location'   => 'pie',
			]
		);

		( new Uninstaller() )->uninstall_plugin();

		$this->assertFalse( get_option( Insights_View_Rest_Controller::OPTION_NAME ) );
	}
}
