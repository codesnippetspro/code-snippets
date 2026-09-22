<?php

namespace Code_Snippets\Core;

use Code_Snippets\REST_API\Preferences\Insights_View_Rest_Controller;
use Code_Snippets\REST_API\Preferences\Snippet_View_REST_Controller;
use Code_Snippets\Settings\Version_Switch;
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
		Version_Switch::clear_version_caches();
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

	/**
	 * Complete uninstall takes the version switcher's transients with it, whichever
	 * source cached them.
	 *
	 * @return void
	 */
	public function test_complete_uninstall_removes_version_switcher_transients(): void {
		update_option(
			'code_snippets_settings',
			[
				'general' => [ 'complete_uninstall' => true ],
			]
		);

		$cache_key = Version_Switch::get_source()->get_cache_key();

		set_transient(
			$cache_key,
			[
				'versions' => [],
				'floor'    => '',
			],
			HOUR_IN_SECONDS
		);
		set_transient( 'code_snippets_version_switch_progress', '3.9.2', HOUR_IN_SECONDS );
		set_transient( 'code_snippets_version_switch_error', 'version_source_request_error', HOUR_IN_SECONDS );

		( new Uninstaller() )->uninstall_plugin();

		$this->assertFalse( get_transient( $cache_key ) );
		$this->assertFalse( get_transient( 'code_snippets_version_switch_progress' ) );
		$this->assertFalse( get_transient( 'code_snippets_version_switch_error' ) );
	}
}
