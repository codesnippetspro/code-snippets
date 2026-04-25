<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\Export;
use Code_Snippets\Snippet;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\activate_snippet;
use function Code_Snippets\clean_active_snippets_cache;
use function Code_Snippets\code_snippets;
use function Code_Snippets\deactivate_snippet;
use function Code_Snippets\trash_snippet;
use function Code_Snippets\get_snippet;
use function Code_Snippets\get_snippets;
use function Code_Snippets\save_snippet;
use const Code_Snippets\REST_API_NAMESPACE;

/**
 * Allows fetching snippet data through the WordPress REST API.
 *
 * @since   3.4.0
 * @package Code_Snippets
 */
final class Snippets_REST_Controller extends WP_REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base of this controller's route.
	 */
	public const BASE_ROUTE = 'snippets';

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
	protected $rest_base = self::BASE_ROUTE;

	/**
	 * Retrieve this controller's REST API base path, including namespace.
	 *
	 * @return string
	 */
	public static function get_base_route(): string {
		return REST_API_NAMESPACE . self::VERSION . '/' . self::BASE_ROUTE;
	}

	/**
	 * Retrieve the full base route including the REST API prefix.
	 *
	 * @return string
	 */
	public static function get_prefixed_base_route(): string {
		return '/' . rtrim( rest_get_url_prefix(), '/\\' ) . '/' . self::get_base_route();
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		$route = '/' . $this->rest_base;
		$id_route = $route . '/(?P<id>[\d]+)';

		$network_args = array_intersect_key(
			$this->get_endpoint_args_for_item_schema(),
			[ 'network' ]
		);

		// Allow standard collection parameters (page, per_page, etc.) on the collection route.
		$collection_args = array_merge( $network_args, $this->get_collection_params() );

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
	 * Retrieves a collection of snippets, with pagination.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_items( $request ): WP_REST_Response {
		$network = $request->get_param( 'network' );
		$all_snippets = get_snippets( [], $network );
		$all_snippets = $this->get_network_items( $all_snippets, $network );

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

		$snippets_data = [];

		foreach ( $snippets as $snippet ) {
			$snippet_data = $this->prepare_item_for_response( $snippet, $request );
			$snippets_data[] = $this->prepare_response_for_collection( $snippet_data );
		}

		$response = rest_ensure_response( $snippets_data );
		$response->header( 'X-WP-Total', (string) $total_items );
		$response->header( 'X-WP-TotalPages', (string) $total_pages );

		return $response;
	}

	/**
	 * Retrieve and merge shared network snippets.
	 *
	 * @param array<Snippet> $all_snippets List of snippets to merge with.
	 * @param bool|null      $network      Whether fetching network snippets.
	 *
	 * @return array<Snippet> Modified list of snippets.
	 */
	private function get_network_items( array $all_snippets, $network ): array {
		if ( ! is_multisite() || $network ) {
			return $all_snippets;
		}

		$shared_ids = get_site_option( 'shared_network_snippets' );

		if ( ! $shared_ids || ! is_array( $shared_ids ) ) {
			return $all_snippets;
		}

		$active_shared_snippets = get_option( 'active_shared_network_snippets', array() );
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
				[ 'status' => 500 ]
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
		$result = $snippet ? save_snippet( $snippet ) : null;

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

		if ( $result ) {
			$request->set_param( 'id', $result->id );
			return $this->get_item( $request );
		}

		return new WP_Error(
			'rest_cannot_update',
			__( 'The snippet could not be updated.', 'code-snippets' ),
			[ 'status' => 500 ]
		);
	}

	/**
	 * Delete one item from the collection (trash)
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$item = $this->prepare_item_for_database( $request );
		$result = trash_snippet( $item->id, $item->network );

		return $result ?
			new WP_REST_Response( null, 204 ) :
			new WP_Error(
				'rest_cannot_delete',
				__( 'The snippet could not be deleted.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
	}

	/**
	 * Activate one item in the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function activate_item( WP_REST_Request $request ) {
		$item = $this->prepare_item_for_database( $request );
		$snippet = $item ? get_snippet( $item->id, $item->network ) : null;

		if ( ! $snippet || ! $snippet->id ) {
			return new WP_Error(
				'rest_cannot_activate',
				__( 'The snippet could not be found.', 'code-snippets' ),
				[ 'status' => 404 ]
			);
		}

		if ( $snippet->shared_network ) {
			$this->set_shared_network_active( $snippet->id, true );
			$snippet->active = true;
			return rest_ensure_response( $snippet );
		}

		$result = activate_snippet( $snippet->id, $snippet->network );

		return $result instanceof Snippet ?
			rest_ensure_response( $result ) :
			new WP_Error(
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
		$item = $this->prepare_item_for_database( $request );
		$snippet = $item ? get_snippet( $item->id, $item->network ) : null;

		if ( ! $snippet || ! $snippet->id ) {
			return new WP_Error(
				'rest_cannot_activate',
				__( 'The snippet could not be found.', 'code-snippets' ),
				[ 'status' => 404 ]
			);
		}

		if ( $snippet->shared_network ) {
			$this->set_shared_network_active( $snippet->id, false );
			$snippet->active = false;
			return rest_ensure_response( $snippet );
		}

		$result = deactivate_snippet( $snippet->id, $snippet->network );

		return $result instanceof Snippet ?
			rest_ensure_response( $result ) :
			new WP_Error(
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

		$active_shared_snippets = $active ?
			array_merge( $active_shared_snippets, [ $snippet_id ] ) :
			array_values( array_diff( $active_shared_snippets, [ $snippet_id ] ) );

		update_option( 'active_shared_network_snippets', $active_shared_snippets );
		clean_active_snippets_cache( code_snippets()->db->ms_table );
	}

	/**
	 * Prepare an instance of the Export class from a request.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return Export
	 */
	protected function build_export( WP_REST_Request $request ): Export {
		$item = $this->prepare_item_for_database( $request );
		return new Export( [ $item->id ], $item->network );
	}

	/**
	 * Retrieve one item in the collection in JSON export format.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function export_item( WP_REST_Request $request ) {
		$export = $this->build_export( $request );
		$result = $export->create_export_object();
		return rest_ensure_response( $result );
	}

	/**
	 * Retrieve one item in the collection in the code export format.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function export_item_code( WP_REST_Request $request ) {
		$export = $this->build_export( $request );
		$result = $export->export_snippets_code();

		return rest_ensure_response( $result );
	}

	/**
	 * Prepares one item for create or update operation.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param Snippet|null    $item    Existing item to augment.
	 *
	 * @return Snippet The prepared item.
	 */
	protected function prepare_item_for_database( $request, ?Snippet $item = null ): ?Snippet {
		if ( ! $item instanceof Snippet ) {
			$item = new Snippet();
		}

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
		$schema = $this->get_item_schema();
		$response = [];

		foreach ( array_keys( $schema['properties'] ) as $property ) {
			$response[ $property ] = $item->$property;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Determine whether a request targets network-scoped snippets.
	 *
	 * Only the literal boolean `true` (or its common string/integer equivalents)
	 * is treated as a network-scoped request. A missing or null `network` param
	 * means "site-scoped", and must not be escalated to the network capability.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	private function is_network_scoped_request( $request ): bool {
		if ( ! is_multisite() ) {
			return false;
		}

		if ( ! $request instanceof WP_REST_Request || ! $request->has_param( 'network' ) ) {
			return false;
		}

		$network = $request->get_param( 'network' );

		if ( is_bool( $network ) ) {
			return $network;
		}

		if ( is_string( $network ) ) {
			return in_array( strtolower( $network ), [ '1', 'true', 'yes' ], true );
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
	private function check_request_capability( $request ): bool {
		if ( $this->is_network_scoped_request( $request ) ) {
			return code_snippets()->user_can_manage_network_snippets();
		}

		return code_snippets()->current_user_can();
	}

	/**
	 * Determine whether the request targets a shared network snippet.
	 *
	 * Shared network snippets are stored network-wide but each site decides whether
	 * to activate them via the per-site `active_shared_network_snippets` option. The
	 * `id` route parameter is used to look up the snippet so the result reflects the
	 * actual stored row rather than a value supplied in the request payload.
	 *
	 * @param WP_REST_Request $request Full data about the request.
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

		return $snippet && $snippet->id && $snippet->shared_network;
	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
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
	 * @return boolean
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
	 * @return boolean
	 */
	public function create_item_permissions_check( $request ): bool {
		return $this->check_request_capability( $request );
	}

	/**
	 * Check if a given request has access to update a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function update_item_permissions_check( $request ): bool {
		return $this->check_request_capability( $request );
	}

	/**
	 * Check if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
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
	 * @return boolean
	 */
	public function toggle_item_permissions_check( $request ): bool {
		if ( $this->is_shared_network_snippet_request( $request ) ) {
			return code_snippets()->current_user_can();
		}

		return $this->check_request_capability( $request );
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
				'id'             => [
					'description' => esc_html__( 'Unique identifier for the snippet.', 'code-snippets' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'name'           => [
					'description' => esc_html__( 'Descriptive title for the snippet.', 'code-snippets' ),
					'type'        => 'string',
				],
				'desc'           => [
					'description' => esc_html__( 'Descriptive text associated with snippet.', 'code-snippets' ),
					'type'        => 'string',
				],
				'code'           => [
					'description' => esc_html__( 'Executable snippet code.', 'code-snippets' ),
					'type'        => 'string',
				],
				'tags'           => [
					'description' => esc_html__( 'List of tag categories the snippet belongs to.', 'code-snippets' ),
					'type'        => 'array',
					'items'       => [
						'type' => 'string',
					],
				],
				'scope'          => [
					'description' => esc_html__( 'Context in which the snippet is executable.', 'code-snippets' ),
					'type'        => 'string',
				],
				'condition_id'   => [
					'description' => esc_html__( 'Identifier of condition linked to this snippet.', 'code-snippets' ),
					'type'        => 'integer',
				],
				'active'         => [
					'description' => esc_html__( 'Snippet activation status.', 'code-snippets' ),
					'type'        => 'boolean',
				],
				'priority'       => [
					'description' => esc_html__( 'Relative priority in which the snippet is executed.', 'code-snippets' ),
					'type'        => 'integer',
				],
				'network'        => [
					'description' => esc_html__( 'Whether the snippet is network-wide instead of site-wide.', 'code-snippets' ),
					'type'        => [ 'boolean', 'null' ],
					'default'     => null,
				],
				'shared_network' => [
					'description' => esc_html__( 'If a network snippet, whether can be activated on discrete sites instead of network-wide.', 'code-snippets' ),
					'type'        => [ 'boolean', 'null' ],
				],
				'modified'       => [
					'description' => esc_html__( 'Date and time when the snippet was last modified, in ISO format.', 'code-snippets' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				],
				'code_error'     => [
					'description' => esc_html__( 'Error message if the snippet code could not be parsed.', 'code-snippets' ),
					'type'        => 'string',
					'readonly'    => true,
				],
			],
		];

		return $this->schema;
	}
}
