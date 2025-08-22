<?php

namespace Code_Snippets\REST_API;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;
use function Code_Snippets\Utils\delete_self_option;
use function Code_Snippets\Utils\get_self_option;
use const Code_Snippets\REST_API_NAMESPACE;

/**
 * Class for managing the REST API functionality of the plugin.
 *
 * @package Code_Snippets
 */
class REST_Endpoints {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected string $namespace = REST_API_NAMESPACE . self::VERSION;

	/**
	 * Class constrictor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'recently-active',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_recent_list_callback' ],
					'permission_callback' => [ code_snippets(), 'current_user_can' ],
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
			'recently-active',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'clear_recent_list_callback' ],
					'permission_callback' => [ code_snippets(), 'current_user_can' ],
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
	 * Callback for retrieving the recently activated snippets list.
	 *
	 * This will return the list of recently activated snippets, either site-wide or network-wide,
	 * depending on the 'network' parameter.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response The recently activated snippets list.
	 */
	public function get_recent_list_callback( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response(
			$request->get_param( 'network' )
				? get_site_option( 'recently_activated_snippets', [] )
				: get_option( 'recently_activated_snippets', [] )
		);
	}

	/**
	 * Callback for clearing the recently activated snippets list.
	 *
	 * This will clear the list of recently activated snippets, either site-wide or network-wide,
	 * depending on the 'network' parameter.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response The recently activated snippets list prior to clearing it.
	 */
	public function clear_recent_list_callback( WP_REST_Request $request ): WP_REST_Response {
		$network = $request->get_param( 'network' );

		$current = get_self_option( $network, 'recently_activated_snippets', [] );
		delete_self_option( $network, 'recently_activated_snippets' );

		return rest_ensure_response( $current );
	}
}
