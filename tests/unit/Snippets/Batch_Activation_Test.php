<?php

namespace Code_Snippets\Tests;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use function Code_Snippets\activate_snippets;
use function Code_Snippets\get_snippet;
use function Code_Snippets\save_snippet;

/**
 * Tests for validating a batch of snippets together.
 *
 * @group snippets
 */
class Batch_Activation_Test extends UnitTestCase {

	/**
	 * Store an inactive snippet and return it.
	 *
	 * @param string $scope Snippet scope.
	 * @param string $code  Snippet code.
	 *
	 * @return Snippet
	 */
	private function make_snippet( string $scope, string $code ): Snippet {
		$snippet = new Snippet();
		$snippet->name = 'Batch test';
		$snippet->scope = $scope;
		$snippet->code = $code;
		$snippet->active = false;

		return save_snippet( $snippet );
	}

	/**
	 * Whether a snippet is active, read back from storage.
	 *
	 * @param int $id Snippet identifier.
	 *
	 * @return bool
	 */
	private function is_active( int $id ): bool {
		return (bool) get_snippet( $id )->active;
	}

	/**
	 * Two snippets declaring the same function are not both activated.
	 *
	 * Each was previously validated only against what PHP had declared at the
	 * time, which did not include the other snippet in the same batch. Both
	 * passed, both activated, and the next request fataled with
	 * "Cannot redeclare function".
	 *
	 * @return void
	 */
	public function test_two_snippets_declaring_the_same_function_do_not_both_activate(): void {
		$first = $this->make_snippet( 'global', 'function cs_batch_helper() { return 1; }' );
		$second = $this->make_snippet( 'global', 'function cs_batch_helper() { return 2; }' );

		activate_snippets( [ $first->id, $second->id ] );

		$this->assertTrue( $this->is_active( $first->id ), 'The first snippet should activate.' );
		$this->assertFalse( $this->is_active( $second->id ), 'The second should be held back.' );
	}

	/**
	 * The same applies to classes.
	 *
	 * @return void
	 */
	public function test_two_snippets_declaring_the_same_class_do_not_both_activate(): void {
		$first = $this->make_snippet( 'global', 'class CS_Batch_Widget {}' );
		$second = $this->make_snippet( 'global', 'class CS_Batch_Widget {}' );

		activate_snippets( [ $first->id, $second->id ] );

		$this->assertTrue( $this->is_active( $first->id ) );
		$this->assertFalse( $this->is_active( $second->id ) );
	}

	/**
	 * Snippets declaring different names both activate.
	 *
	 * @return void
	 */
	public function test_snippets_with_different_names_both_activate(): void {
		$first = $this->make_snippet( 'global', 'function cs_batch_one() { return 1; }' );
		$second = $this->make_snippet( 'global', 'function cs_batch_two() { return 2; }' );

		activate_snippets( [ $first->id, $second->id ] );

		$this->assertTrue( $this->is_active( $first->id ) );
		$this->assertTrue( $this->is_active( $second->id ) );
	}

	/**
	 * A guarded redeclaration is still allowed, as it cannot fatal.
	 *
	 * @return void
	 */
	public function test_guarded_declarations_are_allowed(): void {
		$first = $this->make_snippet( 'global', 'function cs_batch_guarded() { return 1; }' );
		$second = $this->make_snippet(
			'global',
			"if ( ! function_exists( 'cs_batch_guarded' ) ) {\n\tfunction cs_batch_guarded() { return 2; }\n}"
		);

		activate_snippets( [ $first->id, $second->id ] );

		$this->assertTrue( $this->is_active( $first->id ) );
		$this->assertTrue( $this->is_active( $second->id ) );
	}

	/**
	 * Scripts are not held back by a name another snippet declares.
	 *
	 * @return void
	 */
	public function test_scripts_are_unaffected_by_php_names(): void {
		$php = $this->make_snippet( 'global', 'function cs_batch_shared() { return 1; }' );
		$js = $this->make_snippet( 'site-footer-js', 'function cs_batch_shared() { return 2; }' );

		activate_snippets( [ $php->id, $js->id ] );

		$this->assertTrue( $this->is_active( $php->id ) );
		$this->assertTrue( $this->is_active( $js->id ), 'JavaScript shares no namespace with PHP.' );
	}

	/**
	 * Anonymous functions do not claim a name.
	 *
	 * @return void
	 */
	public function test_anonymous_functions_do_not_collide(): void {
		$first = $this->make_snippet( 'global', "add_filter( 'the_content', function ( \$c ) { return \$c; } );" );
		$second = $this->make_snippet( 'global', "add_filter( 'the_title', function ( \$t ) { return \$t; } );" );

		activate_snippets( [ $first->id, $second->id ] );

		$this->assertTrue( $this->is_active( $first->id ) );
		$this->assertTrue( $this->is_active( $second->id ) );
	}
}
