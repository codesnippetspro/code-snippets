<?php

namespace Code_Snippets\REST_API;

use WP_REST_Controller;
use WP_REST_Request;
use function Code_Snippets\code_snippets;
use const Code_Snippets\REST_API_NAMESPACE;

/**
 * Base class for REST API controllers that manage collections of items.
 */
abstract class REST_Collection_Controller extends WP_REST_Controller {

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
	 * Retrieve this controller's REST API base path, including namespace.
	 *
	 * @return string
	 */
	public static function get_base_route(): string {
		return REST_API_NAMESPACE . static::VERSION . '/' . static::BASE_ROUTE;
	}

	/**
	 * Retrieve the full base route including the REST API prefix.
	 *
	 * @return string
	 */
	public static function get_prefixed_base_route(): string {
		return '/' . rtrim( rest_get_url_prefix(), '/\\' ) . '/' . self::get_base_route();
	}

	/**
	 * Class constrictor.
	 */
	public function __construct() {
		$this->namespace = REST_API_NAMESPACE . static::VERSION;
		$this->rest_base = static::BASE_ROUTE;

		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Default permission callback for this controller's routes.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	abstract public function permission_callback( WP_REST_Request $request ): bool;

	/**
	 * Check if a given request has access to get items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function get_items_permissions_check( $request ): bool {
		return $this->permission_callback( $request );
	}

	/**
	 * Check if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function get_item_permissions_check( $request ): bool {
		return $this->permission_callback( $request );
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function create_item_permissions_check( $request ): bool {
		return $this->permission_callback( $request );
	}

	/**
	 * Check if a given request has access to update a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function update_item_permissions_check( $request ): bool {
		return $this->permission_callback( $request );
	}

	/**
	 * Check if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function delete_item_permissions_check( $request ): bool {
		return $this->permission_callback( $request );
	}
}
