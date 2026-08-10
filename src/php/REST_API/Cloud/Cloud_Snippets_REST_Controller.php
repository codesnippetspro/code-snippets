<?php

namespace Code_Snippets\REST_API\Cloud;

use Code_Snippets\Admin\Menus\Manage\Manage_Menu;
use Code_Snippets\Controller\Cloud_Search_Controller;
use Code_Snippets\Model\Cloud_Snippets;
use Code_Snippets\REST_API\REST_Collection_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippets;

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
							'page'              => $collection_args['page'],
							'per_page'          => $collection_args['per_page'],
						],
						$filter_args
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
					'args'                => [ 'page' => $collection_args['page'] ],
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
							'page'     => $collection_args['page'],
							'per_page' => $collection_args['per_page'],
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
					'callback'            => [ $this, 'create_item' ],
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
	 * Augment the standard controller collection query params for cloud searches.
	 *
	 * The per_page default is intentionally removed rather than set here: routes are
	 * registered once, so a schema default would capture a single user's Screen
	 * Options value. Callbacks resolve the per-user default at request time instead.
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params(): array {
		$params = parent::get_collection_params();
		unset( $params['per_page']['default'] );
		return $params;
	}

	/**
	 * Record which of the given cloud snippets have already been downloaded to this site.
	 *
	 * Downloading stores the remote identifier on the local snippet, so the local
	 * snippets are the only record of the link once the browser has been reloaded.
	 *
	 * @param Cloud_Snippets $snippets Cloud snippets as retrieved from the cloud API.
	 *
	 * @return Cloud_Snippets The same collection, with local identifiers attached.
	 */
	private function attach_local_ids( Cloud_Snippets $snippets ): Cloud_Snippets {
		$local_ids = [];

		foreach ( get_snippets() as $local_snippet ) {
			if ( $local_snippet->cloud_id && ! $local_snippet->trashed ) {
				$local_ids[ $local_snippet->cloud_id ] = $local_snippet->id;
			}
		}

		foreach ( $snippets->snippets as $cloud_snippet ) {
			$cloud_snippet->local_id = $local_ids[ $cloud_snippet->id ] ?? null;
		}

		return $snippets;
	}

	/**
	 * Retrieve cloud snippets using a search query.
	 *
	 * @param WP_REST_Request $request The request object containing the search parameters.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$method = $request->get_param( 'searchByCodevault' ) ? 'codevault' : 'term';
		$query = $request->get_param( 'query' ) ?? '';

		$page = max( 1, intval( $request->get_param( 'page' ) ) );
		$per_page = intval( $request->get_param( 'per_page' ) ?? Manage_Menu::get_cloud_search_per_page() );
		$filters = $this->extract_filters( $request );

		$snippets = $this->search_controller
			->fetch_search_results( $method, $query, $page, $per_page, $filters );

		return $snippets
			? rest_ensure_response( $this->attach_local_ids( $snippets )->to_rest_response() )
			: new WP_Error(
				'code_snippets_get_snippets_failure',
				esc_html__( 'Could not fetch snippets.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
	}

	/**
	 * Retrieve featured snippets from the cloud API.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_featured_items( WP_REST_Request $request ) {
		$page = max( 1, intval( $request->get_param( 'page' ) ) );
		$per_page = intval( $request->get_param( 'per_page' ) ?? Manage_Menu::get_cloud_search_per_page() );
		$filters = $this->extract_filters( $request );

		$snippets = $this->search_controller->get_featured_snippets( $page, $per_page, $filters );

		return $snippets
			? rest_ensure_response( $this->attach_local_ids( $snippets )->to_rest_response() )
			: new WP_Error(
				'code_snippets_featured_snippets_failure',
				esc_html__( 'Could not fetch featured snippets.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
	}

	/**
	 * Download a single cloud snippet.
	 *
	 * @param WP_REST_Request $request The request object containing the search parameters.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$id = intval( $request->get_param( 'id' ) );
		$cloud_snippet = $this->search_controller->get_cloud_snippet( $id );

		if ( ! $cloud_snippet ) {
			return new WP_Error(
				'code_snippets_cloud_snippet_not_found',
				esc_html__( 'Cloud snippet not found.', 'code-snippets' ),
				[ 'status' => 404 ]
			);
		}

		$local_snippet = $this->search_controller->download_snippet_from_cloud( $cloud_snippet );

		return $local_snippet
			? rest_ensure_response(
				[
					'success'    => true,
					'snippet_id' => $local_snippet->id,
				]
			)
			: new WP_Error(
				'code_snippets_cloud_snippet_download_failed',
				esc_html__( 'Failed to create new snippet.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
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
}
