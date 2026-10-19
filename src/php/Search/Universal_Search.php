<?php

namespace Code_Snippets\Search;

use Code_Snippets\Client\Docs_Client;
use Code_Snippets\Controller\Cloud_Search_Controller;
use Code_Snippets\Model\Cloud_Snippet;
use Code_Snippets\Model\Snippet;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippets;
use function Code_Snippets\Settings\are_settings_unified;

/**
 * Searches every source the universal search covers.
 *
 * Each source is searched on its own and yields a group of the shape
 * `{ source, total, items }`. A source the current user may not see, or that
 * cannot be reached, yields nothing rather than an error, so one unavailable
 * source never takes the others down with it.
 *
 * Every item has the shape `{ id, source, type, title, description, url, path, matched }`,
 * with a `code` excerpt when the match is in code, a `snippet` summary for
 * local snippets and the full `cloud` snippet for cloud results.
 *
 * Further sources are added with the `code_snippets/search/sources` filter and
 * searched with the `code_snippets/search/source_items` filter.
 *
 * Trust model for the community source: community snippets come from Code
 * Snippets Cloud over HTTPS and are written by other users, so all of it is
 * treated as untrusted text. Names and descriptions are only ever rendered as
 * escaped text, descriptions are reduced to plain text, and code is returned
 * only to users who may edit snippets. Nothing is executed or saved until a user
 * chooses to download a snippet, which goes through the same path as the Cloud
 * screens.
 *
 * @package Code_Snippets
 */
class Universal_Search {

	/**
	 * Sources searched without leaving the site.
	 */
	public const LOCAL_SOURCES = [ 'snippets', 'settings', 'stats', 'welcome' ];

	/**
	 * Sources that depend on a remote service.
	 */
	public const REMOTE_SOURCES = [ 'docs', 'community' ];

	/**
	 * Transient holding recent community search results, keyed by search term and method.
	 */
	public const COMMUNITY_CACHE_KEY = 'code_snippets_search_community';

	/**
	 * How long remote cloud results are reused for.
	 */
	public const REMOTE_CACHE_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Number of community searches kept in the cache at once.
	 */
	private const COMMUNITY_CACHE_SIZE = 10;

	/**
	 * Number of community results requested for a search, which is as many as a group can list.
	 */
	private const COMMUNITY_RESULTS = 50;

	/**
	 * How much each matched field counts towards the order of results.
	 */
	private const FIELD_WEIGHTS = [
		'title'       => 4,
		'description' => 2,
		'code'        => 1,
	];

	/**
	 * Number of matches the cloud reported for each source searched remotely,
	 * which can be more than were fetched.
	 *
	 * @var array<string, int>
	 */
	private array $remote_totals = [];

	/**
	 * Cloud search controller.
	 *
	 * @var Cloud_Search_Controller
	 */
	private Cloud_Search_Controller $cloud_search;

	/**
	 * Documentation client.
	 *
	 * @var Docs_Client
	 */
	private Docs_Client $docs_client;

	/**
	 * Class constructor.
	 *
	 * @param Cloud_Search_Controller $cloud_search Cloud search controller.
	 * @param Docs_Client             $docs_client  Documentation client.
	 */
	public function __construct( Cloud_Search_Controller $cloud_search, Docs_Client $docs_client ) {
		$this->cloud_search = $cloud_search;
		$this->docs_client = $docs_client;
	}

	/**
	 * Retrieve every source that can be searched, local sources first.
	 *
	 * @return string[]
	 */
	public static function get_sources(): array {
		$sources = apply_filters(
			'code_snippets/search/sources',
			array_merge( self::LOCAL_SOURCES, self::REMOTE_SOURCES )
		);

		return array_values( array_unique( array_filter( (array) $sources, 'is_string' ) ) );
	}

	/**
	 * Purge cached remote results.
	 *
	 * @return void
	 */
	public static function clear_caches() {
		delete_transient( self::COMMUNITY_CACHE_KEY );
		do_action( 'code_snippets/search/clear_caches' );
	}

	/**
	 * Search the requested sources.
	 *
	 * @param Search_Query $query   Search query.
	 * @param string[]     $sources Sources to search.
	 * @param bool         $network Whether to search network-wide snippets and link to the network admin.
	 * @param int          $limit   Largest number of items to return for each source.
	 *
	 * @return array<int, array{source: string, total: int, items: array<int, array<string, mixed>>}>
	 */
	public function search( Search_Query $query, array $sources, bool $network = false, int $limit = 50 ): array {
		if ( ! $query->is_searchable() ) {
			return [];
		}

		$groups = [];
		$this->remote_totals = [];

		foreach ( array_intersect( self::get_sources(), $sources ) as $source ) {
			$items = $this->search_source( $source, $query, $network );

			if ( $items ) {
				$groups[] = [
					'source' => $source,
					'total'  => max( count( $items ), $this->remote_totals[ $source ] ?? 0 ),
					'items'  => array_slice( $items, 0, max( 1, $limit ) ),
				];
			}
		}

		return $groups;
	}

	/**
	 * Record how many matches a remote service reported for a source, when that
	 * is more than the items it returned.
	 *
	 * @param string $source Source name.
	 * @param int    $total  Number of matches.
	 *
	 * @return void
	 */
	public function set_remote_total( string $source, int $total ) {
		$this->remote_totals[ $source ] = $total;
	}

	/**
	 * Search a single source.
	 *
	 * @param string       $source  Source name.
	 * @param Search_Query $query   Search query.
	 * @param bool         $network Whether to search network-wide snippets and link to the network admin.
	 *
	 * @return array<int, array<string, mixed>> Matching items, best match first.
	 */
	private function search_source( string $source, Search_Query $query, bool $network ): array {
		$index = new Search_Index( $network );

		switch ( $source ) {
			case 'snippets':
				$items = $this->search_snippets( $query, $network );
				break;

			case 'settings':
				$items = $this->can_access_settings() ? $this->search_entries( $index->get_settings_entries(), $source, $query, $network ) : [];
				break;

			case 'stats':
				$items = self::can_view_snippets() ? $this->search_entries( $index->get_stats_entries(), $source, $query, $network ) : [];
				break;

			case 'welcome':
				$items = $this->search_entries( $index->get_welcome_entries(), $source, $query, $network );
				break;

			case 'docs':
				$items = $this->search_docs( $query );
				break;

			case 'community':
				$items = $this->search_cloud_snippets( $this->get_community_snippets( $query ), $source, $query );
				break;

			default:
				$items = [];
		}

		/**
		 * Filter the items found in a source, or provide them for a source added with `code_snippets/search/sources`.
		 *
		 * @param array<int, array<string, mixed>> $items   Matching items, best match first.
		 * @param string                           $source  Source name.
		 * @param Search_Query                     $query   Search query.
		 * @param bool                             $network Whether network-wide snippets are being searched.
		 * @param Universal_Search                 $search  Search service, for its helper methods.
		 */
		$items = apply_filters( 'code_snippets/search/source_items', $items, $source, $query, $network, $this );

		return is_array( $items ) ? array_values( $items ) : [];
	}

	/**
	 * Determine whether the current user can open the settings screen.
	 *
	 * @return bool
	 */
	private function can_access_settings(): bool {
		if ( are_settings_unified() && ! is_super_admin() ) {
			return false;
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Determine whether the current user is allowed to see the snippets saved on this site.
	 *
	 * @return bool
	 */
	public static function can_view_snippets(): bool {
		return (bool) apply_filters( 'code_snippets/search/can_view_snippets', code_snippets()->current_user_can() );
	}

	/**
	 * Determine whether the current user is allowed to read snippet code.
	 *
	 * @return bool
	 */
	public static function can_read_code(): bool {
		return (bool) apply_filters( 'code_snippets/search/can_read_code', code_snippets()->current_user_can() );
	}

	/**
	 * Order items so the strongest matches come first, keeping the source's own order between equals.
	 *
	 * @param array<int, array<string, mixed>> $items Matching items.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function rank( array $items ): array {
		$scores = array_map(
			fn( array $item ) => array_sum( array_intersect_key( self::FIELD_WEIGHTS, array_flip( $item['matched'] ) ) ),
			$items
		);

		$order = array_keys( $items );

		usort(
			$order,
			fn( int $a, int $b ) => $scores[ $b ] <=> $scores[ $a ] ? $scores[ $b ] <=> $scores[ $a ] : $a <=> $b
		);

		return array_map( fn( int $index ) => $items[ $index ], $order );
	}

	/**
	 * Search a list of index entries by their title and description.
	 *
	 * @param array<int, array<string, string>> $entries Index entries.
	 * @param string                            $source  Source the entries belong to.
	 * @param Search_Query                      $query   Search query.
	 * @param bool                              $network Whether links lead to the network admin.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function search_entries( array $entries, string $source, Search_Query $query, bool $network = false ): array {
		/**
		 * Filter the index entries searched for a source.
		 *
		 * @param array<int, array<string, string>> $entries Entries of the shape `{ id, type, title, description, url, path }`.
		 * @param string                            $source  Source the entries belong to.
		 * @param bool                              $network Whether links lead to the network admin.
		 */
		$entries = (array) apply_filters( 'code_snippets/search/entries', $entries, $source, $network );
		$items = [];

		foreach ( $entries as $entry ) {
			$matched = $query->matched_fields(
				[
					'title'       => $entry['title'],
					'description' => $entry['description'],
				]
			);

			if ( $matched ) {
				$items[] = array_merge(
					$entry,
					[
						'id'          => "$source-{$entry['id']}",
						'source'      => $source,
						'description' => $query->excerpt( $entry['description'] ),
						'matched'     => $matched,
					]
				);
			}
		}

		return $this->rank( $items );
	}

	/**
	 * Retrieve the snippets listed on the snippets screen: those of this site, or
	 * of the network, along with any network snippets shared with this site.
	 *
	 * @param bool $network Whether to look at network-wide snippets.
	 *
	 * @return Snippet[]
	 */
	private static function get_listed_snippets( bool $network ): array {
		$snippets = get_snippets( [], $network );

		if ( ! is_multisite() || $network ) {
			return $snippets;
		}

		$shared_ids = get_site_option( 'shared_network_snippets' );

		if ( ! $shared_ids || ! is_array( $shared_ids ) ) {
			return $snippets;
		}

		$active_shared = (array) get_option( 'active_shared_network_snippets', [] );

		foreach ( get_snippets( $shared_ids, true ) as $shared ) {
			$shared->shared_network = true;
			$shared->active = in_array( $shared->id, $active_shared, true );
			$snippets[] = $shared;
		}

		return $snippets;
	}

	/**
	 * Search the snippets saved on this site.
	 *
	 * @param Search_Query $query   Search query.
	 * @param bool         $network Whether to search network-wide snippets.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function search_snippets( Search_Query $query, bool $network ): array {
		if ( ! self::can_view_snippets() ) {
			return [];
		}

		$can_read_code = self::can_read_code();
		$edit_url = code_snippets()->get_menu_url( 'edit', $network ? 'network' : 'admin' );
		// Shared network snippets can only be edited by those who manage the network, as in the snippets table.
		$network_edit_url = current_user_can( code_snippets()->get_network_cap_name() ) ? code_snippets()->get_menu_url( 'edit', 'network' ) : '';
		$type_labels = self::get_type_labels();
		$items = [];

		foreach ( self::get_listed_snippets( $network ) as $snippet ) {
			if ( $snippet->trashed ) {
				continue;
			}

			// A condition stores its rules as JSON, which is not code anyone searches for.
			$code = $can_read_code && ! $snippet->is_condition() ? (string) $snippet->code : '';
			$description = Search_Query::plain_text( (string) $snippet->desc );

			$matched = $query->matched_fields(
				[
					'title'       => $snippet->display_name,
					'description' => $description,
					'code'        => $code,
				]
			);

			if ( ! $matched ) {
				continue;
			}

			// Shared network snippets live in their own table, so their IDs can repeat those of this site.
			$is_shared = $snippet->shared_network && ! $network;
			$snippet_edit_url = $is_shared ? $network_edit_url : $edit_url;

			$item = [
				'id'          => $is_shared ? "snippets-network-$snippet->id" : "snippets-$snippet->id",
				'source'      => 'snippets',
				'type'        => $snippet->type,
				'title'       => $snippet->display_name,
				'description' => $query->excerpt( $description ),
				'url'         => $snippet_edit_url ? add_query_arg( 'id', $snippet->id, $snippet_edit_url ) : '',
				'path'        => $type_labels[ $snippet->type ] ?? '',
				'matched'     => $matched,
				'snippet'     => [
					'id'             => $snippet->id,
					'scope'          => $snippet->scope,
					'active'         => (bool) $snippet->active,
					'locked'         => (bool) $snippet->locked,
					'network'        => (bool) $snippet->network,
					'shared_network' => (bool) $snippet->shared_network,
				],
			];

			if ( in_array( 'code', $matched, true ) ) {
				$item['code'] = $query->find_code_line( $code );
			}

			$items[] = $item;
		}

		return $this->rank( $items );
	}

	/**
	 * Retrieve the names of the snippet types.
	 *
	 * @return array<string, string>
	 */
	private static function get_type_labels(): array {
		return [
			'php'  => __( 'Functions', 'code-snippets' ),
			'html' => __( 'Content', 'code-snippets' ),
			'css'  => __( 'Styles', 'code-snippets' ),
			'js'   => __( 'Scripts', 'code-snippets' ),
			'cond' => __( 'Conditions', 'code-snippets' ),
		];
	}

	/**
	 * Search the documentation articles.
	 *
	 * @param Search_Query $query Search query.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function search_docs( Search_Query $query ): array {
		$docs_label = __( 'Docs', 'code-snippets' );
		$items = [];

		foreach ( $this->docs_client->get_articles() as $article ) {
			$matched = $query->matched_fields(
				[
					'title'       => $article['title'],
					'description' => $article['text'],
				]
			);

			if ( $matched ) {
				$items[] = [
					'id'          => "docs-{$article['id']}",
					'source'      => 'docs',
					'type'        => 'doc',
					'title'       => $article['title'],
					'description' => $query->excerpt( $article['text'] ),
					'url'         => $article['url'],
					'path'        => Search_Index::build_path( $docs_label, $article['category'] ),
					'matched'     => $matched,
				];
			}
		}

		return $this->rank( $items );
	}

	/**
	 * Make a request to Code Snippets Cloud, treating an error status as a failure.
	 *
	 * The cloud answers errors with a JSON body, which the clients unpack into an
	 * empty result indistinguishable from a search that found nothing. Caching
	 * that would hide the source until the cache expired, so the status of every
	 * response received while the request runs is checked as well.
	 *
	 * @param callable $request Function making the request and returning its result.
	 *
	 * @return mixed|null The request's result, or null when it failed.
	 */
	public static function request_cloud( callable $request ) {
		$failed = false;

		$watch = function ( $response ) use ( &$failed ) {
			if ( wp_remote_retrieve_response_code( $response ) >= 400 ) {
				$failed = true;
			}

			return $response;
		};

		add_filter( 'http_response', $watch );

		try {
			$result = $request();
		} finally {
			remove_filter( 'http_response', $watch );
		}

		return $failed ? null : $result;
	}

	/**
	 * Retrieve the community snippets matching a search.
	 *
	 * @param Search_Query $query Search query.
	 *
	 * @return Cloud_Snippet[]
	 */
	private function get_community_snippets( Search_Query $query ): array {
		$cache = get_transient( self::COMMUNITY_CACHE_KEY );
		$cache = is_array( $cache ) ? $cache : [];
		$cache_key = md5( $query->get_method() . '|' . strtolower( $query->get_term() ) );
		$now = time();

		$entry = $cache[ $cache_key ] ?? null;

		if ( ! is_array( $entry ) || ! isset( $entry['expires'], $entry['snippets'] ) || $entry['expires'] <= $now ) {
			$result = self::request_cloud(
				fn() => $this->cloud_search->fetch_search_results( $query->get_method(), $query->get_term(), 1, self::COMMUNITY_RESULTS )
			);

			if ( ! $result ) {
				return [];
			}

			$entry = [
				'expires'  => $now + self::REMOTE_CACHE_TTL,
				'total'    => (int) $result->total_snippets,
				'snippets' => array_map( fn( Cloud_Snippet $snippet ) => $snippet->get_fields(), $result->snippets ),
			];

			$cache = array_filter( $cache, fn( $cached ) => is_array( $cached ) && ( $cached['expires'] ?? 0 ) > $now );
			$cache = array_slice( $cache, 1 - self::COMMUNITY_CACHE_SIZE, null, true );
			$cache[ $cache_key ] = $entry;
			set_transient( self::COMMUNITY_CACHE_KEY, $cache, self::REMOTE_CACHE_TTL );
		}

		// The cloud's count only describes the results when they are not narrowed down again here.
		if ( $query->is_ai() || Search_Query::FIELDS === $query->get_fields() ) {
			$this->set_remote_total( 'community', (int) ( $entry['total'] ?? 0 ) );
		}

		return self::attach_local_ids( array_map( fn( array $fields ) => new Cloud_Snippet( $fields ), $entry['snippets'] ) );
	}

	/**
	 * Record which cloud snippets have already been downloaded to this site.
	 *
	 * @param Cloud_Snippet[] $cloud_snippets Cloud snippets.
	 *
	 * @return Cloud_Snippet[] The same snippets, with local identifiers attached where known.
	 */
	private static function attach_local_ids( array $cloud_snippets ): array {
		$local_ids = [];

		foreach ( get_snippets() as $local_snippet ) {
			if ( $local_snippet->cloud_id && ! $local_snippet->trashed ) {
				$local_ids[ $local_snippet->cloud_id ] = $local_snippet->id;
			}
		}

		foreach ( $cloud_snippets as $cloud_snippet ) {
			$cloud_snippet->local_id = $local_ids[ $cloud_snippet->id ] ?? null;
		}

		return $cloud_snippets;
	}

	/**
	 * Build the path shown for a cloud snippet.
	 *
	 * @param Cloud_Snippet $cloud_snippet Cloud snippet.
	 * @param string        $source        Source the snippet belongs to.
	 *
	 * @return string
	 */
	private static function get_cloud_snippet_path( Cloud_Snippet $cloud_snippet, string $source ): string {
		if ( 'community' !== $source ) {
			$state = $cloud_snippet->local_id ? __( 'Downloaded', 'code-snippets' ) : __( 'Not downloaded', 'code-snippets' );
			return __( 'Cloud Library', 'code-snippets' ) . " · $state";
		}

		// translators: %s: name of the author's cloud library.
		$origin = $cloud_snippet->codevault ? sprintf( __( 'by %s', 'code-snippets' ), $cloud_snippet->codevault ) : '';

		return implode( ' · ', array_filter( [ __( 'Community', 'code-snippets' ), $origin ] ) );
	}

	/**
	 * Search a list of cloud snippets.
	 *
	 * Results the cloud already found for the community are kept even when nothing
	 * matches here, as the cloud also matches fields such as tags, and an AI search
	 * matches by meaning rather than by words. They are only narrowed down again
	 * when a keyword search is limited to particular fields.
	 *
	 * @param Cloud_Snippet[] $cloud_snippets Cloud snippets to search.
	 * @param string          $source         Source the snippets belong to.
	 * @param Search_Query    $query          Search query.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function search_cloud_snippets( array $cloud_snippets, string $source, Search_Query $query ): array {
		$can_read_code = self::can_read_code();
		$keeps_cloud_matches = 'community' === $source && ( $query->is_ai() || Search_Query::FIELDS === $query->get_fields() );
		$edit_url = code_snippets()->get_menu_url( 'edit', 'admin' );
		$items = [];

		foreach ( $cloud_snippets as $cloud_snippet ) {
			$type = Snippet::get_type_from_scope( (string) $cloud_snippet->scope );
			$description = Search_Query::plain_text( (string) $cloud_snippet->description );
			$code = $can_read_code && 'cond' !== $type ? (string) $cloud_snippet->code : '';

			$matched = $query->matched_fields(
				[
					'title'       => (string) $cloud_snippet->name,
					'description' => $description,
					'code'        => $code,
				]
			);

			if ( ! $matched && ! $keeps_cloud_matches ) {
				continue;
			}

			$item = [
				'id'          => "$source-$cloud_snippet->id",
				'source'      => $source,
				'type'        => $type,
				'title'       => (string) $cloud_snippet->name,
				'description' => $query->excerpt( $description ),
				'url'         => $cloud_snippet->local_id ? add_query_arg( 'id', $cloud_snippet->local_id, $edit_url ) : '',
				'path'        => self::get_cloud_snippet_path( $cloud_snippet, $source ),
				'matched'     => $matched,
			];

			if ( $can_read_code ) {
				$item['cloud'] = $cloud_snippet->get_fields();
			}

			if ( in_array( 'code', $matched, true ) ) {
				$item['code'] = $query->find_code_line( $code );
			}

			$items[] = $item;
		}

		// The cloud ranks AI results by how closely they answer the question, which is kept.
		return $query->is_ai() ? $items : $this->rank( $items );
	}
}
