<?php

namespace Code_Snippets\Admin\Menus\Insights;

use Code_Snippets\AdminUnitTestCase;
use Code_Snippets\Model\Snippet;
use function Code_Snippets\code_snippets;
use function Code_Snippets\clean_snippets_cache;
use function Code_Snippets\save_snippet;
use function Code_Snippets\trash_snippet;

/**
 * Tests for the Insights summary aggregation.
 */
class Insights_Summary_Test extends AdminUnitTestCase {

	/**
	 * An empty library has no insight chart entries.
	 *
	 * @return void
	 */
	public function test_summary_is_empty_when_no_snippets_are_saved(): void {
		$summary = ( new Insights_Summary() )->get();

		$this->assertSame( 0, $summary['active'] );
		$this->assertSame( 0, $summary['inactive'] );
		$this->assertSame( [ 'php', 'html', 'css', 'js', 'cond' ], array_keys( $summary['typeCounts'] ) );
		$this->assertSame( 0, $summary['typeCounts']['php']['count'] );
		$this->assertSame( 0, $summary['typeCounts']['cond']['count'] );
		$this->assertSame( [], $summary['locationCounts'] );
		$this->assertSame( [], $summary['tagCounts'] );
	}

	/**
	 * The summary counts each tag once per saved snippet and orders tag entries by usage.
	 *
	 * @return void
	 */
	public function test_summary_counts_and_orders_used_tags(): void {
		save_snippet(
			new Snippet(
				[
					'name'   => 'First tag fixture',
					'scope'  => 'global',
					'active' => true,
					'tags'   => [ 'Shared', 'Alpha', 'Shared' ],
				]
			)
		);
		save_snippet(
			new Snippet(
				[
					'name'   => 'Second tag fixture',
					'scope'  => 'global',
					'active' => true,
					'tags'   => [ 'Shared', 'Beta' ],
				]
			)
		);
		$trashed = save_snippet(
			new Snippet(
				[
					'name'   => 'Trashed tag fixture',
					'scope'  => 'global',
					'active' => false,
					'tags'   => [ 'Ignored' ],
				]
			)
		);

		$this->assertInstanceOf( Snippet::class, $trashed );
		trash_snippet( $trashed->id );

		$summary = ( new Insights_Summary() )->get();

		$this->assertSame( [ 'Shared', 'Alpha', 'Beta' ], array_keys( $summary['tagCounts'] ) );
		$this->assertSame(
			[
				'label' => 'Shared',
				'count' => 2,
			],
			$summary['tagCounts']['Shared']
		);
		$this->assertSame(
			[
				'label' => 'Alpha',
				'count' => 1,
			],
			$summary['tagCounts']['Alpha']
		);
		$this->assertSame(
			[
				'label' => 'Beta',
				'count' => 1,
			],
			$summary['tagCounts']['Beta']
		);
		$this->assertArrayNotHasKey( 'Ignored', $summary['tagCounts'] );
	}

	/**
	 * The summary includes snippets supplied through the snippets filter.
	 *
	 * @return void
	 */
	public function test_summary_includes_filtered_snippets(): void {
		$filter = static function ( array $snippets ): array {
			$snippets[] = new Snippet(
				[
					'name'   => 'Filtered Insight Fixture',
					'scope'  => 'global',
					'active' => true,
				]
			);

			return $snippets;
		};

		clean_snippets_cache( code_snippets()->db->get_table_name( false ) );
		add_filter( 'code_snippets/get_snippets', $filter );

		try {
			$summary = ( new Insights_Summary() )->get();

			$this->assertSame( 1, $summary['active'] );
			$this->assertSame( 0, $summary['inactive'] );
			$this->assertSame( 1, $summary['typeCounts']['php']['count'] );
			$this->assertSame( 1, $summary['locationCounts']['global'] );
		} finally {
			remove_filter( 'code_snippets/get_snippets', $filter );
			clean_snippets_cache( code_snippets()->db->get_table_name( false ) );
		}
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
	 * The summary groups saved snippets by type, location and status, excluding trashed snippets.
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

		$summary = ( new Insights_Summary() )->get();

		$this->assertSame( 4, $summary['active'] );
		$this->assertSame( 3, $summary['inactive'] );
		$this->assertSame( 3, $summary['typeCounts']['php']['count'] );
		$this->assertSame( 1, $summary['typeCounts']['html']['count'] );
		$this->assertSame( 1, $summary['typeCounts']['css']['count'] );
		$this->assertSame( 1, $summary['typeCounts']['js']['count'] );
		$this->assertSame( 1, $summary['typeCounts']['cond']['count'] );
		$this->assertSame( 2, $summary['locationCounts']['global'] );
		$this->assertSame( 1, $summary['locationCounts']['site-css'] );
		$this->assertArrayNotHasKey( 'condition', $summary['locationCounts'] );
	}
}
