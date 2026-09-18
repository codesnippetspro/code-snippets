<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\AdminUnitTestCase;
use Code_Snippets\Model\Snippet;
use WP_REST_Request;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippet;
use function Code_Snippets\save_snippet;

/**
 * Tests for the Snippets REST API endpoint.
 *
 * @group rest-api
 */
class REST_API_Snippets_Test extends AdminUnitTestCase {

	/**
	 * REST API namespace and base route.
	 *
	 * @var string
	 */
	protected string $namespace = 'code-snippets/v1';

	/**
	 * REST API base route.
	 *
	 * @var string
	 */
	protected string $base_route = 'snippets';

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		$this->clear_all_snippets();
		$this->seed_test_snippets();
	}

	/**
	 * Clear all snippets from the database.
	 */
	protected function clear_all_snippets() {
		global $wpdb;
		$table_name = code_snippets()->db->get_table_name();
		$wpdb->query( "TRUNCATE TABLE $table_name" );
	}

	/**
	 * Helper method to seed test snippets into the database.
	 */
	protected function seed_test_snippets() {
		$count = 25;

		for ( $i = 1; $i <= $count; $i++ ) {
			$snippet = new Snippet(
				[
					'name'   => "Test Snippet $i",
					'desc'   => "This is test snippet number $i",
					'code'   => "// Test snippet $i\necho 'Hello World $i';",
					'scope'  => 'global',
					'active' => false,
					'tags'   => [ 'test', "batch-$i" ],
				]
			);

			save_snippet( $snippet );
		}
	}

	/**
	 * Helper method to make a REST API request.
	 *
	 * @param string $endpoint Endpoint to request.
	 * @param array  $params   Query parameters.
	 *
	 * @return array
	 */
	protected function make_request( string $endpoint, array $params = [] ): array {
		$request = new WP_REST_Request( 'GET', $endpoint );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$response = rest_do_request( $request );
		return rest_get_server()->response_to_data( $response, false );
	}

	/**
	 * Helper method to make a writable REST API request.
	 *
	 * @param string $method   HTTP method.
	 * @param string $endpoint Endpoint to request.
	 * @param array  $params   Request params.
	 *
	 * @return array<string, mixed>
	 */
	protected function make_mutating_request( string $method, string $endpoint, array $params ): array {
		$request = new WP_REST_Request( $method, $endpoint );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$response = rest_do_request( $request );
		return rest_get_server()->response_to_data( $response, false );
	}

	/**
	 * Test that we can retrieve all snippets without pagination.
	 */
	public function test_get_all_snippets_without_pagination() {
		$endpoint = "/$this->namespace/$this->base_route";
		$response = $this->make_request( $endpoint, [ 'network' => false ] );

		$this->assertIsArray( $response );
		$this->assertCount( 25, $response, 'Should return all 25 snippets when no pagination params are provided' );

		$this->assertArrayHasKey( 'id', $response[0] );
		$this->assertArrayHasKey( 'name', $response[0] );
		$this->assertArrayHasKey( 'code', $response[0] );
	}

	/**
	 * Test pagination with per_page parameter only (first page).
	 */
	public function test_get_snippets_with_per_page() {
		$endpoint = "/$this->namespace/$this->base_route";

		$response = $this->make_request(
			$endpoint,
			[
				'network'  => false,
				'per_page' => 2,
			]
		);

		$this->assertIsArray( $response );
		$this->assertCount( 2, $response, 'Should return exactly 2 snippets when per_page=2' );

		$this->assertStringContainsString( 'Test Snippet 1', $response[0]['name'] );
		$this->assertStringContainsString( 'Test Snippet 2', $response[1]['name'] );
	}

	/**
	 * Test pagination with per_page and page parameters.
	 */
	public function test_get_snippets_with_per_page_and_page() {
		$endpoint = "/$this->namespace/$this->base_route";

		$response = $this->make_request(
			$endpoint,
			[
				'network'  => false,
				'per_page' => 2,
				'page'     => 3,
			]
		);

		$this->assertIsArray( $response );
		$this->assertCount( 2, $response, 'Should return exactly 2 snippets for page 3 with per_page=2' );

		$this->assertStringContainsString( 'Test Snippet 5', $response[0]['name'] );
		$this->assertStringContainsString( 'Test Snippet 6', $response[1]['name'] );
	}

	/**
	 * Test pagination with page parameter only (should use default per_page).
	 */
	public function test_get_snippets_with_page_only() {
		$endpoint = "/$this->namespace/$this->base_route";

		$page_1_response = $this->make_request(
			$endpoint,
			[
				'network' => false,
				'page'    => 1,
			]
		);

		$this->assertIsArray( $page_1_response );
		$this->assertGreaterThan( 0, count( $page_1_response ), 'Page 1 should have snippets' );

		$page_2_response = $this->make_request(
			$endpoint,
			[
				'network' => false,
				'page'    => 2,
			]
		);

		$this->assertIsArray( $page_2_response );
		$this->assertCount( 10, $page_2_response, 'Page 2 with default per_page should have 10 snippets' );
	}

	/**
	 * Test that headers contain correct pagination metadata.
	 */
	public function test_pagination_headers() {
		$endpoint = "/$this->namespace/$this->base_route";
		$request = new WP_REST_Request( 'GET', $endpoint );
		$request->set_param( 'network', false );
		$request->set_param( 'per_page', 5 );
		$request->set_param( 'page', 1 );

		$response = rest_do_request( $request );
		$headers = $response->get_headers();

		$this->assertEquals( 25, $headers['X-WP-Total'], 'X-WP-Total header should show 25 total snippets' );
		$this->assertEquals( 5, $headers['X-WP-TotalPages'], 'X-WP-TotalPages should be 5 (25 snippets / 5 per_page)' );
	}

	/**
	 * Test that last page returns correct number of snippets.
	 */
	public function test_last_page_with_partial_results() {
		$endpoint = "/$this->namespace/$this->base_route";

		$response = $this->make_request(
			$endpoint,
			[
				'network'  => false,
				'per_page' => 10,
				'page'     => 3,
			]
		);

		$this->assertIsArray( $response );
		$this->assertCount( 5, $response, 'Last page should have only 5 remaining snippets (25 % 10)' );
	}

	/**
	 * Test that requesting a page beyond available pages returns empty array.
	 */
	public function test_page_beyond_available_returns_empty() {
		$endpoint = "/$this->namespace/$this->base_route";

		$response = $this->make_request(
			$endpoint,
			[
				'network'  => false,
				'per_page' => 10,
				'page'     => 100,
			]
		);

		$this->assertIsArray( $response );
		$this->assertCount( 0, $response, 'Requesting page beyond available should return empty array' );
	}

	/**
	 * Test per_page with value of 1.
	 */
	public function test_per_page_one() {
		$endpoint = "/$this->namespace/$this->base_route";

		$response = $this->make_request(
			$endpoint,
			[
				'network'  => false,
				'per_page' => 1,
				'page'     => 5,
			]
		);

		$this->assertIsArray( $response );
		$this->assertCount( 1, $response, 'Should return exactly 1 snippet when per_page=1' );
		$this->assertStringContainsString( 'Test Snippet 5', $response[0]['name'] );
	}

	/**
	 * Test that per_page larger than total returns all snippets.
	 */
	public function test_per_page_larger_than_total() {
		$endpoint = "/$this->namespace/$this->base_route";

		$response = $this->make_request(
			$endpoint,
			[
				'network'  => false,
				'per_page' => 100,
				'page'     => 1,
			]
		);

		$this->assertIsArray( $response );
		$this->assertCount( 25, $response, 'Should return all 25 snippets when per_page exceeds total' );
	}

	/**
	 * Test that snippet data structure is correct.
	 */
	public function test_snippet_data_structure() {
		$endpoint = "/$this->namespace/$this->base_route";

		$response = $this->make_request(
			$endpoint,
			[
				'network'  => false,
				'per_page' => 1,
			]
		);

		$this->assertIsArray( $response );
		$this->assertCount( 1, $response );

		$snippet = $response[0];

		$required_fields = [ 'id', 'name', 'desc', 'code', 'scope', 'active', 'tags' ];
		foreach ( $required_fields as $field ) {
			$this->assertArrayHasKey( $field, $snippet, "Snippet should have '{$field}' field" );
		}

		$this->assertIsInt( $snippet['id'] );
		$this->assertIsString( $snippet['name'] );
		$this->assertIsString( $snippet['code'] );
		$this->assertIsBool( $snippet['active'] );
		$this->assertIsArray( $snippet['tags'] );
	}

	/**
	 * Test that the snippet description is loaded from the database.
	 */
	public function test_snippet_description_is_loaded_from_database() {
		$snippet = new Snippet(
			[
				'name'   => 'Description Fixture',
				'desc'   => 'Persisted description text',
				'code'   => '// Description fixture',
				'scope'  => 'global',
				'active' => false,
			]
		);

		$saved = save_snippet( $snippet );

		$this->assertInstanceOf( Snippet::class, $saved );
		$this->assertGreaterThan( 0, $saved->id );

		$loaded = get_snippet( $saved->id );

		$this->assertSame( 'Persisted description text', $loaded->desc );
	}

	/**
	 * Test that activation failures return the PHP error and stack trace while keeping the snippet saved.
	 */
	public function test_create_active_snippet_returns_runtime_error_details() {
		$response = $this->make_mutating_request(
			'POST',
			"/{$this->namespace}/{$this->base_route}",
			[
				'name'    => 'Activation Error Fixture',
				'code'    => 'function code_snippets_build_tags_array() {}',
				'scope'   => 'global',
				'active'  => true,
				'network' => false,
			]
		);

		$this->assertArrayHasKey( 'id', $response );
		$this->assertGreaterThan( 0, $response['id'] );
		$this->assertFalse( $response['active'] );
		$this->assertIsArray( $response['code_error'] );
		$this->assertStringContainsString( 'Cannot redeclare', $response['code_error'][0] );
		$this->assertArrayHasKey( 'code_error_trace', $response );
		$this->assertIsString( $response['code_error_trace'] );
		$this->assertNotSame( '', $response['code_error_trace'] );
	}
}
