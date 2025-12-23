<?php

namespace Code_Snippets;

use MatthiasMullie\Minify;

class Header_Footer_Code_Manager_Importer extends Importer_Base {

	private const FIELD_MAPPINGS = [
		'name' => 'name',
		'snippet' => 'code',
		'location' => 'scope',
		'created' => 'modified',
	];

	private const HTML_SCOPE_TRANSFORMATIONS = [
		'' => 'content',
		'header' => 'head-content',
		'footer' => 'footer-content',
	];

	public function get_name() {
		return 'header-footer-code-manager';
	}

	public function get_title() {
		return esc_html__( 'Header Footer Code Manager', 'code-snippets' );
	}

	public static function is_active(): bool {
		return is_plugin_active( 'header-footer-code-manager/99robots-header-footer-code-manager.php' );
	}

	public function get_data( array $ids_to_import = [] ) {
		global $wpdb;
		$nnr_hfcm_table_name = $wpdb->prefix . 'hfcm_scripts';
		$sql = "SELECT * FROM `{$nnr_hfcm_table_name}`";

		if ( ! empty( $ids_to_import ) ) {
			$sql .= ' WHERE script_id IN (' . implode( ',', $ids_to_import ) . ')';
		}

		$snippets = $wpdb->get_results(
			$sql
		);

		foreach ( $snippets as $snippet ) {
			$snippet->table_data = [
				'id' => (int) $snippet->script_id,
				'title' => $snippet->name,
			];
		}

		return $snippets;
	}

	public function create_snippet( $snippet_data, bool $multisite ): ?Snippet {
		$code_type = $snippet_data->snippet_type ?? '';

		$snippet = new Snippet();
		$snippet->network = $multisite;

		foreach ( self::FIELD_MAPPINGS as $source_field => $target_field ) {
			if ( ! isset( $snippet_data->$source_field ) ) {
				continue;
			}

			$value = $this->transform_field_value(
				$target_field,
				$snippet_data->$source_field,
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

	private function transform_field_value( string $target_field, $value, $snippet_data ) {
		if ( 'scope' === $target_field ) {
			return $this->transform_scope_value( $value, $snippet_data );
		}

		if ( 'code' === $target_field ) {
			return $this->transform_code_value( $value, $snippet_data );
		}

		return $value;
	}

	private function transform_scope_value( $location_value, $snippet_data ): ?string {
		if ( ! is_scalar( $location_value ) ) {
			return null;
		}

		$code_type = $snippet_data->snippet_type;

		switch ( $code_type ) {
			case 'html':
				$transformations = self::HTML_SCOPE_TRANSFORMATIONS;
				break;
			default:
				return null;
		}

		return $transformations[ $location_value ] ?? null;
	}

	private function transform_code_value( $code_value, $snippet_data ): ?string {
		$code = html_entity_decode( $code_value );
		$code_type = $snippet_data->snippet_type ?? '';

		$code = $this->strip_wrapper_tags( $code, $code_type );
		$code = $this->apply_minification( $code, $code_type );

		return trim( $code );
	}

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

	private function apply_minification( string $code, string $code_type ): string {
		if ( ! in_array( $code_type, [ 'css', 'js' ], true ) ) {
			return $code;
		}

		$setting = Settings\get_setting( 'general', 'minify_output' );
		if ( ! is_array( $setting ) || ! in_array( $code_type, $setting, true ) ) {
			return $code;
		}

		$minifier = 'css' === $code_type ? new Minify\CSS( $code ) : new Minify\JS( $code );
		return $minifier->minify();
	}
}
