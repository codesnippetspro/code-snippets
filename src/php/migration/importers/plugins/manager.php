<?php

namespace Code_Snippets;

use WP_REST_Server;
use const Code_Snippets\REST_API_NAMESPACE;

class Plugins_Import_Manager {

	const VERSION = 1;

	private $plugin_importers = [];

	public function __construct() {
		$this->init_plugin_importers();
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	private function init_plugin_importers() {
		$this->plugin_importers = [
			'insert-headers-and-footers' => new Insert_Headers_And_Footers_Importer(),
			'header-footer-code-manager' => new Header_Footer_Code_Manager_Importer(),
			'insert-php-code-snippet' => new Insert_PHP_Code_Snippet_Importer(),
		];
	}

	public function get_importer( string $source ) {
		return $this->plugin_importers[ $source ] ?? null;
	}

	public function get_importers() {
		if ( empty( $this->plugin_importers ) ) {
			$this->init_plugin_importers();
		}

		$plugins_list = [];

		foreach ( $this->plugin_importers as $importer ) {
			$plugins_list[] = [
				'name' => $importer->get_name(),
				'title' => $importer->get_title(),
				'is_active' => $importer::is_active(),
			];
		}

		return $plugins_list;
	}

	public function register_rest_routes() {
		$namespace = REST_API_NAMESPACE . self::VERSION;

		register_rest_route( $namespace, 'importers', [
			'methods' => WP_REST_Server::READABLE,
			'callback' => [ $this, 'get_importers' ],
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
		] );
	}
}
