<?php

namespace Code_Snippets\REST_API\Import\Plugins;

use Code_Snippets\Model\Snippet;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;
use function Code_Snippets\save_snippet;
use const Code_Snippets\REST_API_NAMESPACE;

/**
 * Base class for registering plugin importers.
 */
abstract class Importer_REST_Controller {

	/**
	 * REST API version.
	 */
	public const VERSION = 1;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Get the unique name of the importer.
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * Get the human-readable title of the importer.
	 *
	 * @return string
	 */
	abstract public function get_title(): string;

	/**
	 * Retrieve snippet data from the source plugin's database table.
	 *
	 * @param array $ids_to_import Optional array of snippet IDs to import.
	 *
	 * @return array
	 */
	abstract public function get_data( array $ids_to_import = [] ): array;

	/**
	 * Create a Snippet object from the source plugin's snippet data.
	 *
	 * @param array $snippet_data Source plugin's snippet data.
	 * @param bool  $multisite    Whether to create a multisite snippet.
	 *
	 * @return Snippet|null
	 */
	abstract public function create_snippet( array $snippet_data, bool $multisite ): ?Snippet;

	/**
	 * Check if the source plugin is active.
	 *
	 * @return bool
	 */
	abstract public static function is_active(): bool;

	/**
	 * Transform raw snippet data into Snippet objects.
	 *
	 * @param array  $data          Raw snippet data.
	 * @param bool   $multisite     Whether to create multisite snippets.
	 * @param bool   $auto_add_tags Whether to automatically add tags.
	 * @param string $tag_value     The tag value to add if auto_add_tags is true.
	 *
	 * @return array
	 */
	public function transform( array $data, bool $multisite, bool $auto_add_tags = false, string $tag_value = '' ): array {
		$snippets = [];

		foreach ( $data as $snippet_item ) {
			if ( ! is_array( $snippet_item ) ) {
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

	/**
	 * Import snippets via REST API.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response
	 */
	public function import( WP_REST_Request $request ): array {
		$ids_to_import = $request->get_param( 'ids' ) ?? [];
		$multisite = $request->get_param( 'network' ) ?? false;
		$auto_add_tags = $request->get_param( 'auto_add_tags' ) ?? false;
		$tag_value = $request->get_param( 'tag_value' ) ?? '';

		$data = $this->get_data( $ids_to_import );

		$snippets = $this->transform( $data, $multisite, $auto_add_tags, $tag_value );

		$imported = $this->save_snippets( $snippets );

		return rest_ensure_response( [ 'imported' => $imported ] );
	}

	/**
	 * Retrieve all snippet data via REST API.
	 *
	 * @return array
	 */
	public function get_items(): array {
		return $this->get_data();
	}

	/**
	 * Save snippets to the database.
	 *
	 * @param array $snippets Array of Snippet objects.
	 *
	 * @return array
	 */
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

	/**
	 * Register REST API routes for the importer.
	 */
	public function register_rest_routes() {
		$namespace = REST_API_NAMESPACE . self::VERSION;

		register_rest_route(
			$namespace,
			$this->get_name(),
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ code_snippets(), 'current_user_can' ],
			]
		);

		register_rest_route(
			$namespace,
			"{$this->get_name()}/import",
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'import' ],
				'permission_callback' => [ code_snippets(), 'current_user_can' ],
				'args'                => [
					'ids'           => [
						'type'     => 'array',
						'required' => false,
					],
					'network'       => [
						'type'     => 'boolean',
						'required' => false,
					],
					'auto_add_tags' => [
						'type'     => 'boolean',
						'required' => false,
					],
					'tag_value'     => [
						'type'     => 'string',
						'required' => false,
					],
				],
			]
		);
	}
}
