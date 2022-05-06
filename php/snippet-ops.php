<?php
/**
 * Functions to perform snippet operations
 *
 * @package Code_Snippets
 */

namespace Code_Snippets;

/**
 * Retrieve a list of snippets from the database.
 *
 * @param array     $ids       The IDs of the snippets to fetch
 * @param bool|null $multisite Retrieve multisite-wide snippets (true) or site-wide snippets (false).
 *
 * @param array     $args        {
 *                               Optional. Arguments to specify which sorts of snippets to retrieve.
 *
 * @type bool       $active_only Whether to only fetch active snippets. Default false (will fetch both active and inactive snippets).
 * @type int        $limit       Limit the number of retrieved snippets. Default 0, which will not impose a limit on the results.
 * @type string     $orderby     Sort the retrieved snippets by a particular field. Example fields include 'id', 'priority', and 'name'.
 * @type string     $order       Designates ascending or descending order of snippets. Default 'DESC'. Accepts 'ASC', 'DESC'.
 * }
 *
 * @return array An array of Snippet objects.
 *
 * @uses  $wpdb to query the database for snippets
 * @uses  code_snippets()->db->get_table_name() to dynamically retrieve the snippet table name
 *
 * @since 2.0
 */
function get_snippets( array $ids = array(), $multisite = null, array $args = array() ) {
	global $wpdb;

	/* If only one ID has been passed in, defer to the get_snippet() function */
	$ids_count = count( $ids );
	if ( 1 === $ids_count ) {
		return array( get_snippet( $ids[0] ) );
	}

	$searchable_columns = array( 'name', 'description', 'code', 'tags' );

	$args = wp_parse_args(
		$args,
		array(
			'active_only' => false,
			'limit'       => 0,
			'orderby'     => '',
			'order'       => 'desc',
			'search'      => '',
			'searchby'    => $searchable_columns,
		)
	);

	$db = code_snippets()->db;
	$multisite = $db->validate_network_param( $multisite );
	$table = $db->get_table_name( $multisite );

	if ( 0 === $ids_count && $snippets = wp_cache_get( $multisite ? 'all_ms_snippets' : 'all_snippets', 'code_snippets' ) ) {
		return $snippets;
	}

	$sql = "SELECT * FROM $table WHERE 1=1";
	$sql_params = array();

	/* Build a query for specific search terms */
	if ( ! empty( $args['search'] ) && ! empty( $args['searchby'] ) ) {
		$search = array();
		foreach ( $args['searchby'] as $column ) {
			if ( in_array( $column, $searchable_columns, true ) ) {
				$search[] = "$column LIKE %s";
				$sql_params[] = sprintf( '%%%s%%', $wpdb->esc_like( $args['search'] ) );
			}
		}
		$sql .= sprintf( ' AND ( %s )', implode( ' OR ', $search ) );
	}

	/* Build a query containing the specified IDs if there are any */
	if ( $ids_count > 1 ) {
		$sql .= sprintf( ' AND id IN (%s)', implode( ',', array_fill( 0, $ids_count, '%d' ) ) );
		$sql_params = array_merge( $sql_params, array_values( $ids ) );
	}

	/* Restrict the active status of retrieved snippets if requested */
	if ( $args['active_only'] ) {
		$sql .= ' AND active=1';
	}

	/* Apply custom ordering if requested */
	if ( $args['orderby'] ) {
		$order_dir = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$sql .= " ORDER BY %s $order_dir";
		$sql_params[] = $args['orderby'];
	}

	/* Limit the number of retrieved snippets if requested */
	if ( intval( $args['limit'] ) > 0 ) {
		$sql .= ' LIMIT %d';
		$sql_params[] = intval( $args['limit'] );
	}

	/* Retrieve the results from the database */
	if ( ! empty( $sql_params ) ) {
		$sql = $wpdb->prepare( $sql, $sql_params );
	}
	$snippets = $wpdb->get_results( $sql, ARRAY_A );

	if ( $snippets ) {
		/* Convert snippets to snippet objects */
		foreach ( $snippets as $index => $snippet ) {
			$snippet['network'] = $multisite;
			$snippets[ $index ] = new Snippet( $snippet );
		}
	} else {
		$snippets = array();
	}

	$snippets = apply_filters( 'code_snippets/get_snippets', $snippets, $multisite );

	if ( 0 === $ids_count ) {
		wp_cache_set( $multisite ? 'all_ms_snippets' : 'all_snippets', $snippets, 'code_snippets' );
	}

	return $snippets;
}

/**
 * Gets all used tags from the database.
 *
 * @since 2.0
 */
function get_all_snippet_tags() {
	global $wpdb;

	if ( $tags = wp_cache_get( 'all_snippet_tags', 'code_snippets' ) ) {
		return $tags;
	}

	/* Grab all tags from the database */
	$tags = array();
	$table = code_snippets()->db->get_table_name();
	$all_tags = $wpdb->get_col( sprintf( 'SELECT tags FROM %s', $table ) );

	/* Merge all tags into a single array */
	foreach ( $all_tags as $snippet_tags ) {
		$snippet_tags = code_snippets_build_tags_array( $snippet_tags );
		$tags = array_merge( $snippet_tags, $tags );
	}

	/* Remove duplicate tags */
	$tags = array_values( array_unique( $tags, SORT_REGULAR ) );
	wp_cache_set( 'all_snippet_tags', $tags, 'code_snippets' );
	return $tags;
}

/**
 * Make sure that the tags are a valid array.
 *
 * @param mixed $tags The tags to convert into an array.
 *
 * @return array The converted tags.
 *
 * @since 2.0.0
 */
function code_snippets_build_tags_array( $tags ) {

	/* If there are no tags set, return an empty array */
	if ( empty( $tags ) ) {
		return array();
	}

	/* If the tags are set as a string, convert them into an array */
	if ( is_string( $tags ) ) {
		$tags = wp_strip_all_tags( $tags );
		$tags = str_replace( ', ', ',', $tags );
		$tags = explode( ',', $tags );
	}

	/* If we still don't have an array, just convert whatever we do have into one */

	return (array) $tags;
}

/**
 * Retrieve a single snippets from the database.
 * Will return empty snippet object if no snippet ID is specified.
 *
 * @param int          $id        The ID of the snippet to retrieve. 0 to build a new snippet.
 * @param boolean|null $multisite Retrieve a multisite-wide snippet (true) or site-wide snippet (false).
 *
 * @return Snippet A single snippet object.
 * @since 2.0.0
 */
function get_snippet( $id = 0, $multisite = null ) {
	global $wpdb;

	$id = absint( $id );
	$multisite = code_snippets()->db->validate_network_param( $multisite );
	$table = code_snippets()->db->get_table_name( $multisite );

	$cache_key = ( $multisite ? 'ms_' : '' ) . 'snippet_' . $id;

	if ( $snippet = wp_cache_get( $cache_key, 'code_snippets' ) ) {
		return $snippet;
	}

	if ( 0 !== $id ) {

		/* Retrieve the snippet from the database */
		$snippet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

		/* Unescape the snippet data, ready for use */
		$snippet = new Snippet( $snippet );

	} else {

		/* Get an empty snippet object */
		$snippet = new Snippet();
	}

	$snippet->network = $multisite;

	$snippet = apply_filters( 'code_snippets/get_snippet', $snippet, $id, $multisite );
	wp_cache_set( $cache_key, $snippet, 'code_snippets' );
	return $snippet;
}

/**
 * Activates a snippet
 *
 * @param int       $id        ID of the snippet to activate.
 * @param bool|null $multisite Whether the snippets are multisite-wide (true) or site-wide (false).
 *
 * @return boolean
 * @since 2.0.0
 */
function activate_snippet( $id, $multisite = null ) {
	global $wpdb;
	$db = code_snippets()->db;
	$table = $db->get_table_name( $multisite );

	/** @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching */
	$wpdb->update(
		$table,
		array( 'active' => '1' ),
		array( 'id' => $id ),
		array( '%d' ),
		array( '%d' )
	);

	/* Retrieve the snippet code from the database for validation before activating */
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT code FROM $table WHERE id = %d;", $id ) );
	if ( ! $row ) {
		return false;
	}

	$validator = new Validator( $row->code );
	if ( $validator->validate() ) {
		return false;
	}

	$wpdb->update( $table, array( 'active' => '1' ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );

	/* Remove snippet from shared network snippet list if it was Network Activated */
	if ( $table === $db->ms_table ) {
		$shared_network_snippets = get_site_option( 'shared_network_snippets' );
		if ( $shared_network_snippets ) {
			$shared_network_snippets = array_diff( $shared_network_snippets, array( $id ) );
			update_site_option( 'shared_network_snippets', $shared_network_snippets );
		}
	}

	do_action( 'code_snippets/activate_snippet', $id, $multisite );
	return true;
}

/**
 * Activates multiple snippets.
 *
 * @param array     $ids       The IDs of the snippets to activate.
 * @param bool|null $multisite Whether the snippets are multisite-wide (true) or site-wide (false).
 *
 * @return array The IDs of the snippets which were successfully activated.
 *
 * @since 2.0.0
 */
function activate_snippets( array $ids, $multisite = null ) {
	global $wpdb;
	$db = code_snippets()->db;
	$table = $db->get_table_name( $multisite );

	/* Build SQL query containing all the provided snippet IDs */
	$ids_format = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, code FROM $table WHERE id IN ($ids_format)", $ids ) );

	if ( ! $rows ) {
		return array();
	}

	/* Loop through each snippet code and validate individually */
	$valid_ids = array();

	foreach ( $rows as $row ) {
		$validator = new Validator( $row->code );
		$code_error = $validator->validate();

		if ( ! $code_error ) {
			$valid_ids[] = $row->id;
		}
	}

	/* If there are no valid snippets, then we're done */
	if ( ! $valid_ids ) {
		return $valid_ids;
	}

	/* Build SQL query containing all the valid snippet IDs and activate the valid snippets */
	$ids_format = implode( ',', array_fill( 0, count( $valid_ids ), '%d' ) );
	$wpdb->query( $wpdb->prepare( "UPDATE $table SET active = 1 WHERE id IN ($ids_format)", $valid_ids ) );

	/* Remove snippet from shared network snippet list if it was Network Activated */
	if ( $table === $db->ms_table ) {
		$shared_network_snippets = get_site_option( 'shared_network_snippets' );
		if ( $shared_network_snippets ) {
			$shared_network_snippets = array_diff( $shared_network_snippets, $valid_ids );
			update_site_option( 'shared_network_snippets', $shared_network_snippets );
		}
	}

	do_action( 'code_snippets/activate_snippets', $valid_ids, $multisite );
	return $valid_ids;
}

/**
 * Deactivate a snippet
 *
 * @param int       $id        ID of the snippet to deactivate.
 * @param bool|null $multisite Whether the snippets are multisite-wide (true) or site-wide (false).
 *
 * @uses  $wpdb to set the snippets' active status
 * @since 2.0.0
 */
function deactivate_snippet( $id, $multisite = null ) {
	global $wpdb;
	$db = code_snippets()->db;
	$table = $db->get_table_name( $multisite );

	/* Set the snippet to active */

	/** @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching */
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
 * @param int       $id        ID of the snippet to delete.
 * @param bool|null $multisite Delete from network-wide (true) or site-wide (false) table.
 * @since 2.0.0
 */
function delete_snippet( $id, $multisite = null ) {
	global $wpdb;

	/** @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching */
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
 * @param Snippet $snippet The snippet to add/update to the database.
 *
 * @return int ID of the snippet
 *
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * @since 2.0.0
 */
function save_snippet( Snippet $snippet ) {
	global $wpdb;

	$table = code_snippets()->db->get_table_name( $snippet->network );

	/* Update the last modification date and the creation date if necessary */
	$snippet->update_modified();

	/* Build array of data to insert */
	$data = array(
		'name'        => $snippet->name,
		'description' => $snippet->desc,
		'code'        => $snippet->code,
		'tags'        => $snippet->tags_list,
		'scope'       => $snippet->scope,
		'priority'    => $snippet->priority,
		'active'      => intval( $snippet->active ),
		'modified'    => $snippet->modified,
	);

	/* Create a new snippet if the ID is not set */
	if ( 0 === $snippet->id ) {
		$wpdb->insert( $table, $data, '%s' );
		$snippet->id = $wpdb->insert_id;

		do_action( 'code_snippets/create_snippet', $snippet->id, $table );
	} else {

		/* Otherwise, update the snippet data */
		$wpdb->update( $table, $data, array( 'id' => $snippet->id ), null, array( '%d' ) );

		do_action( 'code_snippets/update_snippet', $snippet->id, $table );
	}

	return $snippet->id;
}

/**
 * Update a snippet entry given a list of fields
 *
 * @param int       $snippet_id ID of the snippet to update.
 * @param array     $fields     An array of fields mapped to their values.
 * @param bool|null $network    Delete from network-wide (true) or site-wide (false) table.
 */
function update_snippet_fields( $snippet_id, $fields, $network = null ) {
	global $wpdb;

	$table = code_snippets()->db->get_table_name( $network );

	/* Build a new snippet object for the validation */
	$snippet = new Snippet();
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
 * Code must NOT be escaped, as it will be executed directly.
 *
 * @param string $code         Snippet code to execute.
 * @param int    $id           Snippet ID.
 * @param bool   $catch_output Whether to attempt to suppress the output of execution using buffers.
 *
 * @return mixed Result of the code execution
 * @since 2.0.0
 */
function execute_snippet( $code, $id = 0, $catch_output = true ) {

	if ( empty( $code ) || defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) {
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
 * @return bool true on success, false on failure
 *
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * @since 2.0.0
 */
function execute_active_snippets() {
	global $wpdb;

	/* Bail early if safe mode is active */
	if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE || ! apply_filters( 'code_snippets/execute_snippets', true ) ) {
		return false;
	}

	$db = code_snippets()->db;
	$scopes = array( 'global', 'single-use', is_admin() ? 'admin' : 'front-end' );
	/** @noinspection PhpRedundantOptionalArgumentInspection */
	$data = $db->fetch_active_snippets( $scopes, 'id, code, scope' );

	foreach ( $data as $table_name => $active_snippets ) {

		/* Loop through the returned snippets and execute the PHP code */
		foreach ( $active_snippets as $snippet ) {
			$snippet_id = intval( $snippet['id'] );
			$code = $snippet['code'];

			// if the snippet is a single-use snippet, deactivate it before execution to ensure that the process always happens
			if ( 'single-use' === $snippet['scope'] ) {
				if ( $table_name === $db->ms_table && isset( $active_shared_ids ) && in_array( $snippet_id, $active_shared_ids, true ) ) {
					unset( $active_shared_ids[ array_search( $snippet_id, $active_shared_ids, true ) ] );
					$active_shared_ids = array_values( $active_shared_ids );
					update_option( 'active_shared_network_snippets', $active_shared_ids );
				} else {
					$wpdb->update( $table_name, array( 'active' => '0' ), array( 'id' => $snippet_id ), array( '%d' ), array( '%d' ) );
				}
			}

			if ( apply_filters( 'code_snippets/allow_execute_snippet', true, $snippet_id, $table_name ) ) {
				execute_snippet( $code, $snippet_id );
			}
		}
	}

	return true;
}
