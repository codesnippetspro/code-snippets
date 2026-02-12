<?php

namespace Code_Snippets\REST_API\Import\Plugins;

use Code_Snippets\Model\Snippet;
use WPCode_Snippet;

/**
 * Importer for the 'Insert Headers and Footers' plugin.
 */
class Insert_Headers_And_Footers_Plugin_Importer extends Plugin_Importer {

	protected const FIELD_MAPPINGS = [
		'title'    => 'name',
		'note'     => 'desc',
		'code'     => 'code',
		'tags'     => 'tags',
		'location' => 'scope',
		'priority' => 'priority',
		'modified' => 'modified',
	];

	private const PHP_SCOPE_TRANSFORMATIONS = [
		'everywhere'    => 'global',
		'admin_only'    => 'admin',
		'frontend_only' => 'front-end',
	];

	private const HTML_SCOPE_TRANSFORMATIONS = [
		''                 => 'content',
		'site_wide_header' => 'head-content',
		'site_wide_footer' => 'footer-content',
	];

	/**
	 * Get the unique name of the importer.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'insert-headers-and-footers';
	}

	/**
	 * Get the human-readable title of the importer.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'WPCode (Insert Headers and Footers)', 'code-snippets' );
	}

	/**
	 * Check if the 'Insert Headers and Footers' plugin is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return is_plugin_active( 'insert-headers-and-footers/ihaf.php' );
	}

	/**
	 * Retrieve snippet data from the 'Insert Headers and Footers' plugin's database table.
	 *
	 * @param array $ids_to_import Optional array of snippet IDs to import.
	 *
	 * @return array
	 */
	public function get_data( array $ids_to_import = [] ): array {
		$query_args = [
			'post_type'   => 'wpcode',
			'post_status' => [
				'publish',
				'draft',
			],
			'nopaging'    => true,
		];

		if ( ! empty( $ids_to_import ) ) {
			$query_args['include'] = $ids_to_import;
		}

		$data = [];
		$snippets = get_posts( $query_args );

		foreach ( $snippets as $snippet_item ) {
			$snippet = new WPCode_Snippet( $snippet_item );
			$snippet_data = $snippet->get_data_for_caching();
			$snippet_data['tags'] = $snippet->get_tags();
			$snippet_data['note'] = $snippet->get_note();
			$snippet_data['cloud_id'] = null;
			$snippet_data['custom_shortcode'] = $snippet->get_custom_shortcode();
			$snippet_data['table_data'] = [
				'id'    => $snippet_item->ID,
				'title' => $snippet_item->post_title,
			];

			$data[] = apply_filters( 'wpcode_export_snippet_data', $snippet_data, $snippet );
		}

		return array_reverse( $data );
	}

	/**
	 * Create a Snippet object from the provided snippet data.
	 *
	 * @param array $snippet_data Snippet data object.
	 * @param bool  $multisite    Whether the snippet is for a multisite network.
	 *
	 * @return Snippet|null The created Snippet object or null if unsupported.
	 */
	public function create_snippet( array $snippet_data, bool $multisite ): ?Snippet {
		$code_type = $snippet_data['code_type'] ?? '';
		$is_supported_code_type = in_array( $code_type, [ 'php', 'css', 'html', 'js' ], true );

		return $is_supported_code_type
			? parent::create_snippet( $snippet_data, $multisite )
			: null;
	}

	/**
	 * Transform field value to match Code Snippets format.
	 *
	 * @param string $target_field Name of field.
	 * @param mixed  $value        Field value.
	 * @param array  $snippet_data Snippet data.
	 *
	 * @return mixed
	 */
	protected function transform_field_value( string $target_field, $value, array $snippet_data ) {
		if ( 'scope' === $target_field ) {
			return $this->transform_scope_value( $value, $snippet_data );
		}

		return $value;
	}

	/**
	 * Transform location value to Code Snippets scope.
	 *
	 * @param mixed $location_value Location value from the source snippet.
	 * @param array $snippet_data   Snippet data.
	 *
	 * @return string|null
	 */
	private function transform_scope_value( $location_value, array $snippet_data ): ?string {
		if ( ! is_scalar( $location_value ) ) {
			return null;
		}

		$code_type = $snippet_data['code_type'];

		switch ( $code_type ) {
			case 'html':
				$transformations = self::HTML_SCOPE_TRANSFORMATIONS;
				break;
			case 'php':
				$transformations = self::PHP_SCOPE_TRANSFORMATIONS;
				break;
			default:
				return null;
		}

		return $transformations[ $location_value ] ?? null;
	}
}
