<?php

namespace Code_Snippets\REST_API\Snippets;

use Code_Snippets\REST_API\REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;
use function Code_Snippets\Utils\delete_self_option;
use function Code_Snippets\Utils\get_self_option;
use const Code_Snippets\REST_API_NAMESPACE;

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
	 * Register REST routes.
	 */
	public function register_routes() {
		$permission_callback = [ code_snippets(), 'current_user_can' ];

		register_rest_route(
			$this->namespace,
			self::BASE_ROUTE,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_recent_list_callback' ],
					'permission_callback' => $permission_callback,
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
					'permission_callback' => $permission_callback,
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
		return rest_ensure_response(
			$request->get_param( 'network' )
				? get_site_option( 'recently_active_snippets', [] )
				: get_option( 'recently_active_snippets', [] )
		);
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
		$network = $request->get_param( 'network' );

		$current = get_self_option( $network, 'recently_active_snippets', [] );
		delete_self_option( $network, 'recently_active_snippets' );

		return rest_ensure_response( $current );
	}
}
