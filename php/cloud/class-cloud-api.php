<?php

namespace Code_Snippets\Cloud;

use Code_Snippets\Snippet;
use WP_Error;
use function Code_Snippets\get_snippet_by_cloud_id;
use function Code_Snippets\get_snippets;
use function Code_Snippets\save_snippet;
use function Code_Snippets\update_snippet_fields;

/**
 * Functions used to manage cloud synchronisation.
 *
 * @package Code_Snippets
 */
class Cloud_API {

	/**
	 * Base URL for cloud API.
	 *
	 * @var string
	 */
	const CLOUD_API_URL = 'https://codesnippets.cloud/api/v1/';

	/**
	 * Base URL for cloud API.
	 *
	 * @var string
	 */
	const CLOUD_SEARCH_API_TOKEN = 'csc-1a2b3c4d5e6f7g8h9i0j';


	/**
	 * Key used to access the local-to-cloud map transient data.
	 *
	 * @var string
	 */
	const CLOUD_MAP_TRANSIENT_KEY = 'cs_local_to_cloud_map';


	/**
	 * Days to cache data retrieved from API.
	 *
	 * @var integer
	 */
	const DAYS_TO_STORE_CS = 1;

	/**
	 * Local to Cloud Snippets Map Object
	 *
	 * @var Cloud_Link[]|null
	 */
	private $local_to_cloud_map = null;

	/**
	 * Create local-to-cloud map to keep track of local snippets that have been synced to the cloud.
	 *
	 * @return Cloud_Link[]
	 */
	public function get_local_to_cloud_map(): ?array {
		// Return the cached data if available.
		if ( $this->local_to_cloud_map ) {
			return $this->local_to_cloud_map;
		}

		// Fetch data from the stored transient, if available.
		$stored_data = get_transient( self::CLOUD_MAP_TRANSIENT_KEY );
		if ( $stored_data ) {
			$this->local_to_cloud_map = $stored_data;
			return $stored_data;
		}

		// Otherwise, regenerate the local-to-cloud-map.
		$this->local_to_cloud_map = [];

		// Fetch and iterate through all local snippets to create the map.
		foreach ( get_snippets() as $local_snippet ) {
			// Skip snippets that are only stored locally.
			if ( ! $local_snippet->cloud_id ) {
				continue;
			}

			// If the snippet is a token snippet skip it.
			$has_valid_cloud_id = boolval( strpos( $local_snippet->cloud_id, '_' ) );
			if ( ! $has_valid_cloud_id ) {
				continue;
			}

			$link = new Cloud_Link();
			$cloud_id = explode( '_', $local_snippet->cloud_id );
			$cloud_id_int = (int) $cloud_id[0] ?? '';
			$link->local_id = $local_snippet->id;
			$link->cloud_id = $cloud_id_int;
			$link->is_owner = false;
			$link->in_codevault = false;

			$this->local_to_cloud_map[] = $link;
		}

		set_transient(
			self::CLOUD_MAP_TRANSIENT_KEY,
			$this->local_to_cloud_map,
			DAY_IN_SECONDS * self::DAYS_TO_STORE_CS
		);

		return $this->local_to_cloud_map;
	}

	/**
	 * Unpack JSON data from a request response.
	 *
	 * @param array|WP_Error $response Response from wp_request_*.
	 *
	 * @return array<string, mixed>|null Associative array of JSON data on success, null on failure.
	 */
	private static function unpack_request_json( $response ): ?array {
		$body = wp_remote_retrieve_body( $response );
		return $body ? json_decode( $body, true ) : null;
	}

	/**
	 * Search Code Snippets Cloud -> Static Function
	 *
	 * @param string  $search_method Search by name of codevault or keyword(s).
	 * @param string  $search        Search query.
	 * @param integer $page          Search result page to retrieve. Defaults to '0'.
	 *
	 * @return Cloud_Snippets Result of search query.
	 */
	public static function fetch_search_results( string $search_method, string $search, int $page = 0 ): Cloud_Snippets {
		$api_url = add_query_arg(
			[
				's_method'   => $search_method,
				's'          => $search,
				'page'       => $page,
				'site_token' => self::CLOUD_SEARCH_API_TOKEN,
				'site_host'  => wp_parse_url( get_site_url(), PHP_URL_HOST ),
			],
			self::CLOUD_API_URL . 'public/search'
		);

		$results = self::unpack_request_json( wp_remote_get( $api_url ) );

		$results = new Cloud_Snippets( $results );
		$results->page = $page;

		return $results;
	}

	/**
	 * Refresh all stored data.
	 *
	 * @return void
	 */
	public function refresh_synced_data() {
		// Simply deleting the data is sufficient, as it will be recreated and stored the next time it is requested.
		$this->local_to_cloud_map = null;
		delete_transient( self::CLOUD_MAP_TRANSIENT_KEY );
	}

	/**
	 * Add a new link item to the local-to-cloud map.
	 *
	 * @param Cloud_Link $link Link to add.
	 *
	 * @return void
	 */
	public function add_map_link( Cloud_Link $link ) {
		$local_to_cloud_map = get_transient( self::CLOUD_MAP_TRANSIENT_KEY );
		$local_to_cloud_map[] = $link;

		set_transient(
			self::CLOUD_MAP_TRANSIENT_KEY,
			$local_to_cloud_map,
			DAY_IN_SECONDS * self::DAYS_TO_STORE_CS
		);
	}

	/**
	 * Delete a snippet from local-to-cloud map.
	 *
	 * @param integer $snippet_id Local snippet ID.
	 *
	 * @return Cloud_Link|null The deleted map link if one was found, null otherwise.
	 */
	public function delete_snippet_from_transient_data( int $snippet_id ): ?Cloud_Link {
		$this->get_local_to_cloud_map();
		$link_to_delete = null;

		foreach ( $this->local_to_cloud_map as $link ) {
			if ( $link->local_id === $snippet_id ) {
				$link_to_delete = $link;
				break;
			}
		}
		if ( $link_to_delete ) {

			$this->refresh_synced_data();
		}

		return $link_to_delete;
	}

	/**
	 * Get the current revision of a single cloud snippet.
	 *
	 * @param string $cloud_id Cloud snippet ID.
	 *
	 * @return string|null Revision number on success, null otherwise.
	 */
	public static function get_cloud_snippet_revision( string $cloud_id ): ?string {
		$api_url = self::CLOUD_API_URL . sprintf( 'public/getsnippetrevision/%s', $cloud_id );
		$body = wp_remote_retrieve_body( wp_remote_get( $api_url ) );

		if ( ! $body ) {
			return null;
		}

		$cloud_snippet_revision = json_decode( $body, true );
		return $cloud_snippet_revision['snippet_revision'] ?? null;
	}

	/**
	 * Download a snippet from the cloud.
	 *
	 * @param int|string $cloud_id The cloud ID of the snippet as string from query args.
	 * @param string     $source   The source table of the snippet: 'codevault' or 'search'.
	 * @param string     $action   The action to be performed: 'download' or 'update'.
	 *
	 * @return array<string, string|bool> Result of operation: an array with `success` and `error_message` keys.
	 */
	public function download_or_update_snippet( int $cloud_id, string $source, string $action ): array {
		$cloud_id = intval( $cloud_id );
		$snippet_to_store = $this->get_single_cloud_snippet( $cloud_id );

		switch ( $action ) {
			case 'download':
				return $this->download_snippet_from_cloud( $snippet_to_store );
			case 'update':
				return $this->update_snippet_from_cloud( $snippet_to_store );
			default:
				return [
					'success' => false,
					'error'   => __( 'Invalid action.', 'code-snippets' ),
				];
		}
	}

	/**
	 * Retrieve a single cloud snippet from the API.
	 *
	 * @param int $cloud_id Remote cloud snippet ID.
	 *
	 * @return Cloud_Snippet Retrieved snippet.
	 */
	public static function get_single_cloud_snippet( int $cloud_id ): Cloud_Snippet {
		$url = self::CLOUD_API_URL . sprintf( 'public/getsnippet/%s', $cloud_id );
		$response = wp_remote_get( $url );
		$cloud_snippet = self::unpack_request_json( $response );

		return new Cloud_Snippet( $cloud_snippet['snippet'] );
	}

	/**
	 * Download a snippet from the cloud.
	 *
	 * @param Cloud_Snippet|Cloud_Snippet[] $snippets_to_store The snippet to be downloaded.
	 *
	 * @return array The result of the download.
	 */
	public function download_snippet_from_cloud( $snippets_to_store ): array {
		$new_snippet = null;
		$link = null;

		if ( ! is_array( $snippets_to_store ) ) {
			$snippets_to_store = [ $snippets_to_store ];
		}

		foreach ( $snippets_to_store as $snippet_to_store ) {
			$snippet = new Snippet( $snippet_to_store );

			// Set the snippet id to 0 to ensure that the snippet is saved as a new snippet.
			$ownership = '0';
			$snippet->id = 0;
			$snippet->active = 0;
			$snippet->cloud_id = $snippet_to_store->id . '_' . $ownership;
			$snippet->desc = $snippet_to_store->description ? $snippet_to_store->description : '';

			// Save the snippet to the database.
			$new_snippet = save_snippet( $snippet );

			$link = new Cloud_Link();
			$link->local_id = $new_snippet->id;
			$link->cloud_id = $snippet->cloud_id;
			$link->is_owner = $snippet_to_store->is_owner;
			$link->in_codevault = false;
			$link->update_available = false;

			$this->add_map_link( $link );
		}

		if ( 1 === count( $snippets_to_store ) && $new_snippet && $link ) {
			return [
				'success'    => true,
				'action'     => __( 'Single Downloaded', 'code-snippets' ),
				'snippet_id' => $new_snippet->id,
				'link_id'    => $link->cloud_id,
			];
		}

		if ( count( $snippets_to_store ) > 1 ) {
			return [
				'success' => true,
				'action'  => __( 'Downloaded', 'code-snippets' ),
			];
		} else {
			return [
				'success' => false,
				'error'   => __( 'There was a problem saving or no snippets found to download.', 'code-snippets' ),
			];
		}
	}

	/**
	 * Update a snippet from the cloud.
	 *
	 * @param Cloud_Snippet|Cloud_Snippet[] $snippet_to_store Array of snippets to be updated.
	 *
	 * @return array The result of the update.
	 */
	public function update_snippet_from_cloud( $snippet_to_store ): array {
		if ( is_array( $snippet_to_store ) ) {
			$snippet_to_store = reset( $snippet_to_store );
		}

		$ownership = $snippet_to_store->is_owner ? '1' : '0';
		$cloud_id = $snippet_to_store->id . '_' . $ownership;
		$local_snippet = get_snippet_by_cloud_id( sanitize_key( $cloud_id ) );
		// Only update the code, active and revision fields.
		$fields = [
			'code'     => $snippet_to_store->code,
			'active'   => false,
			'revision' => $snippet_to_store->revision,
		];

		update_snippet_fields( $local_snippet->id, $fields );

		$this->refresh_synced_data();

		return [
			'success' => true,
			'action'  => __( 'Updated', 'code-snippets' ),
		];
	}

	/**
	 * Check if a snippet has update available using cloud link.
	 *
	 * @param int $snippet_id The local ID of the snippet.
	 *
	 * @return bool Whether the snippet has update available or not.
	 */
	public function is_update_available( int $snippet_id ): bool {
		$cloud_link = $this->get_local_to_cloud_map();

		// Find the snippet from the array of objects using snippet id.
		$snippet = array_filter(
			$cloud_link,
			function ( $snippet ) use ( $snippet_id ) {
				return $snippet->local_id === $snippet_id;
			}
		);

		// Get the first element of the array.
		$snippet = reset( $snippet );
		// Return the update available value which is a boolean.
		return $snippet->update_available;
	}

	/**
	 * Check if snippet is synced to cloud.
	 *
	 * @param int    $snippet_id     Snippet ID.
	 * @param string $local_or_cloud Whether the ID is a local ID or cloud ID.
	 *
	 * @return Cloud_Link|null
	 */
	public function get_cloud_link( int $snippet_id, string $local_or_cloud ): ?Cloud_Link {
		$local_to_cloud_map = $this->get_local_to_cloud_map();

		if ( 'local' === $local_or_cloud || 'cloud' === $local_or_cloud ) {
			$column = 'cloud' === $local_or_cloud ? 'cloud_id' : 'local_id';
			$local_id_array = array_map( 'intval', array_column( $local_to_cloud_map, $column ) );

			if ( in_array( $snippet_id, $local_id_array, true ) ) {
				$index = array_search( $snippet_id, $local_id_array, true );
				return $local_to_cloud_map[ $index ];
			}
		}

		return null;
	}

	/**
	 *
	 * Static Helper Methods
	 */

	/**
	 * Translate a snippet scope to a type.
	 *
	 * @param string $scope The scope of the snippet.
	 *
	 * @return string The type of the snippet.
	 */
	public static function get_type_from_scope( string $scope ): string {
		switch ( $scope ) {
			case 'global':
				return 'php';
			case 'site-css':
				return 'css';
			case 'site-footer-js':
				return 'js';
			case 'content':
				return 'html';
			default:
				return '';
		}
	}

	/**
	 * Translate a snippet status to a style class.
	 *
	 * @param int $status The scope of the snippet.
	 *
	 * @return string The style to be used for the stats badge.
	 */
	public static function get_style_from_status( int $status ): string {
		switch ( $status ) {
			case 3: // Private.
				return 'css';
			case 4: // Public.
				return 'js';
			case 5: // Unverified.
				return 'unverified';
			case 6: // AI Verified.
			case 8: // Pro Verified.
				return 'html';
			default:
				return 'php';
		}
	}

	/**
	 * Translate a snippet status to a status-name.
	 *
	 * @param int $status The scope of the snippet.
	 *
	 * @return string The style to be used for the stats badge.
	 */
	public static function get_status_name_from_status( int $status ): string {
		switch ( $status ) {
			case 3:
				return __( 'Private', 'code-snippets' );
			case 4:
				return __( 'Public', 'code-snippets' );
			case 5:
				return __( 'Unverified', 'code-snippets' );
			case 6:
				return __( 'AI Verified', 'code-snippets' );
			case 8:
				return __( 'Pro Verified', 'code-snippets' );
			default:
				return '';
		}
	}

	/**
	 * Renders the html for the preview thickbox popup.
	 *
	 * @return void
	 */
	public static function render_cloud_snippet_thickbox() {
		add_thickbox();
		?>
		<div id="show-code-preview" style="display: none;">
			<h3 id="snippet-name-thickbox"></h3>
			<h4><?php esc_html_e( 'Snippet Code:', 'code-snippets' ); ?></h4>
			<pre class="thickbox-code-viewer">
				<code id="snippet-code-thickbox"></code>
			</pre>
		</div>
		<?php
	}
}
