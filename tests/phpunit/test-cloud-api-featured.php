<?php

namespace Code_Snippets\Tests;

use Code_Snippets\Client\Cloud_API;
use Code_Snippets\Model\Cloud_Snippets;

/**
 * Tests for Cloud_API::get_featured_snippets().
 *
 * @group cloud
 */
class Cloud_API_Featured_Test extends TestCase {

	/**
	 * Number of HTTP requests intercepted during a test.
	 *
	 * @var int
	 */
	private int $http_request_count = 0;

	/**
	 * Response to return from the mock HTTP filter.
	 *
	 * @var array|\WP_Error|null
	 */
	private $mock_response = null;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->clear_featured_transients();
		$this->http_request_count = 0;
		$this->mock_response = null;

		add_filter( 'pre_http_request', [ $this, 'mock_featured_request' ], 10, 3 );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'mock_featured_request' ], 10 );
		$this->clear_featured_transients();

		parent::tear_down();
	}

	/**
	 * Build the transient key matching Cloud_API's format.
	 *
	 * @param int   $page     Page number.
	 * @param int   $per_page Per-page count.
	 * @param array $filters  Filter params.
	 *
	 * @return string
	 */
	private function transient_key( int $page = 1, int $per_page = 10, array $filters = [] ): string {
		$encoded = wp_json_encode( $filters );
		$hash = md5( false === $encoded ? '' : $encoded );
		return "cs_featured_snippets_p{$page}_pp{$per_page}_{$hash}";
	}

	/**
	 * Clear all featured transients for default params.
	 *
	 * @return void
	 */
	private function clear_featured_transients(): void {
		delete_transient( $this->transient_key() );
	}

	/**
	 * Build a successful mock HTTP response in the new API format.
	 *
	 * @param int $count Number of snippets to include.
	 *
	 * @return array Mock response array compatible with pre_http_request.
	 */
	private function build_success_response( int $count = 3 ): array {
		$snippets = [];

		for ( $i = 1; $i <= $count; $i++ ) {
			$snippets[] = [
				'id'          => $i,
				'name'        => 'Featured Snippet ' . $i,
				'description' => 'A featured snippet.',
				'code'        => '<?php echo "featured";',
				'tags'        => [],
				'scope'       => 'global',
				'language'    => [
					'id' => 2,
					'name' => 'PHP',
				],
				'status'      => 4,
				'codevault'   => 'FeaturedVault',
				'vote_count'  => '5',
				'updated'     => '2026-03-30 12:00:00',
			];
		}

		$body = [
			'data'              => $snippets,
			'snippets'          => $snippets,
			'meta'              => [
				'total'       => $count,
				'total_pages' => 1,
				'page'        => 0,
				'per_page'    => 10,
				'from_cache'  => true,
			],
			'available_filters' => [
				'categories' => [],
				'types'      => [
					[
						'id' => 2,
						'name' => 'PHP',
					],
				],
				'statuses'   => [
					[
						'id' => 4,
						'name' => 'Public',
					],
				],
			],
			'cloud_id_rev'      => [],
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
	 * Intercept outbound HTTP requests to the featured endpoint.
	 *
	 * @param mixed  $preempt     Existing preempted value.
	 * @param array  $parsed_args Parsed HTTP request arguments.
	 * @param string $url         Requested URL.
	 *
	 * @return mixed
	 */
	public function mock_featured_request( $preempt, array $parsed_args, string $url ) {
		if ( false === strpos( $url, 'public/featured' ) ) {
			return $preempt;
		}

		$this->http_request_count += 1;

		if ( null !== $this->mock_response ) {
			return $this->mock_response;
		}

		return $this->build_success_response();
	}

	/**
	 * Verify get_featured_snippets() returns a Cloud_Snippets object.
	 *
	 * @return void
	 */
	public function test_returns_cloud_snippets_object(): void {
		$result = Cloud_API::get_featured_snippets();

		$this->assertInstanceOf( Cloud_Snippets::class, $result );
		$this->assertCount( 3, $result->snippets );
		$this->assertSame( 3, $result->total_snippets );
	}

	/**
	 * The transient is set after the first call.
	 *
	 * @return void
	 */
	public function test_transient_is_set_after_first_call(): void {
		$this->assertFalse( get_transient( $this->transient_key() ) );

		Cloud_API::get_featured_snippets();

		$cached = get_transient( $this->transient_key() );
		$this->assertInstanceOf( Cloud_Snippets::class, $cached );
	}

	/**
	 * The second call returns from the transient without making an HTTP request.
	 *
	 * @return void
	 */
	public function test_second_call_returns_from_transient(): void {
		Cloud_API::get_featured_snippets();
		$this->assertSame( 1, $this->http_request_count );

		$result = Cloud_API::get_featured_snippets();
		$this->assertSame( 1, $this->http_request_count );
		$this->assertInstanceOf( Cloud_Snippets::class, $result );
		$this->assertCount( 3, $result->snippets );
	}

	/**
	 * An HTTP error returns an empty Cloud_Snippets (graceful fallback).
	 *
	 * @return void
	 */
	public function test_returns_empty_on_http_error(): void {
		$this->mock_response = new \WP_Error( 'http_request_failed', 'Connection refused' );

		$result = Cloud_API::get_featured_snippets();

		$this->assertInstanceOf( Cloud_Snippets::class, $result );
		$this->assertCount( 0, $result->snippets );
		$this->assertSame( 0, $result->total_snippets );
	}

	/**
	 * An invalid JSON response returns an empty Cloud_Snippets.
	 *
	 * @return void
	 */
	public function test_returns_empty_on_invalid_json(): void {
		$this->mock_response = [
			'headers'  => [],
			'body'     => 'not json',
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];

		$result = Cloud_API::get_featured_snippets();

		$this->assertInstanceOf( Cloud_Snippets::class, $result );
		$this->assertCount( 0, $result->snippets );
	}

	/**
	 * An empty body returns an empty Cloud_Snippets.
	 *
	 * @return void
	 */
	public function test_returns_empty_on_empty_body(): void {
		$this->mock_response = [
			'headers'  => [],
			'body'     => '',
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];

		$result = Cloud_API::get_featured_snippets();

		$this->assertInstanceOf( Cloud_Snippets::class, $result );
		$this->assertCount( 0, $result->snippets );
	}

	/**
	 * Transient is set using the minimum TTL.
	 *
	 * @return void
	 */
	public function test_transient_is_cached(): void {
		Cloud_API::get_featured_snippets();

		$cached = get_transient( $this->transient_key() );
		$this->assertInstanceOf( Cloud_Snippets::class, $cached );

		delete_transient( $this->transient_key() );
		$this->mock_response = $this->build_success_response( 2 );

		Cloud_API::get_featured_snippets();

		$cached_again = get_transient( $this->transient_key() );
		$this->assertInstanceOf( Cloud_Snippets::class, $cached_again );
	}

	/**
	 * Response missing the data key returns an empty Cloud_Snippets.
	 *
	 * @return void
	 */
	public function test_returns_empty_on_missing_data_key(): void {
		$this->mock_response = [
			'headers'  => [],
			'body'     => wp_json_encode( [ 'status' => 'ok' ] ),
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];

		$result = Cloud_API::get_featured_snippets();

		$this->assertInstanceOf( Cloud_Snippets::class, $result );
		$this->assertCount( 0, $result->snippets );
		$this->assertSame( 0, $result->total_snippets );
	}

	/**
	 * Transient stores an actual Cloud_Snippets instance, not a plain array.
	 *
	 * @return void
	 */
	public function test_transient_stores_cloud_snippets_instance(): void {
		Cloud_API::get_featured_snippets();

		$raw_transient = get_transient( $this->transient_key() );

		$this->assertInstanceOf( Cloud_Snippets::class, $raw_transient );
		$this->assertFalse( is_array( $raw_transient ) );
		$this->assertCount( 3, $raw_transient->snippets );
	}

	/**
	 * Available filters are preserved from the API response.
	 *
	 * @return void
	 */
	public function test_available_filters_preserved(): void {
		$result = Cloud_API::get_featured_snippets();

		$this->assertIsArray( $result->available_filters );
		$this->assertArrayHasKey( 'types', $result->available_filters );
		$this->assertArrayHasKey( 'statuses', $result->available_filters );
	}
}
