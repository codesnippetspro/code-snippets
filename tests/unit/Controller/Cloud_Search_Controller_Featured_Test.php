<?php

namespace Code_Snippets\Controller;

use Code_Snippets\Model\Basic_Cloud_Connection;
use Code_Snippets\Model\Cloud_Snippets;
use Code_Snippets\UnitTestCase;
use WP_Error;

/**
 * Tests for Cloud_Search_Controller::get_featured_snippets().
 *
 * @group cloud
 */
class Cloud_Search_Controller_Featured_Test extends UnitTestCase {

	/**
	 * Number of HTTP requests intercepted during a test.
	 *
	 * @var int
	 */
	private int $http_request_count = 0;

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

		$this->clear_featured_transients();
		$this->http_request_count = 0;
		$this->mock_response = null;

		add_filter( 'pre_http_request', [ $this, 'mock_featured_request' ], 10, 3 );
	}

	/**
	 * Construct a new instance of the controller.
	 *
	 * @return Cloud_Search_Controller
	 */
	private static function make_controller(): Cloud_Search_Controller {
		return new Cloud_Search_Controller( new Basic_Cloud_Connection() );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'mock_featured_request' ] );
		$this->clear_featured_transients();

		parent::tear_down();
	}

	/**
	 * Build the transient key matching Cloud_API's format.
	 *
	 * @param array $filters Filter params.
	 *
	 * @return string
	 */
	private function transient_key( array $filters = [] ): string {
		$page = 1;
		$per_page = 10;

		$active_filters = array_filter( $filters );
		$encoded = wp_json_encode( $active_filters );
		$hash = md5( false === $encoded ? '' : $encoded );

		$version = get_transient( 'cs_featured_cache_version' );

		if ( ! $version ) {
			// Mirror the production helper by persisting the freshly generated version, so that
			// repeated calls within a test resolve to the same value rather than a new
			// timestamp each time (which made cache-key comparisons non-deterministic).
			$version = (string) ( microtime( true ) * 1000 );
			set_transient( 'cs_featured_cache_version', $version, MONTH_IN_SECONDS );
		}
		return "cs_featured_snippets_v{$version}_p{$page}_pp{$per_page}_$hash";
	}

	/**
	 * Clear all featured transients for default params.
	 *
	 * @return void
	 */
	private function clear_featured_transients(): void {
		delete_transient( $this->transient_key() );
		delete_transient( 'cs_featured_cache_version' );
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
					'id'   => 2,
					'name' => 'PHP',
				],
				'status'      => 4,
				'codevault'   => 'FeaturedVault',
				'vote_count'  => '5',
				'updated'     => '2026-03-30 12:00:00',
			];
		}

		$body = [
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
						'id'   => 2,
						'name' => 'PHP',
					],
				],
				'statuses'   => [
					[
						'id'   => 4,
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
		$result = self::make_controller()->get_featured_snippets();

		$this->assertInstanceOf( Cloud_Snippets::class, (object) $result );
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

		self::make_controller()->get_featured_snippets();

		$cached = get_transient( $this->transient_key() );
		$this->assertInstanceOf( Cloud_Snippets::class, $cached );
	}

	/**
	 * The second call returns from the transient without making an HTTP request.
	 *
	 * @return void
	 */
	public function test_second_call_returns_from_transient(): void {
		self::make_controller()->get_featured_snippets();
		$this->assertSame( 1, $this->http_request_count );

		$result = self::make_controller()->get_featured_snippets();
		$this->assertSame( 1, $this->http_request_count );
		$this->assertInstanceOf( Cloud_Snippets::class, $result );
		$this->assertCount( 3, $result->snippets );
	}

	/**
	 * An HTTP error returns null (graceful fallback).
	 *
	 * @return void
	 */
	public function test_returns_null_on_http_error(): void {
		$this->mock_response = new WP_Error( 'http_request_failed', 'Connection refused' );

		$result = self::make_controller()->get_featured_snippets();
		$this->assertNull( $result );
	}

	/**
	 * An invalid JSON response returns null.
	 *
	 * @return void
	 */
	public function test_returns_null_on_invalid_json(): void {
		$this->mock_response = [
			'headers'  => [],
			'body'     => 'not json',
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];

		$result = self::make_controller()->get_featured_snippets();
		$this->assertNull( $result );
	}

	/**
	 * An empty body returns null.
	 *
	 * @return void
	 */
	public function test_returns_null_on_empty_body(): void {
		$this->mock_response = [
			'headers'  => [],
			'body'     => '',
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];

		$result = self::make_controller()->get_featured_snippets();
		$this->assertNull( $result );
	}

	/**
	 * Transient is set using the minimum TTL.
	 *
	 * @return void
	 */
	public function test_transient_is_cached(): void {
		self::make_controller()->get_featured_snippets();

		$cached = get_transient( $this->transient_key() );
		$this->assertInstanceOf( Cloud_Snippets::class, $cached );

		delete_transient( $this->transient_key() );
		$this->mock_response = $this->build_success_response( 2 );

		self::make_controller()->get_featured_snippets();

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

		$result = self::make_controller()->get_featured_snippets();

		$this->assertInstanceOf( Cloud_Snippets::class, (object) $result );
		$this->assertCount( 0, $result->snippets );
		$this->assertSame( 0, $result->total_snippets );
	}

	/**
	 * Transient stores an actual Cloud_Snippets instance, not a plain array.
	 *
	 * @return void
	 */
	public function test_transient_stores_cloud_snippets_instance(): void {
		self::make_controller()->get_featured_snippets();

		$raw_transient = get_transient( $this->transient_key() );

		$this->assertFalse( is_array( $raw_transient ) );
		$this->assertInstanceOf( Cloud_Snippets::class, $raw_transient );
		$this->assertCount( 3, $raw_transient->snippets );
	}

	/**
	 * Available filters are preserved from the API response.
	 *
	 * @return void
	 */
	public function test_available_filters_preserved(): void {
		$result = self::make_controller()->get_featured_snippets();

		$this->assertIsArray( $result->available_filters );
		$this->assertArrayHasKey( 'types', $result->available_filters );
		$this->assertArrayHasKey( 'statuses', $result->available_filters );
	}

	/**
	 * Calling clear_caches() causes the next get_featured_snippets() to miss cache and re-fetch.
	 *
	 * @return void
	 */
	public function test_clear_caches_invalidates_featured_cache(): void {
		self::make_controller()->get_featured_snippets();
		$this->assertSame( 1, $this->http_request_count );

		$controller = self::make_controller();
		$controller->clear_caches();

		$controller->get_featured_snippets();
		$this->assertSame( 2, $this->http_request_count, 'Expected a fresh HTTP request after cache invalidation.' );
	}

	/**
	 * Empty filter values produce the same cache key as omitted filters.
	 *
	 * @return void
	 */
	public function test_empty_filters_produce_same_cache_key(): void {
		$key_empty = $this->transient_key(
			[
				'category' => '',
				'type'     => '',
				'status'   => '',
			]
		);

		$key_none = $this->transient_key();

		$this->assertSame( $key_none, $key_empty, 'Empty filter values should hash identically to no filters.' );
	}
}
