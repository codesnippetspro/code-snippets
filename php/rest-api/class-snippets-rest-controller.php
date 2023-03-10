<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\Snippet;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\delete_snippet;
use const Code_Snippets\REST_API_NAMESPACE;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippet;
use function Code_Snippets\get_snippets;
use function Code_Snippets\save_snippet;

/**
 * Allows fetching snippet data through the WordPress REST API.
 *
 * @since   [NEXT_VERSION]
 * @package Code_Snippets
 */
class Snippets_REST_Controller extends WP_REST_Controller {

	/**
	 * Current API version.
	 */
	const VERSION = 1;

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = REST_API_NAMESPACE . self::VERSION;

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'snippets';

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		$route = '/' . $this->rest_base;

		register_rest_route(
			$this->namespace,
			$route,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
			]
		);

		register_rest_route(
			$this->namespace,
			$route . '/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => [
						'context' => [
							'default' => 'view',
						],
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( false ),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'force' => [
							'default' => false,
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			$route . '/schema',
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Retrieves a collection of snippets.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_items( $request ) {
		$snippets = get_snippets();
		$data = [];

		foreach ( $snippets as $snippet ) {
			$snippet_data = $this->prepare_item_for_response( $snippet, $request );
			$data[] = $this->prepare_response_for_collection( $snippet_data );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success.
	 */
	public function get_item( $request ) {
		$snippet_id = $request->get_param( 'id' );
		$item = get_snippet( $snippet_id, $request->get_param( 'network' ) );

		if ( ! $item->id && $snippet_id !== 0 && $snippet_id !== '0' ) {
			return new WP_Error(
				'rest_cannot_get',
				__( 'The snippet could not be found.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
		}

		$data = $this->prepare_item_for_response( $item, $request );
		return rest_ensure_response( $data );
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$snippet = $this->prepare_item_for_database( $request );
		$result_id = save_snippet( $snippet );

		$result = $result_id ? get_snippet( $result_id, $snippet->network ) : null;

		return $result ?
			$this->prepare_item_for_response( $result, $request ) :
			new WP_Error(
				'rest_cannot_create',
				__( 'The snippet could not be created.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$item = $this->prepare_item_for_database( $request );

		if ( ! $item->id ) {
			return new WP_Error(
				'rest_cannot_update',
				__( 'Cannot update a snippet without a valid ID.', 'code-snippets' ),
				[ 'status' => 400 ]
			);
		}

		$result_id = save_snippet( $item );
		$result = $result_id ? get_snippet( $result_id, $item->network ) : null;

		return $result ?
			$this->prepare_item_for_response( $result, $request ) :
			new WP_Error(
				'rest_cannot_update',
				__( 'The snippet could not be updated.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
	}

	/**
	 * Delete one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$item = $this->prepare_item_for_database( $request );
		$result = delete_snippet( $item->id, $item->network );

		return $result ?
			new WP_REST_Response( null, 204 ) :
			new WP_Error(
				'rest_cannot_delete',
				__( 'The snippet could not be deleted.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
	}


	/**
	 * Prepares one item for create or update operation.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return Snippet The prepared item.
	 */
	protected function prepare_item_for_database( $request ) {
		$item = new Snippet();

		foreach ( $item->get_allowed_fields() as $field ) {
			if ( isset( $request[ $field ] ) ) {
				$item->set_field( $field, $request[ $field ] );
			}
		}

		return $item;
	}

	/**
	 * Prepare the item for the REST response.
	 *
	 * @param Snippet         $item    Snippet object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		return rest_ensure_response( $item->get_fields() );
	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function get_items_permissions_check( $request ) {
		return code_snippets()->current_user_can();
	}

	/**
	 * Check if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function create_item_permissions_check( $request ) {
		return code_snippets()->current_user_can();
	}

	/**
	 * Check if a given request has access to update a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function update_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}
}
