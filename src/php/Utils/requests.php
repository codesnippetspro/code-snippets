<?php
/**
 * A collection of utilities for handling REST requests and responses.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Utils;

use WP_Error;

/**
 * Unpack JSON data from a request response.
 *
 * @param array|WP_Error $response Response from wp_request_*.
 *
 * @return array<string, mixed>|null Associative array of JSON data on success, null on failure.
 */
function unpack_response_body( $response ): ?array {
	$body = wp_remote_retrieve_body( $response );

	if ( $body ) {
		$json = json_decode( $body, true );
		return is_array( $json ) ? $json : null;
	}

	return null;
}
