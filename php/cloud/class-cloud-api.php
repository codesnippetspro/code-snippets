<?php

namespace Code_Snippets\Cloud;

use Code_Snippets\Snippet;
use WP_Error;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippet_by_cloud_id;
use function Code_Snippets\get_snippet_with_token_data;
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
	 * Base URL to cloud platform UI.
	 *
	 * @var string
	 */
	const CLOUD_URL = 'https://codesnippets.cloud/';

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
	 * Cloud Settings Cache Key
	 *
	 * @var string
	 */
	const CLOUD_SETTINGS_CACHE_KEY = 'code_snippets_cloud_settings';

	/**
	 * Locally Generated Token
	 *
	 * @var string
	 */
	private $local_token;

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
	 * Code Snippets Cloud Settings
	 *
	 * @var array|null
	 */
	private $code_snippets_cloud_settings;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init_cs_cloud_settings();
		$this->is_cloud_key_verified();
		add_action( 'code_snippets/deactivate_snippet', array( $this, 'remove_sync' ), 10, 2 );
	}

	/**
	 * Initialise Cloud Settings
	 *
	 * @return void
	 */
	public function init_cs_cloud_settings() {
		$this->code_snippets_cloud_settings = get_option( CLOUD_SETTINGS_CACHE_KEY );
		// Check if the settings exist in the database if not create defaults.
		if ( ! $this->code_snippets_cloud_settings ) {
			$this->code_snippets_cloud_settings = [
				'cloud_token'      => '',
				'local_token'      => '',
				'token_verified'   => false,
				'token_snippet_id' => '',
			];
			update_option( CLOUD_SETTINGS_CACHE_KEY, $this->code_snippets_cloud_settings );
		}

		wp_cache_set( CLOUD_SETTINGS_CACHE_KEY, $this->code_snippets_cloud_settings );

		$this->cloud_key = $this->code_snippets_cloud_settings['cloud_token'];
		$this->local_token = $this->code_snippets_cloud_settings['local_token'];
	}

	/**
	 * Get Specific Cloud Setting
	 *
	 * @param string $setting Setting name.
	 *
	 * @return string|boolean
	 */
	public function get_cloud_setting( string $setting ) {
		// Check if the settings are in cache.
		$this->code_snippets_cloud_settings = wp_cache_get( CLOUD_SETTINGS_CACHE_KEY );

		if ( ! $this->code_snippets_cloud_settings ) {
			$this->init_cs_cloud_settings();
		}

		return $this->code_snippets_cloud_settings[ $setting ] ?? false;
	}

	/**
	 * Retrieve cloud settings.
	 *
	 * @return array
	 */
	public function get_cloud_settings() {
		$this->code_snippets_cloud_settings = wp_cache_get( CLOUD_SETTINGS_CACHE_KEY );

		if ( ! $this->code_snippets_cloud_settings ) {
			$this->init_cs_cloud_settings();
		}

		return $this->code_snippets_cloud_settings;
	}

	/**
	 * Update cloud setting.
	 *
	 * @param string $setting Setting to update.
	 * @param string $value   Updated value.
	 *
	 * @return void
	 */
	public function update_cloud_setting( $setting, $value ) {
		if ( ! isset( $this->code_snippets_cloud_settings[ $setting ] ) ) {
			return;
		}

		$this->code_snippets_cloud_settings[ $setting ] = $value;

		update_option( 'code_snippets_cloud_settings', $this->code_snippets_cloud_settings );
		wp_cache_set( CLOUD_SETTINGS_CACHE_KEY, $this->code_snippets_cloud_settings );
	}

	/**
	 * Update multiple cloud settings
	 *
	 * @param array<string, mixed> $settings to update in cloud settings with key value pairs 'setting' => 'value'.
	 *
	 * @return void
	 */
	public function update_cloud_settings( array $settings ) {
		foreach ( $settings as $setting => $value ) {
			$this->code_snippets_cloud_settings[ $setting ] = $value;
		}

		update_option( 'code_snippets_cloud_settings', $this->code_snippets_cloud_settings );
		wp_cache_set( CLOUD_SETTINGS_CACHE_KEY, $this->code_snippets_cloud_settings );
	}


	/**
	 * Check cloud key is valid and verified
	 *
	 * @return boolean
	 */
	public function is_cloud_key_verified(): bool {
		$this->cloud_key_is_verified = boolval( $this->get_cloud_setting( 'token_verified' ) );
		return $this->cloud_key_is_verified;
	}

	/**
	 * Check snippet is special token snippet
	 *
	 * @param string|integer $snippet_id Snippet ID.
	 *
	 * @return boolean
	 */
	public function is_cloud_access_snippet( $snippet_id ): bool {
		$token_snippet_id = $this->get_cloud_setting( 'token_snippet_id' );
		return (int) $token_snippet_id === (int) $snippet_id;
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

		if ( ! $codevault_snippets ) {
			return $this->local_to_cloud_map;
		}

		$cloud_id_rev = $codevault_snippets->cloud_id_rev;

		// Fetch and iterate through all local snippets to create the map.
		foreach ( get_snippets() as $local_snippet ) {
			// Skip snippets that are only stored locally.
			if ( ! $local_snippet->cloud_id ) {
				continue;
			}

			// If the snippet is a token snippet skip it.
			if ( $this->is_cloud_access_snippet( $local_snippet->id ) ) {
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
				$cloud_snippet_revision =
					$cloud_id_rev[ $cloud_id_int ] ? $cloud_id_rev[ $cloud_id_int ] :
						$this->get_cloud_snippet_revision( $local_snippet->cloud_id );

				$link->update_available = (int) $local_snippet->revision < $cloud_snippet_revision;
			}

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
	 * Check if the API key is set and verified.
	 *
	 * @return boolean
	 */
	public function is_cloud_connection_available(): bool {
		return $this->cloud_key && $this->cloud_key_is_verified;
	}

	/**
	 * Check if the API Token key is available.
	 *
	 * @return array
	 */
	public function is_cloud_key_available(): array {
		// Check if cloud connection is already available.
		if ( $this->is_cloud_connection_available() ) {
			return [
				'success'       => true,
				'redirect-slug' => 'success',
			];
		}

		// Check if the Token Snippet exists.
		$token_snippet = get_snippet_with_token_data();

		if ( ! $token_snippet ) {
			return [
				'success'       => false,
				'redirect-slug' => 'deleted',
			];
		}

		// Check if the Token Snippet is active.
		if ( ! $token_snippet->active ) {
			return [
				'success'       => false,
				'redirect-slug' => 'inactive',
			];
		}

		// Extract token from snippet.
		$saved_cloud_token = $token_snippet->cloud_id;

		if ( ! $saved_cloud_token ) {
			return [
				'success'       => false,
				'redirect-slug' => 'invalid',
			];
		}

		// Establish new cloud connection.
		$cloud_connection = $this->establish_new_cloud_connection( $saved_cloud_token );

		if ( 'no_codevault' === $cloud_connection['message'] ) {
			return [
				'success'       => false,
				'redirect-slug' => 'no-codevault',
			];
		}

		// If the cloud connection is successful, save the token in code snippets settings[cloud][cloud_token].
		if ( ! $cloud_connection['success'] ) {
			return [
				'success'       => false,
				'redirect-slug' => 'invalid',
			];
		}

		$this->update_cloud_settings(
			[
				'cloud_token'      => $saved_cloud_token,
				'local_token'      => $cloud_connection['local_token'],
				'token_verified'   => true,
				'token_snippet_id' => $token_snippet->id,
			]
		);

		$this->cloud_key = $saved_cloud_token;
		$this->local_token = $cloud_connection['local_token'];
		$this->cloud_key_is_verified = true;

		return [
			'success'       => true,
			'redirect-slug' => 'success',
		];
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
			'is_owner'        => (bool) $cloud_id_owner[1],
			'is_owner_string' => $cloud_id_owner[1] ? '1' : '0',
		];
	}

	/**
	 * Build a list of headers required for an authenticated request.
	 *
	 * @return array<string, mixed>
	 */
	private function build_request_headers(): array {
		$cloud_api_key = $this->get_cloud_setting( 'cloud_token' );
		return [
			'Authorization' => 'Bearer ' . $cloud_api_key,
			'Local-Token'   => $this->local_token,
		];
	}

	/**
	 * =======
	 * >>>>>>> PD-157-cloud/core
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
	 * Establish new connection to the cloud platform.
	 *
	 * @param string $cloud_key Cloud API key.
	 *
	 * @return array - success, message, local_token
	 */
	public function establish_new_cloud_connection( string $cloud_key ): array {

		// Create a random string of 30 characters mixed numbers and letters - lower and uppercase.
		$local_token = wp_generate_password( 30, false );

		$site_url = get_site_url();

		// Send POST request to CLOUD_API_URL . 'private/syncandverify' with site_token and site_host as form data.
		$response = wp_remote_post(
			self::CLOUD_API_URL . 'private/syncandverify',
			[
				'method'  => 'POST',
				'headers' => [
					'Authorization'               => 'Bearer ' . $cloud_key,
					'Local-Token'                 => $local_token,
					'Access-Control-Allow-Origin' => '*',
					'Accept'                      => 'application/json',
				],
				'body'    => [
					'site_token' => $local_token,
					'site_host'  => wp_parse_url( $site_url, PHP_URL_HOST ),
				],
			]
		);

		// Check the response codes and return accordingly.
		if ( 401 === wp_remote_retrieve_response_code( $response ) ) {
			return [
				'success' => false,
				'message' => 'That token is invalide - please check and try again.',
			];
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return [
				'success' => false,
				'message' => 'There was an error connecting to the cloud platform. Please try again later.',
			];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( 'error' === $data['sync_status'] ) {
			// Check if the data message starts with No Codevault!
			if ( strpos( $data['message'], 'No Codevault!' ) !== false ) {
				$data['message'] = 'no_codevault';
			}
			return [
				'success' => false,
				'message' => $data['message'],
			];
		} elseif ( 'success' === $data['sync_status'] ) {
			return [
				'success'     => true,
				'message'     => $data['message'],
				'local_token' => $local_token,
			];
		}

		return [
			'success' => false,
			'message' => 'There was an unknown error, please try again later.',
		];
	}

	/**
	 * Retrieves a list of all snippets from the cloud API.
	 *
	 * @param integer $page Page of data to retrieve.
	 *
	 * @return Cloud_Snippets|null
	 */
	public function get_codevault_snippets( int $page = 0 ) {
		// Attempt to retrieve cached data if possible.
		if ( $this->codevault_snippets ) {
			return $this->codevault_snippets;
		}

		// Fetch data from the stored transient, if available.
		$stored_data = get_transient( self::CODEVAULT_SNIPPETS_TRANSIENT_KEY );
		if ( $stored_data ) {
			$this->codevault_snippets = $stored_data;
			return $this->codevault_snippets;
		}

		// Otherwise, fetch from API and store.
		$url = self::CLOUD_API_URL . 'private/allsnippets?page=' . $page;
		$response = wp_remote_get( $url, [ 'headers' => $this->build_request_headers() ] );

		$data = $this->unpack_request_json( $response );

		if ( ! $data || ! isset( $data['snippets'] ) ) {
			return null;
		}

		foreach ( $data['snippets'] as $key => $snippet ) {
			$data['snippets'][ $key ]['cloud_id'] = $snippet['id'];
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
	 * @param string  $search        Search query.
	 * @param integer $page          Search result page to retrieve. Defaults to '0'.
	 *
	 * @return Cloud_Snippets Result of search query.
	 */
	public static function fetch_search_results( string $search_method, string $search, int $page = 0 ): Cloud_Snippets {
		$site_token = code_snippets()->cloud_api->get_cloud_setting( 'local_token' );
		$api_url = add_query_arg(
			[
				's_method'   => $search_method,
				's'          => $search,
				'page'       => $page,
				'site_token' => $site_token,
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
	public function update_snippets_in_cloud( array $snippets_to_update ) {
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
	public function delete_snippet_from_transient_data( int $snippet_id ) {
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
	public static function get_single_cloud_snippet( int $cloud_id ): Cloud_Snippet {
		$url = self::CLOUD_API_URL . sprintf( 'public/getsnippet/%s', $cloud_id );
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
	public static function get_cloud_snippet_revision( string $cloud_id ) {
		$api_url = self::CLOUD_API_URL . sprintf( 'public/getsnippetrevision/%s', $cloud_id );
		$body = wp_remote_retrieve_body( wp_remote_get( $api_url ) );

		if ( ! $body ) {
			return null;
		}

		$cloud_snippet_revision = json_decode( $body, true );
		return $cloud_snippet_revision['snippet_revision'] ?? null;
	}

	/**
	 * Get list of all bundles from the cloud API.
	 *
	 * @return array|null Bundle name and id, null otherwise.
	 */
	public static function get_bundles() {
		$api_url = self::CLOUD_API_URL . 'private/bundles';
		$self = new self();
		$response = wp_remote_get( $api_url, [ 'headers' => $self->build_request_headers() ] );
		return self::unpack_request_json( $response );
	}

	/**
	 * Get List of Snippets from a Bundle from the cloud API.
	 *
	 * @param int $bundle_id Bundle ID.
	 *
	 * @return Cloud_Snippets
	 */
	public function get_snippets_from_bundle( int $bundle_id ): Cloud_Snippets {
		$api_url = self::CLOUD_API_URL . sprintf( 'private/getbundle/%s', $bundle_id );
		$response = wp_remote_post(
			$api_url,
			[
				'method'  => 'POST',
				'headers' => $this->build_request_headers(),
			]
		);

		$results = self::unpack_request_json( $response );
		$results = new Cloud_Snippets( $results );
		$results->page = 1;

		return $results;
	}

	/**
	 * Get List of Snippets from a Shared Bundle from the cloud API.
	 *
	 * @param string $bundle_share_name Bundle share name.
	 *
	 * @return Cloud_Snippets
	 */
	public function get_snippets_from_shared_bundle( string $bundle_share_name ): Cloud_Snippets {
		$api_url = self::CLOUD_API_URL . sprintf( 'private/getsharedbundle?share_name=%s', $bundle_share_name );
		$response = wp_remote_post(
			$api_url,
			[
				'method'  => 'POST',
				'headers' => $this->build_request_headers(),
			]
		);

		$results = self::unpack_request_json( $response );
		$results = new Cloud_Snippets( $results );
		$results->page = 1;

		return $results;
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

		switch ( $source ) {
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

		switch ( $action ) {
			case 'download':
				return $this->download_snippets_from_cloud( $snippet_to_store, $in_codevault );
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
	 * Get a single snippet from the codevault.
	 *
	 * @param int $actual_cloud_id The cloud ID of the snippet.
	 *
	 * @return Cloud_Snippet[]|null The snippet object on success, null otherwise.
	 */
	public function get_single_snippet_from_codevault( int $actual_cloud_id ) {
		$snippets = $this->get_codevault_snippets();
		// Filter the cloud snippet array to get the snippet that is to be saved to the database.
		return array_filter(
			$snippets->snippets,
			function ( $snippet ) use ( $actual_cloud_id ) {
				return $snippet->id === $actual_cloud_id;
			}
		);
	}

	/**
	 * Download a snippet from the cloud.
	 *
	 * @param Cloud_Snippet $snippet_to_store The snippet to be downloaded.
	 * @param bool          $in_codevault      Whether the snippet is in the codevault or not.
	 *
	 * @return array The result of the download.
	 */
	public function download_snippet_from_cloud( $snippet_to_store, $in_codevault ) {
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
		$link->cloud_id = $snippet->cloud_id;
		$link->is_owner = $snippet_to_store->is_owner;
		$link->in_codevault = $in_codevault;
		$link->update_available = false;

		$this->add_map_link( $link );

		return [
			'success'    => true,
			'action'     => 'Single Downloaded',
			'snippet_id' => $new_snippet->id,
			'link_id'    => $link->cloud_id,
		];
	}

	/**
	 * Download snippets from the cloud.
	 *
	 * @param Cloud_Snippet[] $snippets_to_store The snippet to be downloaded.
	 * @param bool            $in_codevault      Whether the snippet is in the codevault or not.
	 *
	 * @return array The result of the download.
	 */
	public function download_snippets_from_cloud( $snippets_to_store, $in_codevault ) {
		if ( ! is_array( $snippets_to_store ) ) {
			return $this->download_snippet_from_cloud( $snippets_to_store, $in_codevault );
		}

		foreach ( $snippets_to_store as $snippet_to_store ) {
			$this->download_snippet_from_cloud( $snippet_to_store, $in_codevault );
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
	public function get_cloud_link( int $snippet_id, string $local_or_cloud ) {
		$local_to_cloud_map = $this->get_local_to_cloud_map();

		if ( 'local' === $local_or_cloud || 'cloud' === $local_or_cloud ) {
			$local_id_array = array_map( 'intval', array_column( $local_to_cloud_map, "${$local_or_cloud}_id" ) );

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
				return __( 'AI-Verified', 'code-snippets' );
			case 8:
				return __( 'Pro-Verified', 'code-snippets' );
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

	/**
	 * Remove Sync if the token snippet is deleted.
	 *
	 * @param string|int $id Snippet ID.
	 *
	 * @return array<string, mixed>
	 */
	public function remove_sync( $id ): array {
		$token_snippet = $this->get_cloud_setting( 'token_snippet_id' );

		if ( $id === $token_snippet ) {
			$this->refresh_cloud_settings_data();
			$this->refresh_synced_data();

			// TODO: Send request to Cloud API to remove sync.

			return [
				'success' => true,
				'message' => __( 'Sync has been revoked', 'code-snippets' ),
			];
		}

		return [];
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
	 * Refresh all settings data
	 *
	 * @return void
	 */
	public function refresh_cloud_settings_data() {
		// Simply deleting the data is sufficient, as it will be recreated and stored the next time it is requested.
		$this->update_cloud_settings(
			[
				'cloud_token'      => '',
				'token_verified'   => false,
				'token_snippet_id' => '',
				'local_token'      => '',
			]
		);
	}
}
