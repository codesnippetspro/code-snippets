<?php

namespace Code_Snippets\REST_API\Import\Plugins;

use Code_Snippets\Model\Snippet;

/**
 * Importer for the 'Insert PHP Code Snippet' plugin.
 */
class Insert_PHP_Code_Snippet_Plugin_Importer extends Plugin_Importer {

	private const FIELD_MAPPINGS = [
		'title'                 => 'name',
		'content'               => 'code',
		'insertionLocationType' => 'scope',
	];

	private const SCOPE_TRANSFORMATIONS = [
		0 => 'single-use',
		2 => 'admin',
		3 => 'front-end',
	];

	private const SHORTCODE_SCOPE_TRANSFORMATIONS = [
		3 => 'content',
	];

	/**
	 * Get the unique name of the importer.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'insert-php-code-snippet';
	}

	/**
	 * Get the human-readable title of the importer.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Insert PHP Code Snippet', 'code-snippets' );
	}

	/**
	 * Check if the 'Insert PHP Code Snippet' plugin is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return is_plugin_active( 'insert-php-code-snippet/insert-php-code-snippet.php' );
	}

	/**
	 * Retrieve snippet data from the 'Insert PHP Code Snippet' plugin's database table.
	 *
	 * @param array $ids_to_import Optional array of snippet IDs to import.
	 *
	 * @return array
	 *
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
	 */
	public function get_data( array $ids_to_import = [] ): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'xyz_ips_short_code';

		if ( empty( $ids_to_import ) ) {
			$snippets = $wpdb->get_results( "SELECT * FROM `$table_name`" );
		} else {
			$ids_format = implode( ',', array_fill( 0, count( $ids_to_import ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$snippets = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$table_name` WHERE if IN ($ids_format)", $ids_to_import ) );
		}

		foreach ( $snippets as $snippet ) {
			$snippet->table_data = [
				'id'    => (int) $snippet->id,
				'title' => $snippet->title,
			];
		}

		return $snippets;
	}

	/**
	 * Create a new snippet from the provided snippet data.
	 *
	 * @param array $snippet_data Snippet data.
	 * @param bool  $multisite    Whether to create a network-wide snippet.
	 *
	 * @return Snippet|null
	 */
	public function create_snippet( array $snippet_data, bool $multisite ): ?Snippet {
		$snippet = new Snippet();
		$snippet->network = $multisite;

		foreach ( self::FIELD_MAPPINGS as $source_field => $target_field ) {
			if ( ! isset( $snippet_data[ $source_field ] ) ) {
				continue;
			}

			$value = $this->transform_field_value(
				$target_field,
				$snippet_data[ $source_field ],
				$snippet_data
			);

			$scope_not_supported = 'scope' === $target_field && null === $value;
			if ( $scope_not_supported ) {
				return null;
			}

			$snippet->set_field( $target_field, $value );
		}

		return $snippet;
	}

	/**
	 * Transform field value to match Code Snippets format.
	 *
	 * @param string $target_field Name of field.
	 * @param mixed  $value        Field value.
	 * @param array  $snippet_data Snippet data.
	 *
	 * @return string|null
	 */
	private function transform_field_value( string $target_field, $value, array $snippet_data ): ?string {
		if ( 'scope' === $target_field ) {
			return $this->transform_scope_value( $value, $snippet_data );
		}

		if ( 'code' === $target_field ) {
			return $this->transform_code_value( $value, $snippet_data );
		}

		return $value;
	}

	/**
	 * Transform location value to Code Snippets scope.
	 *
	 * @param mixed $location_value Location value from source plugin.
	 * @param array $snippet_data   Snippet data.
	 *
	 * @return string|null
	 */
	private function transform_scope_value( $location_value, array $snippet_data ): ?string {
		if ( ! is_scalar( $location_value ) ) {
			return null;
		}

		$transformations = self::SCOPE_TRANSFORMATIONS;

		if ( '2' === $snippet_data['insertionMethod'] ) {
			$transformations = self::SHORTCODE_SCOPE_TRANSFORMATIONS;
		}

		return $transformations[ $location_value ] ?? null;
	}

	/**
	 * Transform code value by decoding HTML entities and stripping wrapper tags if necessary.
	 *
	 * @param mixed $code_value   Code value from source plugin.
	 * @param array $snippet_data Snippet data.
	 *
	 * @return string|null
	 */
	private function transform_code_value( $code_value, array $snippet_data ): ?string {
		$code = html_entity_decode( $code_value );

		if ( '2' !== $snippet_data['insertionMethod'] ) {
			$code = $this->strip_wrapper_tags( $code );
		}

		return trim( $code );
	}

	/**
	 * Strip PHP opening and closing tags from the code.
	 *
	 * @param string $code PHP code.
	 *
	 * @return string
	 */
	private function strip_wrapper_tags( string $code ): string {
		return preg_replace( '/^\s*<\?\s*(php)?\s*|\?>\s*$/i', '', $code );
	}
}
