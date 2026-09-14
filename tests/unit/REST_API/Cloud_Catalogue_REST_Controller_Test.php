<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\Model\Authenticated_Cloud_Connection;
use Code_Snippets\REST_API\Cloud\Cloud_Catalogue_REST_Controller;
use Code_Snippets\UnitTestCase;
use WP_REST_Request;

/**
 * Tests for the Cloud Catalogue REST API endpoint.
 *
 * @group rest-api
 */
class Cloud_Catalogue_REST_Controller_Test extends UnitTestCase {

	/**
	 * REST API namespace and base route.
	 *
	 * @var string
	 */
	protected string $endpoint = '/code-snippets/v1/cloud/catalogue';

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

		// Force a fresh REST server/route table each test: routes registered
		// without $override=true otherwise accumulate handlers bound to a
		// previous test's connection instance, and the dispatcher always
		// uses the first-registered handler for a given route+method.
		global $wp_rest_server;
		$wp_rest_server = null;

		$this->connection = new Authenticated_Cloud_Connection();
		new Cloud_Catalogue_REST_Controller( $this->connection );
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
	 * Read the active cloud connection's local token.
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
	 * Seed a real, non-empty local_token directly onto a connection instance
	 * via reflection, bypassing the settings that were loaded (empty) at
	 * bootstrap and won't otherwise pick up a changed option.
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
	 * A valid Access-Control token alone (no logged-in user) authorises the request.
	 *
	 * @return void
	 */
	public function test_get_items_with_valid_token_and_no_user(): void {
		wp_set_current_user( 0 );

		$token = 'test-site-token-abc123';
		$this->set_connection_local_token( $this->connection, $token );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->add_header( 'Access-Control', $token );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'plugins', $data );
		$this->assertArrayHasKey( 'roles', $data );
	}

	/**
	 * Dispatch the catalogue endpoint with a valid token and return its data.
	 *
	 * @return array<string, mixed>
	 */
	private function fetch_catalogue(): array {
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
	 * Seed the object cache that get_plugins() reads from so the endpoint sees a
	 * controlled set of installed plugins instead of scanning the test site's
	 * plugins directory. Also sets which of those are active.
	 *
	 * @param array<string, array<string, string>> $installed Plugins keyed by plugin file.
	 * @param array<int, string>                   $active    Active plugin files.
	 *
	 * @return void
	 */
	private function seed_installed_plugins( array $installed, array $active = [] ): void {
		wp_cache_set( 'plugins', [ '' => $installed ], 'plugins' );
		update_option( 'active_plugins', $active );
	}

	/**
	 * Clear the seeded plugin cache/state so it does not leak into other tests.
	 *
	 * @return void
	 */
	private function clear_seeded_plugins(): void {
		wp_cache_delete( 'plugins', 'plugins' );
		update_option( 'active_plugins', [] );
	}

	/**
	 * The code_snippets block is always present with the strict field contract,
	 * even when neither plugin is installed.
	 *
	 * @return void
	 */
	public function test_code_snippets_block_present_with_strict_contract(): void {
		$this->seed_installed_plugins( [] );

		$data = $this->fetch_catalogue();

		$this->clear_seeded_plugins();

		$this->assertArrayHasKey( 'code_snippets', $data );
		$block = $data['code_snippets'];

		foreach ( [ 'pro_installed', 'pro_active', 'free_installed' ] as $key ) {
			$this->assertArrayHasKey( $key, $block );
			$this->assertIsBool( $block[ $key ], "$key must be a boolean" );
		}

		foreach ( [ 'pro_version', 'free_version' ] as $key ) {
			$this->assertArrayHasKey( $key, $block );
			$this->assertTrue(
				null === $block[ $key ] || is_string( $block[ $key ] ),
				"$key must be a string or null"
			);
		}
	}

	/**
	 * Pro reports itself installed and active with its own version even when it
	 * lives in a non-standard folder (e.g. a `src/` dev checkout) and no
	 * `code-snippets-pro` entry is present in the plugin list.
	 *
	 * @return void
	 */
	public function test_pro_reported_directly_regardless_of_folder(): void {
		$this->seed_installed_plugins(
			[
				'src/code-snippets.php' => [
					'Name'    => 'Code Snippets Pro',
					'Version' => '4.0.0-beta.1',
				],
			]
		);

		$block = $this->fetch_catalogue()['code_snippets'];

		$this->clear_seeded_plugins();

		$this->assertTrue( $block['pro_installed'] );
		$this->assertTrue( $block['pro_active'] );
		$this->assertSame( \Code_Snippets\PLUGIN_VERSION, $block['pro_version'] );
	}

	/**
	 * The free plugin is detected from WordPress plugin data; Pro stays true.
	 *
	 * @return void
	 */
	public function test_free_detected_from_plugin_data(): void {
		$this->seed_installed_plugins(
			[
				'code-snippets/code-snippets.php' => [
					'Name'    => 'Code Snippets',
					'Version' => '3.6.8',
				],
			]
		);

		$block = $this->fetch_catalogue()['code_snippets'];

		$this->clear_seeded_plugins();

		$this->assertTrue( $block['pro_installed'] );
		$this->assertTrue( $block['free_installed'] );
		$this->assertSame( '3.6.8', $block['free_version'] );
	}

	/**
	 * A root-level single-file plugin (e.g. hello.php) is listed with its file
	 * name as the slug, never the bare "." that dirname() returns.
	 *
	 * @return void
	 */
	public function test_root_level_plugin_slug_is_filename_not_dot(): void {
		$this->seed_installed_plugins(
			[
				'hello.php' => [
					'Name'    => 'Hello Dolly',
					'Version' => '1.7.2',
				],
			]
		);

		$data = $this->fetch_catalogue();

		$this->clear_seeded_plugins();

		$slugs = wp_list_pluck( $data['plugins'], 'slug' );
		$this->assertContains( 'hello.php', $slugs );
		$this->assertNotContains( '.', $slugs );
	}

	/**
	 * With no free plugin present, free fields report absence while Pro remains
	 * reported as installed and active.
	 *
	 * @return void
	 */
	public function test_free_absent_when_not_in_plugin_list(): void {
		$this->seed_installed_plugins( [] );

		$block = $this->fetch_catalogue()['code_snippets'];

		$this->clear_seeded_plugins();

		$this->assertTrue( $block['pro_installed'] );
		$this->assertTrue( $block['pro_active'] );
		$this->assertFalse( $block['free_installed'] );
		$this->assertNull( $block['free_version'] );
	}

	/**
	 * No logged-in user and a wrong/absent token is rejected.
	 *
	 * @return void
	 */
	public function test_get_items_with_invalid_token_and_no_user_is_rejected(): void {
		wp_set_current_user( 0 );

		$this->set_connection_local_token( $this->connection, 'test-site-token-abc123' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->add_header( 'Access-Control', 'not-the-right-token' );

		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), [ 401, 403 ] );
	}

	/**
	 * A never-connected site (empty local_token) with no logged-in user and a
	 * present-but-empty Access-Control header must be rejected, not fail open.
	 *
	 * @return void
	 */
	public function test_get_items_with_empty_token_and_empty_header_is_rejected(): void {
		wp_set_current_user( 0 );

		$this->set_connection_local_token( $this->connection, '' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->add_header( 'Access-Control', '' );

		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), [ 401, 403 ] );
	}
}
