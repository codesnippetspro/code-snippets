<?php

namespace Code_Snippets\Client;

use Code_Snippets\Model\Basic_Cloud_Connection;
use Code_Snippets\Model\Cloud_Snippet;
use WP_Error;
use Code_Snippets\Model\Cloud_Snippets;

/**
 * Client for fetching data from Code Snippets Cloud.
 *
 * @package Code_Snippets
 */
class Cloud_Snippets_Client {

	/**
	 * Maximum number of cloud search results allowed per page.
	 */
	public const MAX_RESULTS_PER_PAGE = 100;

	/**
	 * Default number of cloud search results allowed per page.
	 */
	public const DEFAULT_RESULTS_PER_PAGE = 10;

	/**
	 * Request timeout, in seconds, for the cloud search endpoint. Higher than WordPress's 5s
	 * default because search can be slow on a cold cache and would otherwise time out.
	 */
	private const SEARCH_REQUEST_TIMEOUT = 15;

	/**
	 * Connection to Code Snippets Cloud.
	 *
	 * @var Basic_Cloud_Connection
	 */
	private Basic_Cloud_Connection $connection;

	/**
	 * Class constructor.
	 *
	 * @param Basic_Cloud_Connection $connection Connection to Code Snippets Cloud.
	 */
	public function __construct( Basic_Cloud_Connection $connection ) {
		$this->connection = $connection;
	}

	/**
	 * Retrive access token from the cloud connection.
	 *
	 * @return string
	 */
	public function get_access_control_token(): string {
		return $this->connection->get_local_token();
	}

	/**
	 * Unpack JSON data from a request response.
	 *
	 * @param array|WP_Error $response Response from wp_request_*.
	 *
	 * @return array<string, mixed>|null Associative array of JSON data on success, null on failure.
	 */
	private static function unpack_response_body( $response ): ?array {
		$body = wp_remote_retrieve_body( $response );

		if ( ! $body ) {
			return null;
		}

		$json = json_decode( $body, true );
		return is_array( $json ) ? $json : null;
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
	public function fetch_search_results( string $search_method, string $search, int $page, int $per_page, array $filters ): Cloud_Snippets {
		$per_page = min( self::MAX_RESULTS_PER_PAGE, max( 1, $per_page ) );

		$params = [
			's_method'   => $search_method,
			's'          => $search,
			'page'       => max( 0, $page - 1 ),
			'per_page'   => $per_page,
			'site_token' => $this->connection->get_local_token(),
			'site_host'  => wp_parse_url( get_site_url(), PHP_URL_HOST ),
		];

		foreach ( [ 'category', 'type', 'status' ] as $key ) {
			if ( ! empty( $filters[ $key ] ) ) {
				$params[ $key ] = $filters[ $key ];
			}
		}

		$api_url = add_query_arg( $params, sprintf( '%s/public/search', $this->connection->get_api_url() ) );

		// The search endpoint can be slow on a cold cache; allow more time than WordPress's
		// default 5s request timeout so the request is not cut short and returned as empty.
		$response = wp_remote_get( $api_url, [ 'timeout' => self::SEARCH_REQUEST_TIMEOUT ] );

		$results = Cloud_Snippets::unpack_api_response( self::unpack_response_body( $response ) );
		$results->page = $page;

		return $results;
	}

	/**
	 * Get the current revision of a single cloud snippet.
	 *
	 * @param string $cloud_id Cloud snippet ID.
	 *
	 * @return string|null Revision number on success, null otherwise.
	 */
	public function get_cloud_snippet_revision( string $cloud_id ): ?string {
		$api_url = sprintf( '%s/public/getsnippetrevision/%s', $this->connection->get_api_url(), $cloud_id );

		$cloud_snippet_revision = self::unpack_response_body( wp_remote_get( $api_url ) );

		return $cloud_snippet_revision
			? $cloud_snippet_revision['snippet_revision'] ?? null
			: null;
	}

	/**
	 * Retrieve a single cloud snippet from the API.
	 *
	 * @param int $cloud_id Remote cloud snippet ID.
	 *
	 * @return Cloud_Snippet Retrieved snippet.
	 */
	public function get_cloud_snippet( int $cloud_id ): Cloud_Snippet {
		$url = sprintf( '%s/public/getsnippet/%s', $this->connection->get_api_url(), $cloud_id );
		$response = wp_remote_get( $url );
		$cloud_snippet = self::unpack_response_body( $response );
		return new Cloud_Snippet( is_array( $cloud_snippet ) ? ( $cloud_snippet['snippet'] ?? [] ) : [] );
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
	public function get_featured_snippets( int $page, int $per_page, array $filters ): Cloud_Snippets {
		$per_page = min( self::MAX_RESULTS_PER_PAGE, max( 1, $per_page ) );

		$params = [
			'page'     => max( 0, $page - 1 ),
			'per_page' => $per_page,
		];

		foreach ( [ 'category', 'type', 'status' ] as $key ) {
			if ( ! empty( $filters[ $key ] ) ) {
				$params[ $key ] = $filters[ $key ];
			}
		}

		$response = wp_remote_get(
			add_query_arg( $params, sprintf( '%s/public/featured', $this->connection->get_api_url() ) ),
			[ 'headers' => $this->connection->get_request_headers() ]
		);

		$result = Cloud_Snippets::unpack_api_response( self::unpack_response_body( $response ) );
		$result->page = $page;

		return $result;
	}
}
