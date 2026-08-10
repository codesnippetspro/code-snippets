<?php

namespace Code_Snippets\Client;

use Code_Snippets\Model\Basic_Cloud_Connection;
use Code_Snippets\Model\Cloud_Snippet;
use Code_Snippets\Model\Cloud_Snippets;
use WP_REST_Request;
use function Code_Snippets\Utils\unpack_response_body;

/**
 * Client for fetching public data from Code Snippets Cloud.
 *
 * @package Code_Snippets
 */
class Cloud_Public_Client {

	/**
	 * Request timeout, in seconds, for the cloud search endpoint. Higher than WordPress's 5s
	 * default because search can be slow on a cold cache and would otherwise time out.
	 */
	private const SEARCH_REQUEST_TIMEOUT = 15;

	/**
	 * Maximum number of cloud search results allowed per page.
	 */
	public const MAX_RESULTS_PER_PAGE = 100;

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
	 * Verify a REST API request is authorised to access controller functions.
	 *
	 * @param WP_REST_Request $request The REST API request.
	 *
	 * @return bool
	 */
	public function verify_rest_request( WP_REST_Request $request ): bool {
		return $this->connection->verify_rest_request( $request );
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
	public function fetch_search_results( string $search_method, string $search, int $page, int $per_page, array $filters ): ?Cloud_Snippets {
		$params = [
			's_method'   => $search_method,
			's'          => $search,
			'page'       => max( 0, $page - 1 ),
			'per_page'   => max( 1, $per_page ),
			'site_token' => $this->connection->get_local_token(),
			'site_host'  => wp_parse_url( get_site_url(), PHP_URL_HOST ),
		];

		foreach ( [ 'category', 'type', 'status' ] as $key ) {
			if ( ! empty( $filters[ $key ] ) ) {
				$params[ $key ] = $filters[ $key ];
			}
		}

		$response = wp_remote_get(
			add_query_arg( $params, sprintf( '%s/public/search', $this->connection->get_api_url() ) ),
			// The search endpoint can be slow on a cold cache; allow more time than WordPress's
			// default 5s request timeout so the request is not cut short and returned as empty.
			[ 'timeout' => self::SEARCH_REQUEST_TIMEOUT ]
		);

		return Cloud_Snippets::unpack_api_response( unpack_response_body( $response ), $page );
	}

	/**
	 * Retrieve a single cloud snippet from the API.
	 *
	 * @param int $cloud_id Remote cloud snippet ID.
	 *
	 * @return Cloud_Snippet Retrieved snippet.
	 */
	public function get_cloud_snippet( int $cloud_id ): ?Cloud_Snippet {
		$response = wp_remote_get(
			sprintf( '%s/public/getsnippet/%s', $this->connection->get_api_url(), $cloud_id )
		);

		$data = unpack_response_body( $response );

		if ( ! is_array( $data ) || empty( $data['success'] ) || ! is_array( $data['snippet'] ?? null ) ) {
			return null;
		}

		return new Cloud_Snippet( $data['snippet'] );
	}

	/**
	 * Get the current revision of a single cloud snippet.
	 *
	 * @param string $cloud_id Cloud snippet ID.
	 *
	 * @return string|null Revision number on success, null otherwise.
	 */
	public function get_cloud_snippet_revision( string $cloud_id ): ?string {
		$response = wp_remote_get(
			sprintf( '%s/public/getsnippetrevision/%s', $this->connection->get_api_url(), $cloud_id )
		);

		$body = unpack_response_body( $response );
		return $body['snippet_revision'] ?? null;
	}

	/**
	 * Retrieve featured snippets from the cloud API, with transient caching.
	 *
	 * @param int                  $page     Page number (1-indexed).
	 * @param int                  $per_page Results per page.
	 * @param array<string,string> $filters  Optional filters: category, type, status.
	 *
	 * @return Cloud_Snippets|null Featured snippets, or an null on failure.
	 */
	public function get_featured_snippets( int $page, int $per_page, array $filters ): ?Cloud_Snippets {
		$params = [
			'page'     => max( 0, $page - 1 ),
			'per_page' => min( self::MAX_RESULTS_PER_PAGE, max( 1, $per_page ) ),
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

		return Cloud_Snippets::unpack_api_response( unpack_response_body( $response ), $page );
	}
}
