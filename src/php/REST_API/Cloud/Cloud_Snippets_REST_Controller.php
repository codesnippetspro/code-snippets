<?php

namespace Code_Snippets\REST_API\Cloud;

use Code_Snippets\Client\Cloud_API;
use Code_Snippets\Model\Cloud_Link;
use Code_Snippets\Model\Snippet;
use Code_Snippets\REST_API\REST_Collection_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\save_snippet;

/**
 * Allows fetching cloud snippets through the WordPress REST API.
 *
 * @package Code_Snippets
 */
final class Cloud_Snippets_REST_Controller extends Cloud_Collection_REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base of this controller's route.
	 */
	public const BASE_ROUTE = 'cloud/snippets';

	/**
	 * Common filter args shared across search and featured endpoints.
	 *
	 * Each filter accepts a single numeric ID (e.g. category=12). The cloud API
	 * resolves IDs to the underlying name/slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_filter_args(): array {
		return [
			'category' => [
				'description' => esc_html__( 'Filter by category ID.', 'code-snippets' ),
				'type'        => 'string',
				'default'     => '',
			],
			'type'     => [
				'description' => esc_html__( 'Filter by language/type ID.', 'code-snippets' ),
				'type'        => 'string',
				'default'     => '',
			],
			'status'   => [
				'description' => esc_html__( 'Filter by status ID.', 'code-snippets' ),
				'type'        => 'string',
				'default'     => '',
			],
		];
	}

	/**
	 * Extract filter values from a request.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array<string, string>
	 */
	private function extract_filters( WP_REST_Request $request ): array {
		return array_filter(
			[
				'category' => $request->get_param( 'category' ) ?? '',
				'type'     => $request->get_param( 'type' ) ?? '',
				'status'   => $request->get_param( 'status' ) ?? '',
			]
		);
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		$collection_args = $this->get_collection_params();
		$collection_args['per_page']['default'] = $this->get_snippets_per_page();
		$filter_args = $this->get_filter_args();

		register_rest_route(
			$this->namespace,
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => array_merge(
						$collection_args,
						$filter_args,
						[
							'query'             => [
								'description' => esc_html__( 'Search query.', 'code-snippets' ),
								'type'        => 'string',
								'required'    => true,
							],
							'searchByCodevault' => [
								'description' => esc_html__( 'Treat the search query as the name of a CodeVault instead of a search term.', 'code-snippets' ),
								'type'        => 'boolean',
								'default'     => false,
							],
							'page'              => [
								'description' => esc_html__( 'Page number.', 'code-snippets' ),
								'type'        => 'integer',
								'default'     => 1,
							],
						]
					),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item_from_cloud' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => rest_get_endpoint_args_for_schema( $this->get_cloud_snippet_schema() ),
				],
				'schema' => [ $this, 'get_cloud_snippet_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/featured',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_featured_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => array_merge(
						$filter_args,
						[
							'page'     => [
								'description' => esc_html__( 'Page number.', 'code-snippets' ),
								'type'        => 'integer',
								'default'     => 1,
							],
							'per_page' => [
								'description' => esc_html__( 'Results per page.', 'code-snippets' ),
								'type'        => 'integer',
								'default'     => $this->get_snippets_per_page(),
							],
						]
					),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<id>\d+)/download',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'download_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => esc_html__( 'Cloud snippet ID.', 'code-snippets' ),
							'type'        => 'number',
							'required'    => true,
						],
					],
				],
			]
		);
	}

	/**
	 * Retrieve cloud snippets using a search query.
	 *
	 * @param WP_REST_Request $request The request object containing the search parameters.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$method = $request->get_param( 'searchByCodevault' ) ? 'codevault' : 'term';
		$query = $request->get_param( 'query' );
		$query_params = $request->get_query_params();
		$page = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = isset( $query_params['per_page'] )
			? min( Cloud_API::MAX_RESULTS_PER_PAGE, max( 1, (int) $request->get_param( 'per_page' ) ) )
			: $this->get_snippets_per_page();

		$filters = $this->extract_filters( $request );
		$cloud_snippets = Cloud_API::fetch_search_results( $method, $query, $page, $per_page, $filters );
		return $cloud_snippets->to_rest_response();
	}

	/**
	 * Retrieve featured snippets from the cloud API.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_featured_items( WP_REST_Request $request ): WP_REST_Response {
		$page = max( 1, (int) $request->get_param( 'page' ) );
		$query_params = $request->get_query_params();
		$per_page = isset( $query_params['per_page'] )
			? min( Cloud_API::MAX_RESULTS_PER_PAGE, max( 1, (int) $request->get_param( 'per_page' ) ) )
			: $this->get_snippets_per_page();

		$filters = $this->extract_filters( $request );
		$cloud_snippets = Cloud_API::get_featured_snippets( $page, $per_page, $filters );
		return $cloud_snippets->to_rest_response();
	}

	/**
	 * Get the user's snippets per-page preference for Screen Options pagination.
	 *
	 * @return int
	 */
	private function get_snippets_per_page(): int {
		$per_page = (int) get_user_option( 'snippets_per_page' );

		return $per_page > 0 ? min( Cloud_API::MAX_RESULTS_PER_PAGE, $per_page ) : 10;
	}

	/**
	 * Get the schema for a cloud snippet request body.
	 *
	 * @return array
	 */
	public function get_cloud_snippet_schema(): array {
		static $schema = null;

		if ( ! is_null( $schema ) ) {
			return $schema;
		}

		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'cloud snippet',
			'type'       => 'object',
			'properties' => [
				'id'          => [
					'description' => esc_html__( 'Cloud snippet identifier.', 'code-snippets' ),
					'type'        => 'string',
				],
				'name'        => [
					'description' => esc_html__( 'Title of cloud snippet.', 'code-snippets' ),
					'type'        => 'string',
				],
				'description' => [
					'description' => esc_html__( 'Descriptive text associated with snippet.', 'code-snippets' ),
					'type'        => 'string',
				],
				'code'        => [
					'description' => esc_html__( 'Executable snippet code.', 'code-snippets' ),
					'type'        => 'string',
				],
				'scope'       => [
					'description' => esc_html__( 'Context in which the snippet is executable.', 'code-snippets' ),
					'type'        => 'string',
				],
				'created'     => [
					'description' => esc_html__( 'Date and time when the snippet was last created, in ISO format.', 'code-snippets' ),
					'type'        => 'string',
				],
				'revision'    => [
					'description' => esc_html__( 'Snippet revision number.', 'code-snippets' ),
					'type'        => 'integer',
				],
			],
		];

		return $schema;
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$body = json_decode( $request->get_body() );
		$snippet_data = json_decode( $body[0], true );

		$snippet = new Snippet();

		$snippet->name = $snippet_data['name'];
		$snippet->desc = $snippet_data['description'];
		$snippet->code = $snippet_data['code'];
		$snippet->scope = $snippet_data['scope'];
		$snippet->modified = $snippet_data['created'];
		$snippet->revision = $snippet_data['revision'] ?? 1;
		$snippet->cloud_id = $snippet_data['id'] . '_0'; // Set to not owner.
		$snippet->shared_network = false;
		$snippet->network = false;
		$snippet->active = false;

		$result = save_snippet( $snippet );

		if ( ! $result ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'The snippet could not be created.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
		}

		$link = new Cloud_Link();
		$link->local_id = $snippet_data['id'];
		$link->cloud_id = $snippet->cloud_id;
		$link->is_owner = false;
		$link->in_codevault = false;
		$link->update_available = false;

		$this->cloud_api->add_cloud_link( $link );

		$response = [
			'status'  => 'success',
			'message' => __( 'Snippet created', 'code-snippets' ),
		];

		return rest_ensure_response( $response );
	}

	/**
	 * Download a single cloud snippet.
	 *
	 * @param WP_REST_Request $request The request object containing the search parameters.
	 *
	 * @return WP_REST_Response
	 */
	public function download_item( WP_REST_Request $request ): WP_REST_Response {
		$id = $request->get_param( 'id' );
		$in_codevault = $request->get_param( 'in_codevault' );

		$cloud_snippet = $this->cloud_api->get_single_snippet_from_cloud( $id );
		return rest_ensure_response( $this->cloud_api->download_snippet_from_cloud( $cloud_snippet, $in_codevault ) );
	}
}
