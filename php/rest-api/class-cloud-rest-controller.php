<?php

namespace Code_Snippets\REST_API;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use Code_Snippets\Cloud\Cloud_Link;
use function Code_Snippets\code_snippets;


/**
 * Allows two-way sync with Code Snippets Cloud API.
 *
 * @since   3.4.0
 * @package Code_Snippets
 */
class Cloud_REST_Controller extends Snippets_REST_Controller {

	/**
	 * Locally  Token
	 *
	 * @var string
	 */
	private $local_token;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->local_token = code_snippets()->cloud_api->get_cloud_setting( 'token_snippet_id' ) ?? '';

	}


	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		$route = '/' . $this->rest_base;

		register_rest_route(
			$this->namespace,
			$route . '/cloudcreate',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item_from_cloud' ],
					'permission_callback' => [ $this, 'cloud_api_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$route . '/removesync',
			[
				[
					'methods'  => WP_REST_Server::READABLE,
					'callback' => [ $this, 'remove_sync' ],
					'args'     => $this->get_endpoint_args_for_item_schema( true ),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);
	}

	/**
	 * Check the request from Cloud API is valid
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function cloud_api_check( WP_REST_Request $request ): bool {
		return $request->get_header( 'access-control' ) === $this->local_token;
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item_from_cloud( $request ) {
		$snippet_to_store = [];
		$body = json_decode( $request->get_body() );
		$body = reset( $body );
		$body = json_decode( $body, true );

		$snippet_to_store['id'] = 0;
		$snippet_to_store['name'] = $body['name'];
		$snippet_to_store['desc'] = $body['description'];
		$snippet_to_store['code'] = $body['code'];
		$snippet_to_store['scope'] = $body['scope'];
		$snippet_to_store['active'] = false;
		$snippet_to_store['network'] = false;
		$snippet_to_store['modified'] = $body['created'];
		$snippet_to_store['revision'] = $body['revision'] ?? 1;
		$snippet_to_store['priority'] = 10;
		$snippet_to_store['cloud_id'] = $body['id'] . '_0'; // Set to not owner.
		$snippet_to_store['shared_network'] = false;

		$snippet = $this->create_item( $snippet_to_store );

		if ( is_wp_error( $snippet ) ) {
			$error = $snippet->get_error_message();
			return new WP_Error( 'snippet_not_created', $error, [ 'status' => 500 ] );
		}

		$link = new Cloud_Link();
		$link->local_id = $snippet->data['id'];
		$link->cloud_id = $snippet_to_store['id'];
		$link->is_owner = false;
		$link->in_codevault = false;
		$link->update_available = false;

		code_snippets()->cloud_api->add_map_link( $link );

		$response = [
			'status'  => 'success',
			'message' => __( 'Snippet created', 'code-snippets' ),
		];

		return rest_ensure_response( $response );
	}

	/**
	 * Remove sync.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function remove_sync() {
		
		code_snippets()->cloud_api->refresh_cloud_settings_data( true ); 
		code_snippets()->cloud_api->refresh_synced_data();
		
		// Consider disabling the token snippet
		$this->local_token = '';

		$response = [
			'status'  => 'success',
			'message' => __( 'Sync has been revoked', 'code-snippets' ),
		];

		return rest_ensure_response( $response );
	}
}
