<?php

namespace Code_Snippets\REST_API\Cloud;

use Code_Snippets\UnitTestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTest_Factory;

/**
 * Tests for the public community bundles REST endpoint.
 *
 * @group rest-api
 * @group cloud
 */
class Cloud_Public_Bundles_REST_Controller_Test extends UnitTestCase {

	/**
	 * REST API namespace and base route.
	 *
	 * @var string
	 */
	protected string $endpoint = '/code-snippets/v1/cloud/community-bundles';

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static int $admin_user_id;

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	protected static int $editor_user_id;

	/**
	 * Response returned by the HTTP mock.
	 *
	 * @var array|WP_Error
	 */
	private $mock_response;

	/**
	 * Set up fixtures before any tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_user_id = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$editor_user_id = $factory->user->create( [ 'role' => 'editor' ] );
	}

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$admin_user_id );
		$this->mock_response = $this->json_response( [ 'bundles' => [] ] );
		$this->requested_urls = [];
		add_filter( 'pre_http_request', [ $this, 'mock_request' ], 10, 3 );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'mock_request' ] );

		parent::tear_down();
	}

	/**
	 * URLs of intercepted cloud requests.
	 *
	 * @var string[]
	 */
	private array $requested_urls = [];

	/**
	 * Intercept featured bundle and shared bundle requests.
	 *
	 * @param mixed  $preempt     Short-circuit value.
	 * @param array  $parsed_args Request arguments.
	 * @param string $url         Request URL.
	 *
	 * @return array|WP_Error|mixed
	 */
	public function mock_request( $preempt, array $parsed_args, string $url ) {
		if ( false === strpos( $url, 'public/featured-bundles' ) && false === strpos( $url, 'public/getsharedbundle' ) ) {
			return $preempt;
		}

		$this->requested_urls[] = $url;
		return $this->mock_response;
	}

	/**
	 * Wrap a JSON body in a mock HTTP response.
	 *
	 * @param array $body Response body to encode.
	 *
	 * @return array
	 */
	private function json_response( array $body ): array {
		return [
			'body'     => wp_json_encode( $body ),
			'response' => [ 'code' => 200 ],
		];
	}

	/**
	 * Dispatch a request to the endpoint.
	 *
	 * @param string               $route  Route suffix to append to the endpoint.
	 * @param array<string,scalar> $params Query parameters.
	 *
	 * @return WP_REST_Response
	 */
	private function make_request( string $route = '', array $params = [] ): WP_REST_Response {
		$request = new WP_REST_Request( 'GET', $this->endpoint . $route );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return \rest_do_request( $request );
	}

	/**
	 * Valid bundles and a valid empty result are returned unchanged.
	 *
	 * @return void
	 */
	public function test_returns_valid_bundle_results(): void {
		$bundle = [
			'id'             => 12,
			'name'           => 'Community Bundle',
			'share_code'     => 'share-12',
			'snippets_count' => 3,
		];
		$this->mock_response = $this->json_response( [ 'bundles' => [ $bundle ] ] );

		$response = $this->make_request();
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );

		$this->assertSame( [ array_merge( $bundle, [ 'is_public' => true ] ) ], $data['bundles'] );
		$this->assertSame(
			[
				'total'       => 1,
				'total_pages' => 1,
				'page'        => 1,
				'per_page'    => 10,
			],
			$data['meta']
		);

		$this->mock_response = $this->json_response( [ 'bundles' => [] ] );
		$this->assertSame( [], $this->make_request()->get_data()['bundles'] );
	}

	/**
	 * Cloud pagination meta is preserved and exposed with 1-indexed pages.
	 *
	 * @return void
	 */
	public function test_pagination_meta_and_params_pass_through(): void {
		$this->mock_response = $this->json_response(
			[
				'bundles' => [],
				'meta'    => [
					'total'       => 25,
					'total_pages' => 3,
					'page'        => 1,
					'per_page'    => 10,
				],
			]
		);

		$data = $this->make_request(
			'',
			[
				'page'     => 2,
				'per_page' => 10,
			]
		)->get_data();

		$this->assertSame(
			[
				'total'       => 25,
				'total_pages' => 3,
				'page'        => 2,
				'per_page'    => 10,
			],
			$data['meta']
		);

		$this->assertCount( 1, $this->requested_urls );
		\wp_parse_str( (string) \wp_parse_url( $this->requested_urls[0], PHP_URL_QUERY ), $query_args );

		// The cloud API uses 0-indexed pages.
		$this->assertSame( '1', $query_args['page'] );
		$this->assertSame( '10', $query_args['per_page'] );
	}

	/**
	 * An explicit is_public flag from the cloud API is preserved.
	 *
	 * @return void
	 */
	public function test_explicit_is_public_flag_is_preserved(): void {
		$bundle = [
			'id'             => 5,
			'share_code'     => 'share-5',
			'snippets_count' => 1,
			'is_public'      => false,
		];
		$this->mock_response = $this->json_response( [ 'bundles' => [ $bundle ] ] );

		$this->assertFalse( $this->make_request()->get_data()['bundles'][0]['is_public'] );
	}

	/**
	 * Shared bundle snippets are fetched through the public endpoint.
	 *
	 * @return void
	 */
	public function test_share_code_returns_bundle_snippets(): void {
		$this->mock_response = $this->json_response(
			[
				'snippets'       => [
					[
						'id'   => 42,
						'name' => 'Bundled Snippet',
					],
				],
				'total_snippets' => 12,
				'total_pages'    => 2,
				'meta'           => [
					'total'       => 12,
					'total_pages' => 2,
					'page'        => 0,
					'per_page'    => 10,
				],
			]
		);

		$response = $this->make_request( '/share_code/my-bundle-7', [ 'page' => 1 ] );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $data['snippets'] );
		$this->assertSame( 42, $data['snippets'][0]['id'] );
		$this->assertSame( 2, $data['total_pages'] );
		$this->assertSame( 12, $data['total_snippets'] );
		$this->assertSame( 1, $data['page'] );

		$this->assertCount( 1, $this->requested_urls );
		$this->assertStringContainsString( 'public/getsharedbundle', $this->requested_urls[0] );
		$this->assertStringContainsString( 'share_code=my-bundle-7', $this->requested_urls[0] );
	}

	/**
	 * A shared bundle containing a snippet with malformed frontend-consumed
	 * fields is rejected as an upstream error rather than passed to clients.
	 *
	 * @return void
	 */
	public function test_share_code_malformed_snippet_returns_bad_gateway(): void {
		$this->mock_response = $this->json_response(
			[
				'snippets' => [
					[
						'id'    => 42,
						'name'  => 'Bundled Snippet',
						'scope' => [ 'not', 'a', 'string' ],
					],
				],
			]
		);

		$response = $this->make_request( '/share_code/my-bundle-7', [ 'page' => 1 ] );

		$this->assertSame( 502, $response->get_status() );
		$this->assertSame(
			'code_snippets_cloud_bundle_snippets_invalid_response',
			$response->get_data()['code'] ?? null
		);
	}

	/**
	 * An unavailable shared bundle is reported as not found.
	 *
	 * @return void
	 */
	public function test_share_code_failure_returns_not_found(): void {
		$this->mock_response = [
			'body'     => \wp_json_encode( [ 'success' => false ] ),
			'response' => [ 'code' => 404 ],
		];

		$response = $this->make_request( '/share_code/missing-1' );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'code_snippets_rest_bundle_not_found', $response->get_data()['code'] );
	}

	/**
	 * Users without snippet capabilities cannot fetch shared bundle snippets.
	 *
	 * @return void
	 */
	public function test_share_code_requires_snippet_capability(): void {
		\wp_set_current_user( self::$editor_user_id );

		$this->assertContains( $this->make_request( '/share_code/share-1' )->get_status(), [ 401, 403 ] );
		$this->assertCount( 0, $this->requested_urls );
	}

	/**
	 * Users without snippet capabilities cannot retrieve community bundles.
	 *
	 * @return void
	 */
	public function test_requires_snippet_capability(): void {
		wp_set_current_user( self::$editor_user_id );

		$this->assertContains( $this->make_request()->get_status(), [ 401, 403 ] );
	}

	/**
	 * Transport failures are surfaced as an upstream error.
	 *
	 * @return void
	 */
	public function test_transport_failure_returns_bad_gateway(): void {
		$this->mock_response = new WP_Error( 'http_request_failed', 'Connection failed.' );

		$response = $this->make_request();

		$this->assertSame( 502, $response->get_status() );
		$this->assertSame( 'code_snippets_cloud_bundles_request_failed', $response->get_data()['code'] );
	}

	/**
	 * A non-2xx status masks the body even when it carries a valid bundles envelope.
	 *
	 * @return void
	 */
	public function test_non_2xx_status_returns_bad_gateway(): void {
		$this->mock_response = [
			'body'     => wp_json_encode( [ 'bundles' => [] ] ),
			'response' => [ 'code' => 500 ],
		];

		$response = $this->make_request();

		$this->assertSame( 502, $response->get_status() );
		$this->assertSame( 'code_snippets_cloud_bundles_request_failed', $response->get_data()['code'] );
	}

	/**
	 * Malformed response envelopes are surfaced as an upstream error.
	 *
	 * @return void
	 */
	public function test_malformed_response_returns_bad_gateway(): void {
		$this->mock_response = [
			'body' => '{',
			'response' => [ 'code' => 200 ],
		];

		$response = $this->make_request();

		$this->assertSame( 502, $response->get_status() );
		$this->assertSame( 'code_snippets_cloud_bundles_invalid_response', $response->get_data()['code'] );

		$this->mock_response = $this->json_response( [ 'data' => [] ] );
		$this->assertSame( 502, $this->make_request()->get_status() );
	}

	/**
	 * Bundles with malformed consumed fields are rejected.
	 *
	 * @dataProvider data_malformed_bundle_fields
	 *
	 * @param array $bundle Bundle with one malformed field.
	 *
	 * @return void
	 */
	public function test_malformed_bundle_field_returns_bad_gateway( array $bundle ): void {
		$this->mock_response = $this->json_response( [ 'bundles' => [ $bundle ] ] );

		$response = $this->make_request();

		$this->assertSame( 502, $response->get_status() );
		$this->assertSame( 'code_snippets_cloud_bundles_invalid_response', $response->get_data()['code'] );
	}

	/**
	 * Data provider for malformed bundle field tests.
	 *
	 * @return array<string,array{array}>
	 */
	public function data_malformed_bundle_fields(): array {
		$valid = [
			'id'             => 12,
			'name'           => 'Test',
			'share_code'     => 'share-12',
			'snippets_count' => 3,
			'description'    => 'A test bundle',
			'is_public'      => true,
		];

		return [
			'non-string description' => [ array_merge( $valid, [ 'description' => 7 ] ) ],
			'non-integer id'         => [ array_merge( $valid, [ 'id' => 'abc' ] ) ],
			'non-integer count'      => [ array_merge( $valid, [ 'snippets_count' => '3' ] ) ],
			'non-string name'        => [ array_merge( $valid, [ 'name' => 123 ] ) ],
			'non-boolean is_public'  => [ array_merge( $valid, [ 'is_public' => 1 ] ) ],
		];
	}

	/**
	 * Every bundle requires a non-empty share code.
	 *
	 * @return void
	 */
	public function test_bundle_without_share_code_returns_bad_gateway(): void {
		$this->mock_response = $this->json_response( [ 'bundles' => [ [ 'id' => 12 ] ] ] );

		$response = $this->make_request();

		$this->assertSame( 502, $response->get_status() );
		$this->assertSame( 'code_snippets_cloud_bundles_invalid_response', $response->get_data()['code'] );

		$this->mock_response = $this->json_response( [ 'bundles' => [ [ 'share_code' => '  ' ] ] ] );
		$this->assertSame( 502, $this->make_request()->get_status() );
	}
}
