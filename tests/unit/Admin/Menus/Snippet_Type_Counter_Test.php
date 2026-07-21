<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use function Code_Snippets\save_snippet;
use function Code_Snippets\trash_snippet;

/**
 * Tests for snippet type counts used by the manage menu.
 */
class Snippet_Type_Counter_Test extends UnitTestCase {

	/**
	 * Compatible scopes are combined and trashed snippets are excluded.
	 *
	 * @return void
	 */
	public function test_count_returns_non_trashed_counts_by_snippet_type(): void {
		$counter = new Snippet_Type_Counter();
		$before = $counter->count();

		save_snippet( new Snippet( [ 'scope' => 'global' ] ) );
		save_snippet( new Snippet( [ 'scope' => 'admin' ] ) );
		save_snippet( new Snippet( [ 'scope' => 'content' ] ) );
		$trashed = save_snippet( new Snippet( [ 'scope' => 'site-css' ] ) );

		$this->assertInstanceOf( Snippet::class, $trashed );
		trash_snippet( $trashed->id );

		$counts = $counter->count();

		$this->assertSame( ( $before['all'] ?? 0 ) + 3, $counts['all'] );
		$this->assertSame( ( $before['php'] ?? 0 ) + 2, $counts['php'] );
		$this->assertSame( ( $before['html'] ?? 0 ) + 1, $counts['html'] );
		$this->assertSame( $before['css'] ?? 0, $counts['css'] ?? 0 );
	}
}
