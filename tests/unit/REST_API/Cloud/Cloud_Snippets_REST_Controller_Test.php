<?php

namespace Code_Snippets\REST_API\Cloud;

use Code_Snippets\Admin\Menus\Manage\Manage_Menu;
use Code_Snippets\AdminUnitTestCase;
use Code_Snippets\Model\Snippet;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;
use function Code_Snippets\save_snippet;

/**
 * Tests for the Cloud REST API endpoint.
 *
 * @group rest-api
 */
class Cloud_Snippets_REST_Controller_Test extends AdminUnitTestCase {

	/**
	 * Default per-page value used when none is configured.
	 */
	private const DEFAULT_PER_PAGE = 10;

	/**
	 * REST API namespace and base route.
	 *
	 * @var string
	 */
	protected string $endpoint = '/code-snippets/v1/cloud/snippets';

	/**
	 * Most recent outbound cloud search URL.
	 *
	 * @var string
	 */
	private string $requested_url = '';

	/**
	 * Number of outbound CodeVault list requests intercepted during a test.
	 *
	 * @var int
	 */
	private int $codevault_request_count = 0;

	/**
	 * REST server that was active before the current test.
	 *
	 * @var WP_REST_Server|null
	 */
	private ?WP_REST_Server $rest_server = null;

	/**
	 * Whether this test seeded a local token on the shared cloud connection.
	 *
	 * @var bool
	 */
	private bool $did_seed_local_token = false;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		global $wp_rest_server;

		$this->requested_url = '';
		$this->rest_server = $wp_rest_server ?? null;

		delete_user_option( $this->get_user_id(), 'snippets_per_page' );

		add_filter( 'pre_http_request', [ $this, 'mock_cloud_search_request' ], 10, 3 );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $wp_rest_server;

		remove_filter( 'pre_http_request', [ $this, 'mock_cloud_search_request' ] );
		delete_user_option( $this->get_user_id(), 'snippets_per_page' );

		$wp_rest_server = $this->rest_server;

		parent::tear_down();
	}

	/**
	 * Mock the outbound cloud search request.
	 *
	 * @param mixed  $preempt     Existing preempted value.
	 * @param array  $parsed_args Parsed HTTP request arguments.
	 * @param string $url         Requested URL.
	 *
	 * @return mixed
	 */
	public function mock_cloud_search_request( $preempt, array $parsed_args, string $url ) {
		if ( false !== strpos( $url, 'private/allsnippets' ) ) {
			++$this->codevault_request_count;

			return [
				'headers'  => [],
				'body'     => wp_json_encode(
					[
						'snippets'     => [],
						'cloud_id_rev' => [],
						'meta'         => [
							'total'       => 0,
							'total_pages' => 0,
							'page'        => 1,
						],
					]
				),
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
				'cookies'  => [],
			];
		}

		if ( false === strpos( $url, 'public/search' ) && false === strpos( $url, 'public/featured' ) ) {
			return $preempt;
		}

		$this->requested_url = $url;

		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query_args );

		$per_page = isset( $query_args['per_page'] ) ? (int) $query_args['per_page'] : self::DEFAULT_PER_PAGE;
		$page = isset( $query_args['page'] ) ? (int) $query_args['page'] : 0;
		$total_items = 12;
		$total_pages = (int) ceil( $total_items / max( 1, $per_page ) );
		$items_to_return = min( $per_page, $total_items );

		$snippets = [];

		for ( $index = 0; $index < $items_to_return; $index++ ) {
			$snippets[] = [
				'id'          => ( $page * $per_page ) + $index + 1,
				'name'        => 'Cloud Snippet ' . ( $index + 1 ),
				'description' => 'Test description',
				'code'        => '<?php echo "test";',
				'tags'        => [],
				'scope'       => 'global',
				'status'      => 4,
				'codevault'   => 'General',
				'vote_count'  => '0',
				'updated'     => '2026-03-10 12:00:00',
			];
		}

		return [
			'headers'  => [],
			'body'     => wp_json_encode(
				[
					'data' => $snippets,
					'meta' => [
						'total'       => $total_items,
						'total_pages' => $total_pages,
						'page'        => $page + 1,
					],
				]
			),
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];
	}

	/**
	 * Make a REST API request to the cloud endpoint.
	 *
	 * @param array<string, bool|int|string> $params Request params.
	 * @param string                         $route  Optional route suffix.
	 *
	 * @return WP_REST_Response
	 */
	private function make_request( array $params, string $route = '' ): WP_REST_Response {
		global $wp_rest_server;

		$wp_rest_server = null;
		rest_get_server();

		$request = new WP_REST_Request( 'GET', $this->endpoint . $route );
		$request->add_header( 'Access-Control', code_snippets()->cloud_connection->get_local_token() );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_do_request( $request );
	}

	/**
	 * The cloud REST endpoint uses the snippets Screen Options value when per_page is omitted.
	 *
	 * @return void
	 */
	public function test_get_items_uses_snippets_per_page_user_option(): void {
		update_user_option( $this->get_user_id(), 'snippets_per_page', 7 );

		$response = $this->make_request(
			[
				'query' => 'test',
				'page'  => 2,
			]
		);

		parse_str( (string) wp_parse_url( $this->requested_url, PHP_URL_QUERY ), $query_args );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '7', $query_args['per_page'] ?? null );
		$this->assertSame( '1', $query_args['page'] ?? null );
	}

	/**
	 * Screen Options values above the cloud API limit are capped before the request is sent.
	 *
	 * @return void
	 */
	public function test_get_items_caps_snippets_per_page_user_option_at_one_hundred(): void {
		update_user_option( $this->get_user_id(), 'snippets_per_page', 250 );

		$response = $this->make_request(
			[
				'query' => 'test',
				'page'  => 2,
			]
		);

		parse_str( (string) wp_parse_url( $this->requested_url, PHP_URL_QUERY ), $query_args );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '100', $query_args['per_page'] ?? null );
		$this->assertSame( '1', $query_args['page'] ?? null );
	}

	/**
	 * Explicit per_page requests override the snippets Screen Options value.
	 *
	 * @return void
	 */
	public function test_get_items_respects_explicit_per_page_request(): void {
		update_user_option( $this->get_user_id(), 'snippets_per_page', 7 );

		$response = $this->make_request(
			[
				'query'    => 'test',
				'page'     => 2,
				'per_page' => 3,
			]
		);

		parse_str( (string) wp_parse_url( $this->requested_url, PHP_URL_QUERY ), $query_args );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '3', $query_args['per_page'] ?? null );
		$this->assertSame( '1', $query_args['page'] ?? null );
	}

	/**
	 * Featured snippets use the default cloud search page size.
	 *
	 * @return void
	 */
	public function test_get_featured_items_uses_default_cloud_search_page_size(): void {
		$expected_per_page = Manage_Menu::get_cloud_search_per_page();

		$response = $this->make_request( [], '/featured' );

		parse_str( (string) wp_parse_url( $this->requested_url, PHP_URL_QUERY ), $query_args );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( (string) $expected_per_page, $query_args['per_page'] ?? null );
	}

	/**
	 * Featured snippets honour the cloud search page-size filter.
	 *
	 * @return void
	 */
	public function test_get_featured_items_uses_filtered_cloud_search_page_size(): void {
		$filter = static fn() => 6;
		add_filter( 'code_snippets/cloud_search/per_page', $filter );

		$this->assertSame( 6, Manage_Menu::get_cloud_search_per_page() );

		$response = $this->make_request( [], '/featured' );

		remove_filter( 'code_snippets/cloud_search/per_page', $filter );
		parse_str( (string) wp_parse_url( $this->requested_url, PHP_URL_QUERY ), $query_args );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '6', $query_args['per_page'] ?? null );
	}

	/**
	 * Cloud snippets already downloaded to this site are reported with their local ID.
	 *
	 * @return void
	 */
	public function test_get_items_reports_local_ids_for_downloaded_snippets(): void {
		$local = save_snippet( new Snippet( [ 'name' => 'Downloaded snippet' ] ) );
		$local->cloud_id = 2;
		save_snippet( $local );

		$response = $this->make_request( [ 'query' => 'test' ] );
		$snippets = $response->get_data()['snippets'] ?? [];

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $snippets );

		$local_ids = wp_list_pluck( $snippets, 'local_id', 'id' );

		$this->assertSame( $local->id, $local_ids[2] ?? null );
		$this->assertArrayHasKey( 1, $local_ids );
		$this->assertNull( $local_ids[1] );
	}

	/**
	 * Featured snippets report local IDs in the same way as search results.
	 *
	 * @return void
	 */
	public function test_get_featured_items_reports_local_ids_for_downloaded_snippets(): void {
		$local = save_snippet( new Snippet( [ 'name' => 'Downloaded featured snippet' ] ) );
		$local->cloud_id = 3;
		save_snippet( $local );

		$response = $this->make_request( [], '/featured' );
		$snippets = $response->get_data()['snippets'] ?? [];

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $local->id, wp_list_pluck( $snippets, 'local_id', 'id' )[3] ?? null );
	}

	/**
<<<<<<< HEAD
	 * Registering REST routes for an unrelated request must not fetch CodeVault data.
	 *
	 * @return void
	 */
	public function test_unrelated_rest_request_does_not_fetch_codevault_snippets(): void {
		$request = new WP_REST_Request( 'GET', '/wp/v2/types' );
		rest_do_request( $request );

		$this->assertSame( 0, $this->codevault_request_count );
	}

	/**
	 * The first Cloud Library request fetches CodeVault data once and then uses the transient cache.
	 *
	 * @return void
	 */
	public function test_codevault_request_uses_cached_codevault_snippets(): void {
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/codevault' );
		$request->add_header( 'Access-Control', code_snippets()->cloud_connection->get_local_token() );
		$request->set_param( 'page', 1 );

		$second_request = clone $request;

		$server = rest_get_server();
		$server->dispatch( $request );
		$server->dispatch( $second_request );

		$this->assertSame( 1, $this->codevault_request_count );
	}

	/**
=======
>>>>>>> 8e653c946b1fe836902c9ef664f86f992bf8d9ab
	 * The AI search method is forwarded to the cloud as s_method=ai.
	 */
	public function test_search_method_ai_is_forwarded_to_cloud(): void {
		$response = $this->make_request(
			[
				'query'        => 'make my site more secure',
				'searchMethod' => 'ai',
			]
		);

		parse_str( (string) wp_parse_url( $this->requested_url, PHP_URL_QUERY ), $query_args );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'ai', $query_args['s_method'] ?? null );
	}

	/**
	 * With no method params, the search defaults to keyword matching (term).
	 */
	public function test_search_method_defaults_to_term(): void {
		$this->make_request( [ 'query' => 'test' ] );

		parse_str( (string) wp_parse_url( $this->requested_url, PHP_URL_QUERY ), $query_args );

		$this->assertSame( 'term', $query_args['s_method'] ?? null );
	}

	/**
	 * A codevault search takes precedence over an AI search method.
	 */
	public function test_codevault_takes_precedence_over_ai(): void {
		$this->make_request(
			[
				'query'             => 'general',
				'searchByCodevault' => true,
				'searchMethod'      => 'ai',
			]
		);

		parse_str( (string) wp_parse_url( $this->requested_url, PHP_URL_QUERY ), $query_args );

		$this->assertSame( 'codevault', $query_args['s_method'] ?? null );
	}
}
