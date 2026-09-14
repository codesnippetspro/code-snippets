<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use WP_REST_Request;
use function Code_Snippets\delete_snippet;
use function Code_Snippets\get_snippets;
use function Code_Snippets\save_snippet;
use function Code_Snippets\trash_snippet;

/**
 * Tests for the cloud-app drift-detection endpoints on the snippets REST controller.
 *
 * @group rest-api
 */
class Cloud_Snippets_Drift_Test extends UnitTestCase {

	/**
	 * REST API namespace and base route.
	 *
	 * @var string
	 */
	protected string $endpoint = '/code-snippets/v1/cloud/snippets';

	/**
	 * A cloud-linked local snippet used across tests.
	 *
	 * @var Snippet
	 */
	private Snippet $linked_snippet;

	/**
	 * The live connection's local_token as it was before this test mutated it,
	 * so it can be restored in tear_down() without leaking state (empty or
	 * otherwise) into other test files that share the same live connection.
	 *
	 * @var string
	 */
	private string $original_local_token = '';

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->original_local_token = $this->get_connection_token();

		wp_set_current_user( 0 );
		add_filter( 'pre_http_request', [ $this, 'mock_codevault_request' ], 10, 3 );

		$snippet = new Snippet();
		$snippet->name = 'Drift Test Snippet';
		$snippet->code = '<?php echo "drift test"; ?>';
		$snippet->scope = 'global';
		$snippet->cloud_id = 501;

		$this->linked_snippet = save_snippet( $snippet );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'mock_codevault_request' ] );

		// Restore the shared live connection's local_token so it doesn't leak
		// into other test files that depend on its original value.
		$this->seed_connection_token( $this->original_local_token );

		parent::tear_down();
	}

	/**
	 * Read the active cloud connection's local token.
	 *
	 * @return string
	 */
	private function get_connection_token(): string {
		$plugin = \Code_Snippets\code_snippets();

		$property = new \ReflectionProperty( $plugin, 'cloud_connection' );
		$property->setAccessible( true );

		return $property->getValue( $plugin )->get_local_token();
	}

	/**
	 * Short-circuit the outbound "all codevault snippets" request that
	 * `trash_snippet()` triggers indirectly via `Cloud_Library_Controller`,
	 * so trashing a snippet in these tests doesn't depend on real network
	 * access.
	 *
	 * @param mixed  $preempt     Existing preempted value.
	 * @param array  $parsed_args Parsed HTTP request arguments.
	 * @param string $url         Requested URL.
	 *
	 * @return mixed
	 */
	public function mock_codevault_request( $preempt, array $parsed_args, string $url ) {
		if ( false === strpos( $url, 'private/allsnippets' ) ) {
			return $preempt;
		}

		return [
			'headers'  => [],
			'body'     => wp_json_encode(
				[
					'data' => [],
					'meta' => [
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

	/**
	 * Seed a real, non-empty local_token directly onto the live connection
	 * via reflection. The live connection's settings were loaded (empty) at
	 * bootstrap and won't pick up a changed option, so this sets it directly
	 * on the shared instance the routes actually authenticate against.
	 *
	 * @param string $token Token value to set.
	 *
	 * @return string The token that was set.
	 */
	private function seed_connection_token( string $token = 'test-site-token-abc123' ): string {
		$plugin = \Code_Snippets\code_snippets();
		$conn_prop = new \ReflectionProperty( $plugin, 'cloud_connection' );
		$conn_prop->setAccessible( true );
		$connection = $conn_prop->getValue( $plugin );

		$settings_prop = new \ReflectionProperty( $connection, 'settings' );
		$settings_prop->setAccessible( true );
		$settings = $settings_prop->getValue( $connection );
		$settings['local_token'] = $token;
		$settings_prop->setValue( $connection, $settings );

		return $token;
	}

	/**
	 * A valid token with no logged-in user can fetch the hashes list, and
	 * trashed snippets are excluded.
	 *
	 * @return void
	 */
	public function test_get_hashes_with_valid_token_and_no_user(): void {
		$trashed = new Snippet();
		$trashed->name = 'Trashed Drift Snippet';
		$trashed->code = '<?php echo "trashed"; ?>';
		$trashed->scope = 'global';
		$trashed->cloud_id = 502;
		$trashed = save_snippet( $trashed );
		trash_snippet( $trashed->id );

		$request = new WP_REST_Request( 'GET', $this->endpoint . '/hashes' );
		$request->add_header( 'Access-Control', $this->seed_connection_token() );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'snippets', $data );

		$cloud_ids = wp_list_pluck( $data['snippets'], 'cloud_id' );
		$this->assertContains( '501', $cloud_ids );
		$this->assertNotContains( '502', $cloud_ids );

		$index = array_search( '501', $cloud_ids, true );
		$entry = $data['snippets'][ $index ];
		$this->assertSame( hash( 'sha256', $this->linked_snippet->code ), $entry['code_hash'] );
	}

	/**
	 * No logged-in user and a wrong/absent token is rejected for the hashes endpoint.
	 *
	 * @return void
	 */
	public function test_get_hashes_with_invalid_token_and_no_user_is_rejected(): void {
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/hashes' );
		$request->add_header( 'Access-Control', 'not-the-right-token' );

		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), [ 401, 403 ] );
	}

	/**
	 * A never-connected site (empty local_token), no logged-in user, and a
	 * present-but-empty Access-Control header must be rejected outright for
	 * the hashes endpoint, not fail open.
	 *
	 * @return void
	 */
	public function test_get_hashes_with_empty_token_and_empty_header_is_rejected(): void {
		$this->seed_connection_token( '' );

		$request = new WP_REST_Request( 'GET', $this->endpoint . '/hashes' );
		$request->add_header( 'Access-Control', '' );

		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), [ 401, 403 ] );
	}

	/**
	 * A locally-created snippet (no cloud_id) is reported with cloud_id: null so
	 * the cloud can surface it as site-only.
	 *
	 * @return void
	 */
	public function test_get_hashes_includes_local_only_with_null_cloud_id(): void {
		$local = new Snippet();
		$local->name = 'Site Only Snippet';
		$local->code = '<?php echo "local only"; ?>';
		$local->scope = 'global';
		$local = save_snippet( $local );

		$request = new WP_REST_Request( 'GET', $this->endpoint . '/hashes' );
		$request->add_header( 'Access-Control', $this->seed_connection_token() );

		$data = rest_do_request( $request )->get_data();

		$entry = null;
		foreach ( $data['snippets'] as $snippet ) {
			if ( (int) $local->id === (int) $snippet['local_id'] ) {
				$entry = $snippet;
				break;
			}
		}

		$this->assertNotNull( $entry, 'the site-only snippet should be listed' );
		$this->assertNull( $entry['cloud_id'] );
		$this->assertSame( hash( 'sha256', $local->code ), $entry['code_hash'] );
	}

	/**
	 * Every snippet is returned by default (single page), and pagination is
	 * opt-in via per_page, with totals advertised through X-WP-Total(-Pages).
	 *
	 * @return void
	 */
	public function test_get_hashes_pagination_is_opt_in_with_headers(): void {
		foreach ( [ 'One', 'Two' ] as $index => $name ) {
			$snippet = new Snippet();
			$snippet->name = "Page $name";
			$snippet->code = "<?php echo 'page $index';";
			$snippet->scope = 'global';
			save_snippet( $snippet );
		}

		// Default: no cap, one page containing every snippet.
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/hashes' );
		$request->add_header( 'Access-Control', $this->seed_connection_token() );
		$response = rest_do_request( $request );
		$headers = $response->get_headers();

		$total = (int) $headers['X-WP-Total'];
		$this->assertGreaterThanOrEqual( 3, $total );
		$this->assertSame( '1', (string) $headers['X-WP-TotalPages'] );
		$this->assertCount( $total, $response->get_data()['snippets'] );

		// Opt-in: per_page caps each page and TotalPages reflects the split.
		$paged = new WP_REST_Request( 'GET', $this->endpoint . '/hashes' );
		$paged->add_header( 'Access-Control', $this->seed_connection_token() );
		$paged->set_query_params(
			[
				'per_page' => '1',
				'page'     => '1',
			]
		);
		$paged_response = rest_do_request( $paged );
		$paged_headers = $paged_response->get_headers();

		$this->assertCount( 1, $paged_response->get_data()['snippets'] );
		$this->assertSame( (string) $total, (string) $paged_headers['X-WP-Total'] );
		$this->assertSame( (string) $total, (string) $paged_headers['X-WP-TotalPages'] );
	}

	/**
	 * A valid token with no logged-in user can fetch a snippet's body by cloud_id.
	 *
	 * @return void
	 */
	public function test_get_body_by_cloud_id_with_valid_token_and_no_user(): void {
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/501_0/body' );
		$request->add_header( 'Access-Control', $this->seed_connection_token() );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( $this->linked_snippet->code, $data['code'] );
		$this->assertSame( hash( 'sha256', $this->linked_snippet->code ), $data['code_hash'] );
	}

	/**
	 * The site stores cloud ids with an ownership suffix (e.g. "177_1"), but the
	 * cloud fetches bodies by the bare numeric id ("177") from the hashes list.
	 * All owner-suffix forms must resolve to the same snippet.
	 *
	 * @return void
	 */
	public function test_get_body_resolves_bare_numeric_cloud_id(): void {
		$owned = new Snippet();
		$owned->name = 'Owned Cloud Snippet';
		$owned->code = '<?php echo "owned body"; ?>';
		$owned->scope = 'global';
		$owned->cloud_id = 177;
		$owned->is_cloud_owner = true;
		$owned = save_snippet( $owned );

		foreach ( [ '177', '177_0', '177_1' ] as $requested_id ) {
			$request = new WP_REST_Request( 'GET', $this->endpoint . '/' . $requested_id . '/body' );
			$request->add_header( 'Access-Control', $this->seed_connection_token() );

			$response = rest_do_request( $request );

			$this->assertSame( 200, $response->get_status(), "cloud_id form '$requested_id' should resolve" );
			$this->assertSame( $owned->code, $response->get_data()['code'] );
			$this->assertSame( (int) $owned->id, (int) $response->get_data()['local_id'] );
		}
	}

	/**
	 * An unknown cloud_id returns a 404.
	 *
	 * @return void
	 */
	public function test_get_body_by_cloud_id_unknown_returns_404(): void {
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/does-not-exist/body' );
		$request->add_header( 'Access-Control', $this->seed_connection_token() );

		$response = rest_do_request( $request );

		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * A trashed snippet's cloud_id returns a 404 for the body endpoint.
	 *
	 * @return void
	 */
	public function test_get_body_by_cloud_id_trashed_returns_404(): void {
		$trashed = new Snippet();
		$trashed->name = 'Trashed Drift Snippet Body';
		$trashed->code = '<?php echo "trashed body"; ?>';
		$trashed->scope = 'global';
		$trashed->cloud_id = 503;
		$trashed = save_snippet( $trashed );
		trash_snippet( $trashed->id );

		$request = new WP_REST_Request( 'GET', $this->endpoint . '/503_0/body' );
		$request->add_header( 'Access-Control', $this->seed_connection_token() );

		$response = rest_do_request( $request );

		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * A failed inventory read returns a non-2xx and no `snippets` key, rather
	 * than an empty list — the cloud rebuilds its inventory from this response
	 * and reads an empty list as "every snippet was deleted".
	 *
	 * @return void
	 */
	public function test_get_hashes_read_failure_returns_non_2xx_without_empty_list(): void {
		global $wpdb;

		wp_cache_flush();
		$suppressing = $wpdb->suppress_errors( true );
		add_filter( 'query', [ $this, 'break_snippets_query' ] );

		$request = new WP_REST_Request( 'GET', $this->endpoint . '/hashes' );
		$request->add_header( 'Access-Control', $this->seed_connection_token() );

		$response = rest_do_request( $request );

		remove_filter( 'query', [ $this, 'break_snippets_query' ] );
		$wpdb->suppress_errors( $suppressing );
		$wpdb->last_error = '';
		wp_cache_flush();

		$this->assertSame( 503, $response->get_status() );
		$this->assertArrayNotHasKey( 'snippets', (array) $response->get_data() );
	}

	/**
	 * A site that genuinely has no snippets still reports an empty list with a
	 * 200, so a real deletion is not mistaken for a read failure.
	 *
	 * @return void
	 */
	public function test_get_hashes_on_empty_site_returns_empty_list_with_200(): void {
		foreach ( get_snippets() as $snippet ) {
			delete_snippet( $snippet->id );
		}

		$request = new WP_REST_Request( 'GET', $this->endpoint . '/hashes' );
		$request->add_header( 'Access-Control', $this->seed_connection_token() );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [], $response->get_data()['snippets'] );
	}

	/**
	 * Point the snippets table read at a table that does not exist, so the
	 * query fails the way a transient database fault would.
	 *
	 * @param string $query Query about to run.
	 *
	 * @return string
	 */
	public function break_snippets_query( string $query ): string {
		return 0 === strpos( $query, 'SELECT * FROM ' )
			? 'SELECT * FROM code_snippets_no_such_table_for_tests'
			: $query;
	}
}
