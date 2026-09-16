<?php

namespace Code_Snippets\Model;

use Code_Snippets\Utils\System_Info;

/**
 * Connection to the Code Snippets Cloud reporting API.
 *
 * The reporting endpoint is resolved on its own rather than from the cloud API URL the rest
 * of the plugin uses. Those are separate services: a site pointed at a local cloud build for
 * development would otherwise send its reports there too, where nobody would read them, and
 * the reports would fail outright whenever that build was not running.
 *
 * Trust model: the programme key below is public. It ships in the plugin source and identifies
 * the reporting programme, not the site, in the same way as the public token on the parent
 * class. Per-site authenticity comes from the registration handshake instead: a site enrols
 * once, receives a public identifier and a secret, and signs every later request with an
 * HMAC over `timestamp.METHOD.request_uri.sha256(body)`. Binding the signature to the method,
 * path, query string and body gives the cloud replay protection within its accepted timestamp
 * window. The secret is stored without autoloading, never reaches the browser and is never
 * included in a REST response. Rate limiting beyond the reporter's own throttle is the
 * cloud's responsibility.
 *
 * @package Code_Snippets
 */
class Feedback_Connection extends Basic_Cloud_Connection {

	/**
	 * Public key identifying the reporting programme.
	 */
	private const PROGRAMME_KEY = 'csb_nhv937hQa0mbBNyB0n9FTQvXZR6i3d9UA2OAZU2E04lu9loS';

	/**
	 * Host serving the reporting API.
	 */
	private const REPORTS_HOST = 'https://codesnippets.cloud';

	/**
	 * Path of the reporting endpoint, relative to the reporting host.
	 */
	private const REPORTS_PATH = 'api/v1/beta-reports';

	/**
	 * Name of the option holding this site's credential.
	 */
	public const CREDENTIALS_OPTION = 'code_snippets_feedback_credentials';

	/**
	 * Shape of a public identifier issued by the cloud.
	 */
	private const PUBLIC_ID_PATTERN = '/^[A-Za-z0-9_-]{16,128}$/';

	/**
	 * Shape of a secret issued by the cloud.
	 */
	private const SECRET_PATTERN = '/^[A-Za-z0-9]{32,128}$/';

	/**
	 * Retrieve the key identifying the reporting programme.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return apply_filters( 'code_snippets_feedback_key', self::PROGRAMME_KEY );
	}

	/**
	 * Retrieve the host serving the reporting API.
	 *
	 * `CS_BETA_FEEDBACK_HOST` names a reporting service on its own, so it wins over the
	 * cloud URL a site may be pointing elsewhere for unrelated development.
	 *
	 * @return string
	 */
	public function get_host(): string {
		$host = self::REPORTS_HOST;

		if ( defined( 'CS_BETA_FEEDBACK_HOST' ) && CS_BETA_FEEDBACK_HOST ) {
			$host = CS_BETA_FEEDBACK_HOST;
		}

		return untrailingslashit( apply_filters( 'code_snippets_feedback_host', $host ) );
	}

	/**
	 * Retrieve the URL of a reporting endpoint.
	 *
	 * @param string $path Optional path below the reporting endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint_url( string $path = '' ): string {
		$url = sprintf( '%s/%s', $this->get_host(), self::REPORTS_PATH );

		if ( $path ) {
			$url .= '/' . ltrim( $path, '/' );
		}

		return apply_filters( 'code_snippets_feedback_endpoint_url', $url, $path );
	}

	/**
	 * Retrieve the credential issued to this site.
	 *
	 * @return array<string, mixed> Empty when this site has not enrolled.
	 */
	public function get_credentials(): array {
		$stored = get_option( self::CREDENTIALS_OPTION, [] );

		return is_array( $stored ) ? $stored : [];
	}

	/**
	 * Store the credential issued to this site.
	 *
	 * @param array<string, mixed> $credentials Credential to store.
	 *
	 * @return void
	 */
	public function save_credentials( array $credentials ): void {
		update_option( self::CREDENTIALS_OPTION, $credentials, false );
	}

	/**
	 * Discard the credential issued to this site.
	 *
	 * @return void
	 */
	public function delete_credentials(): void {
		delete_option( self::CREDENTIALS_OPTION );
	}

	/**
	 * Determine whether a credential has the shape the cloud issues.
	 *
	 * @param array<string, mixed> $credentials Credential to check.
	 *
	 * @return bool
	 */
	public function is_valid_credentials( array $credentials ): bool {
		return ! empty( $credentials['public_id'] ) && ! empty( $credentials['secret'] )
			&& preg_match( self::PUBLIC_ID_PATTERN, (string) $credentials['public_id'] )
			&& preg_match( self::SECRET_PATTERN, (string) $credentials['secret'] );
	}

	/**
	 * Create the headers common to every reporting request.
	 *
	 * @return array<string, string>
	 */
	public function get_request_headers(): array {
		$headers = [
			'Content-Type' => 'application/json; charset=utf-8',
			'Accept'       => 'application/json',
			'X-CS-Site'    => site_url(),
			'X-CS-Edition' => System_Info::get_edition(),
		];

		$key = $this->get_key();

		if ( $key ) {
			$headers['Authorization'] = 'Bearer ' . $key;
		}

		return $headers;
	}

	/**
	 * Create the headers proving a request came from this site.
	 *
	 * @param array<string, mixed> $credentials Credential issued to this site.
	 * @param string               $method      HTTP method.
	 * @param string               $uri         Request path, including any query string.
	 * @param string               $body        Raw request body, empty for a GET.
	 *
	 * @return array<string, string>
	 */
	public function get_signature_headers( array $credentials, string $method, string $uri, string $body ): array {
		$offset = isset( $credentials['offset'] ) ? (int) $credentials['offset'] : 0;
		$timestamp = (string) ( time() + $offset );
		$payload = $timestamp . '.' . strtoupper( $method ) . '.' . $uri . '.' . hash( 'sha256', $body );

		return [
			'X-CS-Site-Id'   => (string) $credentials['public_id'],
			'X-CS-Timestamp' => $timestamp,
			'X-CS-Signature' => hash_hmac( 'sha256', $payload, (string) $credentials['secret'] ),
		];
	}

	/**
	 * Reduce a URL to the part a signature covers.
	 *
	 * @param string $url Absolute URL.
	 *
	 * @return string Path, followed by the query string when there is one.
	 */
	public static function get_request_uri( string $url ): string {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$query = wp_parse_url( $url, PHP_URL_QUERY );

		return $query ? $path . '?' . $query : $path;
	}
}
