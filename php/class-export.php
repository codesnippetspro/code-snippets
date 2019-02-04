<?php

namespace Code_Snippets;

/**
 * Handles exporting snippets from the site in various downloadable formats
 * @package Code_Snippets
 * @since   3.0.0
 */
class Export {

	/**
	 * Set up the current page to act like a downloadable file instead of being shown in the browser
	 *
	 * @param array  $ids
	 * @param string $table_name
	 *
	 * @return array
	 */
	private static function fetch_snippets( $ids, $table_name = '' ) {
		global $wpdb;

		/* Fetch the snippets from the database */
		if ( '' === $table_name ) {
			$table_name = code_snippets()->db->get_table_name();
		}

		if ( ! count( $ids ) ) {
			return array();
		}

		$sql = sprintf(
			'SELECT * FROM %s WHERE id IN (%s)', $table_name,
			implode( ',', array_fill( 0, count( $ids ), '%d' ) )
		);

		$snippets = $wpdb->get_results( $wpdb->prepare( $sql, $ids ), ARRAY_A );

		return $snippets;
	}

	/**
	 * Set up the current page to act like a downloadable file instead of being shown in the browser
	 *
	 * @param array  $snippets
	 * @param string $format
	 * @param string $mime_type
	 */
	private static function do_headers( $snippets, $format, $mime_type = '' ) {

		/* Build the export filename */
		if ( 1 == count( $snippets ) ) {
			/* If there is only snippet to export, use its name instead of the site name */
			$first_snippet = new Snippet( $snippets[0] );
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
	 *
	 * @param array  $ids        list of snippet IDs to export
	 * @param string $table_name name of the database table to fetch snippets from
	 */
	public static function export_snippets( $ids, $table_name = '' ) {
		$raw_snippets = self::fetch_snippets( $ids, $table_name );
		$final_snippets = array();

		foreach ( $raw_snippets as $snippet ) {
			$snippet = new Snippet( $snippet );

			$fields = array( 'name', 'desc', 'tags', 'scope', 'code', 'priority' );
			$final_snippet = array();

			foreach ( $fields as $field ) {
				if ( ! empty( $snippet->$field ) ) {
					$final_snippet[ $field ] = str_replace( "\r\n", "\n", $snippet->$field );
				}
			}

			if ( $final_snippet ) {
				$final_snippets[] = $final_snippet;
			}
		}

		self::do_headers( $raw_snippets, 'json', 'application/json' );

		$data = array(
			'generator'    => 'Code Snippets v' . code_snippets()->version,
			'date_created' => date( 'Y-m-d H:i' ),
			'snippets'     => $final_snippets,
		);

		echo wp_json_encode( $data, apply_filters( 'code_snippets/export/json_encode_options', 0 ) );
		exit;
	}

	/**
	 * Export snippets to a downloadable PHP or CSS file
	 *
	 * @param $ids
	 * @param $table_name
	 */
	public static function download_snippets( $ids, $table_name = '' ) {
		$snippets = self::fetch_snippets( $ids, $table_name );
		$first_snippet = new Snippet( $snippets[0] );

		if ( 'css' === $first_snippet->type ) {
			self::build_css_file( $snippets );
		} else {
			self::build_php_file( $snippets );
		}

		exit;
	}

	/**
	 * Generate a downloadable PHP file from a list of snippets
	 *
	 * @param $snippets
	 */
	private static function build_php_file( $snippets ) {
		self::do_headers( $snippets, 'php' );

		echo "<?php\n";

		/* Loop through the snippets */
		foreach ( $snippets as $snippet ) {
			$snippet = new Snippet( $snippet );

			if ( 'php' !== $snippet->type ) {
				continue;
			}

			echo "\n/**\n * {$snippet->name}\n";

			if ( ! empty( $snippet->desc ) ) {

				/* Convert description to PhpDoc */
				$desc = strip_tags( str_replace( "\n", "\n * ", $snippet->desc ) );

				echo " *\n * $desc\n";
			}

			echo " */\n{$snippet->code}\n";
		}
	}

	/**
	 * Generate a downloadable CSS file from a list of snippets
	 *
	 * @param $snippets
	 */
	private static function build_css_file( $snippets ) {
		self::do_headers( $snippets, 'css', 'text/css' );

		/* Loop through the snippets */
		foreach ( $snippets as $snippet ) {
			$snippet = new Snippet( $snippet );

			if ( 'css' !== $snippet->type ) {
				continue;
			}

			echo "\n/**\n * {$snippet->name}\n";

			if ( ! empty( $snippet->desc ) ) {

				/* Convert description to PhpDoc */
				$desc = strip_tags( $snippet->desc );

				echo " *\n * $desc\n";
			}

			echo " */\n{$snippet->code}\n";
		}
	}
}
