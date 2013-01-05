<?php

/**
 * This file handles the export functions
 *
 * It's better to call the $code_snippets->export()
 * and $code_snippets->export_php() methods then
 * directly use those in this file
 *
 * @package Code Snippets
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

	global $wpdb, $code_snippets;

	$ids = (array) $ids;

	$table = $code_snippets->get_table_name();

	if ( count( $ids ) < 2 ) {
		// If there is only snippet to export, use its name instead of the site name
		$entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d", $ids ) );
		$sitename = sanitize_key( $entry->name );
	} else {
		// Otherwise, use the site name as set in Settings > General
		$sitename = sanitize_key( get_bloginfo( 'name' ) );
	}

	$filename = apply_filters( 'code_snippets_export_filename', "{$sitename}.code-snippets.{$format}", $format, $sitename );

	/* Apply the file headers */

	header( 'Content-Disposition: attachment; filename=' . $filename );

	if ( $format === 'xml' ) {
		header( 'Content-Type: text/xml; charset=utf-8' );

		echo '<?xml version="1.0"?>' . "\n";
		echo '<snippets sitename="' . $sitename . '">';

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

			if ( ! empty( $snippet->description ) ) {
				printf (
					'/**
					  * %1$s
					  *
					  * %2$s
					  */

					  %3$s

					  ',
					 htmlspecialchars_decode( stripslashes( $snippet->name ) ),
					 htmlspecialchars_decode( stripslashes( $snippet->description ) ),
					 htmlspecialchars_decode( stripslashes( $snippet->code ) )
				);
			} else {
				printf (
					'/**' . "\n" .
					' * %1$s' . "\n" .
					' * '  . "\n" .
					' */

					  %3$s

					  ',
					 htmlspecialchars_decode( stripslashes( $snippet->name ) ),
					 htmlspecialchars_decode( stripslashes( $snippet->description ) ),
					 htmlspecialchars_decode( stripslashes( $snippet->code ) )
				);
			}
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