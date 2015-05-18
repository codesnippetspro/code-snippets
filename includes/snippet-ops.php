<?php

/**
 * Functions to preform snippet operations
 *
 * @package Code_Snippets
 */

/**
 * Converts an array of snippet data into a snippet object
 *
 * @since 2.0
 * @param mixed $data The snippet data to convert
 * @return object The resulting snippet object
 */
function build_snippet_object( $data = null ) {

	$snippet = new stdClass;

	/* Define an empty snippet object with default values */
	$snippet->id = 0;
	$snippet->name = '';
	$snippet->description = '';
	$snippet->code = '';
	$snippet->tags = array();
	$snippet->scope = 0;
	$snippet->active = 0;
	$snippet = apply_filters( 'code_snippets/build_default_snippet', $snippet );

	if ( ! isset( $data ) ) {
		return $snippet;

	} elseif ( is_object( $data ) ) {

		/* If we already have a snippet object, merge it with the default */
		return (object) array_merge( (array) $snippet, (array) $data );

	} elseif ( is_array( $data ) ) {

		foreach ( $data as $field => $value ) {

			/* Remove 'snippet_' prefix */
			if ( 'snippet_' === substr( $field, 0, 8 ) ) {
				$field = substr( $field, 8 );
			}

			/* Check the field is whitelisted */
			if ( ! isset( $snippet->$field ) ) {
				continue;
			}

			/* Update the field */
			$snippet->$field = $value;
		}

		return apply_filters( 'code_snippets/build_snippet_object', $snippet, $data );
	}

	return $snippet;
}

/**
 * Retrieve a list of snippets from the database
 *
 * @since 2.0
 *
 * @uses $wpdb To query the database for snippets
 * @uses get_snippets_table_name() To dynamically retrieve the snippet table name
 *
 * @param boolean|null $multisite Retrieve multisite-wide or site-wide snippets?
 * @return array An array of snippet objects
 */
function get_snippets( $multisite = null ) {
	global $wpdb;

	$table = get_snippets_table_name( $multisite );
	$snippets = $wpdb->get_results( "SELECT * FROM $table", ARRAY_A );

	foreach ( $snippets as $index => $snippet ) {
		$snippets[ $index ] = unescape_snippet_data( $snippet );
	}

	return apply_filters( 'code_snippets/get_snippets', $snippets, $multisite );
}

/**
* Gets all of the used tags from the database
* @since 2.0
*/
function get_all_snippet_tags() {
	global $wpdb;

	/* Grab all tags from the database */
	$tags = array();
	$table = get_snippets_table_name();
	$all_tags = $wpdb->get_col( "SELECT `tags` FROM $table" );

	/* Merge all tags into a single array */
	foreach ( $all_tags as $snippet_tags ) {
		$snippet_tags = maybe_unserialize( $snippet_tags );
		$snippet_tags = code_snippets_build_tags_array( $snippet_tags );
		$tags = array_merge( $snippet_tags, $tags );
	}

	/* Remove duplicate tags */
	return array_values( array_unique( $tags, SORT_REGULAR ) );
}

/**
 * Make sure that the tags are a valid array
 * @since 2.0
 *
 * @param mixed $tags The tags to convert into an array
 * @return array The converted tags
 */
function code_snippets_build_tags_array( $tags ) {

	/* If there are no tags set, create a default empty array */
	if ( empty( $tags ) ) {
		$tags = array();
	}

	/* If the tags are set as a string, convert them into an array */
	elseif ( is_string( $tags ) ) {
		$tags = str_replace( ', ', ',', $tags );
		$tags = explode( ',', $tags );
	}

	/* If we still don't have an array, just convert whatever we do have into one */
	if ( ! is_array( $tags ) ) {
		$tags = (array) $tags;
	}

	return $tags;
}

/**
 * Escape snippet data for inserting into the database
 *
 * @since 2.0
 * @param mixed $snippet An object or array containing the data to escape
 * @return object The resulting snippet object, with data escaped
 */
function escape_snippet_data( $snippet ) {

	$snippet = build_snippet_object( $snippet );

	/* Remove <?php and <? from beginning of snippet */
	$snippet->code = preg_replace( '|^[\s]*<\?(php)?|', '', $snippet->code );

	/* Remove ?> from end of snippet */
	$snippet->code = preg_replace( '|\?>[\s]*$|', '', $snippet->code );

	/* Ensure the ID is a positive integer */
	$snippet->id = absint( $snippet->id );

	/* Make sure that the scope is a valid value */
	if ( ! in_array( $snippet->scope, array( 0, 1, 2 ) ) ) {
		$snippet->scope = 0;
	}

	/* Store tags as a string, with tags separated by commas */
	$snippet->tags = code_snippets_build_tags_array( $snippet->tags );
	$snippet->tags = implode( ', ', $snippet->tags );

	return apply_filters( 'code_snippets/escape_snippet_data', $snippet );
}

/**
 * Unescape snippet data after retrieval from the database
 * ready for use
 *
 * @since 2.0
 * @param mixed $snippet An object or array containing the data to unescape
 * @return object The resulting snippet object, with data unescaped
 */
function unescape_snippet_data( $snippet ) {
	$snippet = build_snippet_object( $snippet );

	/* Ensure the tags are a valid array */
	$snippet->tags = code_snippets_build_tags_array( $snippet->tags );

	return apply_filters( 'code_snippets/unescape_snippet_data', $snippet );
}

/**
 * Retrieve a single snippets from the database.
 * Will return empty snippet object if no snippet
 * ID is specified
 *
 * @since 2.0
 *
 * @uses $wpdb To query the database for snippets
 * @uses get_snippets_table_name() To dynamically retrieve the snippet table name
 *
 * @param int $id The ID of the snippet to retrieve. 0 to build a new snippet
 * @param boolean|null $multisite Retrieve a multisite-wide or site-wide snippet?
 * @return object A single snippet object
 */
function get_snippet( $id = 0, $multisite = null ) {
	global $wpdb;

	$id = absint( $id );
	$table = get_snippets_table_name( $multisite );

	if ( 0 !== $id ) {

		/* Retrieve the snippet from the database */
		$snippet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

		/* Unescape the snippet data, ready for use */
		$snippet = unescape_snippet_data( $snippet );

	} else {

		/* Get an empty snippet object */
		$snippet = build_snippet_object();
	}
	return apply_filters( 'code_snippets/get_snippet', $snippet, $id, $multisite );
}

/**
 * Activates a snippet
 *
 * @since 2.0
 *
 * @uses $wpdb To set the snippet's active status
 *
 * @param array $id The ID of the snippet to activate
 * @param boolean|null $multisite Are the snippets multisite-wide or site-wide?
 */
function activate_snippet( $id, $multisite = null ) {
	global $wpdb;
	$table = get_snippets_table_name( $multisite );

	$wpdb->update(
		$table,
		array( 'active' => '1' ),
		array( 'id' => $id ),
		array( '%d' ),
		array( '%d' )
	);

	do_action( 'code_snippets/activate_snippet', $id, $multisite );
}

/**
 * Deactivate a snippet
 *
 * @since 2.0
 *
 * @uses $wpdb To set the snippets' active status
 *
 * @param array $id The ID of the snippet to deactivate
 * @param boolean|null $multisite Are the snippets multisite-wide or site-wide?
 */
function deactivate_snippet( $id, $multisite = null ) {
	global $wpdb;
	$table = get_snippets_table_name( $multisite );

	/* Set the snippet to active */

	$wpdb->update(
		$table,
		array( 'active' => '0' ),
		array( 'id' => $id ),
		array( '%d' ),
		array( '%d' )
	);

	/* Update the recently active list */

	$recently_active = array( $id => time() );

	if ( $table === $wpdb->ms_snippets ) {

		update_site_option(
			'recently_activated_snippets',
			$recently_active + (array) get_site_option( 'recently_activated_snippets' )
		);
	} elseif ( $table === $wpdb->snippets ) {

		update_option(
			'recently_activated_snippets',
			$recently_active + (array) get_option( 'recently_activated_snippets' )
		);
	}

	do_action( 'code_snippets/deactivate_snippet', $id, $multisite );
}

/**
 * Deletes a snippet from the database
 *
 * @since 2.0
 * @uses $wpdb To access the database
 * @uses get_snippets_table_name() To dynamically retrieve the name of the snippet table
 *
 * @param int $id The ID of the snippet to delete
 * @param boolean|null $multisite Delete from site-wide or network-wide table?
 */
function delete_snippet( $id, $multisite = null ) {
	global $wpdb;

	$wpdb->delete(
		get_snippets_table_name( $multisite ),
		array( 'id' => $id ),
		array( '%d' )
	);

	do_action( 'code_snippets/delete_snippet', $id, $multisite );
}

/**
 * Saves a snippet to the database.
 *
 * @since 2.0
 * @uses $wpdb To update/add the snippet to the database
 * @uses get_snippets_table_name() To dynamically retrieve the name of the snippet table
 *
 * @param object $snippet The snippet to add/update to the database
 * @param boolean|null $multisite Save the snippet to the site-wide or network-wide table?
 * @return int|boolean The ID of the snippet on success, false on failure
 */
function save_snippet( $snippet, $multisite = null ) {
	global $wpdb;

	$data = array();
	$table = get_snippets_table_name( $multisite );
	$snippet = escape_snippet_data( $snippet );

	foreach ( get_object_vars( $snippet ) as $field => $value ) {
		if ( 'id' === $field ) {
			continue;
		}

		if ( is_array( $value ) ) {
			$value = maybe_serialize( $value );
		}

		$data[ $field ] = $value;
	}

	if ( isset( $snippet->id ) && 0 !== $snippet->id ) {

		$wpdb->update( $table, $data, array( 'id' => $snippet->id ), null, array( '%d' ) );
		do_action( 'code_snippets/update_snippet', $snippet, $table );
		return $snippet->id;

	} else {

		$wpdb->insert( $table, $data, '%s' );
		do_action( 'code_snippets/create_snippet', $snippet, $table );
		return $wpdb->insert_id;
	}
}

/**
 * Imports snippets from an XML file
 *
 * @since 2.0
 * @uses save_snippet() To add the snippets to the database
 *
 * @param string $file The path to the XML file to import
 * @param boolean|null $multisite Import into network-wide table or site-wide table?
 * @return integer|boolean The number of snippets imported on success, false on failure
 */
function import_snippets( $file, $multisite = null ) {

	if ( ! file_exists( $file ) || ! is_file( $file ) ) {
		return false;
	}

	$dom = new DOMDocument( '1.0', get_bloginfo( 'charset' ) );
	$dom->load( $file );

	$snippets_xml = $dom->getElementsByTagName( 'snippet' );
	$fields = array( 'name', 'description', 'code', 'tags', 'scope' );
	$count = 0;

	/* Loop through all snippets */
	foreach ( $snippets_xml as $snippet_xml ) {
		$snippet = new stdClass;

		/* Build a snippet object by looping through the field names */
		foreach ( $fields as $field_name ) {

			/* Fetch the field element from the document */
			$field = $snippet_xml->getElementsByTagName( $field_name )->item( 0 );

			/* If the field element exists, add it to the snippet object */
			if ( isset( $field->nodeValue ) ) {
				$snippet->$field_name = $field->nodeValue;
			}
		}

		/* Save the snippet and increase the counter if successful */
		if ( save_snippet( $snippet, $multisite ) ) {
			$count += 1;
		}
	}

	do_action( 'code_snippets/import', $dom, $multisite );
	return $count;
}

/**
 * Exports snippets as an XML file
 *
 * @since 2.0
 * @uses Code_Snippets_Export To export selected snippets
 * @uses get_snippets_table_name() To dynamically retrieve the name of the snippet table
 *
 * @param array $ids The IDs of the snippets to export
 * @param boolean|null $multisite Is the snippet a network-wide or site-wide snippet?
 * @param string $format Export to xml or php?
 */
function export_snippets( $ids, $multisite = null, $format = 'xml' ) {
	$table = get_snippets_table_name( $multisite );

	if ( ! class_exists( 'Code_Snippets_Export' ) ) {
		require_once plugin_dir_path( CODE_SNIPPETS_FILE ) . 'includes/class-export.php';
	}

	$class = new Code_Snippets_Export( $ids, $table, $format );
	$class->do_export();
}

/**
 * Execute a snippet
 *
 * Code must NOT be escaped, as
 * it will be executed directly
 *
 * @since 2.0
 * @param string $code The snippet code to execute
 * @return mixed The result of the code execution
 */
function execute_snippet( $code ) {

	if ( empty( $code ) ) {
		return false;
	}

	ob_start();
	$result = eval( $code );
	$output = ob_get_contents();
	ob_end_clean();

	return $result;
}

/**
 * Run the active snippets
 *
 * @since 2.0
 * @return boolean true on success, false on failure
 */
function execute_active_snippets() {

	/* Bail early if safe mode is active */
	if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) {
		return false;
	}

	global $wpdb;

	if ( ! isset( $wpdb->snippets, $wpdb->ms_snippets ) ) {
		set_snippet_table_vars();
	}

	/* Check if the snippets table exists */
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->snippets'" ) === $wpdb->snippets ) {
		$sql = "SELECT code FROM {$wpdb->snippets} WHERE active=1";
	}

	/* Check if the multisite snippets table exists */
	if ( is_multisite() && $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->ms_snippets'" ) === $wpdb->ms_snippets ) {
		$sql = ( isset( $sql ) ? $sql . "\nUNION ALL\n" : '' );
		$sql .= "SELECT code FROM {$wpdb->ms_snippets} WHERE active=1";
	}

	if ( ! empty( $sql ) ) {
		$sql .= sprintf( ' AND (scope=0 OR scope=%d)', is_admin() ? 1 : 2 );

		/* Grab the active snippets from the database */
		$active_snippets = $wpdb->get_col( $sql );

		foreach ( $active_snippets as $snippet_id => $snippet_code ) {

			if ( apply_filters( 'code_snippets/allow_execute_snippet', true, $snippet_id ) ) {
				/* Execute the PHP code */
				execute_snippet( $snippet_code );
			}
		}

		return true;
	}

	/* If we're made it this far without returning true, assume failure */
	return false;
}
