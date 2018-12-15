<?php

/**
 * Functions to perform snippet operations
 *
 * @package Code_Snippets
 */

/**
 * Retrieve a list of snippets from the database
 *
 * @since 2.0
 *
 * @uses $wpdb to query the database for snippets
 * @uses code_snippets()->db->get_table_name() to dynamically retrieve the snippet table name
 *
 * @param  array     $ids       The IDs of the snippets to fetch
 * @param  bool|null $multisite Retrieve multisite-wide or site-wide snippets?
 * @return array                An array of Snippet objects
 */
function get_snippets( array $ids = array(), $multisite = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	$db = code_snippets()->db;
	$multisite = $db->validate_network_param( $multisite );
	$table = $db->get_table_name( $multisite );

	$ids_count = count( $ids );

	/* If only one ID has been passed in, defer to the get_snippet() function */
	if ( 1 == $ids_count ) {
		return array( get_snippet( $ids[0] ) );
	}

	/* Build a query containing the specified IDs if there are any */
	if ( $ids_count > 1 ) {
		$sql = sprintf(
			'SELECT * FROM %s WHERE id IN (%s);',
			$table,
			implode( ',', array_fill( 0, $ids_count, '%d' ) )
		);
		$sql = $wpdb->prepare( $sql, $ids );

	} else {
		$sql = "SELECT * FROM $table;";
	}

	/* Retrieve the results from the database */
	$snippets = $wpdb->get_results( $sql, ARRAY_A );

	/* Convert snippets to snippet objects */
	foreach ( $snippets as $index => $snippet ) {
		$snippet['network'] = $multisite;
		$snippets[ $index ] = new Code_Snippet( $snippet );
	}

	return apply_filters( 'code_snippets/get_snippets', $snippets, $multisite );
}

/**
 * Gets all of the used tags from the database
 * @since 2.0
 */
function get_all_snippet_tags() {
	/** @var wpdb $wpdb */
	global $wpdb;

	/* Grab all tags from the database */
	$tags = array();
	$table = code_snippets()->db->get_table_name();
	$all_tags = $wpdb->get_col( "SELECT `tags` FROM $table" );

	/* Merge all tags into a single array */
	foreach ( $all_tags as $snippet_tags ) {
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
 * @param  mixed $tags The tags to convert into an array
 * @return array       The converted tags
 */
function code_snippets_build_tags_array( $tags ) {

	/* If there are no tags set, return an empty array */
	if ( empty( $tags ) ) {
		return array();
	}

	/* If the tags are set as a string, convert them into an array */
	if ( is_string( $tags ) ) {
		$tags = strip_tags( $tags );
		$tags = str_replace( ', ', ',', $tags );
		$tags = explode( ',', $tags );
	}

	/* If we still don't have an array, just convert whatever we do have into one */
	return (array) $tags;
}

/**
 * Retrieve a single snippets from the database.
 * Will return empty snippet object if no snippet
 * ID is specified
 *
 * @since 2.0
 *
 * @uses $wpdb to query the database for snippets
 * @uses code_snippets()->db->get_table_name() to dynamically retrieve the snippet table name
 *
 * @param  int          $id        The ID of the snippet to retrieve. 0 to build a new snippet
 * @param  boolean|null $multisite Retrieve a multisite-wide or site-wide snippet?
 *
 * @return Code_Snippet                 A single snippet object
 */
function get_snippet( $id = 0, $multisite = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	$id = absint( $id );
	$table = code_snippets()->db->get_table_name( $multisite );

	if ( 0 !== $id ) {

		/* Retrieve the snippet from the database */
		$snippet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

		/* Unescape the snippet data, ready for use */
		$snippet = new Code_Snippet( $snippet );

	} else {

		/* Get an empty snippet object */
		$snippet = new Code_Snippet();
	}

	$snippet->network = $multisite;

	return apply_filters( 'code_snippets/get_snippet', $snippet, $id, $multisite );
}

/**
 * Activates a snippet
 *
 * @since 2.0
 *
 * @uses $wpdb to set the snippet's active status
 *
 * @param int       $id        The ID of the snippet to activate
 * @param bool|null $multisite Are the snippets multisite-wide or site-wide?
 */
function activate_snippet( $id, $multisite = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;
	$db = code_snippets()->db;
	$table = $db->get_table_name( $multisite );

	$wpdb->update(
		$table,
		array( 'active' => '1' ),
		array( 'id' => $id ),
		array( '%d' ),
		array( '%d' )
	);

	/* Remove snippet from shared network snippet list if it was Network Activated */
	if ( $table === $db && $shared_network_snippets = get_site_option( 'shared_network_snippets', false ) ) {
		$shared_network_snippets = array_diff( $shared_network_snippets, array( $id ) );
		update_site_option( 'shared_network_snippets', $shared_network_snippets );
	}

	do_action( 'code_snippets/activate_snippet', $id, $multisite );
}

/**
 * Deactivate a snippet
 *
 * @since 2.0
 *
 * @uses $wpdb to set the snippets' active status
 *
 * @param int       $id        The ID of the snippet to deactivate
 * @param bool|null $multisite Are the snippets multisite-wide or site-wide?
 */
function deactivate_snippet( $id, $multisite = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;
	$db = code_snippets()->db;
	$table = $db->get_table_name( $multisite );

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

	if ( $table === $db->table ) {

		update_option(
			'recently_activated_snippets',
			$recently_active + (array) get_option( 'recently_activated_snippets', array() )
		);

	} elseif ( $table === $db->ms_table ) {

		update_site_option(
			'recently_activated_snippets',
			$recently_active + (array) get_site_option( 'recently_activated_snippets', array() )
		);
	}

	do_action( 'code_snippets/deactivate_snippet', $id, $multisite );
}

/**
 * Deletes a snippet from the database
 *
 * @since 2.0
 * @uses $wpdb to access the database
 * @uses code_snippets()->db->get_table_name() to dynamically retrieve the name of the snippet table
 *
 * @param int       $id        The ID of the snippet to delete
 * @param bool|null $multisite Delete from site-wide or network-wide table?
 */
function delete_snippet( $id, $multisite = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	$wpdb->delete(
		code_snippets()->db->get_table_name( $multisite ),
		array( 'id' => $id ),
		array( '%d' )
	);

	do_action( 'code_snippets/delete_snippet', $id, $multisite );
}

/**
 * Saves a snippet to the database.
 *
 * @since 2.0
 *
 * @uses $wpdb to update/add the snippet to the database
 * @uses code_snippets()->db->get_table_name() To dynamically retrieve the name of the snippet table
 *
 * @param  Code_Snippet $snippet   The snippet to add/update to the database
 *
 * @return int                  The ID of the snippet
 */
function save_snippet( Code_Snippet $snippet ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	$table = code_snippets()->db->get_table_name( $snippet->network );

	/* Build array of data to insert */
	$data = array(
		'name' => $snippet->name,
		'description' => $snippet->desc,
		'code' => $snippet->code,
		'tags' => $snippet->tags_list,
		'scope' => $snippet->scope,
		'priority' => $snippet->priority,
		'active' => intval( $snippet->active ),
	);

	/* Create a new snippet if the ID is not set */
	if ( 0 == $snippet->id ) {

		$wpdb->insert( $table, $data, '%s' );
		$snippet->id = $wpdb->insert_id;

		do_action( 'code_snippets/create_snippet', $snippet->id, $table );
	} else {

		/* Otherwise update the snippet data */
		$wpdb->update( $table, $data, array( 'id' => $snippet->id ), null, array( '%d' ) );

		do_action( 'code_snippets/update_snippet', $snippet->id, $table );
	}

	return $snippet->id;
}

/**
 * Update a snippet entry given a list of fields
 *
 * @param int   $snippet_id The ID of the snippet to update
 * @param array $fields     An array of fields mapped to their values
 * @param bool  $network    Whether the snippet is network-wide or site-wide
 */
function update_snippet_fields( $snippet_id, $fields, $network = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	$table = code_snippets()->db->get_table_name( $network );

	/* Build a new snippet object for the validation */
	$snippet = new Code_Snippet();
	$snippet->id = $snippet_id;

	/* Validate fields through the snippet class and copy them into a clean array */
	$clean_fields = array();

	foreach ( $fields as $field => $value ) {

		if ( $snippet->set_field( $field, $value ) ) {
			$clean_fields[ $field ] = $snippet->$field;
		}
	}

	/* Update the snippet in the database */
	$wpdb->update( $table, $clean_fields, array( 'id' => $snippet->id ), null, array( '%d' ) );
	do_action( 'code_snippets/update_snippet', $snippet->id, $table );
}

/**
 * Execute a snippet
 *
 * Code must NOT be escaped, as
 * it will be executed directly
 *
 * @since 2.0
 *
 * @param string $code         The snippet code to execute
 * @param int    $id           The snippet ID
 * @param bool   $catch_output Whether to attempt to suppress the output of execution using buffers
 *
 * @return mixed        The result of the code execution
 */
function execute_snippet( $code, $id = 0, $catch_output = true ) {

	if ( empty( $code ) ) {
		return false;
	}

	if ( $catch_output ) {
		ob_start();
	}

	$result = eval( $code );

	if ( $catch_output ) {
		ob_end_clean();
	}

	do_action( 'code_snippets/after_execute_snippet', $id, $code, $result );

	return $result;
}

/**
 * Run the active snippets
 *
 * @since 2.0
 *
 * @return bool true on success, false on failure
 */
function execute_active_snippets() {

	/* Bail early if safe mode is active */
	if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE || ! apply_filters( 'code_snippets/execute_snippets', true ) ) {
		return false;
	}

	/** @var wpdb $wpdb */
	global $wpdb;
	$db = code_snippets()->db;

	$current_scope = is_admin() ? 'admin' : 'front-end';
	$queries = array();

	$sql_format = "SELECT id, code, scope FROM %s WHERE scope IN ('global', 'single-use', %%s) ";
	$order = 'ORDER BY priority ASC, id ASC';

	/* Fetch snippets from site table */
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$db->table'" ) === $db->table ) {
		$queries[ $db->table ] = $wpdb->prepare( sprintf( $sql_format, $db->table ) . 'AND active=1 ' . $order, $current_scope );
	}

	/* Fetch snippets from the network table */
	if ( is_multisite() && $wpdb->get_var( "SHOW TABLES LIKE '$db->ms_table'" ) === $db->ms_table ) {
		$active_shared_ids = get_option( 'active_shared_network_snippets', array() );

		/* If there are active shared snippets, include them in the query */
		if ( count( $active_shared_ids ) ) {

			/* Build a list of "%d, %d, %d ..." for every active network shared snippet we have */
			$active_shared_ids_format = implode( ',', array_fill( 0, count( $active_shared_ids ), '%d' ) );

			/* Include them in the query */
			$sql = sprintf( $sql_format, $db->ms_table ) . " AND (active=1 OR id IN ($active_shared_ids_format)) $order";

			/* Add the scope number to the IDs array, so that it is the first variable in the query */
			array_unshift( $active_shared_ids, $current_scope );
			$queries[ $db->ms_table ] = $wpdb->prepare( $sql, $active_shared_ids );

		} else {
			$sql = sprintf( $sql_format, $db->ms_table ) . 'AND active=1 ' . $order;
			$queries[ $db->ms_table ] = $wpdb->prepare( $sql, $current_scope );
		}
	}

	foreach ( $queries as $table_name => $query ) {
		$active_snippets = $wpdb->get_results( $query, ARRAY_A );

		/* Loop through the returned snippets and execute the PHP code */
		foreach ( $active_snippets as $snippet ) {
			$snippet_id = intval( $snippet['id'] );
			$code = $snippet['code'];

			if ( 'single-use' === $snippet['scope'] ) {
				$wpdb->update( $table_name, array( 'active' => '0' ), array( 'id' => $snippet_id ), array( '%d' ), array( '%d' ) );
			}

			if ( apply_filters( 'code_snippets/allow_execute_snippet', true, $snippet_id, $table_name ) ) {
				execute_snippet( $code, $snippet_id );
			}
		}
	}

	return true;
}


