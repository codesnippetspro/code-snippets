<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\Client\Cloud_API;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;
use const Code_Snippets\REST_API_NAMESPACE;

/**
 * Allows fetching cloud snippets through the WordPress REST API.
 *
 * @package Code_Snippets
 */
final class Cloud_Snippets_REST_Controller extends WP_REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base of this controller's route.
	 */
	public const BASE_ROUTE = 'cloud/search';

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
					'args'                => [
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
					],
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);
	}

	/**
	 * Check if a given request has permission to view cloud snippets.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return bool Whether the request has permission to view cloud snippets.
	 */
	public function get_items_permissions_check( $request ): bool {
		return code_snippets()->current_user_can();
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
		$page = $request->get_param( 'page' );

		$cloud_snippets = Cloud_API::fetch_search_results( $method, $query, $page );

		$results = [];

		foreach ( $cloud_snippets->snippets as $snippet ) {
			$results[] = $snippet->get_fields();
		}

		$response = rest_ensure_response( $results );

		$response->header( 'X-WP-Total', $cloud_snippets->total_snippets );
		$response->header( 'X-WP-TotalPages', $cloud_snippets->total_pages );

		return $response;
	}
}
