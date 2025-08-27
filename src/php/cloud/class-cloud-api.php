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
	 * Key used to access the local-to-cloud map transient data.
	 */
	private const CLOUD_MAP_TRANSIENT_KEY = 'cs_local_to_cloud_map';

	/**
	 * Days to cache data retrieved from API.
	 */
	private const DAYS_TO_STORE_CS = 1;

	/**
	 * Token used for public API access.
	 *
	 * @var string
	 */
	private const CLOUD_SEARCH_API_TOKEN = 'csc-1a2b3c4d5e6f7g8h9i0j';

	/**
	 * Cached list of cloud links.
	 *
	 * @var Cloud_Link[]|null
	 */
	private ?array $cached_cloud_links = null;

	/**
	 * 'Private' status code.
	 */
	public const STATUS_PRIVATE = 3;

	/**
	 * 'Public' status code.
	 */
	public const STATUS_PUBLIC = 4;

	/**
	 * 'Public' status code.
	 */
	public const STATUS_UNVERIFIED = 5;

	/**
	 * 'AI Verified' status code.
	 */
	public const STATUS_AI_VERIFIED = 6;

	/**
	 * 'Pro Verified' status code.
	 */
	public const STATUS_PRO_VERIFIED = 8;

	/**
	 * Retrieve the Cloud URL from wp-config or fallback to default.
	 *
	 * @return string
	 *
	 * @noinspection PhpUndefinedConstantInspection
	 */
	public static function get_cloud_url(): string {
		return defined( 'CS_CLOUD_URL' )
			? CS_CLOUD_URL
			: 'https://codesnippets.cloud/';
	}

	/**
	 * Retrieve the Cloud API URL from wp-config or fallback to default.
	 *
	 * @return string
	 *
	 * @noinspection PhpUndefinedConstantInspection
	 */
	public static function get_cloud_api_url(): string {
		return defined( 'CS_CLOUD_API_URL' )
			? CS_CLOUD_API_URL
			: self::get_cloud_url() . 'api/v1/';
	}

	/**
	 * Retrieve the cloud local token.
	 *
	 * @return string
	 */
	public static function get_local_token(): string {
		return self::CLOUD_SEARCH_API_TOKEN;
	}

	/**
	 * Check that the cloud key is valid and verified.
	 *
	 * @return boolean
	 */
	public static function is_cloud_key_verified(): bool {
		return false;
	}

	/**
	 * Check if the API key is set and verified.
	 *
	 * @return boolean
	 */
	public static function is_cloud_connection_available(): bool {
		return false;
	}

	/**
	 * Create local-to-cloud map to keep track of local snippets that have been synced to the cloud.
	 *
	 * @return Cloud_Link[]
	 */
	private function get_cloud_links(): ?array {
		// Return the cached data if available.
		if ( is_array( $this->cached_cloud_links ) ) {
			return $this->cached_cloud_links;
		}

		// Fetch data from the stored transient, if available.
		$transient_data = get_transient( self::CLOUD_MAP_TRANSIENT_KEY );
		if ( is_array( $transient_data ) ) {
			$this->cached_cloud_links = $transient_data;
			return $this->cached_cloud_links;
		}

		// Otherwise, regenerate the local-to-cloud-map.
		$this->cached_cloud_links = [];

		// Fetch and iterate through all local snippets to create the map.
		foreach ( get_snippets() as $local_snippet ) {
			// Skip snippets that are only stored locally.
			if ( ! $local_snippet->cloud_id ) {
				continue;
			}

			$link = new Cloud_Link();
			$cloud_id_owner = $this->get_cloud_id_and_ownership( $local_snippet->cloud_id );
			$cloud_id_int = intval( $cloud_id_owner['cloud_id'] );
			$link->local_id = $local_snippet->id;
			$link->cloud_id = $cloud_id_int;
			$link->is_owner = $cloud_id_owner['is_owner'];
			// Check if cloud id exists in cloud_id_rev array - this shows if the snippet is in the codevault.
			$link->in_codevault = $cloud_id_rev[ $cloud_id_int ] ?? false;

			// Get the cloud snippet revision if in codevault get from cloud_id_rev array otherwise get from cloud.
			if ( $link->in_codevault ) {
				$cloud_snippet_revision = $cloud_id_rev[ $cloud_id_int ] ?? $this->get_cloud_snippet_revision( $local_snippet->cloud_id );
				$link->update_available = $local_snippet->revision < $cloud_snippet_revision;
			}

			$this->cached_cloud_links[] = $link;
		}

		set_transient(
			self::CLOUD_MAP_TRANSIENT_KEY,
			$this->cached_cloud_links,
			DAY_IN_SECONDS * self::DAYS_TO_STORE_CS
		);

		return $this->cached_cloud_links;
	}

	/**
	 * Get ownership and Cloud ID of a snippet.
	 *
	 * @param string $cloud_id Cloud ID.
	 *
	 * @return array<string, mixed>
	 */
	public function get_cloud_id_and_ownership( string $cloud_id ): array {
		$cloud_id_owner = explode( '_', $cloud_id );

		return [
			'cloud_id'        => (int) $cloud_id_owner[0] ?? '',
			'is_owner'        => isset( $cloud_id_owner[1] ) && $cloud_id_owner[1],
			'is_owner_string' => isset( $cloud_id_owner[1] ) && $cloud_id_owner[1] ? '1' : '0',
		];
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
				'site_token' => self::get_local_token(),
				'site_host'  => wp_parse_url( get_site_url(), PHP_URL_HOST ),
			],
			self::get_cloud_api_url() . 'public/search'
		);

		$results = self::unpack_request_json( wp_remote_get( $api_url ) );

		$results = new Cloud_Snippets( $results );
		$results->page = $page;

		return $results;
	}

	/**
	 * Add a new link item to the local-to-cloud map.
	 *
	 * @param Cloud_Link $link Link to add.
	 *
	 * @return void
	 */
	public function add_cloud_link( Cloud_Link $link ) {
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
	 * @param int $snippet_id Local snippet ID.
	 *
	 * @return void
	 */
	public function delete_snippet_from_transient_data( int $snippet_id ) {
		if ( ! $this->cached_cloud_links ) {
			$this->get_cloud_links();
		}

		foreach ( $this->cached_cloud_links as $link ) {
			if ( $link->local_id === $snippet_id ) {
				// Remove the link from the local_to_cloud_map.
				$index = array_search( $link, $this->cached_cloud_links, true );
				unset( $this->cached_cloud_links[ $index ] );

				// Update the transient data.
				set_transient(
					self::CLOUD_MAP_TRANSIENT_KEY,
					$this->cached_cloud_links,
					DAY_IN_SECONDS * self::DAYS_TO_STORE_CS
				);
			}
		}
	}

	/**
	 * Retrieve a single cloud snippet from the API.
	 *
	 * @param int $cloud_id Remote cloud snippet ID.
	 *
	 * @return Cloud_Snippet Retrieved snippet.
	 */
	public static function get_single_snippet_from_cloud( int $cloud_id ): Cloud_Snippet {
		$url = self::get_cloud_api_url() . sprintf( 'public/getsnippet/%s', $cloud_id );
		$response = wp_remote_get( $url );
		$cloud_snippet = self::unpack_request_json( $response );
		return new Cloud_Snippet( $cloud_snippet['snippet'] );
	}

	/**
	 * Get the current revision of a single cloud snippet.
	 *
	 * @param string $cloud_id Cloud snippet ID.
	 *
	 * @return string|null Revision number on success, null otherwise.
	 */
	public static function get_cloud_snippet_revision( string $cloud_id ): ?string {
		$api_url = self::get_cloud_api_url() . sprintf( 'public/getsnippetrevision/%s', $cloud_id );
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
	 * @param string     $source   Unused in Core.
	 * @param string     $action   The action to be performed: 'download' or 'update'.
	 *
	 * @return array<string, string|bool> Result of operation: an array with `success` and `error_message` keys.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function download_or_update_snippet( int $cloud_id, string $source, string $action ): array {
		$cloud_id = intval( $cloud_id );
		$snippet_to_store = $this->get_single_snippet_from_cloud( $cloud_id );

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
	 * Download a snippet from the cloud.
	 *
	 * @param Cloud_Snippet $snippet_to_store The snippet to be downloaded.
	 *
	 * @return array The result of the download.
	 */
	public function download_snippet_from_cloud( Cloud_Snippet $snippet_to_store ): array {
		$snippet = new Snippet( $snippet_to_store );

		// Set the snippet id to 0 to ensure that the snippet is saved as a new snippet.
		$ownership = $snippet_to_store->is_owner ? '1' : '0';
		$snippet->id = 0;
		$snippet->active = 0;
		$snippet->cloud_id = $snippet_to_store->id . '_' . $ownership;
		$snippet->desc = $snippet_to_store->description ? $snippet_to_store->description : '';

		// Save the snippet to the database.
		$new_snippet = save_snippet( $snippet );

		$link = new Cloud_Link();
		$link->local_id = $new_snippet->id;
		$link->cloud_id = $snippet_to_store->id;
		$link->is_owner = $snippet_to_store->is_owner;
		$link->in_codevault = false;
		$link->update_available = false;

		$this->add_cloud_link( $link );

		return [
			'success'    => true,
			'action'     => 'Single Downloaded',
			'snippet_id' => $new_snippet->id,
			'link_id'    => $link->cloud_id,
		];
	}

	/**
	 * Update a snippet from the cloud.
	 *
	 * @param Cloud_Snippet $snippet_to_store Snippet to be updated.
	 *
	 * @return array The result of the update.
	 */
	public function update_snippet_from_cloud( Cloud_Snippet $snippet_to_store ): array {
		$cloud_id = $snippet_to_store->id . '_' . ( $snippet_to_store->is_owner ? '1' : '0' );

		$local_snippet = get_snippet_by_cloud_id( sanitize_key( $cloud_id ) );

		// Only update the code, active and revision fields.
		$fields = [
			'code'     => $snippet_to_store->code,
			'active'   => false,
			'revision' => $snippet_to_store->revision,
		];

		update_snippet_fields( $local_snippet->id, $fields );
		$this->clear_caches();

		return [
			'success' => true,
			'action'  => __( 'Updated', 'code-snippets' ),
		];
	}

	/**
	 * Find the cloud link for a given cloud snippet identifier.
	 *
	 * @param int $cloud_id Cloud ID.
	 *
	 * @return Cloud_Link|null
	 */
	public function get_link_for_cloud_id( int $cloud_id ): ?Cloud_Link {
		$cloud_links = $this->get_cloud_links();

		if ( $cloud_links ) {
			foreach ( $cloud_links as $cloud_link ) {
				if ( $cloud_link->cloud_id === $cloud_id ) {
					return $cloud_link;
				}
			}
		}

		return null;
	}


	/**
	 * Find the cloud link for a given cloud snippet.
	 *
	 * @param Cloud_Snippet $cloud_snippet Cloud snippet.
	 *
	 * @return Cloud_Link|null
	 */
	public function get_link_for_cloud_snippet( Cloud_Snippet $cloud_snippet ): ?Cloud_Link {
		return $this->get_link_for_cloud_id( $cloud_snippet->id );
	}

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
	 * Get the label for a given cloud status.
	 *
	 * @param int $status Cloud status code.
	 *
	 * @return string The label for the status.
	 */
	public static function get_status_label( int $status ): string {
		$labels = [
			self::STATUS_PRIVATE      => __( 'Private', 'code-snippets' ),
			self::STATUS_PUBLIC       => __( 'Public', 'code-snippets' ),
			self::STATUS_UNVERIFIED   => __( 'Unverified', 'code-snippets' ),
			self::STATUS_AI_VERIFIED  => __( 'AI Verified', 'code-snippets' ),
			self::STATUS_PRO_VERIFIED => __( 'Pro Verified', 'code-snippets' ),
		];

		return $labels[ $status ] ?? __( 'Unknown', 'code-snippets' );
	}

	/**
	 * Get the badge class for a given cloud status.
	 *
	 * @param int $status Cloud status code.
	 *
	 * @return string
	 */
	public static function get_status_badge( int $status ): string {
		$badge_names = [
			self::STATUS_PRIVATE      => 'private',
			self::STATUS_PUBLIC       => 'public',
			self::STATUS_UNVERIFIED   => 'failure',
			self::STATUS_AI_VERIFIED  => 'success',
			self::STATUS_PRO_VERIFIED => 'info',
		];

		return $badge_names[ $status ] ?? 'neutral';
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

	/**
	 * Refresh the cached synced data.
	 *
	 * @return void
	 */
	public function clear_caches() {
		$this->cached_cloud_links = null;

		delete_transient( self::CLOUD_MAP_TRANSIENT_KEY );
	}
}
