<?php

namespace Code_Snippets\Frontend;

use Code_Snippets\UnitTestCase;
use WP_Filesystem_Direct;

/**
 * Source contracts for the snippet editor form.
 */
class Snippet_Form_Source_Test extends UnitTestCase {

	/**
	 * Dirty navigation supports browsers that require the legacy return value.
	 *
	 * @return void
	 */
	public function test_dirty_beforeunload_handler_sets_legacy_return_value(): void {
		$filesystem = new WP_Filesystem_Direct( null );
		$source = $filesystem->get_contents(
			dirname( __DIR__, 3 ) . '/src/js/components/EditMenu/SnippetForm/SnippetForm.tsx'
		);

		$this->assertIsString( $source );
		$this->assertMatchesRegularExpression(
			'/event\.preventDefault\(\)\s+(?:(?:\/\/[^\n]*)\s+)*event\.returnValue = true/',
			$source
		);
	}
}
