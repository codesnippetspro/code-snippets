<?php

/**
 * This file handles the export functions
 *
 * It's better to call the $code_snippets->export()
 * and $code_snippets->export_php() methods then
 * directly use those in this file
 *
 * @package    Code Snippets
 * @subpackage Export
 */

if ( ! function_exists( 'code_snippets_export' ) ) :

/**
 * Exports seleted snippets to a XML or PHP file.
 *
 * @package Code Snippets
 * @since Code Snippets 1.3
 *
 * @param array $ids The IDs of the snippets to export
 * @param string $format The format of the export file
 * @return void
 */
function code_snippets_export( $ids, $format = 'xml' ) {

	global $code_snippets;

	$ids = (array) $ids;

	if ( 1 === count( $ids ) ) {
		// If there is only snippet to export, use its name instead of the site name
		$entry = $code_snippets->get_snippet( $ids );
		$sitename = sanitize_title_with_dashes( $entry->name );
	} else {
		// Otherwise, use the site name as set in Settings > General
		$sitename = sanitize_title_with_dashes( get_bloginfo( 'name' ) );
	}

	$filename = apply_filters( 'code_snippets_export_filename', "{$sitename}.code-snippets.{$format}", $format, $sitename );

	/* Apply the file headers */

	header( 'Content-Disposition: attachment; filename=' . $filename );

	if ( $format === 'xml' ) {
		header( 'Content-Type: text/xml; charset=utf-8' );

		echo '<?xml version="1.0"?>' . "\n";
		echo '<snippets>';

	} elseif ( $format === 'php' ) {

		echo "<?php\n";

	}

	do_action( 'code_snippets_export_file_header', $format, $ids, $filename );

	/* Loop through the snippets */

	foreach( $ids as $id ) {

		if ( ! intval( $id ) > 0 ) continue; // skip this one if we don't have a valid ID

		$snippet = $code_snippets->get_snippet( $id );

		if ( $format === 'xml' ) {

			echo "\n\t" . '<snippet>';
			echo "\n\t\t" . "<name>$snippet->name</name>";
			echo "\n\t\t" . "<description>$snippet->description</description>";
			echo "\n\t\t" . "<code>$snippet->code</code>";
			echo "\n\t" . '</snippet>';
		}
		elseif ( $format === 'php' ) {

			echo "\n/**\n * {$snippet->name}\n";

			if ( ! empty( $snippet->description ) ) {
				/* Convert description to PHP Doc */
				$snippet->description = strip_tags( str_replace( "\n", "\n * ", $snippet->description ) );
				echo " *\n * {$snippet->description}\n";
			}

			echo " */\n{$snippet->code}\n";
		}
	}

	do_action( 'code_snippets_export_file_snippet', $format, $id, $filename );

	/* Finish off the file */

	if ( 'xml' === $format ) {

		echo "\n</snippets>";

	} elseif ( 'php' === $format ) {

		echo '?>';

	}

	do_action( 'code_snippets_export_file_footer', $format, $ids, $filename );

	exit;
}

endif; // function exists check