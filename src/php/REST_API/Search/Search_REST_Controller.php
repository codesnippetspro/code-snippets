<?php

namespace Code_Snippets\REST_API\Search;

use Code_Snippets\REST_API\REST_Controller;
use Code_Snippets\Search\Search_Query;
use Code_Snippets\Search\Universal_Search;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;

/**
 * Serves universal search results, grouped by the source they came from.
 *
 * The browser asks for the local sources and each remote source in separate
 * requests, so a slow remote service delays only its own group.
 *
 * @package Code_Snippets
 */
final class Search_REST_Controller extends REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base of this controller's route.
	 */
	public const BASE_ROUTE = 'search';

	/**
	 * Largest number of items returned for a single source.
	 */
	private const MAX_PER_SOURCE = 100;

	/**
	 * Search service.
	 *
	 * @var Universal_Search
	 */
	private Universal_Search $search;

	/**
	 * Class constructor.
	 *
	 * @param Universal_Search $search Search service.
	 */
	public function __construct( Universal_Search $search ) {
		parent::__construct();
		$this->search = $search;
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			self::BASE_ROUTE,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_results_callback' ],
					'permission_callback' => [ $this, 'permission_callback' ],
					'args'                => [
						'q'          => [
							'description'       => esc_html__( 'Text to search for.', 'code-snippets' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => [ Search_Query::class, 'sanitize_term' ],
						],
						'sources'    => [
							'description' => esc_html__( 'Sources to search. Defaults to every source.', 'code-snippets' ),
							'type'        => 'array',
							'items'       => [
								'type' => 'string',
								'enum' => Universal_Search::get_sources(),
							],
							'default'     => Universal_Search::get_sources(),
						],
						'fields'     => [
							'description' => esc_html__( 'Fields to match the search text against. Defaults to every field.', 'code-snippets' ),
							'type'        => 'array',
							'items'       => [
								'type' => 'string',
								'enum' => Search_Query::FIELDS,
							],
							'default'     => Search_Query::FIELDS,
						],
						'method'     => [
							'description' => esc_html__( 'How to search the cloud: by keyword, or with AI.', 'code-snippets' ),
							'type'        => 'string',
							'enum'        => Search_Query::METHODS,
							'default'     => 'term',
						],
						'per_source' => [
							'description' => esc_html__( 'Largest number of results to return for each source.', 'code-snippets' ),
							'type'        => 'integer',
							'default'     => 50,
							'minimum'     => 1,
							'maximum'     => self::MAX_PER_SOURCE,
						],
						'excerpts'   => [
							'description' => esc_html__( 'Whether to shorten long descriptions to an excerpt around the match.', 'code-snippets' ),
							'type'        => 'boolean',
							'default'     => true,
						],
						'network'    => [
							'description' => esc_html__( 'Search network-wide snippets instead of site-wide.', 'code-snippets' ),
							'type'        => 'boolean',
							'default'     => false,
						],
					],
				],
			]
		);
	}

	/**
	 * Determine whether the request has permission to search.
	 *
	 * @param WP_REST_Request $request Incoming HTTP request.
	 *
	 * @return bool
	 */
	public function permission_callback( WP_REST_Request $request ): bool {
		return is_multisite() && $request->get_param( 'network' )
			? code_snippets()->user_can_manage_network_snippets()
			: code_snippets()->current_user_can();
	}

	/**
	 * Callback for retrieving search results.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response Search results, grouped by source.
	 */
	public function get_results_callback( WP_REST_Request $request ): WP_REST_Response {
		$query = new Search_Query(
			(string) $request->get_param( 'q' ),
			(array) $request->get_param( 'fields' ),
			(bool) $request->get_param( 'excerpts' ),
			(string) $request->get_param( 'method' )
		);

		return rest_ensure_response(
			[
				'query'  => $query->get_term(),
				'groups' => $this->search->search(
					$query,
					(array) $request->get_param( 'sources' ),
					is_multisite() && $request->get_param( 'network' ),
					(int) $request->get_param( 'per_source' )
				),
			]
		);
	}
}
