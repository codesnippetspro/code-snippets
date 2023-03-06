<?php


namespace Code_Snippets\Cloud;

use Code_Snippets\Snippet;
use WP_Error;
use function Code_Snippets\get_snippets;
use function Code_Snippets\save_snippet;
use function Code_Snippets\Settings\get_setting;
use function Code_Snippets\update_snippet_fields;
use function Code_Snippets\get_snippet_by_cloud_id;

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
	 * Base URL to cloud platform UI.
	 *
	 * @var string
	 */
	const CLOUD_URL = 'https://codesnippets.cloud/';

	/**
	 * Key used to access the local-to-cloud map transient data.
	 *
	 * @var string
	 */
	const CLOUD_MAP_TRANSIENT_KEY = 'cs_local_to_cloud_map';

	/**
	 * Key used to access the codevault snippets transient data.
	 *
	 * @var string
	 */
	const CODEVAULT_SNIPPETS_TRANSIENT_KEY = 'cs_codevault_snippets';

	/**
	 * Days to cache data retrieved from API.
	 *
	 * @var integer
	 */
	const DAYS_TO_STORE_CS = 1;

	/**
	 * Cloud API key.
	 *
	 * @var string
	 */
	private $cloud_key;

	/**
	 * Verification status of cloud API key.
	 *
	 * @var boolean
	 */
	private $cloud_key_is_verified;

	/**
	 * List of cloud snippets.
	 *
	 * @var Cloud_Snippets|null
	 */
	private $codevault_snippets = null;

	/**
	 * Local to Cloud Snippets Map Object
	 *
	 * @var Cloud_Link[]|null
	 */
	private $local_to_cloud_map = null;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->refresh_synced_data();
		$this->cloud_key = get_setting( 'cloud', 'cloud_token' );
		$token_verified = get_setting( 'cloud', 'token_verified' );
		$this->cloud_key_is_verified = $token_verified && 'false' !== $token_verified;
	}

	/**
	 * Create local-to-cloud map to keep track of local snippets that have been synced to the cloud.
	 *
	 * @return Cloud_Link[]
	 */
	public function get_local_to_cloud_map() {
		// Return the cached data if available.
		if ( $this->local_to_cloud_map ) {
			return $this->local_to_cloud_map;
		}

		// Fetch data from the stored transient, if available.
		$stored_data = get_transient( self::CLOUD_MAP_TRANSIENT_KEY );
		if ( $stored_data && is_array( $stored_data ) ) {
			$this->local_to_cloud_map = $stored_data;
			return $stored_data;
		}

		// Otherwise, regenerate the local-to-cloud-map.
		$this->local_to_cloud_map = [];

		// Create a list of snippet revisions in the format `cloud_id` => `revision`, e.g. [163_1 => 2].
		$cloud_snippet_revisions = [];
		$codevault_snippets = $this->get_codevault_snippets();

		foreach ( $codevault_snippets->snippets as $cloud_snippet ) {
			$cloud_snippet_revisions[ $cloud_snippet->cloud_id ] = $cloud_snippet->revision;
		}

		// Fetch and iterate through all local snippets to create the map.
		foreach ( get_snippets() as $local_snippet ) {
			// Skip snippets that are only stored locally.
			if ( ! $local_snippet->cloud_id ) {
				continue;
			}

			$link = new Cloud_Link();
			$link->local_id = $local_snippet->id;
			$link->cloud_id = $local_snippet->cloud_id;
			$link->in_codevault = isset( $cloud_snippet_revisions[ $local_snippet->cloud_id ] );

			$cloud_snippet_revision = $link->in_codevault ?
				$cloud_snippet_revisions[ $local_snippet->cloud_id ] :
				$this->get_cloud_snippet_revision( $local_snippet->cloud_id );

			// Check if local revision is less than cloud revision.
			$link->update_available = $local_snippet->revision < $cloud_snippet_revision;

			$this->local_to_cloud_map[] = $link;
		}

		set_transient( self::CLOUD_MAP_TRANSIENT_KEY, $this->local_to_cloud_map, DAY_IN_SECONDS * self::DAYS_TO_STORE_CS );
		return $this->local_to_cloud_map;
	}

	/**
	 * Check if the API key is set and verified.
	 *
	 * @return boolean
	 */
	public function is_cloud_connection_available() {
		return $this->cloud_key && $this->cloud_key_is_verified;
	}

	/**
	 * Build a list of headers required for an authenticated request.
	 *
	 * @return array<string, mixed>
	 */
	private function build_request_headers() {
		$cloud_api_key = get_setting( 'cloud', 'cloud_token' );
		return [ 'Authorization' => 'Bearer ' . $cloud_api_key ];
	}

	/**
	 * Unpack JSON data from a request response.
	 *
	 * @param array|WP_Error $response Response from wp_request_*.
	 *
	 * @return array<string, mixed>|null Associative array of JSON data on success, null on failure.
	 */
	private static function unpack_request_json( $response ) {
		$body = wp_remote_retrieve_body( $response );
		return $body ? json_decode( $body, true ) : null;
	}

	/**
	 * Retrieves a list of all snippets from the cloud API.
	 *
	 * @param integer $page Page of data to retrieve.
	 *
	 * @return Cloud_Snippets[]
	 */
	public function get_codevault_snippets( $page = 0 ) {
		// Attempt to retrieve cached data if possible.
		if ( $this->codevault_snippets ) {
			return $this->codevault_snippets;
		}

		// Fetch data from the stored transient, if available.
		$stored_data = get_transient( self::CODEVAULT_SNIPPETS_TRANSIENT_KEY );
		if ( $stored_data && is_array( $stored_data ) ) {
			$this->codevault_snippets = $stored_data;
			return $this->codevault_snippets;
		}

		$this->codevault_snippets = null;

		// Otherwise, fetch from API and store.
		$url = self::CLOUD_API_URL . 'private/allsnippets';
		$response = wp_remote_get( $url, [ 'headers' => $this->build_request_headers() ] );

		$data = $this->unpack_request_json( $response );
		$this->codevault_snippets = new Cloud_Snippets( $data );
		$this->codevault_snippets->page = $page;

		set_transient(
			self::CODEVAULT_SNIPPETS_TRANSIENT_KEY,
			$this->codevault_snippets,
			DAY_IN_SECONDS * self::DAYS_TO_STORE_CS
		);

		return $this->codevault_snippets;
	}

	/**
	 * Search Code Snippets Cloud -> Static Function
	 *
	 * @param string  $search Search query.
	 * @param integer $page   Search result page to retrieve. Defaults to '0'.
	 *
	 * @return Cloud_Snippets Result of search query.
	 */
	public static function fetch_search_results( $search, $page = 0 ) {
		$api_url = add_query_arg(
			[
				's'    => $search,
				'page' => $page,
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
		$this->codevault_snippets = null;
		delete_transient( self::CLOUD_MAP_TRANSIENT_KEY );
		delete_transient( self::CODEVAULT_SNIPPETS_TRANSIENT_KEY );
	}

	/**
	 * Add a new link item to the local-to-cloud map.
	 *
	 * @param Cloud_Link $link Link to add.
	 *
	 * @return void
	 */
	protected function add_map_link( Cloud_Link $link ) {
		$local_to_cloud_map = $this->get_local_to_cloud_map();
		$local_to_cloud_map[] = $link;

		set_transient(
			self::CLOUD_MAP_TRANSIENT_KEY,
			$local_to_cloud_map,
			DAY_IN_SECONDS * self::DAYS_TO_STORE_CS
		);
	}

	/**
	 * Upload a series of local snippets to the cloud platform.
	 *
	 * @param Snippet[] $snippets List of code snippets to store.
	 */
	public function store_snippets_in_cloud( $snippets ) {
		foreach ( $snippets as $snippet ) {
			// Send post request to cs store api with snippet data.
			$response = wp_remote_post(
				self::CLOUD_API_URL . 'private/storesnippet',
				[
					'method'  => 'POST',
					'headers' => $this->build_request_headers(),
					'body'    => [
						'name'     => $snippet->name,
						'desc'     => $snippet->desc,
						'code'     => $snippet->code,
						'scope'    => $snippet->scope,
						'revision' => $snippet->revision,
					],
				]
			);

			$data = $this->unpack_request_json( $response );
			$cloud_snippet = new Cloud_Snippet( $data );

			// Update the stored local snippet information.
			update_snippet_fields(
				$snippet->id,
				array(
					'cloud_id' => $cloud_snippet->cloud_id,
					'revision' => $snippet->revision ? $snippet->revision : $cloud_snippet->revision,
				)
			);

			// Clear cached data.
			$this->refresh_synced_data();

			// Update local-to-cloud map transient.
			$link = new Cloud_Link();
			$link->local_id = $snippet->id;
			$link->cloud_id = $cloud_snippet->cloud_id;
			$link->in_codevault = true;
			$link->update_available = false;

			$this->add_map_link( $link );
		}
	}

	/**
	 * Update the already-existing remote data for a series of snippets.
	 *
	 * @param Snippet[] $snippets_to_update List of snippets to update.
	 *
	 * @return void
	 */
	public function update_snippets_in_cloud( $snippets_to_update ) {
		foreach ( $snippets_to_update as $snippet ) {
			$cloud_id = explode( '_', $snippet->cloud_id );

			// Send post request to cs store api with snippet data.
			$response = wp_remote_post(
				self::CLOUD_API_URL . 'private/updatesnippet',
				[
					'method'  => 'POST',
					'headers' => $this->build_request_headers(),
					'body'    => [
						'name'     => $snippet->name,
						'desc'     => $snippet->desc,
						'code'     => $snippet->code,
						'revision' => $snippet->revision,
						'cloud_id' => $cloud_id[0],
						'local_id' => $snippet->id,
					],
				]
			);

			$updated = $this->unpack_request_json( $response );

			if ( $updated['success'] ) {
				$this->refresh_synced_data();
			}
		}
	}

	/**
	 * Delete a snippet from local-to-cloud map.
	 *
	 * @param integer $snippet_id Local snippet ID.
	 *
	 * @return Cloud_Link|null The deleted map link if one was found, null otherwise.
	 */
	public function delete_snippet_from_transient_data( $snippet_id ) {
		$this->get_local_to_cloud_map();
		$link_to_delete = null;

		foreach ( $this->local_to_cloud_map as $link ) {
			if ( $link->local_id === $snippet_id ) {
				$link_to_delete = $link;
				break;
			}
		}

		if ( $link_to_delete ) {
			$this->local_to_cloud_map = array_diff( $this->local_to_cloud_map, [ $link_to_delete ] );

			set_transient(
				self::CLOUD_MAP_TRANSIENT_KEY,
				$this->local_to_cloud_map,
				DAY_IN_SECONDS * self::DAYS_TO_STORE_CS
			);
		}

		return $link_to_delete;
	}

	/**
	 * Retrieve a single cloud snippet from the API.
	 *
	 * @param string $cloud_id Remote cloud snippet ID.
	 *
	 * @return Cloud_Snippet Retrieved snippet.
	 */
	public static function get_single_cloud_snippet( $cloud_id ) {
		$response = wp_remote_get(
			add_query_arg(
				[
					'site_host'  => wp_parse_url( get_site_url(), PHP_URL_HOST ),
					'site_token' => get_setting( 'cloud', 'local_token' ),
				],
				self::CLOUD_API_URL . sprintf( 'public/getsnippet/%s', $cloud_id )
			)
		);

		$cloud_snippet = self::unpack_request_json( $response );
		return new Cloud_Snippet( $cloud_snippet );
	}

	/**
	 * Get the current revision of a single cloud snippet.
	 *
	 * @param string $cloud_id Cloud snippet ID.
	 *
	 * @return string|null Revision number on success, null otherwise.
	 */
	public static function get_cloud_snippet_revision( $cloud_id ) {
		$api_url = self::CLOUD_API_URL . sprintf( 'public/getsnippetrevision/%s', $cloud_id );
		$body = wp_remote_retrieve_body( wp_remote_get( $api_url ) );

		if ( ! $body ) {
			return null;
		}

		$cloud_snippet_revision = json_decode( $body, true );
		return isset( $cloud_snippet_revision['snippet_revision'] ) ? $cloud_snippet_revision['snippet_revision'] : null;
	}

	/**
	 * Download a snippet from the cloud.
	 *
	 * @param string $cloud_id The cloud ID of the snippet to download.
	 * @param string $source   The source table of the snippet: 'codevault' or 'search'.
	 * @param string $action   The action to be performed: 'download' or 'update'.
	 *
	 * @return array<string, string|bool> Result of operation: an array with `success` and `error_message` keys.
	 */
	public function download_or_update_snippet( $cloud_id, $source, $action ) {

		// Check source and get the snippet to be downloaded.
		if ( 'codevault' === $source ) {
			$snippets = $this->get_codevault_snippets();

			// Filter the cloud snippet array to get the snippet that is to be saved to the database.
			$snippet_to_store = array_filter(
				$snippets->snippets,
				function ( $snippet ) use ( $cloud_id ) {
					return $snippet->cloud_id === $cloud_id;
				}
			);

			$in_codevault = true;

		} elseif ( 'search' === $source ) {
			$snippet_to_store = $this->get_single_cloud_snippet( $cloud_id );
			$in_codevault = false;

		} else {
			// No snippet to download; return now.
			return [
				'success' => false,
				'error'   => 'Invalid source.',
			];
		}

		// Check if action was download or update.
		if ( 'download' === $action ) {
			$snippet = new Snippet( $snippet_to_store );

			// Set the snippet id to 0 to ensure that the snippet is saved as a new snippet.
			$snippet->id = 0;
			$snippet->active = 0;

			// Save the snippet to the database.
			$new_snippet_id = save_snippet( $snippet );

			$link = new Cloud_Link();
			$link->local_id = $new_snippet_id;
			$link->cloud_id = $snippet->cloud_id;
			$link->in_codevault = $in_codevault;
			$link->update_available = false;

			$this->add_map_link( $link );

			return [
				'success' => true,
				'action'  => 'Downloaded',
			];

		} elseif ( 'update' === $action ) {
			$local_snippet = get_snippet_by_cloud_id( sanitize_key( $cloud_id ) );

			// Only update the code, active and revision fields.
			$fields = [
				'code'     => $snippet_to_store->code,
				'active'   => false,
				'revision' => $snippet_to_store->revision,
			];

			update_snippet_fields( $local_snippet->id, $fields );

			foreach ( $this->get_local_to_cloud_map() as $link ) {
				if ( $link->local_id === $local_snippet->id ) {
					$link->update_available = false;
				}
			}

			set_transient(
				self::CLOUD_MAP_TRANSIENT_KEY,
				$this->local_to_cloud_map,
				DAY_IN_SECONDS * self::DAYS_TO_STORE_CS
			);

			foreach ( $this->get_codevault_snippets() as $cloud_snippet ) {
				if ( $cloud_snippet->id === $local_snippet->id ) {
					$cloud_snippet->code = $snippet_to_store->code;
					$cloud_snippet->revision = $snippet_to_store->revision;
				}
			}

			set_transient(
				self::CODEVAULT_SNIPPETS_TRANSIENT_KEY,
				$this->codevault_snippets,
				DAY_IN_SECONDS * self::DAYS_TO_STORE_CS
			);

			return [
				'success' => true,
				'action'  => 'Updated',
			];

		} else {
			// No action to perform.
			return [
				'success' => false,
				'error'   => 'Invalid action.',
			];
		}
	}

}
