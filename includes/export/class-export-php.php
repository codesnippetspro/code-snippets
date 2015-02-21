<?php

/**
 * This file handles exporting snippets in PHP format
 *
 * It's better to call the export_snippets_to_php()
 * function than directly using this class
 *
 * @since      1.9
 * @package    Code_Snippets
 * @subpackage Export
 */

if ( ! class_exists( 'Code_Snippets_Export_PHP' ) ) :

/**
 * Exports selected snippets to a XML or PHP file.
 *
 * @since  1.3
 * @param  array  $ids    The IDs of the snippets to export
 * @param  string $format The format of the export file
 * @return void
 */
class Code_Snippets_Export_PHP extends Code_Snippets_Export {

	/**
	 * Constructor function
	 * @param array  $ids   The IDs of the snippets to export
	 * @param string $table The name of the table to fetch snippets from
	 */
	public function __construct( array $ids, $table ) {
		add_filter( 'code_snippets/export/filename', array( $this, 'replace_filename_extension' ) );
		parent::__construct( $ids, $table );
	}

	/**
	 * Replace the .xml file extension with a .php file extension
	 * @param  string $filename The filename with a .xml extension
	 * @return string           The filename with a .php extension
	 */
	public function replace_filename_extension( $filename ) {
		$filename = str_replace( '.xml', '.php', $filename );
		return $filename;
	}

	/**
	 * Begin the export file
	 */
	protected function do_header() {
		echo '<?php';
	}

	/**
	 * Output a single snippet
	 * @param array $snippet
	 */
	protected function do_item( $snippet ) {

		echo "\n/**\n * {$snippet['name']}\n";

		if ( ! empty( $snippet['description'] ) ) {

			/* Convert description to PhpDoc */
			$desc = strip_tags( str_replace( "\n", "\n * ", $snippet['description'] ) );

			echo " *\n * $desc\n";
		}

		echo " */\n{$snippet['code']}\n";
	}

	/**
	 * Finish off the file
	 */
	protected function do_footer() {}
}

endif; // class exists check
