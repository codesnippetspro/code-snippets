<?php

namespace Code_Snippets;

/**
 * Handles exporting snippets from the site in various downloadable formats
 * @package Code_Snippets
 * @since   3.0.0
 */
class Export {

	/**
	 * Array of snippet data fetched from the database
	 * @var array
	 */
	private $snippets_list;

	/**
	 * Class constructor
	 *
	 * @param array|int $ids        List of snippet IDs to export
	 * @param string    $table_name Name of the database table to fetch snippets from
	 */
	public function __construct( $ids, $table_name = '' ) {
		$this->fetch_snippets( $ids, $table_name );
	}

	/**
	 * Fetch the selected snippets from the database
	 *
	 * @param array|int $ids
	 * @param string    $table_name
	 */
	private function fetch_snippets( $ids, $table_name ) {
		global $wpdb;

		/* Fetch the snippets from the database */
		if ( '' === $table_name ) {
			$table_name = code_snippets()->db->get_table_name();
		}

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		if ( count( $ids ) ) {
			$sql = sprintf(
				'SELECT * FROM %s WHERE id IN (%s)', $table_name,
				implode( ',', array_fill( 0, count( $ids ), '%d' ) )
			);

			$this->snippets_list = $wpdb->get_results( $wpdb->prepare( $sql, $ids ), ARRAY_A );

		} else {
			$this->snippets_list = array();
		}
	}

	/**
	 * Set up the current page to act like a downloadable file instead of being shown in the browser
	 *
	 * @param string $format
	 * @param string $mime_type
	 */
	private function do_headers( $format, $mime_type = '' ) {

		/* Build the export filename */
		if ( 1 === count( $this->snippets_list ) ) {
			/* If there is only snippet to export, use its name instead of the site name */
			$first_snippet = new Snippet( $this->snippets_list[0] );
			$title = strtolower( $first_snippet->name );
		} else {
			/* Otherwise, use the site name as set in Settings > General */
			$title = strtolower( get_bloginfo( 'name' ) );
		}

		$filename = "{$title}.code-snippets.{$format}";
		$filename = apply_filters( 'code_snippets/export/filename', $filename, $title );

		/* Set HTTP headers */
		header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $filename ) );

		if ( '' !== $mime_type ) {
			header( "Content-Type: $mime_type; charset=" . get_bloginfo( 'charset' ) );
		}
	}

	/**
	 * Export snippets in JSON format
	 */
	public function export_snippets() {
		$snippets = array();

		foreach ( $this->snippets_list as $snippet ) {
			$snippet = new Snippet( $snippet );

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
			'date_created' => date( 'Y-m-d H:i' ),
			'snippets'     => $snippets,
		);

		echo wp_json_encode( $data, apply_filters( 'code_snippets/export/json_encode_options', 0 ) );
		exit;
	}

	/**
	 * Export snippets to a downloadable PHP or CSS file
	 */
	public function download_snippets() {
		$first_snippet = new Snippet( $this->snippets_list[0] );

		if ( 'css' === $first_snippet->type ) {
			$this->download_css_snippets();
		} else {
			$this->download_php_snippets();
		}
	}

	/**
	 * Generate a downloadable PHP file from a list of snippets
	 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	 */
	public function download_php_snippets() {
		$this->do_headers( 'php' );

		echo "<?php\n";

		/* Loop through the snippets */
		foreach ( $this->snippets_list as $snippet ) {
			$snippet = new Snippet( $snippet );

			if ( 'php' !== $snippet->type ) {
				continue;
			}

			echo "\n/**\n * {$snippet->name}\n";

			if ( ! empty( $snippet->desc ) ) {

				/* Convert description to PhpDoc */
				$desc = str_replace( "\n", "\n * ", $snippet->desc );
				echo " *\n * ", wp_strip_all_tags( $desc ), "\n";
			}

			echo " */\n{$snippet->code}\n";
		}

		exit;
	}

	/**
	 * Generate a downloadable CSS file from a list of snippets
	 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	 */
	public function download_css_snippets() {
		$this->do_headers( 'css', 'text/css' );

		/* Loop through the snippets */
		foreach ( $this->snippets_list as $snippet ) {
			$snippet = new Snippet( $snippet );

			if ( 'css' !== $snippet->type ) {
				continue;
			}

			echo "\n/*\n";

			if ( $snippet->name ) {
				echo wp_strip_all_tags( $snippet->name ), "\n\n";
			}

			if ( ! empty( $snippet->desc ) ) {
				echo wp_strip_all_tags( $snippet->desc ), "\n";
			}

			echo "*/\n\n{$snippet->code}\n\n";
		}

		exit;
	}
}
