<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTest_Factory;
use function Code_Snippets\save_snippet;

/**
 * Tests for permission checks on the Snippets REST API endpoint.
 *
 * These tests verify that the `network` parameter in the request payload is
 * explicitly validated against the `manage_network_options` capability, so
 * that a subsite administrator cannot escalate to network-scoped operations
 * by forging `network=true`.
 *
 * @group rest-api
 * @group permissions
 */
class REST_API_Snippets_Permissions_Test extends UnitTestCase {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected string $namespace = 'code-snippets/v1';

	/**
	 * REST API base route.
	 *
	 * @var string
	 */
	protected string $base_route = 'snippets';

	/**
	 * Super administrator user ID (multisite) or administrator (single site).
	 *
	 * @var int
	 */
	protected static int $super_admin_id;

	/**
	 * Subsite administrator user ID.
	 *
	 * @var int
	 */
	protected static int $subsite_admin_id;

	/**
	 * Editor user ID (no snippet capabilities).
	 *
	 * @var int
	 */
	protected static int $editor_id;

	/**
	 * Shared fixture snippet id (site-scoped).
	 *
	 * @var int
	 */
	protected int $site_snippet_id;

	/**
	 * Shared fixture snippet id (network-scoped). 0 on single site installs.
	 *
	 * @var int
	 */
	protected int $network_snippet_id = 0;

	/**
	 * Set up fixture users before any tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$super_admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$subsite_admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$editor_id = $factory->user->create( [ 'role' => 'editor' ] );

		if ( is_multisite() ) {
			grant_super_admin( self::$super_admin_id );
		}
	}

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$super_admin_id );

		$site_snippet = new Snippet(
			[
				'name'   => 'Site Snippet Fixture',
				'desc'   => 'Fixture snippet for permission tests.',
				'code'   => "// site fixture\n",
				'scope'  => 'global',
				'active' => false,
			]
		);

		$saved_site = save_snippet( $site_snippet );
		$this->assertInstanceOf( Snippet::class, $saved_site );
		$this->site_snippet_id = $saved_site->id;

		if ( is_multisite() ) {
			$network_snippet = new Snippet(
				[
					'name'    => 'Network Snippet Fixture',
					'desc'    => 'Fixture snippet for permission tests (network).',
					'code'    => "// network fixture\n",
					'scope'   => 'global',
					'active'  => false,
					'network' => true,
				]
			);

			$saved_network = save_snippet( $network_snippet );
			$this->assertInstanceOf( Snippet::class, $saved_network );
			$this->network_snippet_id = $saved_network->id;
		}
	}

	/**
	 * Dispatch a REST request and return the raw response object.
	 *
	 * @param string               $method   HTTP method.
	 * @param string               $endpoint Endpoint path.
	 * @param array<string, mixed> $params   Request parameters.
	 *
	 * @return WP_REST_Response
	 */
	protected function dispatch( string $method, string $endpoint, array $params = [] ): WP_REST_Response {
		$request = new WP_REST_Request( $method, $endpoint );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_do_request( $request );
	}

	/**
	 * Test that an editor (no snippets cap) is blocked on every endpoint, regardless of network flag.
	 */
	public function test_editor_is_always_blocked() {
		wp_set_current_user( self::$editor_id );

		$response = $this->dispatch( 'GET', "/$this->namespace/$this->base_route" );
		$this->assert_forbidden_or_unauthorised( $response );

		$response = $this->dispatch( 'GET', "/$this->namespace/$this->base_route", [ 'network' => true ] );
		$this->assert_forbidden_or_unauthorised( $response );
	}

	/**
	 * Test that the schema route remains publicly accessible.
	 */
	public function test_schema_route_is_public() {
		wp_set_current_user( 0 );

		$response = $this->dispatch( 'GET', "/$this->namespace/$this->base_route/schema" );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Test that a site administrator can list site-scoped snippets.
	 */
	public function test_site_admin_can_list_site_snippets() {
		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'GET',
			"/$this->namespace/$this->base_route",
			[ 'network' => false ]
		);

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Test that an omitted `network` param defaults to site-scoped and is allowed.
	 */
	public function test_site_admin_can_list_with_omitted_network_param() {
		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch( 'GET', "/$this->namespace/$this->base_route" );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Test that a site administrator without the network cap is blocked when `network=true`.
	 *
	 * This is the core vulnerability: forging `network=true` in the payload must not
	 * escalate a subsite admin to network-scoped operations.
	 */
	public function test_site_admin_is_blocked_from_network_scoped_list() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'GET',
			"/$this->namespace/$this->base_route",
			[ 'network' => true ]
		);

		$this->assert_forbidden_or_unauthorised( $response );
	}

	/**
	 * Test that a site admin cannot read a specific network-scoped snippet via forged network=true.
	 */
	public function test_site_admin_is_blocked_from_network_scoped_get_item() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'GET',
			"/$this->namespace/$this->base_route/$this->network_snippet_id",
			[ 'network' => true ]
		);

		$this->assert_forbidden_or_unauthorised( $response );
	}

	/**
	 * Test that a site admin cannot create a network snippet via forged network=true.
	 */
	public function test_site_admin_is_blocked_from_creating_network_snippet() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'POST',
			"/$this->namespace/$this->base_route",
			[
				'name'    => 'Forged Network Snippet',
				'code'    => "// forged\n",
				'scope'   => 'global',
				'active'  => false,
				'network' => true,
			]
		);

		$this->assert_forbidden_or_unauthorised( $response );
	}

	/**
	 * Test that a site admin cannot update a network snippet via forged network=true.
	 */
	public function test_site_admin_is_blocked_from_updating_network_snippet() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'POST',
			"/$this->namespace/$this->base_route/$this->network_snippet_id",
			[
				'name'    => 'Hijacked',
				'network' => true,
			]
		);

		$this->assert_forbidden_or_unauthorised( $response );
	}

	/**
	 * Test that a site admin cannot delete a network snippet via forged network=true.
	 */
	public function test_site_admin_is_blocked_from_deleting_network_snippet() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'DELETE',
			"/$this->namespace/$this->base_route/$this->network_snippet_id",
			[ 'network' => true ]
		);

		$this->assert_forbidden_or_unauthorised( $response );
	}

	/**
	 * Test that a site admin cannot activate a network snippet via forged network=true.
	 */
	public function test_site_admin_is_blocked_from_activating_network_snippet() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'POST',
			"/$this->namespace/$this->base_route/$this->network_snippet_id/activate",
			[ 'network' => true ]
		);

		$this->assert_forbidden_or_unauthorised( $response );
	}

	/**
	 * Test that a site admin cannot deactivate a network snippet via forged network=true.
	 */
	public function test_site_admin_is_blocked_from_deactivating_network_snippet() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'POST',
			"/$this->namespace/$this->base_route/$this->network_snippet_id/deactivate",
			[ 'network' => true ]
		);

		$this->assert_forbidden_or_unauthorised( $response );
	}

	/**
	 * Test that a site admin cannot export a network snippet via forged network=true.
	 */
	public function test_site_admin_is_blocked_from_exporting_network_snippet() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'GET',
			"/$this->namespace/$this->base_route/$this->network_snippet_id/export",
			[ 'network' => true ]
		);

		$this->assert_forbidden_or_unauthorised( $response );
	}

	/**
	 * Test that a site admin cannot restore a trashed network snippet via forged network=true.
	 */
	public function test_site_admin_is_blocked_from_restoring_network_snippet() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'POST',
			"/$this->namespace/$this->base_route/$this->network_snippet_id/restore",
			[ 'network' => true ]
		);

		$this->assert_forbidden_or_unauthorised( $response );
	}

	/**
	 * Test that stringly-typed truthy values also trigger the network capability check.
	 *
	 * @dataProvider provide_truthy_network_values
	 *
	 * @param mixed $value Payload value for `network`.
	 */
	public function test_forged_truthy_string_values_are_blocked( $value ) {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'GET',
			"/$this->namespace/$this->base_route",
			[ 'network' => $value ]
		);

		$this->assert_forbidden_or_unauthorised(
			$response,
			"Expected forged network=$value to be blocked."
		);
	}

	/**
	 * Data provider for truthy `network` variants a client might send.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function provide_truthy_network_values(): array {
		return [
			'boolean true'      => [ true ],
			'string "true"'     => [ 'true' ],
			'string "1"'        => [ '1' ],
			'integer 1'         => [ 1 ],
			'string "yes"'      => [ 'yes' ],
			'string "on"'       => [ 'on' ],
			'string "anything"' => [ 'anything' ],
		];
	}

	/**
	 * Test that falsy network values remain site-scoped and are allowed for site admins.
	 *
	 * @dataProvider provide_falsy_network_values
	 *
	 * @param mixed $value Payload value for `network`.
	 */
	public function test_site_admin_allowed_for_falsy_network_values( $value ) {
		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'GET',
			"/$this->namespace/$this->base_route",
			[ 'network' => $value ]
		);

		$this->assertSame(
			200,
			$response->get_status(),
			"Expected network=$value to be treated as site-scoped and allowed."
		);
	}

	/**
	 * Data provider for falsy `network` variants.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function provide_falsy_network_values(): array {
		return [
			'boolean false'  => [ false ],
			'string "false"' => [ 'false' ],
			'string "0"'     => [ '0' ],
			'integer 0'      => [ 0 ],
		];
	}

	/**
	 * Test that a super administrator with manage_network_options can perform network-scoped operations.
	 */
	public function test_super_admin_can_perform_network_scoped_operations() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$super_admin_id );

		$response = $this->dispatch(
			'GET',
			"/$this->namespace/$this->base_route",
			[ 'network' => true ]
		);

		$this->assertSame( 200, $response->get_status() );

		$response = $this->dispatch(
			'GET',
			"/$this->namespace/$this->base_route/$this->network_snippet_id",
			[ 'network' => true ]
		);

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Assert that a REST response indicates an auth failure.
	 *
	 * WordPress returns 401 if no user is logged in, otherwise 403.
	 *
	 * @param WP_REST_Response $response Response under test.
	 * @param string           $message  Optional failure message.
	 */
	protected function assert_forbidden_or_unauthorised( WP_REST_Response $response, string $message = '' ) {
		$status = $response->get_status();

		$this->assertContains(
			$status,
			[ 401, 403 ],
			$message
				? $message
				: sprintf( 'Expected 401 or 403, got %d', $status )
		);
	}
}
