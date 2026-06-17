<?php

namespace Code_Snippets\Tests;

use Code_Snippets\Client\Cloud_API;
use Code_Snippets\Model\Cloud_Snippets;
use WP_Error;

/**
 * Tests for $this->api->fetch_search_results().
 *
 * @group cloud
 */
class Cloud_API_Search_Test extends TestCase {

	/**
	 * API instance.
	 *
	 * @var Cloud_API
	 */
	private Cloud_API $api;

	/**
	 * Number of HTTP requests intercepted during a test.
	 *
	 * @var int
	 */
	private int $http_request_count = 0;

	/**
	 * URL of the most recently intercepted request.
	 *
	 * @var string|null
	 */
	private ?string $last_url = null;

	/**
	 * Response to return from the mock HTTP filter.
	 *
	 * @var array|WP_Error|null
	 */
	private $mock_response = null;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->http_request_count = 0;
		$this->last_url = null;
		$this->mock_response = null;

		$this->api = new Cloud_API();

		add_filter( 'pre_http_request', [ $this, 'mock_search_request' ], 10, 3 );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'mock_search_request' ], 10 );

		parent::tear_down();
	}

	/**
	 * Build a successful mock search response in the cloud API envelope format.
	 *
	 * @param int $count Number of snippets to include on the page.
	 * @param int $total Total number of matching snippets.
	 *
	 * @return array
	 */
	private function build_response( int $count = 2, int $total = 42 ): array {
		$snippets = [];

		for ( $i = 1; $i <= $count; $i++ ) {
			$snippets[] = [
				'id'   => $i,
				'name' => 'Result ' . $i,
			];
		}

		$body = [
			'snippets'          => $snippets,
			'meta'              => [
				'total'       => $total,
				'total_pages' => 5,
				'page'        => 0,
			],
			'available_filters' => [],
			'success'           => true,
		];

		return [
			'headers'  => [],
			'body'     => wp_json_encode( $body ),
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];
	}

	/**
	 * Intercept outbound HTTP requests to the search endpoint.
	 *
	 * @param mixed  $preempt     Existing preempted value.
	 * @param array  $parsed_args Parsed HTTP request arguments.
	 * @param string $url         Requested URL.
	 *
	 * @return mixed
	 */
	public function mock_search_request( $preempt, array $parsed_args, string $url ) {
		if ( false === strpos( $url, 'public/search' ) ) {
			return $preempt;
		}

		$this->http_request_count += 1;
		$this->last_url = $url;

		return null !== $this->mock_response ? $this->mock_response : $this->build_response();
	}

	/**
	 * Parse the query arguments from a request URL.
	 *
	 * @param string|null $url Request URL.
	 *
	 * @return array<string, string>
	 */
	private function query_args( ?string $url ): array {
		$args = [];
		wp_parse_str( (string) wp_parse_url( (string) $url, PHP_URL_QUERY ), $args );

		return $args;
	}

	/**
	 * A successful response is parsed into snippets and a total count.
	 *
	 * @return void
	 */
	public function test_returns_parsed_snippets_and_total(): void {
		$result = $this->api->fetch_search_results( 'term', 'woo' );

		$this->assertInstanceOf( Cloud_Snippets::class, $result );
		$this->assertSame( 1, $this->http_request_count );
		$this->assertCount( 2, $result->snippets );
		$this->assertSame( 42, $result->total_snippets );
	}

	/**
	 * The search request carries the expected query arguments.
	 *
	 * @return void
	 */
	public function test_request_includes_expected_query_args(): void {
		$this->api->fetch_search_results( 'term', 'woo', 2, 10 );
		$args = $this->query_args( $this->last_url );

		$this->assertSame( 'term', $args['s_method'] );
		$this->assertSame( 'woo', $args['s'] );
		$this->assertSame( '10', $args['per_page'] );
		$this->assertSame( '1', $args['page'], 'Page should be sent as a zero-based offset.' );
		$this->assertArrayHasKey( 'site_host', $args );
	}

	/**
	 * The per-page value is capped at the maximum allowed by the API.
	 *
	 * @return void
	 */
	public function test_per_page_is_capped_at_maximum(): void {
		$this->api->fetch_search_results( 'term', 'woo', 1, 500 );
		$args = $this->query_args( $this->last_url );

		$this->assertSame( (string) Cloud_API::MAX_RESULTS_PER_PAGE, $args['per_page'] );
	}

	/**
	 * Non-empty filters are added to the request and empty ones are omitted.
	 *
	 * @return void
	 */
	public function test_filters_are_added_to_request(): void {
		$this->api->fetch_search_results(
			'term',
			'woo',
			1,
			10,
			[
				'category' => '5',
				'type'     => '',
				'status'   => '3',
			]
		);
		$args = $this->query_args( $this->last_url );

		$this->assertSame( '5', $args['category'] );
		$this->assertSame( '3', $args['status'] );
		$this->assertArrayNotHasKey( 'type', $args, 'Empty filter values should be omitted from the request.' );
	}

	/**
	 * A transport error returns an empty result rather than failing.
	 *
	 * @return void
	 */
	public function test_returns_empty_on_http_error(): void {
		$this->mock_response = new WP_Error( 'http_request_failed', 'Operation timed out' );

		$result = $this->api->fetch_search_results( 'term', 'woo' );

		$this->assertCount( 0, $result->snippets );
		$this->assertSame( 0, $result->total_snippets );
	}

	/**
	 * An invalid JSON body returns an empty result.
	 *
	 * @return void
	 */
	public function test_returns_empty_on_invalid_json(): void {
		$this->mock_response = [
			'headers'  => [],
			'body'     => 'not-json',
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];

		$result = $this->api->fetch_search_results( 'term', 'woo' );

		$this->assertCount( 0, $result->snippets );
	}

	/**
	 * A response missing the `data` key returns an empty result.
	 *
	 * @return void
	 */
	public function test_returns_empty_on_missing_data_key(): void {
		$this->mock_response = [
			'headers'  => [],
			'body'     => wp_json_encode( [ 'success' => true ] ),
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];

		$result = $this->api->fetch_search_results( 'term', 'woo' );

		$this->assertCount( 0, $result->snippets );
	}

	/**
	 * The result is tagged with the requested page number.
	 *
	 * @return void
	 */
	public function test_result_page_matches_requested_page(): void {
		$result = $this->api->fetch_search_results( 'term', 'woo', 3 );

		$this->assertSame( 3, $result->page );
	}
}
