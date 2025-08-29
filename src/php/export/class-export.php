<?php

namespace Code_Snippets;

/**
 * Handles exporting snippets from the site in various downloadable formats
 *
 * @package Code_Snippets
 * @since   3.0.0
 */
class Export {

	/**
	 * Array of snippet data fetched from the database
	 *
	 * @var Snippet[]
	 */
	protected array $snippets_list;

	/**
	 * Class constructor
	 *
	 * @param array<int>   $ids     List of snippet IDs to export.
	 * @param boolean|null $network Whether to fetch snippets from local or network table.
	 */
	public function __construct( array $ids, ?bool $network = null ) {
		$this->snippets_list = get_snippets( $ids, $network );
	}

	/**
	 * Build the export filename.
	 *
	 * @param string $format File format. Used for file extension.
	 *
	 * @return string
	 */
	public function build_filename( string $format ): string {
		if ( 1 === count( $this->snippets_list ) ) {
			// If there is only snippet to export, use its name instead of the site name.
			$title = strtolower( $this->snippets_list[0]->name );
		} else {
			// Otherwise, use the site name as set in Settings > General.
			$title = strtolower( get_bloginfo( 'name' ) );
		}

		$filename = "$title.code-snippets.$format";
		return apply_filters( 'code_snippets/export/filename', $filename, $title, $this->snippets_list );
	}

	/**
	 * Bundle snippets together into JSON format.
	 *
	 * @return array<string, string|Snippet[]> Snippets as JSON object.
	 */
	public function create_export_object(): array {
		$snippets = array();

		foreach ( $this->snippets_list as $snippet ) {
			$snippets[] = array_map(
				function ( $value ) {
					return is_string( $value ) ?
						str_replace( "\r\n", "\n", $value ) :
						$value;
				},
				$snippet->get_modified_fields()
			);
		}

		return array(
			'generator'    => 'Code Snippets v' . code_snippets()->version,
			'date_created' => gmdate( 'Y-m-d H:i' ),
			'snippets'     => $snippets,
		);
	}

	/**
	 * Bundle a snippets into a PHP file.
	 */
	public function export_snippets_php(): string {
		$result = "<?php\n";

		foreach ( $this->snippets_list as $snippet ) {
			$code = trim( $snippet->code );

			if ( ( 'php' !== $snippet->type && 'html' !== $snippet->type ) || ! $code ) {
				continue;
			}

			$result .= "\n/**\n * $snippet->display_name\n";

			if ( ! empty( $snippet->desc ) ) {
				// Convert description to PhpDoc.
				$desc = wp_strip_all_tags( str_replace( "\n", "\n * ", $snippet->desc ) );
				$result .= " *\n * $desc\n";
			}

			$result .= " */\n";

			if ( 'content' === $snippet->scope ) {
				$shortcode_tag = apply_filters( 'code_snippets_export_shortcode_tag', "code_snippets_export_$snippet->id", $snippet );

				$code = sprintf(
					"add_shortcode( '%s', function () {\n\tob_start();\n\t?>\n\n\t%s\n\n\t<?php\n\treturn ob_get_clean();\n} );",
					$shortcode_tag,
					str_replace( "\n", "\n\t", $code )
				);
			}

			$result .= "$code\n";
		}

		return $result;
	}

	/**
	 * Export conditions in JSON format.
	 *
	 * @return string
	 */
	public function export_conditions_json(): string {
		$conditions_data = [];
		$fields_to_copy = [ 'name', 'desc', 'tags' ];

		foreach ( $this->snippets_list as $snippet ) {
			$condition_data = [];

			if ( ! $snippet->code || 'cond' !== $snippet->type ) {
				continue;
			}

			$rules = json_decode( $snippet->code, false );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				continue;
			}

			foreach ( $fields_to_copy as $field ) {
				if ( ! empty( $snippet->$field ) ) {
					$condition_data[ $field ] = $snippet->$field;
				}
			}

			$condition_data['rules'] = $rules;
			$conditions_data[] = $condition_data;
		}

		return wp_json_encode( 1 === count( $conditions_data ) ? $conditions_data[0] : $conditions_data, JSON_PRETTY_PRINT );
	}

	/**
	 * Generate a downloadable CSS or JavaScript file from a list of snippets
	 *
	 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	 *
	 * @param string|null $type Snippet type. Supports 'css' or 'js'.
	 */
	public function export_snippets_code( ?string $type = null ): string {
		$result = '';

		if ( ! $type ) {
			$type = $this->snippets_list[0]->type;
		}

		if ( 'php' === $type || 'html' === $type ) {
			return $this->export_snippets_php();
		}

		if ( 'cond' === $type ) {
			return $this->export_conditions_json();
		}

		foreach ( $this->snippets_list as $snippet ) {
			$snippet = new Snippet( $snippet );

			if ( $snippet->type !== $type ) {
				continue;
			}

			$result .= "\n/*\n";

			if ( $snippet->name ) {
				$result .= wp_strip_all_tags( $snippet->name ) . "\n\n";
			}

			if ( ! empty( $snippet->desc ) ) {
				$result .= wp_strip_all_tags( $snippet->desc ) . "\n";
			}

			$result .= "*/\n\n$snippet->code\n\n";
		}

		return $result;
	}
}
