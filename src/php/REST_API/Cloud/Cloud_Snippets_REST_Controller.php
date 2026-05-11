<?php

namespace Code_Snippets\REST_API\Cloud;

use Code_Snippets\Client\Cloud_API;
use Code_Snippets\REST_API\REST_Collection_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

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
	public const BASE_ROUTE = 'cloud';

	/**
	 * Cloud API instance.
	 *
	 * @var Cloud_API
	 */
	private Cloud_API $api;

	/**
	 * Class constructor.
	 *
	 * @param Cloud_API $api Cloud API instance.
	 */
	public function __construct( Cloud_API $api ) {
		$this->api = $api;
		parent::__construct();
	}

	/**
	 * Common filter args shared across search and featured endpoints.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_filter_args(): array {
		return [
			'category' => [
				'description' => esc_html__( 'Filter by category name (comma-separated).', 'code-snippets' ),
				'type'        => 'string',
				'default'     => '',
			],
			'type'     => [
				'description' => esc_html__( 'Filter by language/type name (comma-separated).', 'code-snippets' ),
				'type'        => 'string',
				'default'     => '',
			],
			'status'   => [
				'description' => esc_html__( 'Filter by status ID (comma-separated).', 'code-snippets' ),
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
			$this->rest_base . '/types',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_types' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/categories',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_categories' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
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

		$results = [];

		foreach ( $cloud_snippets->snippets as $snippet ) {
			$results[] = $snippet->get_fields();
		}

		$response = rest_ensure_response( $results );

		$response->header( 'X-WP-Total', $cloud_snippets->total_snippets );
		$response->header( 'X-WP-TotalPages', $cloud_snippets->total_pages );

		if ( ! empty( $cloud_snippets->available_filters ) ) {
			$response->header( 'X-WP-Filters', wp_json_encode( $cloud_snippets->available_filters ) );
		}

		return $response;
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

		$results = [];

		foreach ( $cloud_snippets->snippets as $snippet ) {
			$results[] = $snippet->get_fields();
		}

		$response = rest_ensure_response( $results );

		$response->header( 'X-WP-Total', $cloud_snippets->total_snippets );
		$response->header( 'X-WP-TotalPages', $cloud_snippets->total_pages );

		if ( ! empty( $cloud_snippets->available_filters ) ) {
			$response->header( 'X-WP-Filters', wp_json_encode( $cloud_snippets->available_filters ) );
		}

		return $response;
	}

	/**
	 * Retrieve available snippet types (languages) from the cloud API.
	 *
	 * @return WP_REST_Response
	 */
	public function get_types(): WP_REST_Response {
		return rest_ensure_response( Cloud_API::get_cloud_types() );
	}

	/**
	 * Retrieve available snippet categories from the cloud API.
	 *
	 * @return WP_REST_Response
	 */
	public function get_categories(): WP_REST_Response {
		return rest_ensure_response( Cloud_API::get_cloud_categories() );
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
	 * Download a single cloud snippet.
	 *
	 * @param WP_REST_Request $request The request object containing the search parameters.
	 *
	 * @return WP_REST_Response
	 */
	public function download_item( WP_REST_Request $request ): WP_REST_Response {
		$id = $request->get_param( 'id' );

		$cloud_snippet = $this->api->get_single_snippet_from_cloud( $id );
		return rest_ensure_response( $this->api->download_snippet_from_cloud( $cloud_snippet ) );
	}
}
