<?php

namespace Code_Snippets\REST_API;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use const Code_Snippets\REST_API_NAMESPACE;

/**
 * Keeps responses from this plugin's REST API routes out of page caches.
 *
 * Every route reports live state that the admin screens read again after each
 * change, so a stored response replayed by a cache shows data that is no longer true.
 */
class REST_Cache_Bypass {

	/**
	 * Headers read by caches that sit in front of WordPress and never run its PHP hooks.
	 *
	 * @var array<string, string>
	 */
	public const HEADERS = [
		// Browsers and any cache that follows the HTTP caching rules.
		'Cache-Control'                => 'no-store, no-cache, must-revalidate, max-age=0, private',
		'Pragma'                       => 'no-cache',
		'Expires'                      => 'Wed, 11 Jan 1984 05:00:00 GMT',
		// LiteSpeed and OpenLiteSpeed servers, which ignore Cache-Control.
		'X-LiteSpeed-Cache-Control'    => 'no-cache',
		// Nginx FastCGI and proxy caches.
		'X-Accel-Expires'              => '0',
		// Varnish, Fastly and other surrogate caches.
		'Surrogate-Control'            => 'no-store',
		// Cloudflare and CDNs that follow the targeted cache control standard.
		'CDN-Cache-Control'            => 'no-store',
		'Cloudflare-CDN-Cache-Control' => 'no-store',
		// SiteGround dynamic cache.
		'X-Cache-Enabled'              => 'False',
	];

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'rest_post_dispatch', [ $this, 'bypass_cache' ], 10, 3 );
	}

	/**
	 * Mark the response to a request for one of this plugin's routes as uncacheable.
	 *
	 * @param WP_REST_Response|WP_Error $response Response about to be served.
	 * @param WP_REST_Server            $_server  REST server instance.
	 * @param WP_REST_Request|WP_Error  $request  Request the response answers.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function bypass_cache( $response, WP_REST_Server $_server, $request ) {
		if ( ! $response instanceof WP_HTTP_Response || ! $request instanceof WP_REST_Request ) {
			return $response;
		}

		if ( 0 !== strpos( $request->get_route(), '/' . REST_API_NAMESPACE ) ) {
			return $response;
		}

		/**
		 * Filters whether responses from the Code Snippets REST API are kept out of page caches.
		 *
		 * @param bool            $bypass  Whether to mark the response as uncacheable.
		 * @param WP_REST_Request $request Request the response answers.
		 */
		if ( ! apply_filters( 'code_snippets/rest_api/bypass_cache', true, $request ) ) {
			return $response;
		}

		foreach ( self::HEADERS as $name => $value ) {
			$response->header( $name, $value );
		}

		$this->signal_cache_plugins();

		return $response;
	}

	/**
	 * Tell caching plugins running inside WordPress not to store the current response.
	 *
	 * @return void
	 */
	private function signal_cache_plugins(): void {
		// Honoured by WP Super Cache, W3 Total Cache, WP Rocket, WP Fastest Cache,
		// WP-Optimize, Hummingbird, Cache Enabler, Breeze and LiteSpeed Cache.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		// LiteSpeed Cache decides from its own control API, and caches REST responses by default.
		do_action( 'litespeed_control_set_nocache', 'Code Snippets REST API response' );
	}
}
