<?php

namespace Code_Snippets\Admin\Menus\Insights;

use Code_Snippets\AdminUnitTestCase;
use Code_Snippets\Model\Snippet;
use function Code_Snippets\save_snippet;

/**
 * Tests for the Insights menu assets and localized data.
 */
class Insights_Menu_Test extends AdminUnitTestCase {

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

		( new Insights_Menu() )->enqueue_assets();

		$data = wp_scripts()->get_data( 'code-snippets-insights', 'data' );
		$prefix = 'var CODE_SNIPPETS_INSIGHTS = ';
		$offset = is_string( $data ) ? strrpos( $data, $prefix ) : false;

		$this->assertNotFalse( $offset );
		$this->assertIsString( $data );
		$this->assertStringNotContainsString( 'Insight fixture source', $data );

		$json = substr( $data, $offset + strlen( $prefix ) );
		$localized = json_decode( substr( $json, 0, strrpos( $json, ';' ) ), true );

		$this->assertSame(
			[ 'active', 'inactive', 'typeCounts', 'locationCounts', 'tagCounts' ],
			array_keys( $localized )
		);
	}
}
