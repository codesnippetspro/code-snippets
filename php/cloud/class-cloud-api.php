<?php


namespace Code_Snippets\Cloud;

use Code_Snippets\Snippet;
use WP_Error;
use wpdb;

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
	public $cloud_key_is_verified;

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
		$codevault_snippets = $this->get_codevault_snippets();
		$cloud_id_rev = $codevault_snippets->cloud_id_rev;

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
			//Check if cloud id exists in cloud_id_rev array - this shows if the snippet is in the codevault
			$link->in_codevault =  $cloud_id_rev[$cloud_id_int] ? true : false;

			// Get the cloud snippet revision if in codevault get from cloud_id_rev array otherwise get from cloud.
			$cloud_snippet_revision = 
				$cloud_id_rev[$cloud_id_int] ? $cloud_id_rev[$cloud_id_int] :
				$this->get_cloud_snippet_revision( $local_snippet->cloud_id );
			
			// Check if local revision is less than cloud revision.
			$link->update_available = (int) $local_snippet->revision < $cloud_snippet_revision;
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
	 * Get ownership and Cloud ID of a snippet.
	 * 
	 * @param string $cloud_id
	 *
	 * @return array<string, mixed>
	 */
	public function get_cloud_id_and_ownership( $cloud_id ) {

		$cloud_id_owner = explode( '_', $cloud_id );
		return [
			'cloud_id' 			=> (int) $cloud_id_owner[0],
			'is_owner' 			=> (bool) $cloud_id_owner[1],
			'is_owner_string' 	=> (bool) $cloud_id_owner[1] ? '1' : '0',
		];
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
	 * @return object|Cloud_Snippets[]
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
		$url = self::CLOUD_API_URL . 'private/allsnippets?page=' . $page;
		$response = wp_remote_get( $url, [ 'headers' => $this->build_request_headers() ] );

		$data = $this->unpack_request_json( $response );

		if ( ! $data || ! isset( $data['snippets'] ) ) {
			return;
		}

		foreach ( $data['snippets'] as $key => $snippet ) {
			$data['snippets'][$key]['cloud_id'] = $snippet['id']; 
		}

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
	 * @param string  $search_method Search by name of codevault or keyword(s).
	 * @param string  $search Search query.
	 * @param integer $page   Search result page to retrieve. Defaults to '0'.
	 *
	 * @return Cloud_Snippets Result of search query.
	 */
	public static function fetch_search_results( $search_method, $search, $page = 0 ) {
		$api_url = add_query_arg(
			[
				's_method'  => $search_method,
				's'    		=> $search,
				'page' 		=> $page,
				'site_token'=> get_setting( 'cloud', 'local_token' ),
				'site_host'	=> parse_url( get_site_url(), PHP_URL_HOST ),
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
		$local_to_cloud_map = get_transient( self::CLOUD_MAP_TRANSIENT_KEY );
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
			$cloud_id = (string) $data['cloud_id'];
			$revision = (int) $data['revision'];
			// Update the stored local snippet information.
			update_snippet_fields(
				$snippet->id,
				array(
					'cloud_id' => $cloud_id,
					'revision' => $revision,
				)
			);

			// Clear cached data.
			$this->refresh_synced_data();
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
			$cloud_id_owner = $this->get_cloud_id_and_ownership( $snippet->cloud_id );
			$cloud_id = (int) $cloud_id_owner['cloud_id'];

			// Send post request to cs store api with snippet data.
			$response = wp_remote_post(
				self::CLOUD_API_URL . 'private/updatesnippet/' . $cloud_id,
				[
					'method'  => 'POST',
					'headers' => $this->build_request_headers(),
					'body'    => [
						'name'     => $snippet->name,
						'desc'     => $snippet->desc,
						'code'     => $snippet->code,
						'revision' => $snippet->revision,
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

			$this->refresh_synced_data();
		}

		return $link_to_delete;
	}

	/**
	 * Retrieve a single cloud snippet from the API.
	 *
	 * @param int $cloud_id Remote cloud snippet ID.
	 *
	 * @return Cloud_Snippet Retrieved snippet.
	 */
	public static function get_single_cloud_snippet( $cloud_id ) {

		//CHANGE TO PRIVATE ROUTE 'PRIVATE/GETSNIPPET' AND ADD BEARER TOKEN
		$url = self::CLOUD_API_URL . sprintf( 'private/getsnippet/%s', $cloud_id );
		$response = wp_remote_get( $url, [ 'headers' => self::build_request_headers() ] );
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
	 * @param string $cloud_id The cloud ID of the snippet as string from query args
	 * @param string $source   The source table of the snippet: 'codevault' or 'search'.
	 * @param string $action   The action to be performed: 'download' or 'update'.
	 *
	 * @return array<string, string|bool> Result of operation: an array with `success` and `error_message` keys.
	 */
	public function download_or_update_snippet( $cloud_id_string, $source, $action ) {

		$cloud_id = intval( $cloud_id_string );
		switch ($source) {
			case 'codevault':
				$in_codevault = true;
				$snippet_to_store = $this->get_single_snippet_from_codevault( $cloud_id );
				break;
			case 'search':
				$in_codevault = false;
				$snippet_to_store = $this->get_single_cloud_snippet( $cloud_id );
				break;
			default:
				return [
					'success' => false,
					'error'   => 'Invalid source.',
				];
		}

		switch ($action) {
			case 'download':
				return $this->download_snippet_from_cloud( $snippet_to_store, $in_codevault);
			case 'update':
				return $this->update_snippet_from_cloud( $snippet_to_store );
			default:
				return [
					'success' => false,
					'error'   => 'Invalid action.',
				];
		}
	}

	/**
	 * Get a single snippet from the codevault.
	 *
	 * @param int $actual_cloud_id The cloud ID of the snippet 
	 *
	 * @return object|null The snippet object on success, null otherwise.
	 */
	public function get_single_snippet_from_codevault( $actual_cloud_id ){
		$snippets = $this->get_codevault_snippets();
		// Filter the cloud snippet array to get the snippet that is to be saved to the database.
		$snippet_to_store = array_filter(
			$snippets->snippets,
			function ( $snippet ) use ( $actual_cloud_id ) {
				return $snippet->id === $actual_cloud_id;
			}
		);

		return $snippet_to_store;
	}

	/**
	 * Download a snippet from the cloud.
	 *
	 * @param object $snippet_to_store The snippet to be downloaded.
	 * @param bool   $in_codevault     Whether the snippet is in the codevault or not.
	 *
	 * @return array The result of the download.
	 */
	public function download_snippet_from_cloud( $snippets_to_store, $in_codevault ) {
		

		if( is_object($snippets_to_store) ){
			$snippets_to_store = [$snippets_to_store];
		}
		foreach ($snippets_to_store as $snippet_to_store) {
			
			$snippet = new Snippet( $snippet_to_store );

			// Set the snippet id to 0 to ensure that the snippet is saved as a new snippet.
			$ownership = $snippet_to_store->is_owner ? '1' : '0';
			$snippet->id = 0;
			$snippet->active = 0;
			$snippet->cloud_id = $snippet_to_store->id.'_'.$ownership;
			$snippet->desc = $snippet_to_store->description ? $snippet_to_store->description : ''; //if no description is set, set it to empty string

			// Save the snippet to the database.
			$new_snippet_id = save_snippet( $snippet );

			$link = new Cloud_Link();
			$link->local_id = $new_snippet_id;
			$link->cloud_id = $snippet->cloud_id;
			$link->is_owner = $snippet_to_store->is_owner;
			$link->in_codevault = $in_codevault;
			$link->update_available = false;

			$this->add_map_link( $link );
			
		}


		if( count($snippets_to_store) == 1 ){
			return [
				'success' => true,
				'action'  => 'Single Downloaded',
				'snippet_id' => $new_snippet_id,
				'link_id' => $link->id,
			];
		}

		if( count($snippets_to_store) > 1 ){
			return [
				'success' => true,
				'action'  => 'Downloaded',
			];
		}else{
			return [
				'success' => false,
				'error'   => 'There was a problem saving or no snippets found to download.',
			];
		}

	}

	/**
	 * Update a snippet from the cloud.
	 *
	 * @param array  $snippets Array of snippets to be updated.
	 *
	 * @return array The result of the update.
	 */
	public function update_snippet_from_cloud( $snippet_to_store ) {

		if( is_array($snippet_to_store) ){
			$snippet_to_store = reset($snippet_to_store);
		}

		$ownership = $snippet_to_store->is_owner ? '1' : '0';
		$cloud_id = $snippet_to_store->id.'_'.$ownership;
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
			'action'  => 'Updated',
		];
	}

	/**
	 * Check if a snippet has update available using cloud link.
	 *
	 * @param int $snippet_id The local ID of the snippet.
	 *
	 * @return bool Whether the snippet has update available or not.
	 */
	public function is_update_available( $snippet_id ) {
		$cloud_link = $this->get_local_to_cloud_map();
		//find the snippet from the array of objects using snippet id
		$snippet = array_filter(
			$cloud_link,
			function ( $snippet ) use ( $snippet_id ) {
				return $snippet->local_id === $snippet_id;
			}
		);
		//Get the first element of the array
		$snippet = reset($snippet);
		//Return the update available value which is a boolean
		return $snippet->update_available;
	}

	/**
	 * Check if snippet is synced to cloud.
	 *
	 * @param string $snippet_id.
	 * @param string $local_or_cloud - is the id local id or cloud id.
	 *
	 * @return Cloud_Link|null
	 */
	public function get_cloud_link( $snippet_id, $local_or_cloud ) {
		$local_to_cloud_map = $this->get_local_to_cloud_map();
		if( $local_or_cloud == 'cloud' ){
			$local_id_array = array_column( $local_to_cloud_map, 'cloud_id' );
		}
		if( $local_or_cloud == 'local' ){
			$local_id_array = array_column( $local_to_cloud_map, 'local_id' );
		}
		if ( in_array( $snippet_id, $local_id_array ) ) {
			$index = array_search( $snippet_id, $local_id_array );
			return $local_to_cloud_map[$index];
		}

		return null;
	}

	/**
	 * 
	 * Static Helper Methods
	 *
	 */

	/**
	 * Translate a snippet scope to a type.
	 *
	 * @param string $scope The scope of the snippet.
	 *
	 * @return string The type of the snippet.
	 */
	public static function get_type_from_scope( $scope ) {
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
	public static function get_style_from_status( $status ) {
		switch ( $status ) {
			case 3: //Private
				return 'css';
			case 4: //Public
				return 'js';
			case 5: //Unverified
				return 'unverified';
			case 6: //AI Verified
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
	public static function get_status_name_from_status( $status ) {
		switch ( $status ) {
			case 3: //Private
				return 'Private';
			case 4: //Public
				return 'Public';
			case 5: //Unverified
				return 'Unverified';
			case 6: //AI Verified
				return 'AI-Verified';
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
				<code id="snippet-code-thickbox" class=""></code>
			</pre>
		</div>
		<?php
	}
}