<?php

namespace Code_Snippets\Model;

use WP_REST_Request;

/**
 * Represents an unauthenticated connection to Code Snippets Cloud.
 */
class Basic_Cloud_Connection {

	/**
	 * Token used for public API access.
	 *
	 * @var string
	 */
	private const PUBLIC_API_TOKEN = 'csc-1a2b3c4d5e6f7g8h9i0j';

	/**
	 * Base URL for Code Snippets Cloud.
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * Base URL for Code Snippets Cloud API.
	 *
	 * @var string
	 */
	private string $api_url;

	/**
	 * Class constructor.
	 *
	 * @noinspection PhpUndefinedConstantInspection
	 */
	public function __construct() {
		$this->base_url = defined( 'CS_CLOUD_URL' )
			? untrailingslashit( CS_CLOUD_URL )
			: 'https://codesnippets.cloud';

		$this->api_url = defined( 'CS_CLOUD_API_URL' )
			? untrailingslashit( CS_CLOUD_API_URL )
			: sprintf( '%s/api/v1', $this->base_url );
	}

	/**
	 * Retrieve base URL for Code Snippets Cloud.
	 *
	 * @return string
	 */
	public function get_base_url(): string {
		return $this->base_url;
	}

	/**
	 * Retrieve base URL for Code Snippets Cloud API.
	 *
	 * @return string
	 */
	public function get_api_url(): string {
		return $this->api_url;
	}

	/**
	 * Retrieve the cloud local token.
	 *
	 * @return string
	 */
	public function get_local_token(): string {
		return self::PUBLIC_API_TOKEN;
	}

	/**
	 * Generate the client ID from the current local token and site URL.
	 *
	 * @return string Client ID.
	 */
	public function get_client_id(): string {
		$site_host = wp_parse_url( get_site_url(), PHP_URL_HOST );
		return sprintf( '%s-%s', $site_host, $this->get_local_token() );
	}

	/**
	 * Determine whether this connection is suitable for requests requiring authentication.
	 *
	 * @return bool
	 */
	public function is_authenticated(): bool {
		return false;
	}

	/**
	 * Create a list of headers required for a cloud request.
	 *
	 * @return array<string, string> Header name and value pairs.
	 */
	public function get_request_headers(): array {
		return [
			'Local-Token' => $this->get_local_token(),
		];
	}

	/**
	 * Verify a REST API request includes the correct Access-Control header.
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 *
	 * @return bool
	 */
	public function verify_rest_request( WP_REST_Request $request ): bool {
		return $request->get_header( 'Access-Control' ) === $this->get_local_token();
	}
}
