<?php

namespace Code_Snippets\REST_API\Import;

use Code_Snippets\REST_API\Import\Plugins\Header_Footer_Code_Manager_Importer;
use Code_Snippets\REST_API\Import\Plugins\Insert_Headers_And_Footers_Importer;
use Code_Snippets\REST_API\Import\Plugins\Insert_PHP_Code_Snippet_Importer;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;
use const Code_Snippets\REST_API_NAMESPACE;

/**
 * REST API controller for plugin importers.
 *
 * Handles registering REST API routes and providing information about available plugin importers.
 */
class Plugins_Import_REST_Controller {

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
	 * List of plugin importer instances.
	 *
	 * @var array Importer_REST_Controller[]
	 */
	private array $plugin_importers;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->plugin_importers = [
			'insert-headers-and-footers' => new Insert_Headers_And_Footers_Importer(),
			'header-footer-code-manager' => new Header_Footer_Code_Manager_Importer(),
			'insert-php-code-snippet'    => new Insert_PHP_Code_Snippet_Importer(),
		];

		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
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
	public function register_rest_routes() {
		register_rest_route(
			$this->namespace,
			'importers',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_importer_details' ],
				'permission_callback' => [ code_snippets(), 'current_user_can' ],
			]
		);
	}
}
