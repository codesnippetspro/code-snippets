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
	 * @var array
	 */
	private $snippets_list;

	/**
	 * Class constructor
	 *
	 * @param array|int $ids        List of snippet IDs to export.
	 * @param string    $table_name Name of the database table to fetch snippets from.
	 */
	public function __construct( $ids, $table_name = '' ) {
		$this->fetch_snippets( $ids, $table_name );
	}

	/**
	 * Fetch the selected snippets from the database
	 *
	 * @param array|int $ids        List of snippet IDs to export.
	 * @param string    $table_name Name of database table to fetch snippets from.
	 */
	private function fetch_snippets( $ids, $table_name ) {

		if ( '' === $table_name ) {
			$table_name = code_snippets()->db->get_table_name();
		}

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$this->snippets_list = count( $ids ) ? get_snippets( $ids, $table_name ) : array();
	}

	/**
	 * Set up the current page to act like a downloadable file instead of being shown in the browser
	 *
	 * @param string $format    File format. Used for file extension.
	 * @param string $mime_type File MIME type. Used for Content-Type header.
	 */
	private function do_headers( $format, $mime_type = 'text/plain' ) {

		/* Build the export filename */
		if ( 1 === count( $this->snippets_list ) ) {
			/* If there is only snippet to export, use its name instead of the site name */
			$title = strtolower( $this->snippets_list[0]->name );
		} else {
			/* Otherwise, use the site name as set in Settings > General */
			$title = strtolower( get_bloginfo( 'name' ) );
		}

		$filename = "$title.code-snippets.$format";
		$filename = apply_filters( 'code_snippets/export/filename', $filename, $title, $this->snippets_list );

		/* Set HTTP headers */
		header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $filename ) );
		header( sprintf( 'Content-Type: %s; charset=%s', sanitize_mime_type( $mime_type ), get_bloginfo( 'charset' ) ) );
	}

	/**
	 * Export snippets in JSON format
	 */
	public function export_snippets() {
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

		$this->do_headers( 'json', 'application/json' );

		$data = array(
			'generator'    => 'Code Snippets v' . code_snippets()->version,
			'date_created' => gmdate( 'Y-m-d H:i' ),
			'snippets'     => $snippets,
		);

		echo wp_json_encode( $data, apply_filters( 'code_snippets/export/json_encode_options', 0 ) );
		exit;
	}

	/**
	 * Export snippets to a downloadable file.
	 *
	 * The Pro version of this function can download snippets to .css and .js files.
	 */
	public function download_snippets() {
		$this->download_php_snippets();
	}

	/**
	 * Generate a downloadable PHP file from a list of snippets
	 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	 */
	public function download_php_snippets() {
		$this->do_headers( 'php', 'text/php' );
		echo "<?php\n";

		/** Loop through the snippets
		 *
		 * @var Snippet $snippet
		 */
		foreach ( $this->snippets_list as $snippet ) {
			$code = trim( $snippet->code );

			if ( 'php' !== $snippet->type && 'html' !== $snippet->type || ! $code ) {
				continue;
			}

			echo "\n/**\n * $snippet->display_name\n";

			if ( ! empty( $snippet->desc ) ) {
				/* Convert description to PhpDoc */
				$desc = str_replace( "\n", "\n * ", $snippet->desc );
				echo " *\n * ", wp_strip_all_tags( $desc ), "\n";
			}

			echo " */\n";

			if ( 'content' === $snippet->scope ) {
				$shortcode_tag = apply_filters( 'code_snippets_export_shortcode_tag', "code_snippets_export_$snippet->id", $snippet );

				$code = sprintf(
					"add_shortcode( '%s', function () {\n\tob_start();\n\t?>\n\n\t%s\n\n\t<?php\n\treturn ob_get_clean();\n} );",
					$shortcode_tag,
					str_replace( "\n", "\n\t", $code )
				);
			}

			echo $code, "\n";
		}

		exit;
	}
}
