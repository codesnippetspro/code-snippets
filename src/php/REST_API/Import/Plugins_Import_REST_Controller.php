<?php

namespace Code_Snippets\REST_API\Import;

use Code_Snippets\REST_API\Import\Plugins\Elementor_Custom_Code_Plugin_Importer;
use Code_Snippets\REST_API\Import\Plugins\Header_Footer_Code_Manager_Plugin_Importer;
use Code_Snippets\REST_API\Import\Plugins\Insert_Headers_And_Footers_Plugin_Importer;
use Code_Snippets\REST_API\Import\Plugins\Insert_PHP_Code_Snippet_Plugin_Importer;
use Code_Snippets\REST_API\Import\Plugins\Plugin_Importer;
use Code_Snippets\REST_API\REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;

/**
 * REST API controller for plugin importers.
 *
 * Handles registering REST API routes and providing information about available plugin importers.
 */
class Plugins_Import_REST_Controller extends REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * Base route for this controller.
	 */
	public const BASE_ROUTE = 'import/plugins';

	/**
	 * List of plugin importer instances.
	 *
	 * @var array Plugin_Importer[]
	 */
	private array $plugin_importers;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->plugin_importers = [
			'elementor'                  => new Elementor_Custom_Code_Plugin_Importer(),
			'insert-headers-and-footers' => new Insert_Headers_And_Footers_Plugin_Importer(),
			'header-footer-code-manager' => new Header_Footer_Code_Manager_Plugin_Importer(),
			'insert-php-code-snippet'    => new Insert_PHP_Code_Snippet_Plugin_Importer(),
		];
	}

	/**
	 * Get the list of available plugin importers.
	 *
	 * @return WP_REST_Response List of importers with their name, title, and active status.
	 */
	public function get_importer_details(): WP_REST_Response {
		return rest_ensure_response(
			array_map(
				function ( $importer ) {
					return [
						'name'      => $importer->get_name(),
						'title'     => $importer->get_title(),
						'is_active' => $importer::is_active(),
					];
				},
				$this->plugin_importers
			)
		);
	}

	/**
	 * Register REST API route for fetching available importers.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			self::BASE_ROUTE,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_importer_details' ],
				'permission_callback' => [ $this, 'permission_callback' ],
			]
		);

		foreach ( $this->plugin_importers as $importer ) {
			$route = self::BASE_ROUTE . '/' . $importer->get_name();

			register_rest_route(
				$this->namespace,
				$route,
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $importer, 'get_items' ],
					'permission_callback' => [ $this, 'permission_callback' ],
				]
			);

			register_rest_route(
				$this->namespace,
				"$route/import",
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $importer, 'import' ],
					'permission_callback' => [ $this, 'permission_callback' ],
					'args'                => Plugin_Importer::REST_ARGS,
				]
			);
		}
	}

	/**
	 * Determine whether the request has permission to import snippets.
	 *
	 * @param WP_REST_Request $request Incoming HTTP request.
	 *
	 * @return bool
	 */
	public function permission_callback( WP_REST_Request $request ): bool {
		return code_snippets()->current_user_can();
	}
}
