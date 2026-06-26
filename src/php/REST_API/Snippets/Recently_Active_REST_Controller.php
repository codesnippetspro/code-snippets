<?php

namespace Code_Snippets\REST_API\Snippets;

use Code_Snippets\REST_API\REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;
use function Code_Snippets\Utils\delete_self_option;
use function Code_Snippets\Utils\get_self_option;

/**
 * Controller for fetching and clearing list of recently active snippets.
 *
 * @package Code_Snippets
 */
final class Recently_Active_REST_Controller extends REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base of this controller's route.
	 */
	public const BASE_ROUTE = 'recently-active';

	/**
	 * The name of the option used to store the recently active snippets list.
	 */
	private const OPTION_NAME = 'recently_active_snippets';

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
					'callback'            => [ $this, 'get_recent_list_callback' ],
					'permission_callback' => [ $this, 'permission_callback' ],
					'args'                => [
						'network' => [
							'description' => esc_html__( 'Fetch the recent list for network-wide snippets instead of site-wide.', 'code-snippets' ),
							'type'        => 'boolean',
							'default'     => false,
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			self::BASE_ROUTE,
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'clear_recent_list_callback' ],
					'permission_callback' => [ $this, 'permission_callback' ],
					'args'                => [
						'network' => [
							'description' => esc_html__( 'Clear the recent list for network-wide snippets instead of site-wide.', 'code-snippets' ),
							'type'        => 'boolean',
							'default'     => false,
						],
					],
				],
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
	 * Callback for retrieving the recently active snippets list.
	 *
	 * This will return the list of recently active snippets, either site-wide or network-wide,
	 * depending on the 'network' parameter.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response The recently active snippets list.
	 */
	public function get_recent_list_callback( WP_REST_Request $request ): WP_REST_Response {
		$network = boolval( $request->get_param( 'network' ) );
		return rest_ensure_response( get_self_option( $network, self::OPTION_NAME, [] ) );
	}

	/**
	 * Callback for clearing the recently active snippets list.
	 *
	 * This will clear the list of recently active snippets, either site-wide or network-wide,
	 * depending on the 'network' parameter.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response The recently active snippets list prior to clearing it.
	 */
	public function clear_recent_list_callback( WP_REST_Request $request ): WP_REST_Response {
		$network = boolval( $request->get_param( 'network' ) );

		$current = get_self_option( $network, self::OPTION_NAME, [] );
		delete_self_option( $network, self::OPTION_NAME );

		return rest_ensure_response( $current );
	}
}
