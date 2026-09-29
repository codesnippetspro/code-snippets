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
	 * Calls to the current-user filter after which it stops building URLs.
	 *
	 * Past anything a correct implementation produces, but low enough that a
	 * regression fails on the count rather than exhausting the memory limit and
	 * taking the whole test run down with it.
	 */
	private const RESOLUTION_LIMIT = 5;

	/**
	 * How many times the current-user filter was called during a test.
	 *
	 * @var int
	 */
	private int $resolution_count = 0;

	/**
	 * The last URL built while the current user was being resolved.
	 *
	 * @var string|null
	 */
	private ?string $resolved_url = null;

	/**
	 * User the current-user filter resolves to.
	 *
	 * @var int|false
	 */
	private $user_to_resolve = false;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		unset( $_REQUEST['snippets-safe-mode'] );
		$this->resolution_count = 0;
		$this->resolved_url = null;
		$this->user_to_resolve = false;
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		unset( $_REQUEST['snippets-safe-mode'] );
		remove_filter( 'determine_current_user', [ $this, 'resolve_user_through_a_url' ], 15 );
		parent::tear_down();
	}

	/**
	 * Stand in for a plugin that builds a URL while the current user is being resolved.
	 *
	 * WooCommerce does this: its REST authentication runs on determine_current_user
	 * and calls home_url() to work out whether the request is for one of its routes.
	 *
	 * @param int|false $user_id Resolved user, from an unknown earlier callback.
	 *
	 * @return int|false
	 */
	public function resolve_user_through_a_url( $user_id ) {
		++$this->resolution_count;

		if ( $this->resolution_count < self::RESOLUTION_LIMIT ) {
			$this->resolved_url = home_url( '/' );
		}

		return false === $this->user_to_resolve ? $user_id : $this->user_to_resolve;
	}

	/**
	 * Force WordPress to resolve the current user again on the next capability check.
	 *
	 * @return void
	 */
	private function require_user_resolution(): void {
		global $current_user;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- emptying the cached user is the only way to make WordPress resolve one again, which is the whole point of these tests.
		$current_user = null;
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

	/**
	 * Resolving the current user must not re-enter the capability check.
	 *
	 * A plugin that builds a URL on determine_current_user reaches the URL
	 * filter before WordPress knows who the user is. Checking the capability
	 * again there restarts the resolution that is already running, which
	 * recurses until the request exhausts its memory limit. Any visitor can
	 * trigger it by putting the query var on a front-end URL.
	 *
	 * @return void
	 */
	public function test_url_filter_does_not_restart_user_resolution(): void {
		$_REQUEST['snippets-safe-mode'] = '1';

		$evaluate = new Evaluate_Functions( code_snippets()->db );

		add_filter( 'determine_current_user', [ $this, 'resolve_user_through_a_url' ], 15 );
		$this->require_user_resolution();

		$evaluate->disable_snippet_execution( true );

		$this->assertSame(
			1,
			$this->resolution_count,
			'the current user should be resolved once per request, not once per URL built while resolving'
		);
	}

	/**
	 * A URL built during resolution comes back untouched.
	 *
	 * The user the request will turn out to belong to is not known while that
	 * URL is being built, so safe mode cannot yet be part of it, even when the
	 * user being resolved does hold the capability.
	 *
	 * @return void
	 */
	public function test_url_built_during_user_resolution_is_unchanged(): void {
		$_REQUEST['snippets-safe-mode'] = '1';
		$this->user_to_resolve = $this->factory()->user->create( [ 'role' => 'administrator' ] );

		$evaluate = new Evaluate_Functions( code_snippets()->db );

		add_filter( 'determine_current_user', [ $this, 'resolve_user_through_a_url' ], 15 );
		$this->require_user_resolution();

		$evaluate->disable_snippet_execution( true );

		$this->assertIsString( $this->resolved_url );
		$this->assertStringNotContainsString( 'snippets-safe-mode', $this->resolved_url );
	}

	/**
	 * Safe mode still reaches the URLs an administrator follows.
	 *
	 * @return void
	 */
	public function test_query_var_is_added_for_a_user_with_the_capability(): void {
		$_REQUEST['snippets-safe-mode'] = '1';
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		$evaluate = new Evaluate_Functions( code_snippets()->db );

		$this->assertStringContainsString(
			'snippets-safe-mode=1',
			$evaluate->add_safe_mode_query_var( 'https://example.org/wp-admin/' )
		);
	}

	/**
	 * Safe mode stays out of the URLs a visitor without the capability follows.
	 *
	 * @return void
	 */
	public function test_query_var_is_not_added_for_a_user_without_the_capability(): void {
		$_REQUEST['snippets-safe-mode'] = '1';
		wp_set_current_user( 0 );

		$evaluate = new Evaluate_Functions( code_snippets()->db );

		$this->assertSame(
			'https://example.org/',
			$evaluate->add_safe_mode_query_var( 'https://example.org/' )
		);
	}
}
