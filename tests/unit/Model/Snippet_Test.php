<?php

namespace Code_Snippets\Model;

use Code_Snippets\UnitTestCase;

/**
 * Tests for snippet field normalization.
 */
class Snippet_Test extends UnitTestCase {

	/**
	 * Descriptions preserve post-safe markup and strip executable elements.
	 *
	 * @return void
	 */
	public function test_description_sanitizes_remote_markup(): void {
		$snippet = new Snippet(
			[
				'desc' => '<strong>Allowed</strong><script>alert("unsafe")</script>',
			]
		);

		$this->assertStringContainsString( '<strong>Allowed</strong>', $snippet->desc );
		$this->assertStringNotContainsString( '<script', $snippet->desc );
	}
}
