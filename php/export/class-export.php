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
	protected $snippets_list;

	/**
	 * Class constructor
	 *
	 * @param array<int>|int $ids        List of snippet IDs to export.
	 * @param string         $table_name Name of the database table to fetch snippets from.
	 */
	public function __construct( $ids, $table_name = '' ) {
		$this->fetch_snippets( $ids, $table_name );
	}

	/**
	 * Fetch the selected snippets from the database
	 *
	 * @param array<int>|int $ids        List of snippet IDs to export.
	 * @param string         $table_name Name of database table to fetch snippets from.
	 */
	private function fetch_snippets( $ids, $table_name ) {
		if ( '' === $table_name ) {
			$table_name = code_snippets()->db->get_table_name();
		}

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$this->snippets_list = get_snippets( $ids, $table_name );
	}

	/**
	 * Build the export filename.
	 *
	 * @param string $format File format. Used for file extension.
	 *
	 * @return string
	 */
	public function build_filename( $format ) {
		if ( 1 === count( $this->snippets_list ) ) {
			/* If there is only snippet to export, use its name instead of the site name */
			$title = strtolower( $this->snippets_list[0]->name );
		} else {
			/* Otherwise, use the site name as set in Settings > General */
			$title = strtolower( get_bloginfo( 'name' ) );
		}

		$filename = "$title.code-snippets.$format";
		return apply_filters( 'code_snippets/export/filename', $filename, $title, $this->snippets_list );
	}

	/**
	 * Bundle snippets together into JSON format.
	 *
	 * @return string Snippets as JSON object.
	 */
	public function export_snippets_json() {
		$snippets = array();

		foreach ( $this->snippets_list as $snippet ) {
			$fields = array( 'name', 'desc', 'tags', 'scope', 'code', 'priority' );
			$final_snippet = array();

			foreach ( $fields as $field ) {
				if ( ! empty( $snippet->$field ) ) {
					$final_snippet[ $field ] = str_replace( "\r\n", "\n", $snippet->$field );
				}
			}

			if ( $final_snippet ) {
				$snippets[] = $final_snippet;
			}
		}

		$data = array(
			'generator'    => 'Code Snippets v' . code_snippets()->version,
			'date_created' => gmdate( 'Y-m-d H:i' ),
			'snippets'     => $snippets,
		);

		return wp_json_encode( $data, apply_filters( 'code_snippets/export/json_encode_options', 0 ) );
	}

	/**
	 * Bundle a snippets into a PHP file.
	 */
	public function export_snippets_php() {
		$result = "<?php\n";

		foreach ( $this->snippets_list as $snippet ) {
			$code = trim( $snippet->code );

			if ( 'php' !== $snippet->type && 'html' !== $snippet->type || ! $code ) {
				continue;
			}

			$result .= "\n/**\n * $snippet->display_name\n";

			if ( ! empty( $snippet->desc ) ) {
				/* Convert description to PhpDoc */
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
	 * Generate a downloadable CSS or JavaScript file from a list of snippets
	 *
	 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	 *
	 * @param string $type Snippet type. Supports 'css' or 'js'.
	 */
	public function export_snippets_code( $type = null ) {
		$result = '';

		if ( ! $type ) {
			$type = $this->snippets_list[0]->type;
		}

		if ( 'php' === $type || 'html' === $type ) {
			return $this->export_snippets_php();
		}

		/* Loop through the snippets */
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
