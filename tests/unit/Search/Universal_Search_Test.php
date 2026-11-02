<?php

namespace Code_Snippets\Search;

use Code_Snippets\AdminUnitTestCase;
use Code_Snippets\Client\Docs_Client;
use Code_Snippets\Controller\Cloud_Search_Controller;
use Code_Snippets\Model\Snippet;
use WP_UnitTest_Factory;
use function Code_Snippets\clean_snippets_cache;
use function Code_Snippets\code_snippets;
use function Code_Snippets\save_snippet;
use function Code_Snippets\trash_snippet;

/**
 * Tests for searching each universal search source.
 *
 * @group search
 */
class Universal_Search_Test extends AdminUnitTestCase {

	/**
	 * User whose role may view snippets, but not edit them.
	 *
	 * @var int
	 */
	private static int $viewer_user_id;

	/**
	 * Search method of each request made to the cloud search endpoint during a test.
	 *
	 * @var string[]
	 */
	private array $search_methods = [];

	/**
	 * Local identifier the mocked cloud reports for its snippet, if any.
	 *
	 * @var int|null
	 */
	private ?int $cloud_local_id = null;

	/**
	 * Set up fixtures before any tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );
		self::$viewer_user_id = $factory->user->create( [ 'role' => 'editor' ] );
	}

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->search_methods = [];
		$this->cloud_local_id = null;
		Universal_Search::clear_caches();

		add_filter( 'pre_http_request', [ $this, 'mock_cloud_request' ], 10, 3 );

		$this->truncate_snippets_table();

		save_snippet(
			new Snippet(
				[
					'name'  => 'Disable admin bar',
					'desc'  => '<p>Turns off the <strong>toolbar</strong> for subscribers.</p>',
					'code'  => "add_action( 'after_setup_theme', function () {\n\tshow_admin_bar( false );\n} );",
					'scope' => 'global',
				]
			)
		);

		save_snippet(
			new Snippet(
				[
					'name'  => 'Red background',
					'desc'  => '',
					'code'  => 'body { background: red; }',
					'scope' => 'site-css',
				]
			)
		);
	}

	/**
	 * Clear all snippets from the database.
	 *
	 * @return void
	 */
	private function truncate_snippets_table(): void {
		global $wpdb;
		$table_name = code_snippets()->db->get_table_name();
		$wpdb->query( "TRUNCATE TABLE $table_name" );
		clean_snippets_cache( $table_name );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'mock_cloud_request' ] );
		Universal_Search::clear_caches();

		parent::tear_down();
	}

	/**
	 * Intercept requests to Code Snippets Cloud.
	 *
	 * @param mixed  $preempt     Existing preempted value.
	 * @param array  $parsed_args Parsed HTTP request arguments.
	 * @param string $url         Requested URL.
	 *
	 * @return mixed
	 */
	public function mock_cloud_request( $preempt, array $parsed_args, string $url ) {
		if ( false === strpos( $url, 'public/search' ) ) {
			return $preempt;
		}

		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $params );
		$this->search_methods[] = $params['s_method'] ?? '';

		$snippet = [
			'id'          => 501,
			'name'        => 'Colour the admin bar',
			'description' => 'Red on staging, blue on production.',
			'code'        => '#wpadminbar { background: red; }',
			'scope'       => 'admin-css',
			'codevault'   => 'jlewis',
			'local_id'    => $this->cloud_local_id,
		];

		return [
			'headers'  => [],
			'body'     => wp_json_encode( [ 'snippets' => [ $snippet ] ] ),
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];
	}

	/**
	 * Create a search service with a fixed documentation index.
	 *
	 * @return Universal_Search
	 */
	private function make_search(): Universal_Search {
		$docs_client = $this->createStub( Docs_Client::class );

		$docs_client->method( 'get_articles' )->willReturn(
			[
				[
					'id'       => 'a1b2c3d4e5f6',
					'title'    => 'Using the admin bar menu',
					'url'      => 'https://codesnippets.pro/doc/admin-bar/',
					'text'     => 'Activate, deactivate and edit snippets from the admin bar.',
					'category' => 'Features',
				],
			]
		);

		return new Universal_Search( new Cloud_Search_Controller( code_snippets()->cloud_connection ), $docs_client );
	}

	/**
	 * Search and return the items found for a single source.
	 *
	 * @param string   $term   Search term.
	 * @param string   $source Source to search.
	 * @param string[] $fields Fields to match against.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function search_source( string $term, string $source, array $fields = Search_Query::FIELDS ): array {
		$groups = $this->make_search()->search( new Search_Query( $term, $fields ), [ $source ] );

		return $groups ? $groups[0]['items'] : [];
	}

	/**
	 * A term too short to search for returns nothing.
	 *
	 * @return void
	 */
	public function test_short_term_returns_no_groups() {
		$this->assertSame( [], $this->make_search()->search( new Search_Query( 'a' ), Universal_Search::get_sources() ) );
	}

	/**
	 * A snippet is found by its title, and described in plain text.
	 *
	 * @return void
	 */
	public function test_finds_snippet_by_title() {
		$items = $this->search_source( 'admin bar', 'snippets' );

		$this->assertCount( 1, $items );
		$this->assertSame( 'Disable admin bar', $items[0]['title'] );
		$this->assertSame( 'php', $items[0]['type'] );
		$this->assertSame( 'Turns off the toolbar for subscribers.', $items[0]['description'] );
		$this->assertSame( 'Functions', $items[0]['path'] );
		$this->assertContains( 'title', $items[0]['matched'] );
		$this->assertStringContainsString( 'id=' . $items[0]['snippet']['id'], $items[0]['url'] );
		$this->assertFalse( $items[0]['snippet']['active'] );
	}

	/**
	 * A match in code carries the line it was found on.
	 *
	 * @return void
	 */
	public function test_code_match_includes_the_matching_line() {
		$items = $this->search_source( 'show_admin_bar', 'snippets' );

		$this->assertCount( 1, $items );
		$this->assertSame( [ 'code' ], $items[0]['matched'] );
		$this->assertSame( 2, $items[0]['code']['line'] );
		$this->assertSame( 'show_admin_bar( false );', $items[0]['code']['text'] );
	}

	/**
	 * Leaving code out of the fields leaves code matches out of the results.
	 *
	 * @return void
	 */
	public function test_respects_the_requested_fields() {
		$this->assertSame( [], $this->search_source( 'show_admin_bar', 'snippets', [ 'title', 'description' ] ) );
	}

	/**
	 * Trashed snippets are not returned.
	 *
	 * @return void
	 */
	public function test_skips_trashed_snippets() {
		$items = $this->search_source( 'Red background', 'snippets' );
		$this->assertCount( 1, $items );

		trash_snippet( $items[0]['snippet']['id'] );

		$this->assertSame( [], $this->search_source( 'Red background', 'snippets' ) );
	}

	/**
	 * Someone who may only view snippets can find them by name, but never by code.
	 *
	 * @return void
	 */
	public function test_code_is_withheld_from_users_who_cannot_edit() {
		add_filter( 'code_snippets/search/can_read_code', '__return_false' );

		$code_items = $this->search_source( 'show_admin_bar', 'snippets' );
		$items = $this->search_source( 'admin bar', 'snippets' );
		$community_items = $this->search_source( 'admin bar', 'community' );

		remove_filter( 'code_snippets/search/can_read_code', '__return_false' );

		$this->assertSame( [], $code_items );
		$this->assertCount( 1, $items );
		$this->assertArrayNotHasKey( 'code', $items[0] );
		$this->assertArrayNotHasKey( 'cloud', $community_items[0] );
	}

	/**
	 * Someone with no access to snippets finds none.
	 *
	 * @return void
	 */
	public function test_snippets_are_hidden_without_view_permission() {
		wp_set_current_user( self::$viewer_user_id );

		$this->assertSame( [], $this->search_source( 'admin bar', 'snippets' ) );
	}

	/**
	 * A setting links to its tab, and to its own row within it.
	 *
	 * @return void
	 */
	public function test_setting_links_to_its_tab_and_field() {
		$items = $this->search_source( 'Enable Admin Bar Menu', 'settings' );

		$this->assertCount( 1, $items );
		$this->assertSame( 'setting', $items[0]['type'] );
		$this->assertSame( 'Settings › Interface', $items[0]['path'] );
		$this->assertStringContainsString( 'page=snippets-settings', $items[0]['url'] );
		$this->assertStringContainsString( 'section=interface', $items[0]['url'] );
		$this->assertStringEndsWith( '#setting-enable_admin_bar', $items[0]['url'] );
	}

	/**
	 * A setting is found by the text describing it.
	 *
	 * @return void
	 */
	public function test_setting_is_found_by_description() {
		$items = $this->search_source( 'instead of spaces', 'settings' );

		$this->assertCount( 1, $items );
		$this->assertSame( 'Indent With Tabs', $items[0]['title'] );
		$this->assertSame( [ 'description' ], $items[0]['matched'] );
	}

	/**
	 * Settings are only offered to someone who can open the settings screen.
	 *
	 * @return void
	 */
	public function test_settings_are_hidden_from_non_administrators() {
		wp_set_current_user( self::$viewer_user_id );

		$this->assertSame( [], $this->search_source( 'admin bar', 'settings' ) );
	}

	/**
	 * Stats reports link to their own chart on the Stats screen.
	 *
	 * @return void
	 */
	public function test_finds_stats_reports() {
		$items = $this->search_source( 'Activation status', 'stats' );

		$this->assertCount( 1, $items );
		$this->assertSame( 'stat', $items[0]['type'] );
		$this->assertStringContainsString( 'page=code-snippets-stats', $items[0]['url'] );
		$this->assertStringEndsWith( '#stats-chart-activation', $items[0]['url'] );
	}

	/**
	 * The welcome page sections are searchable.
	 *
	 * @return void
	 */
	public function test_finds_welcome_sections() {
		$items = $this->search_source( 'Helpful articles', 'welcome' );

		$this->assertNotEmpty( $items );
		$this->assertSame( 'guide', $items[0]['type'] );
		$this->assertStringContainsString( 'page=code-snippets-welcome', $items[0]['url'] );
	}

	/**
	 * Documentation articles are matched on their title and excerpt.
	 *
	 * @return void
	 */
	public function test_finds_docs_articles() {
		$items = $this->search_source( 'deactivate', 'docs' );

		$this->assertCount( 1, $items );
		$this->assertSame( 'doc', $items[0]['type'] );
		$this->assertSame( 'Docs › Features', $items[0]['path'] );
		$this->assertSame( 'https://codesnippets.pro/doc/admin-bar/', $items[0]['url'] );
		$this->assertSame( [ 'description' ], $items[0]['matched'] );
	}

	/**
	 * The community is searched without a cloud connection.
	 *
	 * @return void
	 */
	public function test_searches_the_community() {
		$groups = $this->make_search()->search( new Search_Query( 'admin bar' ), [ 'community' ] );

		$this->assertSame( [ 'community' ], array_column( $groups, 'source' ) );

		$item = $groups[0]['items'][0];
		$this->assertSame( 'Colour the admin bar', $item['title'] );
		$this->assertSame( 'css', $item['type'] );
		$this->assertSame( 'Community · by jlewis', $item['path'] );
		$this->assertSame( 501, $item['cloud']['id'] );
		$this->assertSame( '', $item['url'] );
		$this->assertSame( [ 'term' ], $this->search_methods );
	}

	/**
	 * A community snippet already downloaded to this site links to its local copy.
	 *
	 * @return void
	 */
	public function test_downloaded_community_snippet_links_to_its_local_copy() {
		$local = save_snippet(
			new Snippet(
				[
					'name'     => 'Downloaded copy',
					'code'     => '#wpadminbar { background: red; }',
					'scope'    => 'admin-css',
					'cloud_id' => 501,
				]
			)
		);

		$item = $this->search_source( 'admin bar', 'community' )[0];

		$this->assertSame( $local->id, $item['cloud']['local_id'] );
		$this->assertStringContainsString( 'id=' . $local->id, $item['url'] );
	}

	/**
	 * Whether a community snippet is on this site is decided here, not by the identifier the cloud reports.
	 *
	 * @return void
	 */
	public function test_community_snippet_ignores_the_local_id_reported_by_the_cloud() {
		$this->cloud_local_id = 999;

		$item = $this->search_source( 'admin bar', 'community' )[0];

		$this->assertNull( $item['cloud']['local_id'] );
		$this->assertSame( '', $item['url'] );
	}

	/**
	 * A shared network snippet only links to the network editor for users who can manage the network.
	 *
	 * @return void
	 */
	public function test_shared_network_snippet_links_only_for_network_managers() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Sharing with subsites only exists on multisite installs.' );
		}

		$shared = save_snippet(
			new Snippet(
				[
					'name'           => 'Shared admin bar colour',
					'code'           => '#wpadminbar { background: green; }',
					'scope'          => 'admin-css',
					'network'        => true,
					'shared_network' => true,
				]
			)
		);

		$find_shared = fn() => array_values(
			array_filter(
				$this->search_source( 'shared admin bar', 'snippets' ),
				fn( array $item ) => "snippets-network-$shared->id" === $item['id']
			)
		);

		$this->assertSame( '', $find_shared()[0]['url'] );

		grant_super_admin( self::$admin_user_id );
		$this->assertStringContainsString( 'id=' . $shared->id, $find_shared()[0]['url'] );
		revoke_super_admin( self::$admin_user_id );
	}

	/**
	 * An AI search is sent to the cloud as one, and keeps results that share no words with the question.
	 *
	 * @return void
	 */
	public function test_ai_search_keeps_the_cloud_results() {
		$query = new Search_Query( 'stop the toolbar showing for clients', Search_Query::FIELDS, true, 'ai' );
		$groups = $this->make_search()->search( $query, [ 'community' ] );

		$this->assertSame( [ 'ai' ], $this->search_methods );
		$this->assertSame( 'Colour the admin bar', $groups[0]['items'][0]['title'] );
		$this->assertSame( [], $groups[0]['items'][0]['matched'] );
	}

	/**
	 * A keyword search narrowed to particular fields only keeps the community results matching in them.
	 *
	 * @return void
	 */
	public function test_narrowed_keyword_search_filters_community_results() {
		$this->assertSame( [], $this->search_source( 'staging', 'community', [ 'title' ] ) );
		$this->assertCount( 1, $this->search_source( 'staging', 'community', [ 'description' ] ) );
	}

	/**
	 * Repeating a search reuses the cloud results fetched for the first one, but an AI search is fetched on its own.
	 *
	 * @return void
	 */
	public function test_community_results_are_cached_by_term_and_method() {
		$search = $this->make_search();

		$search->search( new Search_Query( 'admin bar' ), [ 'community' ] );
		$search->search( new Search_Query( 'Admin Bar' ), [ 'community' ] );
		$this->assertSame( [ 'term' ], $this->search_methods );

		$search->search( new Search_Query( 'admin bar', Search_Query::FIELDS, true, 'ai' ), [ 'community' ] );
		$this->assertSame( [ 'term', 'ai' ], $this->search_methods );
	}

	/**
	 * An unknown search method falls back to a keyword search.
	 *
	 * @return void
	 */
	public function test_unknown_method_is_a_keyword_search() {
		$this->assertSame( 'term', ( new Search_Query( 'admin bar', Search_Query::FIELDS, true, 'other' ) )->get_method() );
	}

	/**
	 * A source that has not been added is not searched.
	 *
	 * @return void
	 */
	public function test_skips_sources_that_have_not_been_added() {
		$this->assertNotContains( 'cloud', Universal_Search::get_sources() );
		$this->assertSame( [], $this->make_search()->search( new Search_Query( 'admin bar' ), [ 'cloud' ] ) );
		$this->assertSame( [], $this->search_methods );
	}

	/**
	 * A source added through the filters is searched like the built-in ones.
	 *
	 * @return void
	 */
	public function test_filters_can_add_a_source() {
		$add_source = fn( array $sources ) => array_merge( $sources, [ 'extra' ] );

		$search_source = function ( array $items, string $source, Search_Query $query, bool $network, Universal_Search $search ) {
			if ( 'extra' !== $source ) {
				return $items;
			}

			$entry = [
				'id'          => 'one',
				'type'        => 'guide',
				'title'       => 'Extra admin bar guide',
				'description' => '',
				'url'         => 'https://example.com/',
				'path'        => 'Extra',
			];

			return $search->search_entries( [ $entry ], $source, $query, $network );
		};

		add_filter( 'code_snippets/search/sources', $add_source );
		add_filter( 'code_snippets/search/source_items', $search_source, 10, 5 );

		$groups = $this->make_search()->search( new Search_Query( 'admin bar' ), [ 'extra' ] );

		remove_filter( 'code_snippets/search/sources', $add_source );
		remove_filter( 'code_snippets/search/source_items', $search_source );

		$this->assertSame( 'extra', $groups[0]['source'] );
		$this->assertSame( 'extra-one', $groups[0]['items'][0]['id'] );
		$this->assertSame( [ 'title' ], $groups[0]['items'][0]['matched'] );
	}

	/**
	 * Entries added to a source's index are searched with it.
	 *
	 * @return void
	 */
	public function test_filters_can_add_index_entries() {
		$add_entry = function ( array $entries, string $source ) {
			if ( 'stats' === $source ) {
				$entries[] = [
					'id'          => 'extra',
					'type'        => 'stat',
					'title'       => 'Execution time',
					'description' => 'How long snippets take to run.',
					'url'         => 'https://example.com/',
					'path'        => 'Stats',
				];
			}

			return $entries;
		};

		add_filter( 'code_snippets/search/entries', $add_entry, 10, 2 );
		$items = $this->search_source( 'Execution time', 'stats' );
		remove_filter( 'code_snippets/search/entries', $add_entry );

		$this->assertSame( 'stats-extra', $items[0]['id'] );
	}

	/**
	 * Results are capped per source, while the total still counts every match.
	 *
	 * @return void
	 */
	public function test_limits_items_but_reports_the_total() {
		$groups = $this->make_search()->search( new Search_Query( 'the' ), [ 'settings' ], false, 2 );

		$this->assertCount( 2, $groups[0]['items'] );
		$this->assertGreaterThan( 2, $groups[0]['total'] );
	}
}
