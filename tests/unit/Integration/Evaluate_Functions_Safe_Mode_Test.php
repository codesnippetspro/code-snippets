<?php

namespace Code_Snippets\Integration;

use Code_Snippets\UnitTestCase;
use function Code_Snippets\code_snippets;

/**
 * Tests for safe mode request handling.
 *
 * @group safe-mode
 */
class Evaluate_Functions_Safe_Mode_Test extends UnitTestCase {

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		unset( $_REQUEST['snippets-safe-mode'] );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		unset( $_REQUEST['snippets-safe-mode'] );
		parent::tear_down();
	}

	/**
	 * The query var check must not touch capabilities.
	 *
	 * The class is constructed while plugins are still being included, which is
	 * before WordPress loads pluggable.php. A capability check at that point
	 * calls an undefined wp_get_current_user() and takes the request down, so
	 * this check has to stay free of anything user-related.
	 *
	 * @return void
	 */
	public function test_query_var_check_does_not_depend_on_pluggable_functions(): void {
		$evaluate = new Evaluate_Functions( code_snippets()->db );

		$this->assertFalse( $evaluate->is_safe_mode_query_var_set() );

		$_REQUEST['snippets-safe-mode'] = '1';

		$this->assertTrue( $evaluate->is_safe_mode_query_var_set() );
	}

	/**
	 * Constructing the class with the query var set must not be fatal.
	 *
	 * @return void
	 */
	public function test_constructing_with_the_query_var_set_is_not_fatal(): void {
		$_REQUEST['snippets-safe-mode'] = '1';

		$evaluate = new Evaluate_Functions( code_snippets()->db );

		$this->assertTrue( $evaluate->is_safe_mode_query_var_set() );
		$this->assertSame( 10, has_filter( 'admin_url', [ $evaluate, 'add_safe_mode_query_var' ] ) );
	}

	/**
	 * The URL callback tolerates a non-string from an earlier callback.
	 *
	 * @return void
	 */
	public function test_url_callback_tolerates_a_null_from_an_earlier_callback(): void {
		$evaluate = new Evaluate_Functions( code_snippets()->db );

		$this->assertIsString( $evaluate->add_safe_mode_query_var( null ) );
	}

	/**
	 * The execution callback tolerates a non-bool from an earlier callback.
	 *
	 * @return void
	 */
	public function test_execution_callback_tolerates_a_null_from_an_earlier_callback(): void {
		$evaluate = new Evaluate_Functions( code_snippets()->db );

		$this->assertFalse( $evaluate->disable_snippet_execution( null ) );
		$this->assertTrue( $evaluate->disable_snippet_execution( true ) );
	}
}
