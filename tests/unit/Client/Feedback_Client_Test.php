<?php
/**
 * Tests for the feedback reporting client.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Client;

use Code_Snippets\Model\Feedback_Connection;
use Code_Snippets\UnitTestCase;
use WP_Error;

/**
 * Enrolment happens once, signatures the cloud rejects are corrected once, and a cloud that
 * cannot be reached never blocks the panel.
 *
 * @group feedback
 */
class Feedback_Client_Test extends UnitTestCase {

	/**
	 * Client under test.
	 *
	 * @var Feedback_Client
	 */
	private Feedback_Client $client;

	/**
	 * Connection backing the client.
	 *
	 * @var Feedback_Connection
	 */
	private Feedback_Connection $connection;

	/**
	 * Responses to return, in order, from the mocked transport.
	 *
	 * @var array<int, array|WP_Error>
	 */
	private array $responses = [];

	/**
	 * Requests the client made, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $requests = [];

	/**
	 * A credential pair of the shape the cloud issues.
	 *
	 * @var array<string, mixed>
	 */
	private array $credentials;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->connection = new Feedback_Connection();
		$this->client = new Feedback_Client( $this->connection );
		$this->responses = [];
		$this->requests = [];
		$this->credentials = [
			'public_id' => str_repeat( 'a', 20 ),
			'secret'    => str_repeat( 'b', 40 ),
			'offset'    => 0,
		];

		add_filter( 'pre_http_request', [ $this, 'mock_request' ], 10, 3 );
	}

	/**
	 * Remove what a test stored or registered.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'mock_request' ] );
		delete_option( Feedback_Connection::CREDENTIALS_OPTION );
		delete_transient( Feedback_Client::REGISTRATION_FAILURE_TRANSIENT );

		parent::tear_down();
	}

	/**
	 * Record each request and answer it with the next queued response.
	 *
	 * @param mixed  $preempt Short-circuit value.
	 * @param array  $args    Request arguments.
	 * @param string $url     Request URL.
	 *
	 * @return array|WP_Error
	 */
	public function mock_request( $preempt, $args, $url ) {
		$this->requests[] = [
			'url'     => $url,
			'method'  => $args['method'] ?? 'GET',
			'headers' => $args['headers'] ?? [],
			'body'    => $args['body'] ?? '',
		];

		return array_shift( $this->responses ) ?? $this->response( 200, [] );
	}

	/**
	 * Build a response of the shape the HTTP API returns.
	 *
	 * @param int   $status HTTP status code.
	 * @param array $body   Response body.
	 *
	 * @return array<string, mixed>
	 */
	private function response( int $status, array $body ): array {
		return [
			'headers'  => [],
			'body'     => (string) wp_json_encode( $body ),
			'response' => [
				'code'    => $status,
				'message' => '',
			],
			'cookies'  => [],
			'filename' => null,
		];
	}

	/**
	 * A response enrolling this site successfully.
	 *
	 * @return array<string, mixed>
	 */
	private function registration_response(): array {
		return $this->response(
			201,
			[
				'public_id' => $this->credentials['public_id'],
				'secret'    => $this->credentials['secret'],
			]
		);
	}

	/**
	 * Enrolment stores the credential the cloud issues.
	 *
	 * @return void
	 */
	public function test_registration_stores_the_issued_credential(): void {
		$this->responses = [ $this->registration_response() ];

		$credentials = $this->client->register_site();

		$this->assertSame( $this->credentials['public_id'], $credentials['public_id'] );
		$this->assertSame( $this->credentials, $this->connection->get_credentials() );
		$this->assertStringEndsWith( '/beta-reports/register', $this->requests[0]['url'] );
	}

	/**
	 * A credential that is not of the issued shape is discarded rather than stored.
	 *
	 * @return void
	 */
	public function test_registration_rejects_a_malformed_credential(): void {
		$this->responses = [
			$this->response(
				201,
				[
					'public_id' => 'too-short',
					'secret'    => $this->credentials['secret'],
				]
			),
		];

		$this->assertSame( [], $this->client->register_site() );
		$this->assertSame( [], $this->connection->get_credentials() );
		$this->assertNotFalse( get_transient( Feedback_Client::REGISTRATION_FAILURE_TRANSIENT ) );
	}

	/**
	 * An endpoint that just refused enrolment is left alone for a while.
	 *
	 * @return void
	 */
	public function test_failed_enrolment_is_not_retried_immediately(): void {
		$this->responses = [ $this->response( 500, [] ) ];

		$this->assertSame( [], $this->client->register_site() );
		$this->assertSame( [], $this->client->ensure_credentials() );
		$this->assertCount( 1, $this->requests );
	}

	/**
	 * A site with a valid credential does not enrol again.
	 *
	 * @return void
	 */
	public function test_an_existing_credential_is_reused(): void {
		$this->connection->save_credentials( $this->credentials );

		$this->assertSame( $this->credentials, $this->client->ensure_credentials() );
		$this->assertCount( 0, $this->requests );
	}

	/**
	 * A report is signed, and carries the key identifying the submission.
	 *
	 * @return void
	 */
	public function test_reports_are_signed_and_carry_the_idempotency_key(): void {
		$this->connection->save_credentials( $this->credentials );
		$this->responses = [ $this->response( 200, [ 'reference' => 'CS-1' ] ) ];

		$result = $this->client->send_report( [ 'report' => [ 'title' => 'A title' ] ], 'key-1' );

		$this->assertSame( 200, $result['status'] );
		$this->assertSame( 'CS-1', $result['body']['reference'] );

		$headers = $this->requests[0]['headers'];

		$this->assertSame( 'key-1', $headers['Idempotency-Key'] );
		$this->assertSame( $this->credentials['public_id'], $headers['X-CS-Site-Id'] );
		$this->assertNotEmpty( $headers['X-CS-Signature'] );
	}

	/**
	 * A clock out of step with the cloud is corrected, and the report is sent again.
	 *
	 * @return void
	 */
	public function test_an_expired_signature_stores_the_offset_and_retries_once(): void {
		$this->connection->save_credentials( $this->credentials );

		$server_time = time() + 4000;

		$this->responses = [
			$this->response(
				401,
				[
					'code'        => 'signature_expired',
					'server_time' => $server_time,
				]
			),
			$this->response( 200, [ 'reference' => 'CS-2' ] ),
		];

		$result = $this->client->send_report( [], 'key-2' );

		$this->assertSame( 200, $result['status'] );
		$this->assertCount( 2, $this->requests );
		$this->assertGreaterThan( 3900, $this->connection->get_credentials()['offset'] );
	}

	/**
	 * A credential the cloud no longer recognises is replaced, keeping the clock correction.
	 *
	 * @return void
	 */
	public function test_an_invalid_signature_reenrols_carrying_the_offset(): void {
		$this->credentials['offset'] = 120;
		$this->connection->save_credentials( $this->credentials );

		$this->responses = [
			$this->response( 401, [ 'code' => 'invalid_signature' ] ),
			$this->registration_response(),
			$this->response( 200, [ 'reference' => 'CS-3' ] ),
		];

		$result = $this->client->send_report( [], 'key-3' );

		$this->assertSame( 200, $result['status'] );
		$this->assertCount( 3, $this->requests );
		$this->assertStringEndsWith( '/beta-reports/register', $this->requests[1]['url'] );
		$this->assertSame( 120, $this->connection->get_credentials()['offset'] );
	}

	/**
	 * Recovery is attempted once, so a cloud that keeps refusing does not loop.
	 *
	 * @return void
	 */
	public function test_a_rejected_signature_is_only_recovered_from_once(): void {
		$this->connection->save_credentials( $this->credentials );

		$this->responses = [
			$this->response(
				401,
				[
					'code'        => 'signature_expired',
					'server_time' => time() + 900,
				]
			),
			$this->response(
				401,
				[
					'code'        => 'signature_expired',
					'server_time' => time() + 900,
				]
			),
		];

		$result = $this->client->send_report( [], 'key-4' );

		$this->assertSame( 401, $result['status'] );
		$this->assertCount( 2, $this->requests );
	}

	/**
	 * A response the cloud rejects for its own reasons is handed back unchanged.
	 *
	 * @return void
	 */
	public function test_a_client_error_is_returned_with_its_status(): void {
		$this->connection->save_credentials( $this->credentials );
		$this->responses = [ $this->response( 422, [ 'message' => 'Title too short.' ] ) ];

		$result = $this->client->send_report( [], 'key-5' );

		$this->assertSame( 422, $result['status'] );
		$this->assertSame( 'Title too short.', $result['body']['message'] );
	}

	/**
	 * A transport failure is passed on rather than swallowed.
	 *
	 * @return void
	 */
	public function test_a_transport_failure_is_returned_as_an_error(): void {
		$this->connection->save_credentials( $this->credentials );
		$this->responses = [ new WP_Error( 'http_request_failed', 'Could not resolve host.' ) ];

		$this->assertWPError( $this->client->send_report( [], 'key-6' ) );
	}

	/**
	 * The panel is offered a handful of similar reports, not the whole list.
	 *
	 * @return void
	 */
	public function test_search_returns_at_most_five_results(): void {
		$this->connection->save_credentials( $this->credentials );

		$results = [];

		for ( $i = 0; $i < 9; $i++ ) {
			$results[] = [
				'title' => 'Report ' . $i,
				'url'   => 'https://example.com/' . $i,
			];
		}

		$this->responses = [ $this->response( 200, [ 'results' => $results ] ) ];

		$this->assertCount( 5, $this->client->search_reports( 'highlighting' ) );
		$this->assertStringContainsString( 'q=highlighting', $this->requests[0]['url'] );
	}

	/**
	 * A search the cloud cannot answer leaves the panel with nothing to show.
	 *
	 * @return void
	 */
	public function test_search_returns_an_empty_list_when_the_cloud_fails(): void {
		$this->connection->save_credentials( $this->credentials );
		$this->responses = [ $this->response( 503, [] ) ];

		$this->assertSame( [], $this->client->search_reports( 'highlighting' ) );
	}
}
