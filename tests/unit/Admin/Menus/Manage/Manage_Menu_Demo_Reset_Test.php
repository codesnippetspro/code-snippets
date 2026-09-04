<?php

namespace Code_Snippets\Admin\Menus\Manage;

use Code_Snippets\REST_API\Preferences\Demos_Seen_REST_Controller;
use Code_Snippets\UnitTestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * Tests for the watched-demo reset request and the nonce that authorises it.
 */
class Manage_Menu_Demo_Reset_Test extends UnitTestCase {

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
		update_option( Demos_Seen_REST_Controller::OPTION_NAME, [ 'ai-agent' ] );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'wp_redirect', [ $this, 'capture_redirect' ] );
		delete_option( Demos_Seen_REST_Controller::OPTION_NAME );
		$_GET = [];
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
	 * Run the reset handler with a given nonce.
	 *
	 * @param string $nonce Nonce to present.
	 *
	 * @return bool Whether the handler redirected, which it only does after resetting.
	 */
	private function reset_request( string $nonce ): bool {
		$_GET[ Manage_Menu::DEMO_RESET_PARAM ] = '1';
		$_GET['_wpnonce'] = $nonce;

		// The admin bootstrap does not run under PHPUnit, so the menu is built here.
		$menu = new Manage_Menu();
		$method = new ReflectionMethod( $menu, 'maybe_reset_demos' );
		$method->setAccessible( true );

		try {
			$method->invoke( $menu );
		} catch ( RuntimeException $e ) {
			return true;
		}

		return false;
	}

	/**
	 * A signed request clears the watched-demo record.
	 *
	 * @return void
	 */
	public function test_a_signed_request_resets_the_watched_demos() {
		$this->assertTrue( $this->reset_request( wp_create_nonce( Manage_Menu::DEMO_RESET_NONCE ) ) );
		$this->assertSame( [], Demos_Seen_REST_Controller::get_demos_seen() );
	}

	/**
	 * An unsigned request leaves the record alone, so a cross-site link cannot clear it.
	 *
	 * @return void
	 */
	public function test_an_unsigned_request_is_ignored() {
		$this->assertFalse( $this->reset_request( '' ) );
		$this->assertSame( [ 'ai-agent' ], Demos_Seen_REST_Controller::get_demos_seen() );
	}

	/**
	 * A request carrying the wrong nonce leaves the record alone.
	 *
	 * @return void
	 */
	public function test_a_request_with_the_wrong_nonce_is_ignored() {
		$this->assertFalse( $this->reset_request( wp_create_nonce( 'some_other_action' ) ) );
		$this->assertSame( [ 'ai-agent' ], Demos_Seen_REST_Controller::get_demos_seen() );
	}

	/**
	 * A subscriber cannot clear the record, even with a valid nonce.
	 *
	 * @return void
	 */
	public function test_a_user_without_the_capability_is_ignored() {
		$nonce = wp_create_nonce( Manage_Menu::DEMO_RESET_NONCE );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertFalse( $this->reset_request( $nonce ) );
		$this->assertSame( [ 'ai-agent' ], Demos_Seen_REST_Controller::get_demos_seen() );
	}

	/**
	 * The generated address carries a nonce the handler accepts.
	 *
	 * @return void
	 */
	public function test_the_generated_reset_url_is_signed() {
		$query = [];
		wp_parse_str( (string) wp_parse_url( Manage_Menu::get_demo_reset_url(), PHP_URL_QUERY ), $query );

		$this->assertArrayHasKey( Manage_Menu::DEMO_RESET_PARAM, $query );
		$this->assertNotFalse( wp_verify_nonce( $query['_wpnonce'], Manage_Menu::DEMO_RESET_NONCE ) );
	}
}
