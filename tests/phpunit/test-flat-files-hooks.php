<?php

namespace Code_Snippets\Tests;

use Code_Snippets\Model\Snippet;
use function Code_Snippets\save_snippet;
use function Code_Snippets\update_snippet_fields;

/**
 * Tests for flat-file execution hooks.
 *
 * @group flat-files
 */
class Flat_Files_Hooks_Test extends TestCase {
	public function test_update_snippet_fields_triggers_update_action_with_snippet_object() {
		$snippet = new Snippet(
			[
				'name'   => 'E2E Flat Files Hook Test',
				'desc'   => '',
				'code'   => '/* test */',
				'scope'  => 'global',
				'active' => false,
			]
		);

		$saved = save_snippet( $snippet );
		$this->assertNotNull( $saved );
		$this->assertGreaterThan( 0, $saved->id );

		$observed = null;
		$callback = static function ( $snippet_arg ) use ( &$observed ) {
			$observed = $snippet_arg;
		};

		add_action( 'code_snippets/update_snippet', $callback, 0, 1 );

		update_snippet_fields( $saved->id, [ 'priority' => 9 ] );

		remove_action( 'code_snippets/update_snippet', $callback, 0 );

		$this->assertInstanceOf( Snippet::class, $observed );
		$this->assertSame( $saved->id, $observed->id );
	}
}
