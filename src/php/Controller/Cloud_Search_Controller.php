<?php

namespace Code_Snippets\Controller;

use Code_Snippets\Client\Cloud_Public_Client;
use Code_Snippets\Model\Basic_Cloud_Connection;
use Code_Snippets\Model\Cloud_Snippet;
use Code_Snippets\Model\Cloud_Snippets;
use Code_Snippets\Model\Snippet;
use WP_REST_Request;
use function Code_Snippets\save_snippet;

/**
 * Controller for interfacing with public data on Code Snippets Cloud.
 */
class Cloud_Search_Controller {

	/**
	 * Maximum number of cloud search results allowed per page.
	 */
	public const MAX_RESULTS_PER_PAGE = 100;

	/**
	 * Minimum TTL in seconds for the featured snippets transient.
	 */
	private const FEATURED_MIN_TTL = 3600;

	/**
	 * Option key holding the current featured-snippets cache version.
	 *
	 * Bumped on flush so old transient keys become unreachable and expire naturally.
	 */
	private const FEATURED_VERSION_OPTION = 'cs_featured_cache_version';

	/**
	 * Transient key for cached featured snippets.
	 */
	private const FEATURED_TRANSIENT_KEY = 'cs_featured_snippets';

	/**
	 * Cloud snippets client instance.
	 *
	 * @var Cloud_Public_Client
	 */
	private Cloud_Public_Client $client;

	/**
	 * Class constructor.
	 *
	 * @param Basic_Cloud_Connection $connection Connection to Code Snippets Cloud.
	 */
	public function __construct( Basic_Cloud_Connection $connection ) {
		$this->client = new Cloud_Public_Client( $connection );
	}

	/**
	 * Verify a REST API request is authorised to access controller functions.
	 *
	 * @param WP_REST_Request $request The REST API request.
	 *
	 * @return bool
	 */
	public function verify_rest_request( WP_REST_Request $request ): bool {
		return $this->client->verify_rest_request( $request );
	}

	/**
	 * Refresh the cached synced data.
	 *
	 * Bumps the featured-cache version counter so previously cached keys
	 * become unreachable and expire via WordPress's normal transient path.
	 *
	 * @return void
	 */
	public static function clear_caches() {
		delete_transient( self::FEATURED_VERSION_OPTION );
	}

	/**
	 * Retrieve a single cloud snippet from the API.
	 *
	 * @param int $cloud_id Remote cloud snippet ID.
	 *
	 * @return Cloud_Snippet Retrieved snippet.
	 */
	public function get_cloud_snippet( int $cloud_id ): ?Cloud_Snippet {
		return $this->client->get_cloud_snippet( $cloud_id );
	}

	/**
	 * Search Code Snippets Cloud.
	 *
	 * @param string               $search_method Search by name of codevault or keyword(s).
	 * @param string               $search        Search query.
	 * @param int                  $page          Search result page to retrieve. Defaults to '1'.
	 * @param int                  $per_page      Number of search results to retrieve per page.
	 * @param array<string,string> $filters       Optional filters: category, type, status.
	 *
	 * @return Cloud_Snippets Result of search query.
	 */
	public function fetch_search_results( string $search_method, string $search, int $page = 1, int $per_page = 10, array $filters = [] ): ?Cloud_Snippets {
		$per_page = min( self::MAX_RESULTS_PER_PAGE, $per_page );
		return $this->client->fetch_search_results( $search_method, $search, $page, $per_page, $filters );
	}

	/**
	 * Download a snippet from the cloud.
	 *
	 * @param Cloud_Snippet $cloud_snippet The snippet to be downloaded.
	 *
	 * @return Snippet|null The newly-created local snippet.
	 */
	public function download_snippet_from_cloud( Cloud_Snippet $cloud_snippet ): ?Snippet {
		$snippet = new Snippet( $cloud_snippet );

		// Set the snippet id to 0 to ensure that the snippet is saved as a new snippet.
		$snippet->id = 0;
		$snippet->active = 0;
		$snippet->cloud_id = $cloud_snippet->id;
		$snippet->is_cloud_owner = $cloud_snippet->is_owner;
		$snippet->desc = $cloud_snippet->description ?? '';

		return save_snippet( $snippet );
	}

	/**
	 * Build the transient key for a specific (version, page, per_page, filters) slot.
	 *
	 * @param int                  $page     Page number (1-indexed).
	 * @param int                  $per_page Results per page.
	 * @param array<string,string> $filters  Filter values.
	 *
	 * @return string
	 */
	private function build_featured_cache_key( int $page, int $per_page, array $filters ): string {
		$encoded = wp_json_encode( array_filter( $filters ) );
		$filter_hash = md5( false === $encoded ? '' : $encoded );

		$version = get_transient( self::FEATURED_VERSION_OPTION );

		if ( ! $version ) {
			$version = (string) ( microtime( true ) * 1000 );
			set_transient( self::FEATURED_VERSION_OPTION, $version, MONTH_IN_SECONDS );
		}

		return self::FEATURED_TRANSIENT_KEY . "_v{$version}_p{$page}_pp{$per_page}_$filter_hash";
	}

	/**
	 * Retrieve featured snippets from the cloud API, with transient caching.
	 *
	 * @param int                  $page     Page number (1-indexed).
	 * @param int                  $per_page Results per page.
	 * @param array<string,string> $filters  Optional filters: category, type, status.
	 *
	 * @return Cloud_Snippets Featured snippets, or an empty result on failure.
	 */
	public function get_featured_snippets( int $page = 1, int $per_page = 10, array $filters = [] ): ?Cloud_Snippets {
		$cache_key = self::build_featured_cache_key( $page, $per_page, $filters );
		$cached = get_transient( $cache_key );

		if ( $cached instanceof Cloud_Snippets ) {
			return $cached;
		}

		$per_page = min( self::MAX_RESULTS_PER_PAGE, max( 1, $per_page ) );
		$result = $this->client->get_featured_snippets( $page, $per_page, $filters );

		if ( $result ) {
			set_transient( $cache_key, $result, self::FEATURED_MIN_TTL );
		}

		return $result;
	}
}
