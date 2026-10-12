<?php

namespace Code_Snippets\REST_API\Preferences;

use Code_Snippets\AdminUnitTestCase;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTest_Factory;

/**
 * Tests for the watched feature demos REST API endpoint.
 *
 * Verifies that watched demos start empty, accumulate without duplicates,
 * reject unrecognised names, survive a corrupted stored option, can be reset,
 * and are only writable by users with snippet capabilities.
 *
 * @group rest-api
 */
class Demos_Seen_REST_Controller_Test extends AdminUnitTestCase {

	/**
	 * REST API endpoint recording which feature demos have been watched.
	 *
	 * @var string
	 */
	protected string $endpoint = '/code-snippets/v1/preferences/demos-seen';

	/**
	 * Editor user ID (no snippet capabilities).
	 *
	 * @var int
	 */
	protected static int $editor_id;

	/**
	 * Set up fixture users before any tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );
		self::$editor_id = $factory->user->create( [ 'role' => 'editor' ] );

		if ( is_multisite() ) {
			grant_super_admin( self::get_user_id() );
		}
	}

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		delete_option( Demos_Seen_REST_Controller::OPTION_NAME );
	}

	/**
	 * Dispatch a REST request and return the raw response object.
	 *
	 * @param string               $method HTTP method.
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return WP_REST_Response
	 */
	protected function dispatch( string $method, array $params = [] ): WP_REST_Response {
		$request = new WP_REST_Request( $method, $this->endpoint );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_do_request( $request );
	}

	/**
	 * No demos have been watched until one is recorded.
	 */
	public function test_demos_seen_starts_empty() {
		$response = $this->dispatch( 'GET' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ 'demos' => [] ], $response->get_data() );
	}

	/**
	 * Recording a watched demo persists it, and repeating it does not duplicate.
	 */
	public function test_demos_seen_records_without_duplicates() {
		$response = $this->dispatch( 'POST', [ 'demo' => 'blueprints' ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ 'demos' => [ 'blueprints' ] ], $response->get_data() );

		$this->dispatch( 'POST', [ 'demo' => 'blueprints' ] );
		$this->dispatch( 'POST', [ 'demo' => 'ai-agent' ] );

		$this->assertSame(
			[ 'ai-agent', 'blueprints' ],
			$this->dispatch( 'GET' )->get_data()['demos']
		);

		$this->assertSame( [ 'ai-agent', 'blueprints' ], Demos_Seen_REST_Controller::get_demos_seen() );
	}

	/**
	 * Unrecognised demo names are rejected.
	 */
	public function test_unknown_demo_is_rejected() {
		$response = $this->dispatch( 'POST', [ 'demo' => 'nonsense' ] );

		$this->assertSame( 400, $response->get_status() );
		$this->assertFalse( get_option( Demos_Seen_REST_Controller::OPTION_NAME ) );
	}

	/**
	 * A corrupted stored option does not leak unknown demo names.
	 */
	public function test_invalid_stored_demos_are_discarded() {
		update_option( Demos_Seen_REST_Controller::OPTION_NAME, 'nonsense' );
		$this->assertSame( [], Demos_Seen_REST_Controller::get_demos_seen() );

		update_option( Demos_Seen_REST_Controller::OPTION_NAME, [ 'blueprints', 'bogus' ] );
		$this->assertSame( [ 'blueprints' ], Demos_Seen_REST_Controller::get_demos_seen() );
	}

	/**
	 * Watched demos can be forgotten, putting their badges back to "New".
	 */
	public function test_demos_seen_can_be_reset() {
		$this->dispatch( 'POST', [ 'demo' => 'ai-agent' ] );
		$this->assertSame( [ 'ai-agent' ], Demos_Seen_REST_Controller::get_demos_seen() );

		Demos_Seen_REST_Controller::reset_demos_seen();

		$this->assertSame( [], Demos_Seen_REST_Controller::get_demos_seen() );
		$this->assertFalse( get_option( Demos_Seen_REST_Controller::OPTION_NAME ) );
	}

	/**
	 * Users without snippet capabilities cannot read or write the preference.
	 */
	public function test_editor_is_blocked() {
		wp_set_current_user( self::$editor_id );

		$response = $this->dispatch( 'GET' );
		$this->assertContains( $response->get_status(), [ 401, 403 ] );

		$response = $this->dispatch( 'POST', [ 'demo' => 'ai-agent' ] );
		$this->assertContains( $response->get_status(), [ 401, 403 ] );
		$this->assertFalse( get_option( Demos_Seen_REST_Controller::OPTION_NAME ) );
	}
}
