<?php

namespace Code_Snippets\Admin\Menus\Manage;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use ReflectionMethod;
use RuntimeException;
use function Code_Snippets\get_snippet;
use function Code_Snippets\save_snippet;

/**
 * Tests for the Run Once request handler and the nonce that authorises it.
 */
class Manage_Menu_Run_Once_Test extends UnitTestCase {

	/**
	 * Where the handler tried to redirect, captured instead of followed.
	 *
	 * @var string
	 */
	private string $redirected_to = '';

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->redirected_to = '';
		add_filter( 'wp_redirect', [ $this, 'capture_redirect' ] );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'wp_redirect', [ $this, 'capture_redirect' ] );
		remove_all_filters( 'code_snippets/execute_snippets' );
		$_REQUEST = [];
		parent::tear_down();
	}

	/**
	 * Record the redirect and stop the handler before it exits.
	 *
	 * @param string $location Redirect target.
	 *
	 * @return never
	 * @throws RuntimeException Always; the location is kept on the test, not in the message.
	 */
	public function capture_redirect( string $location ) {
		$this->redirected_to = $location;
		throw new RuntimeException( 'redirect captured' );
	}

	/**
	 * Run the handler for a snippet id with a given nonce, returning the result query value.
	 *
	 * @param int    $snippet_id Snippet to run.
	 * @param string $nonce      Nonce to present.
	 *
	 * @return string|null The result parameter of the redirect, or null when the handler returned without redirecting.
	 */
	private function run_once_request( int $snippet_id, string $nonce ): ?string {
		$_REQUEST['action']   = 'run-once';
		$_REQUEST['snippet']  = (string) $snippet_id;
		$_REQUEST['_wpnonce'] = $nonce;

		// The admin bootstrap does not run under PHPUnit, so the menu is built here.
		$menu   = new Manage_Menu();
		$method = new ReflectionMethod( $menu, 'handle_run_once' );
		$method->setAccessible( true );

		try {
			$method->invoke( $menu );
		} catch ( RuntimeException $e ) {
			$query = [];
			wp_parse_str( (string) wp_parse_url( $this->redirected_to, PHP_URL_QUERY ), $query );
			return $query['result'] ?? '';
		}

		return null;
	}

	/**
	 * Store a single-use snippet.
	 *
	 * @param string $code Snippet code.
	 *
	 * @return Snippet
	 */
	private function single_use( string $code ): Snippet {
		$snippet         = new Snippet();
		$snippet->name   = 'Run once';
		$snippet->scope  = 'single-use';
		$snippet->code   = $code;
		$snippet->active = false;

		$saved = save_snippet( $snippet );
		$this->assertNotNull( $saved, 'the single-use snippet must save before the test can run it' );

		return $saved;
	}

	/**
	 * A bad nonce does nothing at all.
	 *
	 * @return void
	 */
	public function test_bad_nonce_is_ignored(): void {
		$snippet = $this->single_use( 'update_option( "run_once_ran", "yes" );' );

		$this->assertNull( $this->run_once_request( $snippet->id, 'not-a-nonce' ) );
		$this->assertFalse( (bool) get_snippet( $snippet->id )->active );
	}

	/**
	 * A valid snippet runs and reports it.
	 *
	 * @return void
	 */
	public function test_valid_snippet_is_executed(): void {
		$snippet = $this->single_use( 'update_option( "run_once_ran", "yes" );' );

		$this->assertSame( 'executed', $this->run_once_request( $snippet->id, wp_create_nonce( Manage_Menu::RUN_ONCE_NONCE ) ) );
		$this->assertTrue( (bool) get_snippet( $snippet->id )->active );
	}

	/**
	 * Code that fails validation is reported as a failure and stays inactive.
	 *
	 * @return void
	 */
	public function test_invalid_snippet_reports_failure(): void {
		$snippet = $this->single_use( 'function wp_head() { return "redeclared"; }' );

		$this->assertSame( 'run-once-failed', $this->run_once_request( $snippet->id, wp_create_nonce( Manage_Menu::RUN_ONCE_NONCE ) ) );
		$this->assertFalse( (bool) get_snippet( $snippet->id )->active );
	}

	/**
	 * With execution disabled nothing runs, and the result says so.
	 *
	 * @return void
	 */
	public function test_disabled_execution_is_reported(): void {
		add_filter( 'code_snippets/execute_snippets', '__return_false' );
		$snippet = $this->single_use( 'update_option( "run_once_ran", "yes" );' );

		$this->assertSame( 'run-once-safe-mode', $this->run_once_request( $snippet->id, wp_create_nonce( Manage_Menu::RUN_ONCE_NONCE ) ) );
		$this->assertFalse( (bool) get_snippet( $snippet->id )->active );
	}

	/**
	 * Only single-use snippets can be run this way.
	 *
	 * @return void
	 */
	public function test_other_scopes_are_refused(): void {
		$snippet         = new Snippet();
		$snippet->name   = 'Global';
		$snippet->scope  = 'global';
		$snippet->code   = 'update_option( "run_once_ran", "yes" );';
		$snippet->active = false;
		$saved           = save_snippet( $snippet );

		$this->assertSame( '', $this->run_once_request( $saved->id, wp_create_nonce( Manage_Menu::RUN_ONCE_NONCE ) ), 'redirected back with no result' );
		$this->assertFalse( (bool) get_snippet( $saved->id )->active );
	}

	/**
	 * The Heartbeat carries a fresh Run Once nonce for people who may run snippets, and nothing for others.
	 *
	 * @return void
	 */
	public function test_heartbeat_refreshes_the_nonce(): void {
		$menu = new Manage_Menu();
		$this->assertNotFalse( has_filter( 'heartbeat_received', [ $menu, 'refresh_run_once_nonce' ] ), 'the menu listens to the Heartbeat' );

		$response = $menu->refresh_run_once_nonce( [ 'other' => 'kept' ] );
		$this->assertSame( 'kept', $response['other'] );
		$this->assertArrayHasKey( 'code_snippets_run_once_nonce', $response );
		$this->assertNotFalse( wp_verify_nonce( $response['code_snippets_run_once_nonce'], Manage_Menu::RUN_ONCE_NONCE ) );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertArrayNotHasKey( 'code_snippets_run_once_nonce', $menu->refresh_run_once_nonce( [] ) );
	}

	/**
	 * A user without the capability is refused even with a nonce of their own.
	 *
	 * @return void
	 */
	public function test_capability_is_required_even_with_a_valid_nonce(): void {
		$snippet = $this->single_use( 'update_option( "run_once_ran", "yes" );' );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$own_nonce = wp_create_nonce( Manage_Menu::RUN_ONCE_NONCE );

		$this->assertNull( $this->run_once_request( $snippet->id, $own_nonce ) );
		$this->assertFalse( (bool) get_snippet( $snippet->id )->active );
		$this->assertFalse( get_option( 'run_once_ran' ) );
	}
}
