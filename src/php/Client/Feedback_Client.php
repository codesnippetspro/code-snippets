<?php

namespace Code_Snippets\Client;

use Code_Snippets\Model\Feedback_Connection;
use Code_Snippets\Utils\System_Info;
use WP_Error;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Sends feedback reports to Code Snippets Cloud.
 *
 * A site enrols once and signs everything it sends afterwards. Two failures are worth
 * recovering from rather than surfacing: a clock far enough out of step with the cloud that
 * signatures arrive expired, and a credential the cloud no longer recognises. Each is retried
 * once, so a report is not lost to a problem the site can correct on its own.
 *
 * @package Code_Snippets
 */
class Feedback_Client {

	/**
	 * Transient recording that enrolment failed, so an unreachable endpoint is not
	 * contacted again on every request.
	 */
	public const REGISTRATION_FAILURE_TRANSIENT = 'code_snippets_feedback_registration_failed';

	/**
	 * How long to wait, in seconds, before attempting to enrol again.
	 */
	private const REGISTRATION_FAILURE_TIMEOUT = 90;

	/**
	 * Request timeout, in seconds, for enrolment.
	 */
	private const REGISTRATION_REQUEST_TIMEOUT = 5;

	/**
	 * Request timeout, in seconds, for sending a report.
	 */
	private const REPORT_REQUEST_TIMEOUT = 15;

	/**
	 * Request timeout, in seconds, for the duplicate search.
	 */
	private const SEARCH_REQUEST_TIMEOUT = 10;

	/**
	 * Maximum number of similar reports to offer.
	 */
	private const MAX_SEARCH_RESULTS = 5;

	/**
	 * Connection to the reporting API.
	 *
	 * @var Feedback_Connection
	 */
	private Feedback_Connection $connection;

	/**
	 * Class constructor.
	 *
	 * @param Feedback_Connection $connection Connection to the reporting API.
	 */
	public function __construct( Feedback_Connection $connection ) {
		$this->connection = $connection;
	}

	/**
	 * Enrol this site and store the credential it is issued.
	 *
	 * @param int $carry_offset Clock offset to preserve across a re-enrolment.
	 *
	 * @return array<string, mixed> Credential issued, or empty when enrolment failed.
	 */
	public function register_site( int $carry_offset = 0 ): array {
		$response = wp_remote_post(
			$this->connection->get_endpoint_url( 'register' ),
			[
				'timeout' => self::REGISTRATION_REQUEST_TIMEOUT,
				'headers' => $this->connection->get_request_headers(),
				'body'    => wp_json_encode(
					[
						'site_url'       => site_url(),
						'edition'        => System_Info::get_edition(),
						'plugin_version' => PLUGIN_VERSION,
					]
				),
			]
		);

		if ( is_wp_error( $response ) || 201 !== wp_remote_retrieve_response_code( $response ) ) {
			return $this->fail_registration();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$body = is_array( $body ) ? $body : [];

		if ( ! $this->connection->is_valid_credentials( $body ) ) {
			return $this->fail_registration();
		}

		delete_transient( self::REGISTRATION_FAILURE_TRANSIENT );

		$credentials = [
			'public_id' => (string) $body['public_id'],
			'secret'    => (string) $body['secret'],
			'offset'    => $carry_offset,
		];

		$this->connection->save_credentials( $credentials );

		return $credentials;
	}

	/**
	 * Retrieve this site's credential, enrolling first when there is not one.
	 *
	 * @return array<string, mixed> Credential, or empty when enrolment is unavailable.
	 */
	public function ensure_credentials(): array {
		$credentials = $this->connection->get_credentials();

		if ( $this->connection->is_valid_credentials( $credentials ) ) {
			return $credentials;
		}

		if ( get_transient( self::REGISTRATION_FAILURE_TRANSIENT ) ) {
			return [];
		}

		return $this->register_site();
	}

	/**
	 * Forward a report to the cloud.
	 *
	 * @param array<string, mixed> $payload         Assembled report.
	 * @param string               $idempotency_key Key identifying this submission.
	 *
	 * @return array{status: int, body: array<string, mixed>}|WP_Error
	 */
	public function send_report( array $payload, string $idempotency_key ) {
		$headers = $this->connection->get_request_headers();
		$headers['Idempotency-Key'] = $idempotency_key;

		$response = $this->send_signed(
			$this->connection->get_endpoint_url(),
			'POST',
			$headers,
			(string) wp_json_encode( $payload )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return [
			'status' => wp_remote_retrieve_response_code( $response ),
			'body'   => is_array( $body ) ? $body : [],
		];
	}

	/**
	 * Look for existing reports resembling a title being typed.
	 *
	 * @param string $query Title text entered so far.
	 *
	 * @return array<int, array<string, mixed>> Matching reports, empty when none or unavailable.
	 */
	public function search_reports( string $query ): array {
		$url = add_query_arg( [ 'q' => rawurlencode( $query ) ], $this->connection->get_endpoint_url( 'search' ) );
		$response = $this->send_signed( $url, 'GET', $this->connection->get_request_headers(), '' );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return [];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return isset( $body['results'] ) && is_array( $body['results'] )
			? array_slice( $body['results'], 0, self::MAX_SEARCH_RESULTS )
			: [];
	}

	/**
	 * Record that enrolment failed, and report it as unavailable.
	 *
	 * @return array<string, mixed> Always empty.
	 */
	private function fail_registration(): array {
		set_transient( self::REGISTRATION_FAILURE_TRANSIENT, 1, self::REGISTRATION_FAILURE_TIMEOUT );

		return [];
	}

	/**
	 * Send a request signed with this site's credential, recovering once from a rejected
	 * signature.
	 *
	 * @param string                $url      Absolute endpoint URL.
	 * @param string                $method   HTTP method.
	 * @param array<string, string> $headers  Request headers.
	 * @param string                $body     Raw request body, empty for a GET.
	 * @param bool                  $retrying Whether this is the second attempt.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	private function send_signed( string $url, string $method, array $headers, string $body, bool $retrying = false ) {
		$credentials = $this->ensure_credentials();
		$uri = Feedback_Connection::get_request_uri( $url );

		$headers = array_diff_key(
			$headers,
			array_flip( [ 'X-CS-Site-Id', 'X-CS-Timestamp', 'X-CS-Signature' ] )
		);

		if ( $this->connection->is_valid_credentials( $credentials ) ) {
			$headers = array_merge(
				$headers,
				$this->connection->get_signature_headers( $credentials, $method, $uri, $body )
			);
		}

		$args = [
			'timeout'     => 'GET' === $method ? self::SEARCH_REQUEST_TIMEOUT : self::REPORT_REQUEST_TIMEOUT,
			'redirection' => 0,
			'headers'     => $headers,
		];

		if ( 'GET' === $method ) {
			$response = wp_remote_get( $url, $args );
		} else {
			$args['body'] = $body;
			$response = wp_remote_post( $url, $args );
		}

		if ( is_wp_error( $response ) || $retrying ) {
			return $response;
		}

		return $this->recover_from_rejected_signature( $response, $url, $method, $headers, $body );
	}

	/**
	 * Correct whatever the cloud objected to about a signature and try once more.
	 *
	 * @param array<string, mixed>  $response Response received.
	 * @param string                $url      Absolute endpoint URL.
	 * @param string                $method   HTTP method.
	 * @param array<string, string> $headers  Request headers.
	 * @param string                $body     Raw request body.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	private function recover_from_rejected_signature( array $response, string $url, string $method, array $headers, string $body ) {
		if ( 401 !== wp_remote_retrieve_response_code( $response ) ) {
			return $response;
		}

		$parsed = json_decode( wp_remote_retrieve_body( $response ), true );
		$error = isset( $parsed['code'] ) ? $parsed['code'] : '';

		if ( 'signature_expired' === $error && isset( $parsed['server_time'] ) ) {
			$credentials = $this->connection->get_credentials();
			$credentials['offset'] = (int) $parsed['server_time'] - time();
			$this->connection->save_credentials( $credentials );

			return $this->send_signed( $url, $method, $headers, $body, true );
		}

		if ( 'invalid_signature' === $error ) {
			$credentials = $this->connection->get_credentials();
			$offset = isset( $credentials['offset'] ) ? (int) $credentials['offset'] : 0;

			$this->connection->delete_credentials();
			$this->register_site( $offset );

			return $this->send_signed( $url, $method, $headers, $body, true );
		}

		return $response;
	}
}
