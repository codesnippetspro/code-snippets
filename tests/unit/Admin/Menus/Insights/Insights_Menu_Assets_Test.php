<?php

namespace Code_Snippets\Admin\Menus\Insights;

use Code_Snippets\Admin\Menus\Admin_Menu;
use Code_Snippets\AdminUnitTestCase;
use Code_Snippets\Model\Snippet;
use function Code_Snippets\code_snippets;
use function Code_Snippets\save_snippet;
use function Code_Snippets\trash_snippet;

/**
 * Tests for Insights menu assets and localized data.
 */
class Insights_Menu_Assets_Test extends AdminUnitTestCase {

	/**
	 * An empty library has no insight chart entries.
	 *
	 * @return void
	 */
	public function test_summary_is_empty_when_no_snippets_are_saved(): void {
		$summary = ( new Insights_Menu_Assets() )->get_summary();

		$this->assertSame( 0, $summary['active'] );
		$this->assertSame( 0, $summary['inactive'] );
		$this->assertSame( [ 'php', 'html', 'css', 'js', 'cond' ], array_keys( $summary['typeCounts'] ) );
		$this->assertSame( 0, $summary['typeCounts']['php']['count'] );
		$this->assertSame( 0, $summary['typeCounts']['cond']['count'] );
		$this->assertSame( [], $summary['locationCounts'] );
	}

	/**
	 * The summary excludes unrecognised locations from location counts.
	 *
	 * @return void
	 */
	public function test_summary_ignores_unrecognised_locations(): void {
		global $wpdb;
		$table_name = code_snippets()->db->get_table_name( false );
		$wpdb->insert(
			$table_name,
			[
				'name'   => 'Unknown scope fixture',
				'scope'  => 'unknown-scope',
				'active' => 1,
			]
		);

		$summary = ( new Insights_Summary() )->get();

		$this->assertSame( 1, $summary['active'] );
		$this->assertArrayNotHasKey( 'unknown-scope', $summary['locationCounts'] );
	}

	/**
	 * The summary groups saved snippets and excludes trashed snippets.
	 *
	 * @return void
	 */
	public function test_summary_groups_saved_snippets_by_type_location_and_status(): void {
		$condition = save_snippet(
            new Snippet(
                [
					'name'  => 'Condition Fixture',
					'scope' => 'condition',
				]
            )
        );

		$this->assertInstanceOf( Snippet::class, $condition );

		save_snippet(
            new Snippet(
                [
					'name'         => 'Active Global PHP Fixture',
					'code'         => '/* Insight fixture source */',
					'scope'        => 'global',
					'condition_id' => $condition->id,
					'active'       => true,
				]
            )
        );
		save_snippet(
            new Snippet(
                [
					'name'   => 'Inactive Global PHP Fixture',
					'scope'  => 'global',
					'active' => false,
				]
            )
        );
		save_snippet(
            new Snippet(
                [
					'name'   => 'Inactive Admin PHP Fixture',
					'scope'  => 'admin',
					'active' => false,
				]
            )
        );
		save_snippet(
            new Snippet(
                [
					'name'   => 'Active HTML Fixture',
					'scope'  => 'content',
					'active' => true,
				]
            )
        );
		save_snippet(
            new Snippet(
                [
					'name'   => 'Active Locked CSS Fixture',
					'scope'  => 'site-css',
					'active' => true,
					'locked' => true,
				]
            )
        );
		save_snippet(
            new Snippet(
                [
					'name'   => 'Inactive JavaScript Fixture',
					'scope'  => 'site-footer-js',
					'active' => false,
				]
            )
        );
		$trashed = save_snippet(
            new Snippet(
                [
					'name'   => 'Trashed CSS Fixture',
					'scope'  => 'site-css',
					'active' => false,
				]
            )
        );

		$this->assertInstanceOf( Snippet::class, $trashed );
		trash_snippet( $trashed->id );

		$summary = ( new Insights_Menu_Assets() )->get_summary();

		$this->assertSame( 4, $summary['active'] );
		$this->assertSame( 3, $summary['inactive'] );
		$this->assertSame( 3, $summary['typeCounts']['php']['count'] );
		$this->assertSame( 1, $summary['typeCounts']['html']['count'] );
		$this->assertSame( 1, $summary['typeCounts']['css']['count'] );
		$this->assertSame( 1, $summary['typeCounts']['js']['count'] );
		$this->assertSame( 1, $summary['typeCounts']['cond']['count'] );
		$this->assertSame( 2, $summary['locationCounts']['global']['count'] );
		$this->assertSame( 1, $summary['locationCounts']['site-css']['count'] );
		$this->assertArrayNotHasKey( 'condition', $summary['locationCounts'] );
	}

	/**
	 * The Insights payload contains summary data but not snippet source code.
	 *
	 * @return void
	 */
	public function test_enqueue_localizes_summary_without_snippet_source_code(): void {
		save_snippet(
            new Snippet(
                [
					'name'   => 'Source Exclusion Fixture',
					'code'   => '/* Insight fixture source */',
					'scope'  => 'global',
					'active' => true,
				]
            )
        );

		( new Insights_Menu_Assets() )->enqueue( Admin_Menu::$script_deps, Admin_Menu::$style_deps );

		$data = wp_scripts()->get_data( 'code-snippets-insights', 'data' );
		$prefix = 'var CODE_SNIPPETS_INSIGHTS = ';
		$offset = is_string( $data ) ? strrpos( $data, $prefix ) : false;

		$this->assertNotFalse( $offset );
		$this->assertIsString( $data );
		$this->assertStringNotContainsString( 'Insight fixture source', $data );

		$json = substr( $data, $offset + strlen( $prefix ) );
		$localized = json_decode( substr( $json, 0, strrpos( $json, ';' ) ), true );

		$this->assertSame(
			[ 'active', 'inactive', 'typeCounts', 'locationCounts' ],
			array_keys( $localized )
		);
	}
}
