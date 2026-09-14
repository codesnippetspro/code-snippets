<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\UnitTestCase;
use WP_REST_Request;

/**
 * Verify that all four ported cloud classes are wired into the Plugin bootstrap
 * and their REST routes are actually registered on the server.
 *
 * @package Code_Snippets
 */
class Cloud_Routes_Registered_Test extends UnitTestCase {

	/**
	 * Ensure the REST server has processed rest_api_init before inspecting routes.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_routes(): array {
		// Triggering a request forces rest_get_server() to boot and fire rest_api_init,
		// which is what actually registers every controller's routes.
		rest_do_request( new WP_REST_Request( 'GET', '/' ) );

		return rest_get_server()->get_routes();
	}

	/**
	 * All ported cloud routes must be present after the bootstrap wiring.
	 *
	 * @return void
	 */
	public function test_all_ported_cloud_routes_registered(): void {
		$routes = $this->get_routes();

		foreach (
			[
				'/code-snippets/v1/cloud/catalogue',
				'/code-snippets/v1/cloud/plugin',
				'/code-snippets/v1/cloud/license',
				'/code-snippets/v1/cloud/snippets/hashes',
				'/code-snippets/v1/cloud/snippets/(?P<cloud_id>[\w\-]+)/body',
			] as $route
		) {
			$this->assertArrayHasKey( $route, $routes, "missing $route" );
		}
	}

	/**
	 * The snippets deploy route must accept POST alongside the existing methods.
	 *
	 * @return void
	 */
	public function test_snippets_route_accepts_post_for_deploy(): void {
		$routes = $this->get_routes();

		$this->assertArrayHasKey( '/code-snippets/v1/cloud/snippets', $routes );

		$methods = [];
		foreach ( $routes['/code-snippets/v1/cloud/snippets'] as $handler ) {
			if ( isset( $handler['methods'] ) ) {
				$methods += $handler['methods'];
			}
		}

		$this->assertArrayHasKey( 'POST', $methods, 'POST method missing on cloud/snippets route' );
	}
}
