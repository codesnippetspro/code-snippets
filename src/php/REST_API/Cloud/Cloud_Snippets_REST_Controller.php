<?php

namespace Code_Snippets\REST_API\Cloud;

use Code_Snippets\Admin\Menus\Manage_Menu;
use Code_Snippets\Controller\Cloud_Search_Controller;
use Code_Snippets\REST_API\REST_Collection_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;

/**
 * Allows fetching cloud snippets through the WordPress REST API.
 *
 * @package Code_Snippets
 */
final class Cloud_Snippets_REST_Controller extends REST_Collection_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base of this controller's route.
	 */
	public const BASE_ROUTE = 'cloud/snippets';

	/**
	 * Search controller instance.
	 *
	 * @var Cloud_Search_Controller
	 */
	private Cloud_Search_Controller $search_controller;

	/**
	 * Class constructor.
	 *
	 * @param Cloud_Search_Controller $search_controller Cloud search controller.
	 */
	public function __construct( Cloud_Search_Controller $search_controller ) {
		parent::__construct();
		$this->search_controller = $search_controller;
	}

	/**
	 * Check the request from Cloud API is valid
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function permission_callback( WP_REST_Request $request ): bool {
		return code_snippets()->current_user_can() && $this->search_controller->verify_rest_request( $request );
	}

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
		$filters = [];

		foreach ( [ 'category', 'type', 'status' ] as $filter ) {
			if ( $request->has_param( $filter ) ) {
				$filters[ $filter ] = $request->get_param( $filter ) ?? '';
			}
		}

		return $filters;
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		$collection_args = $this->get_collection_params();
		$collection_args['per_page']['default'] = Manage_Menu::get_default_snippets_per_page();
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
			$this->rest_base . '/codevault',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_codevault_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $collection_args['page'],
					'schema'              => [ $this, 'get_item_schema' ],
				],
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
								'default'     => Manage_Menu::get_default_snippets_per_page(),
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
		$query = $request->get_param( 'query' ) ?? '';

		$page = max( 1, intval( $request->get_param( 'page' ) ) );
		$per_page = intval( $request->get_param( 'per_page' ) ?? Manage_Menu::get_cloud_search_per_page() );
		$filters = $this->extract_filters( $request );

		return $this->search_controller
			->fetch_search_results( $method, $query, $page, $per_page, $filters )
			->to_rest_response();
	}

	/**
	 * Retrieve featured snippets from the cloud API.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_featured_items( WP_REST_Request $request ): WP_REST_Response {
		$page = max( 1, intval( $request->get_param( 'page' ) ) );
		$per_page = intval( $request->get_param( 'per_page' ) ?? Manage_Menu::get_snippets_per_page() );
		$filters = $this->extract_filters( $request );

		return $this->search_controller
			->get_featured_snippets( $page, $per_page, $filters )
			->to_rest_response();
	}

	/**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = [
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

		return $this->schema;
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

		$cloud_snippet = $this->search_controller->get_cloud_snippet( $id );
		return rest_ensure_response( $this->search_controller->download_snippet_from_cloud( $cloud_snippet ) );
	}
}
