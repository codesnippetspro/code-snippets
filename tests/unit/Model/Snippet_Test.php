<?php

namespace Code_Snippets\Model;

use Code_Snippets\UnitTestCase;
use function Code_Snippets\get_snippet;
use function Code_Snippets\save_snippet;

/**
 * Tests for snippet field normalization.
 */
class Snippet_Test extends UnitTestCase {

	/**
	 * Legacy descriptions round-trip through storage unchanged.
	 *
	 * @return void
	 */
	public function test_description_round_trips_without_sanitization(): void {
		$description = '<strong>Allowed</strong><script>alert("unsafe")</script>';
		$saved = save_snippet(
			new Snippet(
				[
					'name' => 'Legacy description',
					'desc' => $description,
				]
			)
		);
		$snippet = get_snippet( $saved->id );

		$this->assertSame( $description, $snippet->desc );
	}
}
