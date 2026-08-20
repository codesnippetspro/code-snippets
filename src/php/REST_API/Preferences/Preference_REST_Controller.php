<?php

namespace Code_Snippets\REST_API\Preferences;

use Code_Snippets\REST_API\REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;
use const Code_Snippets\REST_API_NAMESPACE;

/**
 * Controller for reading and updating a plugin-wide interface preference.
 *
 * @package Code_Snippets
 */
abstract class Preference_REST_Controller extends REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 0;

	public const BASE_ROUTE_PREFIX = 'preferences/';

	/**
	 * The key used to identify this preference in the REST API.
	 */
	protected const PREFERENCE_KEY = '';

	/**
	 * The name of the option used to store the preference.
	 */
	protected const OPTION_NAME = '';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		parent::__construct();
		assert( ! empty( static::PREFERENCE_KEY ), get_class( $this ) . '::PREFERENCE_KEY must be set' );
		assert( ! empty( static::OPTION_NAME ), get_class( $this ) . '::OPTION_NAME must be set' );
	}


	/**
	 * Retrieve this controller's REST API base path, including namespace.
	 *
	 * @return string
	 */
	public static function get_base_route(): string {
		return REST_API_NAMESPACE . static::VERSION . '/' . self::BASE_ROUTE_PREFIX . static::BASE_ROUTE;
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			self::BASE_ROUTE_PREFIX . static::BASE_ROUTE,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_option_value_callback' ],
					'permission_callback' => [ $this, 'permission_callback' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_option_value_callback' ],
					'permission_callback' => [ $this, 'permission_callback' ],
					'args'                => [
						static::PREFERENCE_KEY => $this->get_update_request_schema(),
					],
				],
			]
		);
	}

	/**
	 * Retrieve the schema for the preference argument, to be used in the REST API documentation.
	 *
	 * @return array
	 */
	abstract protected function get_update_request_schema(): array;

	/**
	 * Determine whether the request has permission to manage preferences.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function permission_callback( WP_REST_Request $request ): bool {
		return code_snippets()->current_user_can();
	}

	/**
	 * Retrieve the stored preference value, falling back to the default when the
	 * stored value is missing or invalid.
	 *
	 * @return mixed.
	 */
	abstract protected function get_option_value();

	/**
	 * Retrieve the stored preference value as a REST response.
	 *
	 * @return WP_REST_Response
	 */
	public function get_option_value_callback(): WP_REST_Response {
		return new WP_REST_Response( [ static::PREFERENCE_KEY => static::get_option_value() ] );
	}

	/**
	 * Update the preference value.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_option_value_callback( WP_REST_Request $request ): WP_REST_Response {
		$value = $request->get_param( static::PREFERENCE_KEY );

		update_option( static::OPTION_NAME, $value );

		return new WP_REST_Response( [ static::PREFERENCE_KEY => $value ] );
	}
}
