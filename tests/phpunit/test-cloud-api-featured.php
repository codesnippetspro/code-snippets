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
	 * Transient key used by the featured snippets cache.
	 */
	private const TRANSIENT_KEY = 'cs_featured_snippets';

	/**
	 * Number of HTTP requests intercepted during a test.
	 *
	 * @var int
	 */
	private int $http_request_count = 0;

	/**
	 * Response to return from the mock HTTP filter.
	 *
	 * @var array|null
	 */
	private ?array $mock_response = null;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		delete_transient( self::TRANSIENT_KEY );
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
		delete_transient( self::TRANSIENT_KEY );

		parent::tear_down();
	}

	/**
	 * Build a successful mock HTTP response body.
	 *
	 * @param int    $count       Number of snippets to include.
	 * @param string $cached_until ISO datetime for the cached_until field.
	 *
	 * @return array Mock response array compatible with pre_http_request.
	 */
	private function build_success_response( int $count = 3, string $cached_until = '' ): array {
		$snippets = [];

		for ( $i = 1; $i <= $count; $i++ ) {
			$snippets[] = [
				'id'          => $i,
				'name'        => 'Featured Snippet ' . $i,
				'description' => 'A featured snippet.',
				'code'        => '<?php echo "featured";',
				'tags'        => [],
				'scope'       => 'global',
				'status'      => 4,
				'codevault'   => 'FeaturedVault',
				'vote_count'  => '5',
				'updated'     => '2026-03-30 12:00:00',
			];
		}

		$body = [ 'data' => $snippets ];

		if ( $cached_until ) {
			$body['cached_until'] = $cached_until;
		}

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
		if ( false === strpos( $url, 'api/v1/featured' ) ) {
			return $preempt;
		}

		++$this->http_request_count;

		if ( null !== $this->mock_response ) {
			return $this->mock_response;
		}

		return $this->build_success_response( 3, '2026-04-06T12:00:00Z' );
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
		$this->assertFalse( get_transient( self::TRANSIENT_KEY ) );

		Cloud_API::get_featured_snippets();

		$cached = get_transient( self::TRANSIENT_KEY );
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
}
