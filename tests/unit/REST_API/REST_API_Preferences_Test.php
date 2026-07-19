<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\REST_API\Snippets\Preferences_REST_Controller;
use Code_Snippets\UnitTestCase;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTest_Factory;

/**
 * Tests for the interface preferences REST API endpoint.
 *
 * Verifies that the snippet view preference (cards or table) defaults to
 * the table, persists through the plugin-wide option, rejects invalid values,
 * and is only writable by users with snippet capabilities.
 *
 * @group rest-api
 */
class REST_API_Preferences_Test extends UnitTestCase {

	/**
	 * REST API endpoint for the snippet view preference.
	 *
	 * @var string
	 */
	protected string $endpoint = '/code-snippets/v1/preferences/snippet-view';

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static int $admin_id;

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
		self::$admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$editor_id = $factory->user->create( [ 'role' => 'editor' ] );

		if ( is_multisite() ) {
			grant_super_admin( self::$admin_id );
		}
	}

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$admin_id );
		delete_option( Preferences_REST_Controller::SNIPPET_VIEW_OPTION );
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
	 * With no saved preference, the snippet view defaults to the table.
	 */
	public function test_snippet_view_defaults_to_table() {
		$response = $this->dispatch( 'GET' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ 'view' => 'table' ], $response->get_data() );
	}

	/**
	 * Updating the preference persists it for subsequent reads.
	 */
	public function test_snippet_view_update_persists() {
		$response = $this->dispatch( 'POST', [ 'view' => 'card' ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ 'view' => 'card' ], $response->get_data() );
		$this->assertSame( 'card', get_option( Preferences_REST_Controller::SNIPPET_VIEW_OPTION ) );

		$response = $this->dispatch( 'GET' );
		$this->assertSame( [ 'view' => 'card' ], $response->get_data() );

		$this->assertSame( 'card', Preferences_REST_Controller::get_snippet_view() );
	}

	/**
	 * Values outside the card/table enum are rejected.
	 */
	public function test_invalid_snippet_view_is_rejected() {
		$response = $this->dispatch( 'POST', [ 'view' => 'sideways' ] );

		$this->assertSame( 400, $response->get_status() );
		$this->assertFalse( get_option( Preferences_REST_Controller::SNIPPET_VIEW_OPTION ) );
	}

	/**
	 * An invalid stored option value falls back to the default view.
	 */
	public function test_invalid_stored_option_falls_back_to_default() {
		update_option( Preferences_REST_Controller::SNIPPET_VIEW_OPTION, 'nonsense' );

		$this->assertSame( 'table', Preferences_REST_Controller::get_snippet_view() );
	}

	/**
	 * Users without snippet capabilities cannot read or write the preference.
	 */
	public function test_editor_is_blocked() {
		wp_set_current_user( self::$editor_id );

		$response = $this->dispatch( 'GET' );
		$this->assertContains( $response->get_status(), [ 401, 403 ] );

		$response = $this->dispatch( 'POST', [ 'view' => 'table' ] );
		$this->assertContains( $response->get_status(), [ 401, 403 ] );
		$this->assertFalse( get_option( Preferences_REST_Controller::SNIPPET_VIEW_OPTION ) );
	}
}
