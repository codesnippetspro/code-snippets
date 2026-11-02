<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\UnitTestCase;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Verify that responses from the plugin's REST routes are marked as uncacheable.
 *
 * @package Code_Snippets
 */
class REST_Cache_Bypass_Test extends UnitTestCase {

	/**
	 * Run a response through the filters the REST server applies before serving it.
	 *
	 * @param string $route  Route the request was made to.
	 * @param string $method HTTP method of the request.
	 *
	 * @return WP_REST_Response
	 */
	private function serve( string $route, string $method = 'GET' ): WP_REST_Response {
		// Hooks added by one test are removed before the next, so the server is
		// rebuilt to fire rest_api_init again and register the bypass afresh.
		$GLOBALS['wp_rest_server'] = null;
		$server = rest_get_server();

		return apply_filters( 'rest_post_dispatch', new WP_REST_Response( [] ), $server, new WP_REST_Request( $method, $route ) );
	}

	/**
	 * Routes of this plugin that the admin screens read repeatedly.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function plugin_routes(): array {
		return [
			'snippets list'       => [ '/code-snippets/v1/snippets' ],
			'single snippet'      => [ '/code-snippets/v1/snippets/12' ],
			'AI agent generation' => [ '/code-snippets/v1/cloud/ai-agent/generations/34' ],
			'cloud catalogue'     => [ '/code-snippets/v1/cloud/catalogue' ],
			'view preference'     => [ '/code-snippets/v1/preferences/snippet-view' ],
		];
	}

	/**
	 * Every plugin route must carry the full set of cache-bypass headers.
	 *
	 * @dataProvider plugin_routes
	 *
	 * @param string $route Route to request.
	 *
	 * @return void
	 */
	public function test_plugin_routes_send_bypass_headers( string $route ): void {
		$headers = $this->serve( $route )->get_headers();

		foreach ( REST_Cache_Bypass::HEADERS as $name => $value ) {
			$this->assertArrayHasKey( $name, $headers, "missing $name on $route" );
			$this->assertSame( $value, $headers[ $name ] );
		}
	}

	/**
	 * Responses to writes are marked as well, so a cache cannot store them either.
	 *
	 * @return void
	 */
	public function test_write_requests_send_bypass_headers(): void {
		$headers = $this->serve( '/code-snippets/v1/snippets/12/activate', 'POST' )->get_headers();

		$this->assertSame( 'no-cache', $headers['X-LiteSpeed-Cache-Control'] ?? null );
	}

	/**
	 * Caching plugins that run inside WordPress are told not to store the response.
	 *
	 * @return void
	 */
	public function test_plugin_routes_signal_cache_plugins(): void {
		$litespeed_calls = did_action( 'litespeed_control_set_nocache' );

		$this->serve( '/code-snippets/v1/snippets' );

		$this->assertTrue( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE );
		$this->assertSame( $litespeed_calls + 1, did_action( 'litespeed_control_set_nocache' ) );
	}

	/**
	 * Routes belonging to WordPress or other plugins are left alone.
	 *
	 * @return void
	 */
	public function test_other_routes_are_untouched(): void {
		$litespeed_calls = did_action( 'litespeed_control_set_nocache' );

		foreach ( [ '/wp/v2/posts', '/', '/code-snippets-extra/v1/items' ] as $route ) {
			$this->assertSame( [], $this->serve( $route )->get_headers(), "headers added to $route" );
		}

		$this->assertSame( $litespeed_calls, did_action( 'litespeed_control_set_nocache' ) );
	}

	/**
	 * The bypass can be switched off with a filter.
	 *
	 * @return void
	 */
	public function test_filter_disables_bypass(): void {
		add_filter( 'code_snippets/rest_api/bypass_cache', '__return_false' );
		$litespeed_calls = did_action( 'litespeed_control_set_nocache' );

		$headers = $this->serve( '/code-snippets/v1/snippets' )->get_headers();

		remove_filter( 'code_snippets/rest_api/bypass_cache', '__return_false' );

		$this->assertSame( [], $headers );
		$this->assertSame( $litespeed_calls, did_action( 'litespeed_control_set_nocache' ) );
	}
}
