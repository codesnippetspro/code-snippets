<?php

namespace Code_Snippets;

use WP_REST_Server;
use const Code_Snippets\REST_API_NAMESPACE;

abstract class Importer_Base {

	const VERSION = 1;

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	abstract public function get_name();

	abstract public function get_title();

	abstract public function get_data( array $ids_to_import = [] );

	abstract public function create_snippet( $snippet_data, bool $multisite ): ?Snippet;

	abstract public static function is_active(): bool;

	public function transform( array $data, bool $multisite, bool $auto_add_tags = false, string $tag_value = '' ): array {
		$snippets = [];

		foreach ( $data as $snippet_item ) {
			if ( ! is_array( $snippet_item ) && ! is_object( $snippet_item ) ) {
				continue;
			}

			$snippet = $this->create_snippet( $snippet_item, $multisite );

			if ( $snippet ) {
				if ( $auto_add_tags && ! empty( $tag_value ) ) {
					if ( ! empty( $snippet->tags ) ) {
						$snippet->add_tag( $tag_value );
					} else {
						$snippet->tags = [ $tag_value ];
					}
				}

				$snippets[] = $snippet;
			}
		}

		return $snippets;
	}

	public function import( $request ) {
		$ids_to_import = $request->get_param( 'ids' ) ?? [];
		$multisite = $request->get_param( 'network' ) ?? false;
		$auto_add_tags = $request->get_param( 'auto_add_tags' ) ?? false;
		$tag_value = $request->get_param( 'tag_value' ) ?? '';

		$data = $this->get_data( $ids_to_import );

		$snippets = $this->transform( $data, $multisite, $auto_add_tags, $tag_value );

		$imported = $this->save_snippets( $snippets );

		return [
			'imported' => $imported,
		];
	}

	public function get_items( $request ) {
		return $this->get_data();
	}

	protected function save_snippets( array $snippets ): array {
		$imported = [];

		foreach ( $snippets as $snippet ) {
			$saved_snippet = save_snippet( $snippet );

			$snippet_id = $saved_snippet->id;

			if ( $snippet_id ) {
				$imported[] = $snippet_id;
			}
		}

		return $imported;
	}

	public function register_rest_routes() {
		$namespace = REST_API_NAMESPACE . self::VERSION;

		register_rest_route( $namespace, $this->get_name(), [
			'methods' => WP_REST_Server::READABLE,
			'callback' => [ $this, 'get_items' ],
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
		] );

		register_rest_route( $namespace, $this->get_name() . '/import', [
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => [ $this, 'import' ],
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
			'args' => [
				'ids' => [
					'type' => 'array',
					'required' => false,
				],
				'network' => [
					'type' => 'boolean',
					'required' => false,
				],
				'auto_add_tags' => [
					'type' => 'boolean',
					'required' => false,
				],
				'tag_value' => [
					'type' => 'string',
					'required' => false,
				],
			],
		] );
	}
}
