<?php

namespace Code_Snippets;

class Insert_Headers_And_Footers_Importer extends Importer_Base {

	private const FIELD_MAPPINGS = [
		'title' => 'name',
		'note' => 'desc',
		'code' => 'code',
		'tags' => 'tags',
		'location' => 'scope',
		'priority' => 'priority',
		'modified' => 'modified',
	];

	private const PHP_SCOPE_TRANSFORMATIONS = [
		'everywhere' => 'global',
		'admin_only' => 'admin',
		'frontend_only' => 'front-end',
	];

	private const HTML_SCOPE_TRANSFORMATIONS = [
		'' => 'content',
		'site_wide_header' => 'head-content',
		'site_wide_footer' => 'footer-content',
	];

	public function get_name() {
		return 'insert-headers-and-footers';
	}

	public function get_title() {
		return esc_html__( 'WPCode (Insert Headers and Footers)', 'code-snippets' );
	}

	public static function is_active(): bool {
		return is_plugin_active( 'insert-headers-and-footers/ihaf.php' );
	}

	public function get_data( array $ids_to_import = [] ) {
		$query_args = [
			'post_type' => 'wpcode',
			'post_status' => [
				'publish',
				'draft',
			],
			'nopaging' => true,
		];

		if ( ! empty( $ids_to_import ) ) {
			$query_args['include'] = $ids_to_import;
		}

		$data = [];
		$snippets = get_posts( $query_args );

		foreach ( $snippets as $snippet_item ) {
			$snippet = new \WPCode_Snippet( $snippet_item );
			$snippet_data = $snippet->get_data_for_caching();
			$snippet_data['tags'] = $snippet->get_tags();
			$snippet_data['note'] = $snippet->get_note();
			$snippet_data['cloud_id'] = null;
			$snippet_data['custom_shortcode'] = $snippet->get_custom_shortcode();
			$snippet_data['table_data'] = [
				'id' => $snippet_item->ID,
				'title' => $snippet_item->post_title,
			];

			$data[] = apply_filters( 'wpcode_export_snippet_data', $snippet_data, $snippet );
		}

		$data = array_reverse( $data );

		return $data;
	}

	public function create_snippet( $snippet_data, bool $multisite ): ?Snippet {
		$code_type = $snippet_data['code_type'] ?? '';
		$is_supported_code_type = in_array( $code_type, [ 'php', 'css', 'html', 'js' ], true );
		if ( ! $is_supported_code_type ) {
			return null;
		}

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

	private function transform_field_value( string $target_field, $value, array $snippet_data ) {
		if ( 'scope' === $target_field ) {
			return $this->transform_scope_value( $value, $snippet_data );
		}

		return $value;
	}

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
