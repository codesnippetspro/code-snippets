<?php

namespace Code_Snippets\REST_API;

use WP_Error;
use WP_REST_Request;

/**
 * Shared handling of the `network` request parameter for REST controllers.
 */
trait Network_Scope {

	/**
	 * Determine whether a request targets network-scoped snippets.
	 *
	 * Only the literal boolean `true` (or its common string/integer equivalents)
	 * is treated as a network-scoped request. A missing or null `network` param
	 * means "site-scoped", and must not be escalated to the network capability.
	 *
	 * @param WP_REST_Request|WP_Error $request Full data about the request.
	 *
	 * @return bool
	 */
	protected function is_network_scoped_request( $request ): bool {
		if ( ! is_multisite() || ! $request instanceof WP_REST_Request || ! $request->has_param( 'network' ) ) {
			return false;
		}

		$network = $request->get_param( 'network' );

		if ( is_bool( $network ) ) {
			return $network;
		}

		if ( is_string( $network ) ) {
			return ! in_array( strtolower( $network ), [ '0', 'false', 'no', '' ], true );
		}

		return (bool) $network;
	}
}
