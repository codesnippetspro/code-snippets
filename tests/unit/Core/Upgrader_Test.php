<?php

namespace Code_Snippets\Core;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippet;
use function Code_Snippets\save_snippet;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Tests for plugin installation and upgrades.
 */
class Upgrader_Test extends UnitTestCase {

	/**
	 * Restore the snippets table after an installation test removes it.
	 *
	 * @return void
	 */
	public function tear_down() {
		code_snippets()->db->create_or_upgrade_tables();

		parent::tear_down();
	}

	/**
	 * A first install creates the snippets table and stores the plugin version.
	 *
	 * @return void
	 */
	public function test_fresh_install_creates_the_snippets_table_and_records_the_current_version(): void {
		global $wpdb;

		$db = code_snippets()->db;

		$wpdb->query( "DROP TABLE IF EXISTS $db->table" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- A fresh-install fixture has no snippets table.
		delete_option( 'code_snippets_version' );

		$upgrader = new Upgrader( PLUGIN_VERSION, $db );
		$upgrader->run();
		remove_action( 'init', [ $upgrader, 'create_sample_content' ] );

		$this->assertTrue( DB::table_exists( $db->table, true ) );
		$this->assertSame( PLUGIN_VERSION, get_option( 'code_snippets_version' ) );
	}

	/**
	 * An upgrade keeps existing snippets and settings while recording the new version.
	 *
	 * @return void
	 */
	public function test_upgrade_from_an_older_version_preserves_snippets_and_settings(): void {
		$settings = [ 'general' => [ 'enable_admin_bar' => false ] ];
		$snippet = save_snippet(
			new Snippet(
				[
					'name' => 'Existing installation snippet',
					'code' => 'add_action( \'init\', \'__return_null\' );',
				]
			)
		);

		$this->assertInstanceOf( Snippet::class, $snippet );

		update_option( 'code_snippets_settings', $settings );
		update_option( 'code_snippets_version', '3.9.9' );

		( new Upgrader( PLUGIN_VERSION, code_snippets()->db ) )->run();

		$preserved_snippet = get_snippet( $snippet->id );

		$this->assertSame( 'Existing installation snippet', $preserved_snippet->name );
		$this->assertSame( $settings, get_option( 'code_snippets_settings' ) );
		$this->assertSame( PLUGIN_VERSION, get_option( 'code_snippets_version' ) );
	}
}
