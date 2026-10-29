<?php

namespace Code_Snippets\REST_API\Import\Plugins;

/**
 * Importer for the 'Header Footer Code Manager' plugin.
 */
class Header_Footer_Code_Manager_Plugin_Importer extends Plugin_Importer {

	protected const FIELD_MAPPINGS = [
		'name'     => 'name',
		'snippet'  => 'code',
		'location' => 'scope',
		'created'  => 'modified',
	];

	private const HTML_SCOPE_TRANSFORMATIONS = [
		''       => 'content',
		'header' => 'head-content',
		'footer' => 'footer-content',
	];

	/**
	 * Get the unique name of the importer.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'header-footer-code-manager';
	}

	/**
	 * Get the human-readable title of the importer.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Header Footer Code Manager', 'code-snippets' );
	}

	/**
	 * Check if the 'Header Footer Code Manager' plugin is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return is_plugin_active( 'header-footer-code-manager/99robots-header-footer-code-manager.php' );
	}

	/**
	 * Retrieve snippet data from the 'Header Footer Code Manager' plugin's database table.
	 *
	 * @param array $ids_to_import Optional array of snippet IDs to import.
	 *
	 * @return array
	 *
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
	 */
	public function get_data( array $ids_to_import = [] ): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'hfcm_scripts';

		if ( $ids_to_import ) {
			$ids_format = implode( ',', array_fill( 0, count( $ids_to_import ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$snippets = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM `$table_name` WHERE script_id IN ($ids_format)", $ids_to_import ),
				ARRAY_A
			);
		} else {
			$snippets = $wpdb->get_results( "SELECT * FROM `$table_name`", ARRAY_A );
		}

		foreach ( $snippets as &$snippet ) {
			$snippet['table_data'] = [
				'id'    => (int) ( $snippet['script_id'] ?? 0 ),
				'title' => $snippet['name'] ?? '',
			];
		}
		unset( $snippet );

		return $snippets;
	}

	/**
	 * Transform field value based on the target field.
	 *
	 * @param string $target_field Target field name.
	 * @param mixed  $value        Original value.
	 * @param array  $snippet_data Snippet data.
	 *
	 * @return string|null
	 */
	protected function transform_field_value( string $target_field, $value, array $snippet_data ): ?string {
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
	 * @param mixed $location_value Location value from the source snippet.
	 * @param array $snippet_data   Snippet data.
	 *
	 * @return string|null
	 */
	private function transform_scope_value( $location_value, array $snippet_data ): ?string {
		if ( ! is_scalar( $location_value ) ) {
			return null;
		}

		switch ( $snippet_data['snippet_type'] ?? '' ) {
			case 'html':
				$transformations = self::HTML_SCOPE_TRANSFORMATIONS;
				break;
			default:
				return null;
		}

		return $transformations[ $location_value ] ?? null;
	}

	/**
	 * Transform code value by decoding HTML entities and stripping wrapper tags.
	 *
	 * @param string $code_value   Raw code value.
	 * @param array  $snippet_data Snippet data.
	 *
	 * @return string|null
	 */
	private function transform_code_value( string $code_value, array $snippet_data ): ?string {
		$code = html_entity_decode( $code_value );
		$code_type = $snippet_data['snippet_type'] ?? '';

		$code = $this->strip_wrapper_tags( $code, $code_type );

		return trim( $code );
	}

	/**
	 * Strip wrapper tags from the code based on its type.
	 *
	 * @param string $code      Wrapped code.
	 * @param string $code_type Code type, 'css' or 'js'.
	 *
	 * @return string Unwrapped code.
	 */
	private function strip_wrapper_tags( string $code, string $code_type ): string {
		switch ( $code_type ) {
			case 'css':
				return preg_replace( '/<\s*style[^>]*>|<\s*\/\s*style\s*>/i', '', $code );
			case 'js':
				return preg_replace( '/<\s*script[^>]*>|<\s*\/\s*script\s*>/i', '', $code );
			default:
				return $code;
		}
	}
}
