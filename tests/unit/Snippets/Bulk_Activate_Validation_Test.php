<?php

namespace Code_Snippets\Tests;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use function Code_Snippets\activate_snippet;
use function Code_Snippets\activate_snippets;
use function Code_Snippets\get_snippet;
use function Code_Snippets\save_snippet;

/**
 * Tests for code validation during bulk activation.
 *
 * The validator looks for redeclarations of existing PHP functions and classes.
 * Applied to CSS or JavaScript it reports nothing meaningful, and a script
 * defining a function named after a PHP built-in was refused activation.
 *
 * @group snippets
 */
class Bulk_Activate_Validation_Test extends UnitTestCase {

	/**
	 * Store a snippet, inactive, and return it.
	 *
	 * @param string $scope Snippet scope.
	 * @param string $code  Snippet code.
	 *
	 * @return Snippet
	 */
	private function make_snippet( string $scope, string $code ): Snippet {
		$snippet = new Snippet();
		$snippet->name = 'Validation test';
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
	 * JavaScript naming a PHP built-in can be bulk activated.
	 *
	 * `next` and `reset` are ordinary names in a script, and both are PHP
	 * functions, so the validator reported a redeclaration and the snippet was
	 * quietly left inactive.
	 *
	 * @return void
	 */
	public function test_javascript_naming_php_builtins_can_be_bulk_activated(): void {
		$snippet = $this->make_snippet(
			'site-footer-js',
			"function next() {\n\tindex += 1;\n}\n\nfunction reset() {\n\tindex = 0;\n}"
		);

		activate_snippets( [ $snippet->id ] );

		$this->assertTrue( $this->is_active( $snippet->id ) );
	}

	/**
	 * The same snippet has always activated on its own, which is the
	 * inconsistency people run into.
	 *
	 * @return void
	 */
	public function test_single_activation_of_the_same_snippet_already_worked(): void {
		$snippet = $this->make_snippet( 'site-footer-js', 'function reset() {}' );

		activate_snippet( $snippet->id );

		$this->assertTrue( $this->is_active( $snippet->id ) );
	}

	/**
	 * Stylesheets are not run through the PHP validator either.
	 *
	 * @return void
	 */
	public function test_stylesheets_can_be_bulk_activated(): void {
		$snippet = $this->make_snippet( 'site-css', '.count { color: red; }' );

		activate_snippets( [ $snippet->id ] );

		$this->assertTrue( $this->is_active( $snippet->id ) );
	}

	/**
	 * PHP is still checked: a genuine redeclaration is still refused.
	 *
	 * @return void
	 */
	public function test_php_redeclaring_an_existing_function_is_still_refused(): void {
		$snippet = $this->make_snippet( 'global', 'function get_option() { return 1; }' );

		$result = activate_snippets( [ $snippet->id ] );

		$this->assertNull( $result );
		$this->assertFalse( $this->is_active( $snippet->id ) );
	}

	/**
	 * PHP that is fine still activates.
	 *
	 * @return void
	 */
	public function test_valid_php_is_still_bulk_activated(): void {
		$snippet = $this->make_snippet( 'global', "add_filter( 'the_content', 'cs_test_cb' );" );

		activate_snippets( [ $snippet->id ] );

		$this->assertTrue( $this->is_active( $snippet->id ) );
	}

	/**
	 * A bad PHP snippet does not prevent the others in the batch activating.
	 *
	 * @return void
	 */
	public function test_one_invalid_php_snippet_does_not_block_the_batch(): void {
		$good = $this->make_snippet( 'site-footer-js', 'function count() {}' );
		$bad = $this->make_snippet( 'global', 'function get_option() { return 1; }' );

		activate_snippets( [ $good->id, $bad->id ] );

		$this->assertTrue( $this->is_active( $good->id ) );
		$this->assertFalse( $this->is_active( $bad->id ) );
	}
}
