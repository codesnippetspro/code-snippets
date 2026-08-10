<?php

namespace Code_Snippets\Migration\Export;

use Code_Snippets\Model\Snippet;

/**
 * Handles exporting the code from snippets.
 *
 * @package Code_Snippets
 */
class Export_Code extends Export {

	/**
	 * The type of snippet being exported.
	 *
	 * @var string
	 */
	protected string $export_type;

	/**
	 * Constructor.
	 *
	 * @param array<int>  $ids         The IDs of the snippets to export.
	 * @param bool|null   $network     Whether to export network-wide snippets.
	 * @param string|null $export_type The type of snippet to export (e.g., 'php', 'html', 'css', 'js', 'cond').
	 */
	public function __construct( array $ids, ?bool $network = null, ?string $export_type = null ) {
		parent::__construct( $ids, $network );

		if ( $export_type && in_array( $export_type, Snippet::get_types(), true ) ) {
			$this->export_type = $export_type;
		} else {
			// If no export type is specified, default to the type of the first snippet.
			$snippets_list = $this->get_snippets_list();
			$this->export_type = $snippets_list[0]->type;
		}
	}

	/**
	 * Get the type of snippets being exported.
	 *
	 * @return string
	 */
	public function get_export_type(): string {
		return $this->export_type;
	}

	/**
	 * Get the file extension for the export format.
	 *
	 * @return string
	 */
	public function get_file_extension(): string {
		return 'cond' === $this->export_type
			? 'json'
			: $this->export_type;
	}

	/**
	 * Bundle a snippets into a PHP file.
	 */
	protected function export_snippets_php(): string {
		$result = "<?php\n";

		foreach ( $this->get_snippets_list() as $snippet ) {
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
	protected function export_conditions_json(): string {
		$conditions_data = [];
		$fields_to_copy = [ 'name', 'desc', 'tags' ];

		foreach ( $this->get_snippets_list() as $snippet ) {
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
	 * Export snippets in a generic format, compatible with CSS or JavaScript.
	 */
	protected function export_snippets_css_js(): string {
		$result = '';

		foreach ( $this->get_snippets_list() as $snippet ) {
			$snippet = new Snippet( $snippet );

			if ( $this->get_export_type() !== $snippet->type ) {
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

	/**
	 * Generate a downloadable code file from the exported snippets.
	 */
	public function generate_export(): string {
		switch ( $this->get_export_type() ) {
			case 'php':
			case 'html':
				return $this->export_snippets_php();

			case 'cond':
				return $this->export_conditions_json();

			default:
				return $this->export_snippets_css_js();
		}
	}
}
