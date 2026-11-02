<?php

namespace Code_Snippets\REST_API\Search;

use Code_Snippets\AdminUnitTestCase;
use Code_Snippets\Model\Snippet;
use WP_REST_Request;
use WP_UnitTest_Factory;
use function Code_Snippets\clean_snippets_cache;
use function Code_Snippets\code_snippets;
use function Code_Snippets\save_snippet;

/**
 * Tests for the universal search REST endpoint.
 *
 * @group search
 * @group rest-api
 */
class Search_REST_Controller_Test extends AdminUnitTestCase {

	/**
	 * User with no access to snippets.
	 *
	 * @var int
	 */
	private static int $subscriber_user_id;

	/**
	 * Set up fixtures before any tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );
		self::$subscriber_user_id = $factory->user->create( [ 'role' => 'subscriber' ] );
	}

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = null;
		rest_get_server();

		global $wpdb;
		$table_name = code_snippets()->db->get_table_name();
		$wpdb->query( "TRUNCATE TABLE $table_name" );
		clean_snippets_cache( $table_name );

		save_snippet(
			new Snippet(
				[
					'name'  => 'Disable admin bar',
					'code'  => 'show_admin_bar( false );',
					'scope' => 'global',
				]
			)
		);
	}

	/**
	 * Build a search request.
	 *
	 * @param array<string, mixed> $params Query parameters.
	 *
	 * @return WP_REST_Request
	 */
	private function make_request( array $params ): WP_REST_Request {
		$request = new WP_REST_Request( 'GET', '/' . Search_REST_Controller::get_base_route() );
		$request->set_query_params( $params );

		return $request;
	}

	/**
	 * The route is registered under the plugin's namespace.
	 *
	 * @return void
	 */
	public function test_route_is_registered() {
		$this->assertArrayHasKey( '/code-snippets/v1/search', rest_get_server()->get_routes() );
	}

	/**
	 * Results are grouped by the source they came from.
	 *
	 * @return void
	 */
	public function test_returns_results_grouped_by_source() {
		$response = rest_do_request(
			$this->make_request(
				[
					'q'       => ' admin bar ',
					'sources' => 'snippets,settings',
				]
			)
		);

		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'admin bar', $data['query'] );
		$this->assertSame( [ 'snippets', 'settings' ], array_column( $data['groups'], 'source' ) );
		$this->assertSame( 'Disable admin bar', $data['groups'][0]['items'][0]['title'] );
	}

	/**
	 * Markup and code in the term reach the matcher intact, so searching for
	 * code finds the snippets containing it.
	 *
	 * @return void
	 */
	public function test_code_in_the_term_is_kept_intact() {
		save_snippet(
			new Snippet(
				[
					'name'  => 'Notice markup',
					'code'  => '<div class="notice">Hello</div>',
					'scope' => 'content',
				]
			)
		);

		foreach ( [ '<div class="notice">', 'show_admin_bar( false', "<div\x00 class=\"notice\"" ] as $term ) {
			$response = rest_do_request(
				$this->make_request(
					[
						'q'       => $term,
						'sources' => 'snippets',
					]
				)
			);

			$data = $response->get_data();

			$this->assertSame( 200, $response->get_status() );
			$this->assertNotEmpty( $data['groups'], "No results for $term" );
			$this->assertCount( 1, $data['groups'][0]['items'], "Wrong results for $term" );
		}
	}

	/**
	 * A term too short to search for is answered with no groups.
	 *
	 * @return void
	 */
	public function test_short_term_returns_no_groups() {
		$response = rest_do_request( $this->make_request( [ 'q' => 'a' ] ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [], $response->get_data()['groups'] );
	}

	/**
	 * The search term is required.
	 *
	 * @return void
	 */
	public function test_requires_a_search_term() {
		$this->assertSame( 400, rest_do_request( $this->make_request( [] ) )->get_status() );
	}

	/**
	 * An unknown source is rejected rather than ignored.
	 *
	 * @return void
	 */
	public function test_rejects_unknown_sources() {
		$response = rest_do_request(
			$this->make_request(
				[
					'q'       => 'admin',
					'sources' => 'snippets,users',
				]
			)
		);

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Only the known ways of searching the cloud are accepted.
	 *
	 * @return void
	 */
	public function test_accepts_only_known_search_methods() {
		$valid = rest_do_request(
			$this->make_request(
				[
					'q'       => 'admin',
					'sources' => 'settings',
					'method'  => 'ai',
				]
			)
		);

		$invalid = rest_do_request(
			$this->make_request(
				[
					'q'      => 'admin',
					'method' => 'telepathy',
				]
			)
		);

		$this->assertSame( 200, $valid->get_status() );
		$this->assertSame( 400, $invalid->get_status() );
	}

	/**
	 * Someone without access to snippets cannot search.
	 *
	 * @return void
	 */
	public function test_denies_users_without_snippet_access() {
		wp_set_current_user( self::$subscriber_user_id );

		$this->assertSame( 403, rest_do_request( $this->make_request( [ 'q' => 'admin' ] ) )->get_status() );
	}

	/**
	 * A signed-out visitor cannot search.
	 *
	 * @return void
	 */
	public function test_denies_signed_out_visitors() {
		wp_set_current_user( 0 );

		$this->assertSame( 401, rest_do_request( $this->make_request( [ 'q' => 'admin' ] ) )->get_status() );
	}
}
