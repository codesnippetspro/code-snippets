<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\Client\Fake_Cloud_AI_Agent_Api;
use Code_Snippets\UnitTestCase;
use WP_REST_Request;
use WP_UnitTest_Factory;

/**
 * Tests for the Cloud AI Agent REST controller, exercised against a fake cloud
 * API answering at the HTTP layer.
 *
 * @group rest-api
 * @group ai-agent
 */
class REST_API_AI_Agent_Test extends UnitTestCase {

	/**
	 * REST API namespace and base route.
	 *
	 * @var string
	 */
	protected string $endpoint = '/code-snippets/v1/cloud/ai-agent';

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static int $admin_user_id;

	/**
	 * Fake cloud API backing the requests this controller makes.
	 *
	 * @var Fake_Cloud_AI_Agent_Api
	 */
	private Fake_Cloud_AI_Agent_Api $cloud_api;

	/**
	 * Restores the cloud connection and licensing state after each test.
	 *
	 * @var callable
	 */
	private $disconnect_cloud_account;

	/**
	 * Set up fixtures before any tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_user_id = $factory->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::$admin_user_id );

		$this->disconnect_cloud_account = $this->connect_cloud_account();
		$this->cloud_api = new Fake_Cloud_AI_Agent_Api();
		$this->cloud_api->register();
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		$this->cloud_api->unregister();
		( $this->disconnect_cloud_account )();
		parent::tear_down();
	}

	/**
	 * The controller registers its routes.
	 *
	 * @return void
	 */
	public function test_routes_are_registered() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( $this->endpoint . '/generations', $routes );
		$this->assertArrayHasKey( $this->endpoint . '/generations/(?P<id>\d+)', $routes );
		$this->assertArrayHasKey( $this->endpoint . '/generations/(?P<id>\d+)/refine', $routes );
		$this->assertArrayHasKey( $this->endpoint . '/generations/(?P<id>\d+)/proceed', $routes );
		$this->assertArrayHasKey( $this->endpoint . '/generations/(?P<id>\d+)/retry', $routes );
		$this->assertArrayHasKey( $this->endpoint . '/generations/(?P<id>\d+)/edit', $routes );
		$this->assertArrayHasKey( $this->endpoint . '/quota', $routes );
	}

	/**
	 * Editing existing snippets updates their code in place.
	 *
	 * @return void
	 */
	public function test_edit_updates_snippet_code() {
		$id = rest_do_request( $this->prompt_request( 'Add a custom shortcode' ) )->get_data()['id'];
		rest_do_request( $this->authed_request( 'GET', '/generations/' . $id ) );
		rest_do_request( $this->authed_request( 'POST', '/generations/' . $id . '/proceed' ) );
		$done = rest_do_request( $this->authed_request( 'GET', '/generations/' . $id ) );
		$cloud_id = $done->get_data()['result']['snippet']['cloud_id'];

		$edit = $this->authed_request( 'POST', '/generations/' . $id . '/edit' );
		$edit->set_param( 'snippet_ids', [ $cloud_id ] );
		$edit->set_param( 'message', 'add an aria-label' );
		$response = rest_do_request( $edit );
		$this->assertSame( 'editing', $response->get_data()['status'] );

		$edited = rest_do_request( $this->authed_request( 'GET', '/generations/' . $id ) );
		$this->assertSame( 'completed', $edited->get_data()['status'] );
		$this->assertStringContainsString( 'add an aria-label', $edited->get_data()['result']['snippet']['code'] );
	}

	/**
	 * Creating a generation returns 201 with a planning status.
	 *
	 * @return void
	 */
	public function test_create_returns_planning() {
		$response = rest_do_request( $this->prompt_request( 'Add a custom shortcode' ) );

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 'planning', $response->get_data()['status'] );
	}

	/**
	 * Creating without a prompt fails validation.
	 *
	 * @return void
	 */
	public function test_create_requires_prompt() {
		$response = rest_do_request( $this->authed_request( 'POST', '/generations' ) );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * The full happy path drives planning through to a completed snippet.
	 *
	 * @return void
	 */
	public function test_full_flow_reaches_completed() {
		$create = rest_do_request( $this->prompt_request( 'Add a custom shortcode' ) );
		$id = $create->get_data()['id'];

		$ready = rest_do_request( $this->authed_request( 'GET', '/generations/' . $id ) );
		$this->assertSame( 'plan_ready', $ready->get_data()['status'] );

		$proceed = rest_do_request( $this->authed_request( 'POST', '/generations/' . $id . '/proceed' ) );
		$this->assertSame( 'generating', $proceed->get_data()['status'] );

		$done = rest_do_request( $this->authed_request( 'GET', '/generations/' . $id ) );
		$this->assertSame( 'completed', $done->get_data()['status'] );
		$this->assertArrayHasKey( 'cloud_id', $done->get_data()['result']['snippet'] );
	}

	/**
	 * Listing returns generations for the history rail.
	 *
	 * @return void
	 */
	public function test_list_returns_generations() {
		rest_do_request( $this->prompt_request( 'First request' ) );

		$response = rest_do_request( $this->authed_request( 'GET', '/generations' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $response->get_data()['data'] );
	}

	/**
	 * Requests from users without the capability are rejected.
	 *
	 * @return void
	 */
	public function test_requires_capability() {
		wp_set_current_user( 0 );

		$response = rest_do_request( $this->prompt_request( 'Add a shortcode' ) );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * Build an authenticated request. The `Access-Control` header carries the
	 * cloud local token, which defaults to an empty string under test — matching
	 * the connection's empty token so {@see verify_rest_request} passes.
	 *
	 * @param string $method HTTP method.
	 * @param string $path   Path relative to the controller base route.
	 *
	 * @return WP_REST_Request
	 */
	private function authed_request( string $method, string $path ): WP_REST_Request {
		$request = new WP_REST_Request( $method, $this->endpoint . $path );
		$request->add_header( 'Access-Control', $this->get_connection_token() );
		return $request;
	}

	/**
	 * Read the active cloud connection's local token so the request passes
	 * {@see verify_rest_request}.
	 *
	 * @return string
	 */
	private function get_connection_token(): string {
		$plugin = \Code_Snippets\code_snippets();
		$property = new \ReflectionProperty( $plugin, 'cloud_connection' );
		$property->setAccessible( true );
		return $property->getValue( $plugin )->get_local_token();
	}

	/**
	 * Build an authenticated create request carrying a prompt.
	 *
	 * @param string $prompt Prompt text.
	 *
	 * @return WP_REST_Request
	 */
	private function prompt_request( string $prompt ): WP_REST_Request {
		$request = $this->authed_request( 'POST', '/generations' );
		$request->set_param( 'prompt', $prompt );
		return $request;
	}
}
