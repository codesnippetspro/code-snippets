<?php

namespace Code_Snippets\Core;

use Code_Snippets\Model\Snippet;
use Code_Snippets\REST_API\Preferences\Insights_View_Rest_Controller;
use Code_Snippets\REST_API\Preferences\Snippet_View_REST_Controller;
use Code_Snippets\UnitTestCase;
use function Code_Snippets\code_snippets;
use function Code_Snippets\delete_snippet;
use function Code_Snippets\get_snippet;
use function Code_Snippets\save_snippet;

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

	/**
	 * A standard uninstall leaves snippets and settings ready for a reinstall.
	 *
	 * @return void
	 */
	public function test_incomplete_uninstall_preserves_snippets_and_settings(): void {
		$settings = [ 'general' => [ 'complete_uninstall' => false ] ];
		$snippet  = save_snippet(
			new Snippet(
				[
					'name' => 'Preserved uninstall snippet',
					'code' => 'add_action( \'init\', \'__return_null\' );',
				]
			)
		);

		update_option( 'code_snippets_settings', $settings );
		( new Uninstaller() )->uninstall_plugin();

		$this->assertSame( $settings, get_option( 'code_snippets_settings' ) );
		$this->assertSame( 'Preserved uninstall snippet', get_snippet( $snippet->id )->name );

		delete_snippet( $snippet->id );
	}

	/**
	 * A reinstall after a preserving uninstall keeps existing snippets available.
	 *
	 * @return void
	 */
	public function test_reinstall_after_incomplete_uninstall_keeps_existing_snippets(): void {
		$snippet = save_snippet(
			new Snippet(
				[
					'name' => 'Reinstalled preserved snippet',
					'code' => 'add_action( \'init\', \'__return_null\' );',
				]
			)
		);

		update_option( 'code_snippets_settings', [ 'general' => [ 'complete_uninstall' => false ] ] );
		( new Uninstaller() )->uninstall_plugin();
		code_snippets()->db->create_or_upgrade_tables();

		$this->assertSame( 'Reinstalled preserved snippet', get_snippet( $snippet->id )->name );

		delete_snippet( $snippet->id );
	}

	/**
	 * A complete uninstall removes the snippets table and plugin settings.
	 *
	 * @return void
	 */
	public function test_complete_uninstall_removes_the_snippets_table_and_settings(): void {
		$db = code_snippets()->db;

		update_option(
			'code_snippets_settings',
			[
				'general' => [ 'complete_uninstall' => true ],
			]
		);

		( new Uninstaller() )->uninstall_plugin();

		$this->assertFalse( DB::table_exists( $db->table, true ) );
		$this->assertFalse( get_option( 'code_snippets_settings' ) );
	}
}
