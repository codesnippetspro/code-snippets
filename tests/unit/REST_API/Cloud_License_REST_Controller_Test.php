<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\Model\Authenticated_Cloud_Connection;
use Code_Snippets\REST_API\Cloud\Cloud_License_REST_Controller;
use Code_Snippets\UnitTestCase;
use WP_REST_Request;

/**
 * Tests for the Cloud License REST API endpoint.
 *
 * @group rest-api
 */
class Cloud_License_REST_Controller_Test extends UnitTestCase {

	/**
	 * REST API namespace and base route.
	 *
	 * @var string
	 */
	protected string $endpoint = '/code-snippets/v1/cloud/license';

	/**
	 * The connection instance backing the controller under test.
	 *
	 * @var Authenticated_Cloud_Connection
	 */
	private Authenticated_Cloud_Connection $connection;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = null;

		$this->connection = new Authenticated_Cloud_Connection();
		new Cloud_License_REST_Controller( $this->connection );
		do_action( 'rest_api_init' );
	}

	/**
	 * Reset any token mutated directly on the connection under test.
	 *
	 * @return void
	 */
	public function tear_down() {
		$this->set_connection_local_token( $this->connection, '' );

		parent::tear_down();
	}

	/**
	 * Seed a real, non-empty local_token directly onto a connection instance
	 * via reflection.
	 *
	 * @param Authenticated_Cloud_Connection $connection Connection to mutate.
	 * @param string                         $token      Token value to set.
	 *
	 * @return void
	 */
	private function set_connection_local_token( Authenticated_Cloud_Connection $connection, string $token ): void {
		$settings_prop = new \ReflectionProperty( $connection, 'settings' );
		$settings_prop->setAccessible( true );

		$settings = $settings_prop->getValue( $connection );
		$settings['local_token'] = $token;
		$settings_prop->setValue( $connection, $settings );
	}

	/**
	 * Dispatch the endpoint with a valid token and return its 200 data.
	 *
	 * @return array<string, mixed>
	 */
	private function fetch_license(): array {
		wp_set_current_user( 0 );

		$token = 'test-site-token-abc123';
		$this->set_connection_local_token( $this->connection, $token );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->add_header( 'Access-Control', $token );

		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );

		return $response->get_data();
	}

	/**
	 * A valid token returns the strict response contract: pro_* fields plus a
	 * license object that always contains the seven identifier keys.
	 *
	 * @return void
	 */
	public function test_valid_token_returns_strict_contract(): void {
		$data = $this->fetch_license();

		$this->assertArrayHasKey( 'pro_installed', $data );
		$this->assertIsBool( $data['pro_installed'] );
		$this->assertArrayHasKey( 'pro_active', $data );
		$this->assertIsBool( $data['pro_active'] );
		$this->assertArrayHasKey( 'pro_version', $data );
		$this->assertTrue( null === $data['pro_version'] || is_string( $data['pro_version'] ) );

		$this->assertArrayHasKey( 'license', $data );
		$this->assertSame(
			[ 'is_registered', 'has_valid_license', 'is_paying', 'license_id', 'install_id', 'freemius_user_id', 'plan_id' ],
			array_keys( $data['license'] )
		);
	}

	/**
	 * No secret-bearing field ever appears anywhere in the serialised response.
	 *
	 * @return void
	 */
	public function test_response_never_contains_a_secret(): void {
		$data = $this->fetch_license();

		$json = wp_json_encode( $data );

		foreach ( [ 'secret_key', 'license_key', 'secret', 'activation', 'token' ] as $needle ) {
			$this->assertStringNotContainsStringIgnoringCase( $needle, $json, "response leaked \"$needle\"" );
		}
	}

	/**
	 * The endpoint is served by Pro itself, so it always reports Pro installed and
	 * active with its own version constant — even when the plugin list has Pro
	 * under a non-standard folder (e.g. a `src/` dev checkout) or not at all.
	 *
	 * @return void
	 */
	public function test_pro_reported_directly_regardless_of_folder(): void {
		wp_cache_set(
			'plugins',
			[
				'' => [
					'src/code-snippets.php' => [
						'Name'    => 'Code Snippets Pro',
						'Version' => '4.0.0-beta.1',
					],
				],
			],
			'plugins'
		);

		$data = $this->fetch_license();

		wp_cache_delete( 'plugins', 'plugins' );

		$this->assertTrue( $data['pro_installed'] );
		$this->assertTrue( $data['pro_active'] );
		$this->assertSame( \Code_Snippets\PLUGIN_VERSION, $data['pro_version'] );
	}

	/**
	 * A wrong token with no logged-in user is rejected.
	 *
	 * @return void
	 */
	public function test_invalid_token_is_rejected(): void {
		wp_set_current_user( 0 );

		$this->set_connection_local_token( $this->connection, 'test-site-token-abc123' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->add_header( 'Access-Control', 'not-the-right-token' );

		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), [ 401, 403 ] );
	}
}
