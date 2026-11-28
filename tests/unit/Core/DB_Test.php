<?php

namespace Code_Snippets\Core;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippet;
use function Code_Snippets\save_snippet;
use const Code_Snippets\PLUGIN_VERSION;

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL -- tests stand in and inspect the snippets table directly.

/**
 * Tests for the snippets table and writes to it.
 */
class DB_Test extends UnitTestCase {

	/**
	 * Snippet tables a test replaced with ones from before the authorship columns.
	 *
	 * @var string[]
	 */
	private array $shadowed = [];

	/**
	 * Remove the replacement table.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $wpdb;

		foreach ( $this->shadowed as $table ) {
			$wpdb->query( "DROP TEMPORARY TABLE IF EXISTS `$table`" );
			DB::table_exists( $table, true );
		}

		$this->shadowed = [];

		remove_all_filters( 'query' );
		parent::tear_down();
	}

	/**
	 * Stand in a snippets table that missed the upgrade adding the authorship columns.
	 *
	 * A temporary table hides the real one of the same name for this connection only.
	 *
	 * @param string $table Table to stand in for; the site snippets table when empty.
	 *
	 * @return void
	 */
	private function shadow_table_without_authorship( string $table = '' ): void {
		global $wpdb;

		$table = $table ? $table : code_snippets()->db->table;

		$wpdb->query(
			"CREATE TEMPORARY TABLE `$table` (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				name TINYTEXT NOT NULL,
				description TEXT NOT NULL,
				code LONGTEXT NOT NULL,
				tags LONGTEXT NOT NULL,
				scope VARCHAR(15) NOT NULL DEFAULT 'global',
				condition_id BIGINT(20) NOT NULL DEFAULT 0,
				priority SMALLINT NOT NULL DEFAULT 10,
				active TINYINT(1) NOT NULL DEFAULT 0,
				modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				revision BIGINT(20) NOT NULL DEFAULT 1,
				cloud_id VARCHAR(255) NULL,
				PRIMARY KEY (id)
			)"
		);

		$this->shadowed[] = $table;
		DB::table_exists( $table, true );
	}

	/**
	 * The names of the columns the current snippets table has.
	 *
	 * @param string $table Table to look at; the site snippets table when empty.
	 *
	 * @return string[]
	 */
	private function columns( string $table = '' ): array {
		global $wpdb;

		$table = $table ? $table : code_snippets()->db->table;

		return $wpdb->get_col( "SHOW COLUMNS FROM `$table`" );
	}

	/**
	 * A table that missed the authorship upgrade is upgraded by the first save, which then succeeds.
	 *
	 * @return void
	 */
	public function test_saving_upgrades_a_table_missing_a_column(): void {
		$this->shadow_table_without_authorship();
		$this->assertNotContains( 'updated_by', $this->columns() );

		$suppressed = $GLOBALS['wpdb']->suppress_errors();
		$created    = save_snippet(
			new Snippet(
				[
					'name' => 'Created on an old table',
					'code' => '// one',
				]
			)
		);
		$GLOBALS['wpdb']->suppress_errors( $suppressed );

		$this->assertNotNull( $created );
		$this->assertGreaterThan( 0, $created->id );
		$this->assertContains( 'created_by', $this->columns() );
		$this->assertContains( 'updated_by', $this->columns() );

		$created->code = '// two';
		$this->assertNotNull( save_snippet( $created ) );
		$this->assertSame( '// two', get_snippet( $created->id )->code );
	}

	/**
	 * An edit that cannot be written is reported as failed rather than returning the unchanged snippet.
	 *
	 * @return void
	 */
	public function test_failed_update_returns_null(): void {
		$snippet = save_snippet(
			new Snippet(
				[
					'name' => 'Cannot be updated',
					'code' => '// before',
				]
			)
		);
		$table   = code_snippets()->db->table;

		add_filter(
			'query',
			static fn( string $query ): string => 0 === strpos( $query, "UPDATE `$table`" ) ? 'UPDATE `cs_missing_table` SET id = 1' : $query
		);

		$snippet->code = '// after';

		$suppressed = $GLOBALS['wpdb']->suppress_errors();
		$saved      = save_snippet( $snippet );
		$GLOBALS['wpdb']->suppress_errors( $suppressed );

		$this->assertNull( $saved );

		remove_all_filters( 'query' );
		$this->assertSame( '// before', get_snippet( $snippet->id )->code );
	}

	/**
	 * Upgrading the tables reports success when they match the current schema.
	 *
	 * @return void
	 */
	public function test_create_or_upgrade_tables_reports_success(): void {
		$this->assertTrue( code_snippets()->db->create_or_upgrade_tables() );
	}

	/**
	 * A table upgrade that fails is not recorded as done, so the next request tries it again.
	 *
	 * @return void
	 */
	public function test_failed_table_upgrade_is_tried_again(): void {
		$db     = code_snippets()->db;
		$tables = is_multisite() ? [ $db->table, $db->ms_table ] : [ $db->table ];

		foreach ( $tables as $table ) {
			$this->shadow_table_without_authorship( $table );
		}

		update_option( 'code_snippets_version', '4.0.0' );
		update_site_option( 'code_snippets_version', '4.0.0' );

		$block_changes = static function ( string $query ): string {
			return preg_match( '/^\s*ALTER TABLE/i', $query ) ? 'ALTER TABLE `cs_missing_table` ADD COLUMN x INT' : $query;
		};

		add_filter( 'query', $block_changes );
		$suppressed = $GLOBALS['wpdb']->suppress_errors();
		( new Upgrader( PLUGIN_VERSION, $db ) )->run();
		$GLOBALS['wpdb']->suppress_errors( $suppressed );
		remove_filter( 'query', $block_changes );

		$this->assertSame( '4.0.0', get_option( 'code_snippets_version' ), 'Site version not recorded' );
		$this->assertSame( '4.0.0', get_site_option( 'code_snippets_version' ), 'Network version not recorded' );

		( new Upgrader( PLUGIN_VERSION, $db ) )->run();

		$this->assertSame( PLUGIN_VERSION, get_option( 'code_snippets_version' ) );

		foreach ( $tables as $table ) {
			$this->assertContains( 'updated_by', $this->columns( $table ), $table );
		}

		if ( is_multisite() ) {
			$this->assertSame( PLUGIN_VERSION, get_site_option( 'code_snippets_version' ) );
		}
	}

	/**
	 * Creating a table reports failure when a column could not be added, even when a later query succeeded.
	 *
	 * @return void
	 */
	public function test_create_table_reports_a_missing_column(): void {
		$table = code_snippets()->db->table;
		$this->shadow_table_without_authorship();

		$block_one_column = static function ( string $query ): string {
			return preg_match( '/^\s*ALTER TABLE.*ADD COLUMN `?created_by/i', $query ) ? 'ALTER TABLE `cs_missing_table` ADD COLUMN x INT' : $query;
		};

		add_filter( 'query', $block_one_column );
		$suppressed = $GLOBALS['wpdb']->suppress_errors();
		$result     = DB::create_table( $table );
		$GLOBALS['wpdb']->suppress_errors( $suppressed );

		$this->assertNotContains( 'created_by', $this->columns() );
		$this->assertContains( 'updated_by', $this->columns() );
		$this->assertFalse( $result );
	}
}
