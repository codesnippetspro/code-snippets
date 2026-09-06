<?php
/**
 * Tests for the feedback reporting endpoints.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\REST_API;

use Code_Snippets\Admin\Feedback_Panel;
use Code_Snippets\Client\Feedback_Client;
use Code_Snippets\Model\Feedback_Connection;
use Code_Snippets\REST_API\Feedback\Feedback_REST_Controller;
use Code_Snippets\UnitTestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use function Code_Snippets\Settings\update_setting;

/**
 * A report is checked before it leaves the site, the reporter is told why one was refused,
 * and the environment attached to it is collected here rather than trusted from the browser.
 *
 * @group feedback
 */
class Feedback_REST_Controller_Test extends UnitTestCase {

	/**
	 * Route of the reporting endpoint.
	 *
	 * @var string
	 */
	private string $route;

	/**
	 * Responses to return, in order, from the mocked transport.
	 *
	 * @var array<int, array|WP_Error>
	 */
	private array $responses = [];

	/**
	 * Bodies of the requests the client sent.
	 *
	 * @var array<int, string>
	 */
	private array $sent_bodies = [];

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;

		$this->route = '/' . Feedback_REST_Controller::get_base_route();
		$this->responses = [];
		$this->sent_bodies = [];

		update_setting( 'general', Feedback_Panel::SETTING_FIELD, true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$connection = new Feedback_Connection();
		$connection->save_credentials(
			[
				'public_id' => str_repeat( 'a', 20 ),
				'secret'    => str_repeat( 'b', 40 ),
				'offset'    => 0,
			]
		);

		add_filter( 'pre_http_request', [ $this, 'mock_request' ], 10, 3 );

		$wp_rest_server = new WP_REST_Server();
		new Feedback_REST_Controller( new Feedback_Client( $connection ) );
		do_action( 'rest_api_init', $wp_rest_server );
	}

	/**
	 * Remove what a test stored or registered.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $wp_rest_server;

		remove_filter( 'pre_http_request', [ $this, 'mock_request' ] );
		delete_option( Feedback_Connection::CREDENTIALS_OPTION );
		delete_transient( 'code_snippets_feedback_' . get_current_user_id() );
		update_setting( 'general', Feedback_Panel::SETTING_FIELD, false );
		wp_set_current_user( 0 );
		$wp_rest_server = null;

		parent::tear_down();
	}

	/**
	 * Record each request body and answer with the next queued response.
	 *
	 * @param mixed  $preempt Short-circuit value.
	 * @param array  $args    Request arguments.
	 * @param string $url     Request URL.
	 *
	 * @return array|WP_Error
	 */
	public function mock_request( $preempt, $args, $url ) {
		$this->sent_bodies[] = (string) ( $args['body'] ?? '' );

		return array_shift( $this->responses ) ?? $this->cloud_response( 200, [ 'reference' => 'CS-1' ] );
	}

	/**
	 * Build a response of the shape the HTTP API returns.
	 *
	 * @param int   $status HTTP status code.
	 * @param array $body   Response body.
	 *
	 * @return array<string, mixed>
	 */
	private function cloud_response( int $status, array $body ): array {
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
	 * Build a report that passes validation.
	 *
	 * @param array<string, mixed> $overrides Values to replace.
	 *
	 * @return array<string, mixed>
	 */
	private function valid_report( array $overrides = [] ): array {
		return array_merge(
			[
				'type'        => 'bug',
				'title'       => 'Highlighting stops after switching tabs',
				'description' => 'The editor stops highlighting PHP once the Conditions tab is opened.',
				'steps'       => '1. Open a snippet 2. Click Conditions 3. Switch back to Code',
			],
			$overrides
		);
	}

	/**
	 * Send a report to the endpoint.
	 *
	 * @param array<string, mixed> $body Report to send.
	 *
	 * @return \WP_REST_Response
	 */
	private function post_report( array $body ) {
		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_body_params( $body );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Both routes are available once the reporter is switched on.
	 *
	 * @return void
	 */
	public function test_routes_are_registered(): void {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( $this->route, $routes );
		$this->assertArrayHasKey( $this->route . '/search', $routes );
	}

	/**
	 * Somebody who cannot manage snippets cannot report on the site's behalf.
	 *
	 * @return void
	 */
	public function test_request_is_denied_without_the_snippets_capability(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertSame( 403, $this->post_report( $this->valid_report() )->get_status() );
	}

	/**
	 * Switching the reporter off closes the route rather than only hiding the panel.
	 *
	 * @return void
	 */
	public function test_request_is_denied_while_the_setting_is_off(): void {
		update_setting( 'general', Feedback_Panel::SETTING_FIELD, false );

		$this->assertSame( 403, $this->post_report( $this->valid_report() )->get_status() );
	}

	/**
	 * A report has to say what kind of feedback it is.
	 *
	 * @return void
	 */
	public function test_an_unknown_type_is_rejected(): void {
		$response = $this->post_report( $this->valid_report( [ 'type' => 'complaint' ] ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'code_snippets_feedback_type', $response->get_data()['code'] );
	}

	/**
	 * A one-word title does not describe anything.
	 *
	 * @return void
	 */
	public function test_a_short_title_is_rejected(): void {
		$response = $this->post_report( $this->valid_report( [ 'title' => 'Broken' ] ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'code_snippets_feedback_title', $response->get_data()['code'] );
	}

	/**
	 * Neither does a one-word description.
	 *
	 * @return void
	 */
	public function test_a_short_description_is_rejected(): void {
		$response = $this->post_report( $this->valid_report( [ 'description' => 'It broke.' ] ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'code_snippets_feedback_description', $response->get_data()['code'] );
	}

	/**
	 * A bug is only actionable with the steps that produce it.
	 *
	 * @return void
	 */
	public function test_a_bug_without_steps_is_rejected(): void {
		$response = $this->post_report( $this->valid_report( [ 'steps' => '' ] ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'code_snippets_feedback_steps', $response->get_data()['code'] );
	}

	/**
	 * A feature request needs no steps.
	 *
	 * @return void
	 */
	public function test_a_feature_request_does_not_need_steps(): void {
		$response = $this->post_report(
			$this->valid_report(
				[
					'type'  => 'feature',
					'steps' => '',
				]
			)
		);

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * A report the cloud accepts comes back with its reference, and carries the environment
	 * collected on the server.
	 *
	 * @return void
	 */
	public function test_a_valid_report_is_forwarded_with_the_environment_attached(): void {
		$this->responses = [ $this->cloud_response( 200, [ 'reference' => 'CS-42' ] ) ];

		$response = $this->post_report( $this->valid_report() );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['sent'] );
		$this->assertSame( 'CS-42', $response->get_data()['reference'] );

		$sent = json_decode( end( $this->sent_bodies ), true );

		$this->assertSame( 'bug', $sent['report']['type'] );
		$this->assertSame( PHP_VERSION, $sent['environment']['php_version'] );
		$this->assertNotEmpty( $sent['reporter']['email'] );
	}

	/**
	 * The environment is collected on the server, so a request cannot dictate what the
	 * report says about the site it came from.
	 *
	 * @return void
	 */
	public function test_a_forged_environment_is_ignored(): void {
		$this->post_report(
			$this->valid_report(
				[
					'environment' => [
						'php_version' => '0.0.0',
						'site_url'    => 'https://example.invalid',
					],
				]
			)
		);

		$sent = json_decode( end( $this->sent_bodies ), true );

		$this->assertSame( PHP_VERSION, $sent['environment']['php_version'] );
		$this->assertSame( site_url(), $sent['environment']['site_url'] );
	}

	/**
	 * A title written in a non-Latin script is measured in characters, as the panel
	 * measures it, rather than in bytes.
	 *
	 * @return void
	 */
	public function test_a_short_multibyte_title_is_rejected(): void {
		$response = $this->post_report( $this->valid_report( [ 'title' => '短い題名' ] ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'code_snippets_feedback_title', $response->get_data()['code'] );
	}

	/**
	 * The reporter's own details are used when they leave the fields alone.
	 *
	 * @return void
	 */
	public function test_the_reporter_defaults_to_the_current_user(): void {
		$this->post_report( $this->valid_report() );

		$user = wp_get_current_user();
		$sent = json_decode( end( $this->sent_bodies ), true );

		$this->assertSame( $user->display_name, $sent['reporter']['name'] );
		$this->assertSame( $user->user_email, $sent['reporter']['email'] );
	}

	/**
	 * Only the first few captured errors are worth attaching.
	 *
	 * @return void
	 */
	public function test_js_errors_are_capped(): void {
		$errors = [];

		for ( $i = 0; $i < 24; $i++ ) {
			$errors[] = 'Error ' . $i;
		}

		$this->post_report( $this->valid_report( [ 'js_errors' => $errors ] ) );

		$sent = json_decode( end( $this->sent_bodies ), true );

		$this->assertCount( 10, $sent['js_errors'] );
	}

	/**
	 * A second report straight after the first is held back.
	 *
	 * @return void
	 */
	public function test_a_second_report_within_the_throttle_window_is_rejected(): void {
		$this->assertSame( 200, $this->post_report( $this->valid_report() )->get_status() );

		$response = $this->post_report( $this->valid_report() );

		$this->assertSame( 429, $response->get_status() );
		$this->assertSame( 'code_snippets_feedback_throttled', $response->get_data()['code'] );
	}

	/**
	 * A refusal the reporter can act on reaches them in the cloud's own words.
	 *
	 * @return void
	 */
	public function test_a_cloud_client_error_passes_through_with_its_status(): void {
		$this->responses = [
			$this->cloud_response(
				422,
				[
					'code'    => 'title_taken',
					'message' => 'A report with this title already exists.',
				]
			),
		];

		$response = $this->post_report( $this->valid_report() );

		$this->assertSame( 422, $response->get_status() );
		$this->assertSame( 'cloud_title_taken', $response->get_data()['code'] );
		$this->assertSame( 'A report with this title already exists.', $response->get_data()['message'] );
	}

	/**
	 * A cloud fault is reported as a problem with the service, not with the report.
	 *
	 * @return void
	 */
	public function test_a_cloud_server_error_becomes_a_bad_gateway(): void {
		$this->responses = [ $this->cloud_response( 500, [ 'message' => 'Database is down.' ] ) ];

		$response = $this->post_report( $this->valid_report() );

		$this->assertSame( 502, $response->get_status() );
		$this->assertStringNotContainsString( 'Database is down.', $response->get_data()['message'] );
	}

	/**
	 * A cloud that cannot be reached at all is reported as such.
	 *
	 * @return void
	 */
	public function test_a_transport_failure_becomes_a_bad_gateway(): void {
		$this->responses = [ new WP_Error( 'http_request_failed', 'Could not resolve host.' ) ];

		$response = $this->post_report( $this->valid_report() );

		$this->assertSame( 502, $response->get_status() );
		$this->assertSame( 'code_snippets_feedback_transport', $response->get_data()['code'] );
	}

	/**
	 * A blocked or failed request names its cause, since that is the only part anyone can
	 * act on.
	 *
	 * @return void
	 */
	public function test_a_transport_failure_names_its_cause(): void {
		$this->responses = [ new WP_Error( 'http_request_failed', 'cURL error 7: Failed to connect to codesnippets.cloud port 443' ) ];

		$response = $this->post_report( $this->valid_report() );

		$this->assertStringContainsString( 'cURL error 7', $response->get_data()['message'] );
	}

	/**
	 * A title barely started is not worth searching for.
	 *
	 * @return void
	 */
	public function test_search_returns_an_empty_list_for_short_queries(): void {
		$request = new WP_REST_Request( 'GET', $this->route . '/search' );
		$request->set_param( 'q', 'ab' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [], $response->get_data()['results'] );
		$this->assertCount( 0, $this->sent_bodies );
	}

	/**
	 * Similar reports are offered so the same problem is not filed twice.
	 *
	 * @return void
	 */
	public function test_search_offers_similar_reports(): void {
		$this->responses = [
			$this->cloud_response(
				200,
				[
					'results' => [
						[
							'title' => 'Highlighting stops',
							'url'   => 'https://example.com/1',
						],
					],
				]
			),
		];

		$request = new WP_REST_Request( 'GET', $this->route . '/search' );
		$request->set_param( 'q', 'highlighting' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertCount( 1, $response->get_data()['results'] );
	}
}
