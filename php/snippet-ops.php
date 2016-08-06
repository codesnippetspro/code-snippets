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
 * @uses get_snippets_table_name() to dynamically retrieve the snippet table name
 *
 * @param  array     $ids       The IDs of the snippets to fetch
 * @param  bool|null $multisite Retrieve multisite-wide or site-wide snippets?
 * @return array                An array of Snippet objects
 */
function get_snippets( array $ids = array(), $multisite = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;
	$table = get_snippets_table_name( $multisite );
	$sql = "SELECT * FROM $table";
	$ids_count = count( $ids );

	if ( 1 == $ids_count ) {
		return get_snippet( $ids[0] );
	}

	if ( $ids_count > 1 ) {
		$sql .= ' WHERE id IN (';
		$sql .= implode( ',', array_fill( 0, $ids_count, '%d' ) );
		$sql .= ')';

		$sql = $wpdb->prepare( $sql, $ids );
	}

	$snippets = $wpdb->get_results( $sql, ARRAY_A );

	/* Convert snippets to snippet objects */
	foreach ( $snippets as $index => $snippet ) {
		$snippet['network'] = $multisite;
		$snippets[ $index ] = new Snippet( $snippet );
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
	$table = get_snippets_table_name();
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
 * @uses get_snippets_table_name() to dynamically retrieve the snippet table name
 *
 * @param  int          $id        The ID of the snippet to retrieve. 0 to build a new snippet
 * @param  boolean|null $multisite Retrieve a multisite-wide or site-wide snippet?
 * @return Snippet                 A single snippet object
 */
function get_snippet( $id = 0, $multisite = null ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	$id = absint( $id );
	$table = get_snippets_table_name( $multisite );

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
	$table = get_snippets_table_name( $multisite );

	$wpdb->update(
		$table,
		array( 'active' => '1' ),
		array( 'id' => $id ),
		array( '%d' ),
		array( '%d' )
	);

	/* Remove snippet from shared network snippet list if it was Network Activated */
	if ( $table == $wpdb->ms_snippets && $shared_network_snippets = get_site_option( 'shared_network_snippets', false ) ) {
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
 * @uses $wpdb to access the database
 * @uses get_snippets_table_name() to dynamically retrieve the name of the snippet table
 *
 * @param int       $id        The ID of the snippet to delete
 * @param bool|null $multisite Delete from site-wide or network-wide table?
 */
function delete_snippet( $id, $multisite = null ) {
	/** @var wpdb $wpdb */
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
 *
 * @uses $wpdb to update/add the snippet to the database
 * @uses get_snippets_table_name() To dynamically retrieve the name of the snippet table
 *
 * @param  Snippet   $snippet   The snippet to add/update to the database
 * @return int                  The ID of the snippet
 */
function save_snippet( Snippet $snippet ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	$table = get_snippets_table_name( $snippet->network );

	/* Build array of data to insert */
	$data = array(
		'name' => $snippet->name,
		'description' => $snippet->desc,
		'code' => $snippet->code,
		'tags' => $snippet->tags_list,
		'scope' => $snippet->scope,
		'active' => intval( $snippet->active ),
	);

	/* Create a new snippet if the ID is not set */
	if ( 0 == $snippet->id ) {

		$wpdb->insert( $table, $data, '%s' );
		$snippet->id = $wpdb->insert_id;

		do_action( 'code_snippets/create_snippet', $snippet, $table );
	} else {

		/* Otherwise update the snippet data */
		$wpdb->update( $table, $data, array( 'id' => $snippet->id ), null, array( '%d' ) );

		do_action( 'code_snippets/update_snippet', $snippet, $table );
	}

	return $snippet->id;
}

/**
 * Imports snippets from an XML file
 *
 * @since 2.0
 *
 * @uses save_snippet() to add the snippets to the database
 *
 * @param  string     $file      The path to the XML file to import
 * @param  bool|null  $multisite Import into network-wide table or site-wide table?
 * @return array|bool            An array of imported snippet IDs on success, false on failure
 */
function import_snippets( $file, $multisite = null ) {

	if ( ! file_exists( $file ) || ! is_file( $file ) ) {
		return false;
	}

	$dom = new DOMDocument( '1.0', get_bloginfo( 'charset' ) );
	$dom->load( $file );

	$snippets_xml = $dom->getElementsByTagName( 'snippet' );
	$fields = array( 'name', 'description', 'desc', 'code', 'tags', 'scope' );
	$exported_snippets = array();

	/* Loop through all snippets */

	/** @var DOMElement $snippet_xml */
	foreach ( $snippets_xml as $snippet_xml ) {
		$snippet = new Snippet();
		$snippet->network = $multisite;

		/* Build a snippet object by looping through the field names */
		foreach ( $fields as $field_name ) {

			/* Fetch the field element from the document */
			$field = $snippet_xml->getElementsByTagName( $field_name )->item( 0 );

			/* If the field element exists, add it to the snippet object */
			if ( isset( $field->nodeValue ) ) {
				$snippet->$field_name = $field->nodeValue;
			}
		}

		/* Get scope from attribute */
		$scope = $snippet_xml->getAttribute( 'scope' );
		if ( ! empty( $scope ) ) {
			$snippet->scope = $scope;
		}

		/* Save the snippet and increase the counter if successful */
		if ( $snippet_id = save_snippet( $snippet ) ) {
			$exported_snippets[] = $snippet_id;
		}
	}

	do_action( 'code_snippets/import', $dom, $multisite );
	return $exported_snippets;
}

/**
 * Exports snippets as an XML file
 *
 * @since 2.0
 * @uses Code_Snippets_Export to export selected snippets
 * @uses get_snippets_table_name() to dynamically retrieve the name of the snippet table
 *
 * @param array     $ids       The IDs of the snippets to export
 * @param bool|null $multisite Is the snippet a network-wide or site-wide snippet?
 * @param string    $format    Export to xml or php?
 */
function export_snippets( $ids, $multisite = null, $format = 'xml' ) {
	$table = get_snippets_table_name( $multisite );

	if ( ! class_exists( 'Code_Snippets_Export' ) ) {
		require_once plugin_dir_path( CODE_SNIPPETS_FILE ) . 'php/class-export.php';
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
 *
 * @param  string $code The snippet code to execute
 * @param  int    $id   The snippet ID
 * @return mixed        The result of the code execution
 */
function execute_snippet( $code, $id = 0 ) {

	if ( empty( $code ) ) {
		return false;
	}

	ob_start();
	$result = eval( $code );
	ob_end_clean();

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
	if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) {
		return false;
	}

	if ( isset( $_GET['snippets-safe-mode'] ) && $_GET['snippets-safe-mode'] && code_snippets()->current_user_can() ) {
		return false;
	}

	/** @var wpdb $wpdb */
	global $wpdb;

	$current_scope = is_admin() ? 1 : 2;

	/* Check if the snippets tables exist */
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->snippets'" ) === $wpdb->snippets;
	$ms_table_exists = is_multisite() && $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->ms_snippets'" ) === $wpdb->ms_snippets;
	$sql = '';

	/* Fetch snippets from site table */
	if ( $table_exists ) {
		$sql = $wpdb->prepare( "SELECT id, code FROM {$wpdb->snippets} WHERE active=1 AND (scope=0 OR scope=%d)", $current_scope );
	}

	/* Fetch snippets from the network table */
	if ( $ms_table_exists ) {

		if ( ! empty( $sql ) ) {
			$sql .= ' UNION ALL ';
		}

		/* Only select snippets in the current scope */
		$sql .= $wpdb->prepare( "SELECT id, code FROM {$wpdb->ms_snippets} WHERE active=1 AND (scope=0 OR scope=%d)", $current_scope );

		/* Add shared network snippets */
		if ( $active_shared_ids = get_option( 'active_shared_network_snippets', false ) ) {
			$sql .= ' UNION ALL ';
			$sql .= $wpdb->prepare(
				sprintf(
					"SELECT id, code FROM {$wpdb->ms_snippets} WHERE id IN (%s)",
					implode( ',', array_fill( 0, count( $active_shared_ids ), '%d' ) )
				),
				$active_shared_ids
			);
		}
	}

	/* Return false if there is no query */
	if ( empty( $sql ) ) {
		return false;
	}

	/* Grab the snippets from the database */
	$active_snippets = $wpdb->get_results( $sql, OBJECT_K );

	/* Loop through the returned snippets and execute the PHP code */
	foreach ( $active_snippets as $snippet_id => $snippet ) {

		if ( apply_filters( 'code_snippets/allow_execute_snippet', true, $snippet_id ) ) {
			execute_snippet( $snippet->code, $snippet_id );
		}
	}

	return true;
}


if ( isset( $_REQUEST['snippets-safe-mode'] ) ) {

	/**
	 * Inject the safe mode query var into URLs
	 *
	 * @param $url
	 *
	 * @return string
	 */
	function code_snippets_safe_mode_query_var( $url ) {
		if ( isset( $_REQUEST['snippets-safe-mode'] ) ) {
			return add_query_arg( 'snippets-safe-mode', $_REQUEST['snippets-safe-mode'], $url );
		}
		return $url;
	}

	add_filter( 'home_url', 'code_snippets_safe_mode_query_var' );
	add_filter( 'admin_url', 'code_snippets_safe_mode_query_var' );
}

