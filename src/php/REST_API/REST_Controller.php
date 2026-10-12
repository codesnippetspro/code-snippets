<?php

namespace Code_Snippets\REST_API;

use WP_REST_Request;
use const Code_Snippets\REST_API_NAMESPACE;

/**
 * Base class for REST API controllers.
 */
abstract class REST_Controller {

	/**
	 * The version of the REST API this controller belongs to.
	 *
	 * @var string
	 */
	public const VERSION = 0;

	/**
	 * The base route for this controller, relative to the REST API namespace and version.
	 *
	 * @var string
	 */
	public const BASE_ROUTE = '';

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected string $namespace;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		assert( ! empty( static::VERSION ), get_class( $this ) . '::VERSION must be set' );
		assert( ! empty( static::BASE_ROUTE ), get_class( $this ) . '::BASE_ROUTE must be set' );

		$this->namespace = REST_API_NAMESPACE . static::VERSION;
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Retrieve this controller's REST API base path, including namespace.
	 *
	 * @return string
	 */
	public static function get_base_route(): string {
		return REST_API_NAMESPACE . static::VERSION . '/' . static::BASE_ROUTE;
	}

	/**
	 * Register REST routes.
	 */
	abstract public function register_routes();

	/**
	 * Default permission callback for this controller's routes.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	abstract public function permission_callback( WP_REST_Request $request ): bool;
}
