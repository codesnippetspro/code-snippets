<?php

namespace Code_Snippets;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Allows fetching snippet data through the WordPress REST API.
 *
 * @since   3.0.0
 * @package Code_Snippets
 */
class REST_API {

	/**
	 * Current API version.
	 */
	const VERSION = 1;

	/**
	 * Namespace.
	 */
	const BASE = 'code-snippets/v' . self::VERSION;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::BASE,
			'/snippets-info',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_snippets_info' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Fetch snippet data in response to a request.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_snippets_info( WP_REST_Request $request ) {
		$snippets = get_snippets();
		$data = [];

		/** Snippet @var Snippet $snippet */
		foreach ( $snippets as $snippet ) {
			$data[] = [
				'id'     => $snippet->id,
				'name'   => $snippet->name,
				'type'   => $snippet->type,
				'active' => $snippet->active,
			];
		}

		return new WP_REST_Response( $data, 200 );
	}
}
