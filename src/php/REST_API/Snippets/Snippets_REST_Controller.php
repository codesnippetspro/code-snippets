<?php

namespace Code_Snippets\REST_API\Snippets;

use Code_Snippets\Migration\Export\Export;
use Code_Snippets\Migration\Export\Export_Code;
use Code_Snippets\Migration\Export\Export_JSON;
use Code_Snippets\Model\Snippet;
use Code_Snippets\REST_API\REST_Collection_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\activate_snippet;
use function Code_Snippets\clean_active_snippets_cache;
use function Code_Snippets\code_snippets;
use function Code_Snippets\deactivate_snippet;
use function Code_Snippets\delete_snippet;
use function Code_Snippets\get_snippet;
use function Code_Snippets\get_snippets;
use function Code_Snippets\restore_snippet;
use function Code_Snippets\save_snippet;
use function Code_Snippets\trash_snippet;

/**
 * Allows fetching snippet data through the WordPress REST API.
 *
 * @since   3.4.0
 * @package Code_Snippets
 */
final class Snippets_REST_Controller extends REST_Collection_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base of this controller's route.
	 */
	public const BASE_ROUTE = 'snippets';

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		$route = '/' . self::BASE_ROUTE;
		$id_route = $route . '/(?P<id>[\d]+)';

		$network_args = array_intersect_key(
			$this->get_endpoint_args_for_item_schema(),
			[ 'network' ]
		);

		// Allow standard collection parameters (page, per_page, etc.) on the collection route.
		$collection_args = array_merge( $network_args, $this->get_collection_params() );

		$collection_args['status'] = [
			'description'       => esc_html__( 'Filter snippets by activation status.', 'code-snippets' ),
			'type'              => 'string',
			'enum'              => [ 'all', 'active', 'inactive' ],
			'default'           => 'all',
			'sanitize_callback' => 'sanitize_key',
		];

		$collection_args['exclude_types'] = [
			'description'       => esc_html__( 'List of snippet types to exclude from the response.', 'code-snippets' ),
			'type'              => 'array',
			'items'             => [
				'type' => 'string',
			],
			'default'           => [],
			'sanitize_callback' => static function ( $value ): array {
				$values = is_array( $value ) ? $value : [ $value ];
				return array_values( array_filter( array_map( 'sanitize_key', $values ) ) );
			},
		];

		$collection_args['orderby'] = [
			'description'       => esc_html__( 'Sort collection by object attribute.', 'code-snippets' ),
			'type'              => 'string',
			'enum'              => [ 'id', 'name', 'display_name' ],
			'sanitize_callback' => 'sanitize_key',
		];

		$collection_args['order'] = [
			'description'       => esc_html__( 'Sort direction.', 'code-snippets' ),
			'type'              => 'string',
			'enum'              => [ 'asc', 'desc' ],
			'default'           => 'asc',
			'sanitize_callback' => 'sanitize_key',
		];

		register_rest_route(
			$this->namespace,
			$route,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $collection_args,
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$id_route,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => $network_args,
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
					'args'                => $network_args,
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$id_route . '/restore',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'restore_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
				'args'                => $network_args,
			]
		);

		register_rest_route(
			$this->namespace,
			$route . '/schema',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_public_item_schema' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			$this->namespace,
			$id_route . '/activate',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'activate_item' ],
				'permission_callback' => [ $this, 'toggle_item_permissions_check' ],
				'schema'              => [ $this, 'get_item_schema' ],
				'args'                => $network_args,
			]
		);

		register_rest_route(
			$this->namespace,
			$id_route . '/deactivate',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'deactivate_item' ],
				'permission_callback' => [ $this, 'toggle_item_permissions_check' ],
				'schema'              => [ $this, 'get_item_schema' ],
				'args'                => $network_args,
			]
		);

		register_rest_route(
			$this->namespace,
			$id_route . '/export',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'export_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'schema'              => [ $this, 'get_item_schema' ],
				'args'                => $network_args,
			]
		);

		register_rest_route(
			$this->namespace,
			$id_route . '/export-code',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'export_item_code' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'schema'              => [ $this, 'get_item_schema' ],
				'args'                => $network_args,
			]
		);
	}

	/**
	 * Determine whether the request has permission to access snippets.
	 *
	 * @param WP_REST_Request $request Incoming HTTP request.
	 *
	 * @return bool
	 */
	public function permission_callback( WP_REST_Request $request ): bool {
		return code_snippets()->current_user_can();
	}

	/**
	 * Determine whether a request targets network-scoped snippets.
	 *
	 * Only the literal boolean `true` (or its common string/integer equivalents)
	 * is treated as a network-scoped request. A missing or null `network` param
	 * means "site-scoped", and must not be escalated to the network capability.
	 *
	 * @param WP_REST_Request|WP_Error $request Full data about the request.
	 *
	 * @return bool
	 */
	private function is_network_scoped_request( $request ): bool {
		if ( ! is_multisite() || ! $request instanceof WP_REST_Request || ! $request->has_param( 'network' ) ) {
			return false;
		}

		$network = $request->get_param( 'network' );

		if ( is_bool( $network ) ) {
			return $network;
		}

		if ( is_string( $network ) ) {
			return ! in_array( strtolower( $network ), [ '0', 'false', 'no', '' ], true );
		}

		return (bool) $network;
	}

	/**
	 * Verify the current user has permission for the scope implied by the request.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	private function check_request_capability( WP_REST_Request $request ): bool {
		return $this->is_network_scoped_request( $request )
			? code_snippets()->user_can_manage_network_snippets()
			: code_snippets()->current_user_can();
	}

	/**
	 * Determine whether the request targets a shared network snippet.
	 *
	 * Shared network snippets are stored network-wide but each site decides whether
	 * to activate them via the per-site `active_shared_network_snippets` option. The
	 * `id` route parameter is used to look up the snippet so the result reflects the
	 * actual stored row rather than a value supplied in the request payload.
	 *
	 * @param WP_REST_Request|WP_Error $request Full data about the request.
	 *
	 * @return bool
	 */
	private function is_shared_network_snippet_request( $request ): bool {
		if ( ! is_multisite() || ! $request instanceof WP_REST_Request ) {
			return false;
		}

		$snippet_id = absint( $request->get_param( 'id' ) );

		if ( ! $snippet_id ) {
			return false;
		}

		$snippet = get_snippet( $snippet_id, true );
		return $snippet->id && $snippet->shared_network;
	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function get_items_permissions_check( $request ): bool {
		return $this->check_request_capability( $request );
	}

	/**
	 * Check if a given request has access to get a specific item.
	 *
	 * Shared network snippets are readable by any user who can manage snippets on
	 * the current site, since the snippet is intentionally exposed to subsites.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function get_item_permissions_check( $request ): bool {
		if ( $this->is_shared_network_snippet_request( $request ) ) {
			return code_snippets()->current_user_can();
		}

		return $this->check_request_capability( $request );
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function create_item_permissions_check( $request ): bool {
		return $this->check_request_capability( $request );
	}

	/**
	 * Check if a given request has access to update a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function update_item_permissions_check( $request ): bool {
		return $this->check_request_capability( $request );
	}

	/**
	 * Check if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function delete_item_permissions_check( $request ): bool {
		return $this->check_request_capability( $request );
	}

	/**
	 * Check if a given request has access to toggle a snippet's activation.
	 *
	 * For shared network snippets the activation toggle only writes to the
	 * per-site `active_shared_network_snippets` option, so the site capability
	 * is sufficient. For all other snippets we keep the strict capability check
	 * that prevents a subsite admin from forging `network=true` to operate on
	 * exclusive network-scoped snippets.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function toggle_item_permissions_check( WP_REST_Request $request ): bool {
		if ( $this->is_shared_network_snippet_request( $request ) ) {
			return code_snippets()->current_user_can();
		}

		return $this->check_request_capability( $request );
	}

	/**
	 * Retrieves a collection of snippets, with pagination.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_items( $request ): WP_REST_Response {
		$network = $request->get_param( 'network' );
		$all_snippets = $this->get_network_items( get_snippets( [], $network ), $network );

		$status = sanitize_key( (string) $request->get_param( 'status' ) );

		$exclude_types = $request->get_param( 'exclude_types' );
		$exclude_types = is_array( $exclude_types ) ? array_map( 'sanitize_key', $exclude_types ) : [];

		if ( $exclude_types || 'all' !== $status ) {
			$all_snippets = array_filter(
				$all_snippets,
				static function ( Snippet $snippet ) use ( $exclude_types, $status ): bool {
					if ( $exclude_types && in_array( $snippet->type, $exclude_types, true ) ) {
						return false;
					}

					if ( 'active' === $status ) {
						return ! $snippet->trashed && $snippet->active;
					}

					if ( 'inactive' === $status ) {
						return ! $snippet->trashed && ! $snippet->active;
					}

					return true;
				}
			);
		}

		$orderby = sanitize_key( (string) $request->get_param( 'orderby' ) );
		$order = sanitize_key( (string) $request->get_param( 'order' ) );

		if ( $orderby ) {
			$direction = 'desc' === $order ? -1 : 1;

			usort(
				$all_snippets,
				static function ( Snippet $a, Snippet $b ) use ( $orderby, $direction ): int {
					switch ( $orderby ) {
						case 'display_name':
							$cmp = strcasecmp( $a->display_name, $b->display_name );
							break;
						case 'name':
							$cmp = strcasecmp( $a->name, $b->name );
							break;
						case 'id':
						default:
							$cmp = $a->id <=> $b->id;
							break;
					}

					return 0 === $cmp ? ( $a->id <=> $b->id ) * $direction : $cmp * $direction;
				}
			);
		}

		$total_items = count( $all_snippets );
		$query_params = $request->get_query_params();

		if ( isset( $query_params['per_page'] ) || isset( $query_params['page'] ) ) {
			$collection_params = $this->get_collection_params();
			$per_page = isset( $query_params['per_page'] )
				? max( 1, (int) $query_params['per_page'] )
				: (int) $collection_params['per_page']['default'];

			$page_request = (int) $request->get_param( 'page' );
			$page = max( 1, $page_request ? $page_request : (int) $collection_params['page']['default'] );
			$total_pages = (int) ceil( $total_items / $per_page );

			$offset = ( $page - 1 ) * $per_page;
			$snippets = array_slice( $all_snippets, $offset, $per_page );
		} else {
			$snippets = $all_snippets;
			$total_pages = 1;
		}

		$response = rest_ensure_response(
			array_map(
				function ( $snippet ) use ( $request ) {
					$response_item = $this->prepare_item_for_response( $snippet, $request );
					return $this->prepare_response_for_collection( $response_item );
				},
				$snippets
			)
		);

		$response->header( 'X-WP-Total', (string) $total_items );
		$response->header( 'X-WP-TotalPages', (string) $total_pages );

		return $response;
	}

	/**
	 * Retrieve and merge shared network snippets.
	 *
	 * @param Snippet[] $all_snippets List of snippets to merge with.
	 * @param bool|null $network      Whether fetching network snippets.
	 *
	 * @return Snippet[] Modified list of snippets.
	 */
	private function get_network_items( array $all_snippets, ?bool $network ): array {
		if ( ! is_multisite() || $network ) {
			return $all_snippets;
		}

		$shared_ids = get_site_option( 'shared_network_snippets' );

		if ( ! $shared_ids || ! is_array( $shared_ids ) ) {
			return $all_snippets;
		}

		$active_shared_snippets = get_option( 'active_shared_network_snippets', [] );
		$shared_snippets = get_snippets( $shared_ids, true );

		foreach ( $shared_snippets as $snippet ) {
			$snippet->shared_network = true;
			$snippet->active = in_array( $snippet->id, $active_shared_snippets, true );
		}

		return array_merge( $all_snippets, $shared_snippets );
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

		if ( ! $item->id && 0 !== $snippet_id && '0' !== $snippet_id ) {
			return new WP_Error(
				'rest_cannot_get',
				__( 'The snippet could not be found.', 'code-snippets' ),
				[ 'status' => 404 ]
			);
		}

		$data = $this->prepare_item_for_response( $item, $request );
		return rest_ensure_response( $data );
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request|array $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$snippet = $this->prepare_item_for_database( $request );
		$result = save_snippet( $snippet );

		return $result
			? $this->prepare_item_for_response( $result, $request )
			: new WP_Error(
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
		$snippet_id = absint( $request->get_param( 'id' ) );
		$snippet = $snippet_id ? get_snippet( $snippet_id, $request->get_param( 'network' ) ) : null;

		if ( ! $snippet_id || ! $snippet || ! $snippet->id ) {
			return new WP_Error(
				'rest_cannot_update',
				__( 'Cannot update a snippet without a valid ID.', 'code-snippets' ),
				[ 'status' => 400 ]
			);
		}

		$item = $this->prepare_item_for_database( $request, $snippet );
		$result = save_snippet( $item );

		return $result
			? rest_ensure_response( $this->prepare_item_for_response( $result, $request ) )
			: new WP_Error(
				'rest_cannot_update',
				__( 'The snippet could not be updated.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
	}

	/**
	 * Delete one item from the collection, or trash it if not already trashed.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$item = $this->prepare_item_for_database( $request );
		$snippet = get_snippet( $item->id, $item->network );

		if ( ! $snippet || ! $snippet->id ) {
			return new WP_Error(
				'rest_cannot_delete',
				__( 'The snippet could not be found.', 'code-snippets' ),
				[ 'status' => 404 ]
			);
		}

		if ( $snippet->trashed ) {
			return delete_snippet( $snippet->id, $snippet->network )
				? new WP_REST_Response( null, 204 )
				: new WP_Error(
					'rest_cannot_delete',
					__( 'The snippet could not be deleted.', 'code-snippets' ),
					[ 'status' => 500 ]
				);
		} else {
			return trash_snippet( $snippet->id, $snippet->network )
				? $this->get_item( $request )
				: new WP_Error(
					'rest_cannot_trash',
					__( 'The snippet could not be trashed.', 'code-snippets' ),
					[ 'status' => 500 ]
				);
		}
	}

	/**
	 * Restore a deleted item from the trash.
	 *
	 * @param WP_REST_Request $request Snippet information.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function restore_item( WP_REST_Request $request ) {
		$item = $this->prepare_item_for_database( $request );

		return restore_snippet( $item->id, $item->network )
			? new WP_REST_Response( null, 204 )
			: new WP_Error(
				'rest_cannot_restore',
				__( 'The snippet could not be restored.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
	}

	/**
	 * Fetch snippet using data from request.
	 *
	 * @param WP_REST_Request $request Request containing 'id' and 'network' parameters.
	 *
	 * @return Snippet|WP_Error
	 */
	private function get_requested_snippet( WP_REST_Request $request ): Snippet {
		$id = $request->get_param( 'id' );

		$snippet = $id && is_numeric( $id )
			? get_snippet( $id, $request->get_param( 'network' ) )
			: null;

		if ( ! $snippet || ! $snippet->id ) {
			return new WP_Error(
				'rest_cannot_activate',
				__( 'The snippet could not be found.', 'code-snippets' ),
				[ 'status' => 404 ]
			);
		}

		return $snippet;
	}

	/**
	 * Activate one item in the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function activate_item( WP_REST_Request $request ) {
		$snippet = $this->get_requested_snippet( $request );

		if ( is_wp_error( $snippet ) ) {
			return rest_ensure_response( $snippet );
		}

		if ( $snippet->shared_network ) {
			$this->set_shared_network_active( $snippet->id, true );
			$snippet->active = true;
			return rest_ensure_response( $snippet );
		}

		$result = activate_snippet( $snippet->id, $snippet->network );

		return $result instanceof Snippet
			? rest_ensure_response( $result )
			: new WP_Error(
				'rest_cannot_activate',
				$result,
				[ 'status' => 500 ]
			);
	}

	/**
	 * Deactivate one item in the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function deactivate_item( WP_REST_Request $request ) {
		$snippet = $this->get_requested_snippet( $request );

		if ( is_wp_error( $snippet ) ) {
			return rest_ensure_response( $snippet );
		}

		if ( $snippet->shared_network ) {
			$this->set_shared_network_active( $snippet->id, false );
			$snippet->active = false;
			return rest_ensure_response( $snippet );
		}

		$result = deactivate_snippet( $snippet->id, $snippet->network );

		return $result instanceof Snippet
			? rest_ensure_response( $result )
			: new WP_Error(
				'rest_cannot_activate',
				__( 'The snippet could not be deactivated.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
	}

	/**
	 * Toggle a shared network snippet's active state for the current site only.
	 *
	 * @param int  $snippet_id Snippet identifier.
	 * @param bool $active     Whether the snippet should be active on the current site.
	 *
	 * @return void
	 */
	private function set_shared_network_active( int $snippet_id, bool $active ): void {
		$active_shared_snippets = get_option( 'active_shared_network_snippets', [] );

		if ( ! is_array( $active_shared_snippets ) ) {
			$active_shared_snippets = [];
		}

		$already_active = in_array( $snippet_id, $active_shared_snippets, true );

		if ( $active === $already_active ) {
			return;
		}

		$active_shared_snippets = $active
			? array_merge( $active_shared_snippets, [ $snippet_id ] )
			: array_values( array_diff( $active_shared_snippets, [ $snippet_id ] ) );

		update_option( 'active_shared_network_snippets', $active_shared_snippets );
		clean_active_snippets_cache( code_snippets()->db->ms_table );
	}

	/**
	 * Prepare an instance of the Export class from a request.
	 *
	 * @param Export $export Instance of Export class to use for generating response.
	 *
	 * @return WP_REST_Response
	 */
	protected function build_export_response( Export $export ): WP_REST_Response {
		$response = rest_ensure_response( $export->generate_export() );
		$response->header( 'X-Suggested-Filename', $export->build_filename() );
		return $response;
	}

	/**
	 * Retrieve one item in the collection in JSON export format.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function export_item( WP_REST_Request $request ): WP_REST_Response {
		$item = $this->prepare_item_for_database( $request );
		$export = new Export_JSON( [ $item->id ], $item->network );

		return $this->build_export_response( $export );
	}

	/**
	 * Retrieve one item in the collection in the code export format.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function export_item_code( WP_REST_Request $request ): WP_REST_Response {
		$item = $this->prepare_item_for_database( $request );
		$export = new Export_Code( [ $item->id ], $item->network );

		return $this->build_export_response( $export );
	}

	/**
	 * Prepares one item for create or update operation.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param Snippet|null    $item    Existing item to augment.
	 *
	 * @return Snippet The prepared item.
	 */
	protected function prepare_item_for_database( $request, ?Snippet $item = null ): Snippet {
		if ( ! $item instanceof Snippet ) {
			$item = new Snippet();
		}

		foreach ( $item->get_allowed_fields() as $field ) {
			if ( $request->has_param( $field ) ) {
				$item->set_field( $field, $request->get_param( $field ) );
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
		$schema = $this->get_item_schema();
		$response = [];

		foreach ( array_keys( $schema['properties'] ) as $property ) {
			$response[ $property ] = $item->$property;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Get our sample schema for a post.
	 *
	 * @return array<string, mixed> The sample schema for a post
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'snippet',
			'type'       => 'object',
			'properties' => [
				'id'               => [
					'description' => esc_html__( 'Unique identifier for the snippet.', 'code-snippets' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'name'             => [
					'description' => esc_html__( 'Descriptive title for the snippet.', 'code-snippets' ),
					'type'        => 'string',
				],
				'desc'             => [
					'description' => esc_html__( 'Descriptive text associated with snippet.', 'code-snippets' ),
					'type'        => 'string',
				],
				'code'             => [
					'description' => esc_html__( 'Executable snippet code.', 'code-snippets' ),
					'type'        => 'string',
				],
				'tags'             => [
					'description' => esc_html__( 'List of tag categories the snippet belongs to.', 'code-snippets' ),
					'type'        => 'array',
					'items'       => [
						'type' => 'string',
					],
				],
				'scope'            => [
					'description' => esc_html__( 'Context in which the snippet is executable.', 'code-snippets' ),
					'type'        => 'string',
				],
				'condition_id'     => [
					'description' => esc_html__( 'Identifier of condition linked to this snippet.', 'code-snippets' ),
					'type'        => 'integer',
				],
				'active'           => [
					'description' => esc_html__( 'Snippet activation status.', 'code-snippets' ),
					'type'        => 'boolean',
				],
				'trashed'          => [
					'description' => esc_html__( 'Whether the snippet is marked as deleted.', 'code-snippets' ),
					'type'        => 'boolean',
				],
				'locked'           => [
					'description' => esc_html__( 'Whether the snippet is locked from modification or deletion.', 'code-snippets' ),
					'type'        => 'boolean',
				],
				'priority'         => [
					'description' => esc_html__( 'Relative priority in which the snippet is executed.', 'code-snippets' ),
					'type'        => 'integer',
				],
				'network'          => [
					'description' => esc_html__( 'Whether the snippet is network-wide instead of site-wide.', 'code-snippets' ),
					'type'        => [ 'boolean', 'null' ],
					'default'     => null,
				],
				'shared_network'   => [
					'description' => esc_html__( 'If a network snippet, whether can be activated on discrete sites instead of network-wide.', 'code-snippets' ),
					'type'        => [ 'boolean', 'null' ],
				],
				'modified'         => [
					'description' => esc_html__( 'Date and time when the snippet was last modified, in ISO format.', 'code-snippets' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				],
				'last_active'      => [
					'description' => esc_html__( 'Timestamp of when the snippet was last active, if available.', 'code-snippets' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'code_error'       => [
					'description' => esc_html__( 'Error message if the snippet code could not be parsed.', 'code-snippets' ),
					'type'        => [ 'array', 'null' ],
					'items'       => [
						'type' => [ 'string', 'integer' ],
					],
					'readonly'    => true,
				],
				'code_error_trace' => [
					'description' => esc_html__( 'Stack trace for the most recent snippet code error.', 'code-snippets' ),
					'type'        => [ 'string', 'null' ],
					'readonly'    => true,
				],
			],
		];

		return $this->schema;
	}
}
