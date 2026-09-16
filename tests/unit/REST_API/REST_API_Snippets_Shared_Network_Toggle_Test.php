<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTest_Factory;
use function Code_Snippets\code_snippets;
use function Code_Snippets\save_snippet;

/**
 * Tests for activating and deactivating shared network snippets via the REST API.
 *
 * Shared network snippets live in the network-wide `ms_snippets` table but are
 * activated on a per-site basis through the `active_shared_network_snippets`
 * site option. The REST `…/activate` and `…/deactivate` endpoints must:
 *
 *   1. Allow a regular subsite administrator to toggle the per-site flag for
 *      a shared network snippet.
 *   2. Never mutate the global `active` column on `ms_snippets`, so toggling
 *      on one site cannot accidentally enable the snippet network-wide.
 *   3. Continue to block subsite admins from toggling *exclusive* network
 *      snippets when `network=true` is forged (privilege-escalation guard).
 *
 * @group rest-api
 * @group permissions
 * @group multisite
 */
class REST_API_Snippets_Shared_Network_Toggle_Test extends UnitTestCase {

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
	 * Super administrator user ID.
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
	 * Identifier of the shared network snippet seeded for each test.
	 *
	 * @var int
	 */
	protected int $shared_snippet_id = 0;

	/**
	 * Identifier of an exclusive (non-shared) network snippet seeded for each test.
	 *
	 * @var int
	 */
	protected int $exclusive_network_snippet_id = 0;

	/**
	 * Set up fixture users before any tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$super_admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$subsite_admin_id = $factory->user->create( [ 'role' => 'administrator' ] );

		if ( is_multisite() ) {
			grant_super_admin( self::$super_admin_id );
		}
	}

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Shared network snippets only exist on multisite installs.' );
		}

		// Operate as a super admin while seeding so save_snippet() does not
		// trip permission guards on the underlying table operations.
		wp_set_current_user( self::$super_admin_id );

		delete_option( 'active_shared_network_snippets' );
		delete_site_option( 'shared_network_snippets' );

		$shared = save_snippet(
			new Snippet(
				[
					'name'    => 'Shared Network Snippet Fixture',
					'desc'    => 'Stored in ms_snippets and exposed to subsites.',
					'code'    => "// shared fixture\n",
					'scope'   => 'global',
					'active'  => false,
					'network' => true,
				]
			)
		);

		$this->assertInstanceOf( Snippet::class, $shared );
		$this->shared_snippet_id = $shared->id;

		// Mark the snippet as shared so subsites can opt in / out per-site.
		update_site_option( 'shared_network_snippets', [ $this->shared_snippet_id ] );

		$exclusive = save_snippet(
			new Snippet(
				[
					'name'    => 'Exclusive Network Snippet Fixture',
					'desc'    => 'Network-wide snippet that subsite admins must not be able to toggle.',
					'code'    => "// exclusive fixture\n",
					'scope'   => 'global',
					'active'  => false,
					'network' => true,
				]
			)
		);

		$this->assertInstanceOf( Snippet::class, $exclusive );
		$this->exclusive_network_snippet_id = $exclusive->id;
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
	 * Read the `active` column on `ms_snippets` directly.
	 *
	 * @param int $snippet_id Snippet identifier.
	 *
	 * @return string|null Stored value, or null if the row is missing.
	 */
	protected function read_ms_active_column( int $snippet_id ): ?string {
		global $wpdb;

		$table = code_snippets()->db->ms_table;
		return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare( "SELECT active FROM $table WHERE id = %d", $snippet_id )
		);
	}

	/**
	 * Regression test: a subsite admin must be able to activate a shared network snippet.
	 *
	 * Previously the activate/deactivate endpoints inherited
	 * `update_item_permissions_check`, which required the network capability
	 * for any request carrying `network=true`. The new
	 * `toggle_item_permissions_check` recognises shared network snippets and
	 * accepts the site capability instead.
	 */
	public function test_subsite_admin_can_activate_shared_network_snippet() {
		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'POST',
			"/$this->namespace/$this->base_route/$this->shared_snippet_id/activate",
			[ 'network' => true ]
		);

		$this->assertSame(
			200,
			$response->get_status(),
			'Subsite admin should be able to activate a shared network snippet.'
		);

		$active_shared = get_option( 'active_shared_network_snippets', [] );
		$this->assertContains(
			$this->shared_snippet_id,
			$active_shared,
			'Activation should add the snippet ID to active_shared_network_snippets.'
		);
	}

	/**
	 * Regression test for the original 403: deactivating a shared network snippet
	 * from a subsite must succeed for a subsite admin.
	 */
	public function test_subsite_admin_can_deactivate_shared_network_snippet() {
		update_option(
			'active_shared_network_snippets',
			[ $this->shared_snippet_id ]
		);

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'POST',
			"/$this->namespace/$this->base_route/$this->shared_snippet_id/deactivate",
			[ 'network' => true ]
		);

		$this->assertSame(
			200,
			$response->get_status(),
			'Subsite admin should be able to deactivate a shared network snippet.'
		);

		$active_shared = get_option( 'active_shared_network_snippets', [] );
		$this->assertNotContains(
			$this->shared_snippet_id,
			$active_shared,
			'Deactivation should remove the snippet ID from active_shared_network_snippets.'
		);
	}

	/**
	 * Activating a shared network snippet must NOT flip the global
	 * `ms_snippets.active` column. Doing so would activate the snippet for
	 * every site in the network, which is the bug this test guards against.
	 */
	public function test_activating_shared_snippet_does_not_mutate_ms_snippets_active() {
		$active_before = $this->read_ms_active_column( $this->shared_snippet_id );

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'POST',
			"/$this->namespace/$this->base_route/$this->shared_snippet_id/activate",
			[ 'network' => true ]
		);

		$this->assertSame( 200, $response->get_status() );

		$active_after = $this->read_ms_active_column( $this->shared_snippet_id );

		$this->assertSame(
			$active_before,
			$active_after,
			'Activating a shared snippet must not mutate ms_snippets.active.'
		);
	}

	/**
	 * Companion guard: deactivation must not mutate `ms_snippets.active` either.
	 */
	public function test_deactivating_shared_snippet_does_not_mutate_ms_snippets_active() {
		update_option(
			'active_shared_network_snippets',
			[ $this->shared_snippet_id ]
		);

		$active_before = $this->read_ms_active_column( $this->shared_snippet_id );

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'POST',
			"/$this->namespace/$this->base_route/$this->shared_snippet_id/deactivate",
			[ 'network' => true ]
		);

		$this->assertSame( 200, $response->get_status() );

		$active_after = $this->read_ms_active_column( $this->shared_snippet_id );

		$this->assertSame(
			$active_before,
			$active_after,
			'Deactivating a shared snippet must not mutate ms_snippets.active.'
		);
	}

	/**
	 * The privilege-escalation guard must remain in place for *exclusive*
	 * (non-shared) network snippets: a subsite admin cannot deactivate them
	 * even by sending `network=true`.
	 */
	public function test_subsite_admin_still_blocked_from_toggling_exclusive_network_snippet() {
		wp_set_current_user( self::$subsite_admin_id );

		$activate_response = $this->dispatch(
			'POST',
			"/$this->namespace/$this->base_route/$this->exclusive_network_snippet_id/activate",
			[ 'network' => true ]
		);

		$this->assertContains(
			$activate_response->get_status(),
			[ 401, 403 ],
			'Subsite admin must not be able to activate an exclusive network snippet.'
		);

		$deactivate_response = $this->dispatch(
			'POST',
			"/$this->namespace/$this->base_route/$this->exclusive_network_snippet_id/deactivate",
			[ 'network' => true ]
		);

		$this->assertContains(
			$deactivate_response->get_status(),
			[ 401, 403 ],
			'Subsite admin must not be able to deactivate an exclusive network snippet.'
		);
	}

	/**
	 * A subsite admin should also be able to GET a shared network snippet,
	 * since the snippet is intentionally exposed to subsites and the React UI
	 * refetches it after a toggle.
	 */
	public function test_subsite_admin_can_read_shared_network_snippet() {
		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'GET',
			"/$this->namespace/$this->base_route/$this->shared_snippet_id",
			[ 'network' => true ]
		);

		$this->assertSame(
			200,
			$response->get_status(),
			'Subsite admin should be able to read a shared network snippet.'
		);
	}

	/**
	 * Toggling a shared snippet on a subsite must only affect that site's
	 * `active_shared_network_snippets` option, not other sites'.
	 */
	public function test_activation_is_isolated_to_current_site() {
		$other_site_id = self::factory()->blog->create();

		wp_set_current_user( self::$subsite_admin_id );

		$response = $this->dispatch(
			'POST',
			"/$this->namespace/$this->base_route/$this->shared_snippet_id/activate",
			[ 'network' => true ]
		);

		$this->assertSame( 200, $response->get_status() );

		switch_to_blog( $other_site_id );
		$other_site_active = get_option( 'active_shared_network_snippets', [] );
		restore_current_blog();

		$this->assertNotContains(
			$this->shared_snippet_id,
			$other_site_active,
			'Activation on the current site must not leak into other sites.'
		);
	}
}
