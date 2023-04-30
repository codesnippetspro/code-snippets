<?php
/**
 * Functions to perform snippet operations
 *
 * @package Code_Snippets
 */

namespace Code_Snippets;

use ParseError;

/**
 * Clean the cache where active snippets are stored.
 *
 * @param string              $table_name Snippets table name.
 * @param array<string>|false $scopes     List of scopes. Optional. If not provided, will flush the cache for all scopes.
 *
 * @return void
 */
function clean_active_snippets_cache( string $table_name, $scopes = false ) {
	$scope_groups = $scopes ? [ $scopes ] : [
		[ 'head-content', 'footer-content' ],
		[ 'global', 'single-use', 'front-end' ],
		[ 'global', 'single-use', 'admin' ],
	];

	foreach ( $scope_groups as $scopes ) {
		wp_cache_delete( sprintf( 'active_snippets_%s_%s', sanitize_key( join( '_', $scopes ) ), $table_name ), CACHE_GROUP );
	}
}

/**
 * Flush all snippets caches for a given database table.
 *
 * @param string $table_name Snippets table name.
 *
 * @return void
 */
function clean_snippets_cache( string $table_name ) {
	wp_cache_delete( "all_snippet_tags_$table_name", CACHE_GROUP );
	wp_cache_delete( "all_snippets_$table_name", CACHE_GROUP );
	clean_active_snippets_cache( $table_name );
}

/**
 * Retrieve a list of snippets from the database.
 * Read operation.
 *
 * @param array<string>    $ids       The IDs of the snippets to fetch.
 * @param bool|null|string $multisite Retrieve multisite-wide snippets (true) or site-wide snippets (false).
 *
 * @return array<Snippet> List of Snippet objects.
 *
 * @since 2.0
 */
function get_snippets( array $ids = array(), $multisite = null ): array {
	global $wpdb;

	// If only one ID has been passed in, defer to the get_snippet() function.
	$ids_count = count( $ids );
	if ( 1 === $ids_count ) {
		return array( get_snippet( $ids[0], $multisite ) );
	}

	$db = code_snippets()->db;
	$table_name = $db->get_table_name( $multisite );
	$multisite = $db->ms_table === $table_name;

	$snippets = wp_cache_get( "all_snippets_$table_name", CACHE_GROUP );

	// Fetch all snippets from the database if none are cached.
	if ( ! is_array( $snippets ) ) {
		$results = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A ); // db call ok.

		$snippets = $results ?
			array_map(
				function ( $snippet_data ) use ( $multisite ) {
					$snippet_data['network'] = $multisite;
					return new Snippet( $snippet_data );
				},
				$results
			) :
			array();

		$snippets = apply_filters( 'code_snippets/get_snippets', $snippets, $multisite );

		if ( 0 === $ids_count ) {
			wp_cache_set( "all_snippets_$table_name", $snippets, CACHE_GROUP );
		}
	}

	// If a list of IDs are provided, narrow down the snippets list.
	if ( $ids_count > 0 ) {
		$ids = array_map( 'intval', $ids );
		return array_filter(
			$snippets,
			function ( Snippet $snippet ) use ( $ids ) {
				return in_array( $snippet->id, $ids, true );
			}
		);
	}

	return $snippets;
}

/**
 * Gets all used tags from the database.
 * Read operation.
 *
 * @since 2.0
 */
function get_all_snippet_tags() {
	global $wpdb;
	$table_name = code_snippets()->db->get_table_name();
	$cache_key = "all_snippet_tags_$table_name";

	$tags = wp_cache_get( $cache_key, CACHE_GROUP );
	if ( $tags ) {
		return $tags;
	}

	// Grab all tags from the database.
	$tags = array();
	$all_tags = $wpdb->get_col( "SELECT tags FROM $table_name" ); // db call ok.

	// Merge all tags into a single array.
	foreach ( $all_tags as $snippet_tags ) {
		$snippet_tags = code_snippets_build_tags_array( $snippet_tags );
		$tags = array_merge( $snippet_tags, $tags );
	}

	// Remove duplicate tags.
	$tags = array_values( array_unique( $tags, SORT_REGULAR ) );
	wp_cache_set( $cache_key, $tags, CACHE_GROUP );
	return $tags;
}

/**
 * Make sure that the tags are a valid array.
 *
 * @param array|string $tags The tags to convert into an array.
 *
 * @return array<string> The converted tags.
 *
 * @since 2.0.0
 */
function code_snippets_build_tags_array( $tags ): array {

	/* If there are no tags set, return an empty array. */
	if ( empty( $tags ) ) {
		return array();
	}

	/* If the tags are set as a string, convert them into an array. */
	if ( is_string( $tags ) ) {
		$tags = wp_strip_all_tags( $tags );
		$tags = str_replace( ', ', ',', $tags );
		$tags = explode( ',', $tags );
	}

	/* If we still don't have an array, just convert whatever we do have into one. */
	return (array) $tags;
}

/**
 * Retrieve a single snippets from the database.
 * Will return empty snippet object if no snippet ID is specified.
 * Read operation.
 *
 * @param integer             $id        The ID of the snippet to retrieve. 0 to build a new snippet.
 * @param boolean|string|null $multisite Retrieve a multisite-wide snippet (true) or site-wide snippet (false).
 *
 * @return Snippet A single snippet object.
 * @since 2.0.0
 */
function get_snippet( int $id = 0, $multisite = null ): Snippet {
	global $wpdb;

	$id = absint( $id );

	$db = code_snippets()->db;
	$table_name = $db->get_table_name( $multisite );
	$multisite = $db->ms_table === $multisite;

	if ( 0 === $id ) {
		// If an invalid ID is provided, then return an empty snippet object.
		$snippet = new Snippet();

	} else {
		$cached_snippets = wp_cache_get( "all_snippets_$table_name", CACHE_GROUP );

		// Attempt to fetch snippet from the cached list, if it exists.
		if ( is_array( $cached_snippets ) ) {
			foreach ( $cached_snippets as $snippet ) {
				if ( $snippet->id === $id ) {
					return apply_filters( 'code_snippets/get_snippet', $snippet, $id, $multisite );
				}
			}
		}

		// Otherwise, retrieve the snippet from the database.
		$snippet_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) ); // cache pass, db call ok.
		$snippet = new Snippet( $snippet_data );
	}

	$snippet->network = $multisite;
	return apply_filters( 'code_snippets/get_snippet', $snippet, $id, $multisite );
}

/**
 * Ensure the list of shared network snippets is correct if one has been recently activated or deactivated.
 * Write operation.
 *
 * @access private
 *
 * @param Snippet[] $snippets Snippets that was recently updated.
 *
 * @return boolean Whether an update was performed.
 */
function update_shared_network_snippets( array $snippets ): bool {
	$shared = [];
	$unshared = [];

	if ( ! is_multisite() ) {
		return false;
	}

	foreach ( $snippets as $snippet ) {
		if ( $snippet->shared_network ) {
			if ( $snippet->active ) {
				$shared[] = $snippet;
			} else {
				$unshared[] = $snippet;
			}
		}
	}

	if ( ! $shared && ! $unshared ) {
		return false;
	}

	$shared_snippets = get_site_option( 'shared_network_snippets', [] );
	$updated_shared_snippets = array_values( array_diff( array_merge( $shared_snippets, $shared ), $unshared ) );

	if ( $shared_snippets === $updated_shared_snippets ) {
		return false;
	}

	update_site_option( 'shared_network_snippets', $updated_shared_snippets );

	// Deactivate the snippet on all sites if necessary.
	if ( $unshared ) {
		$sites = get_sites( [ 'fields' => 'ids' ] );

		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			$active_shared_snippets = get_option( 'active_shared_network_snippets' );

			if ( is_array( $active_shared_snippets ) ) {
				$active_shared_snippets = array_diff( $active_shared_snippets, $unshared );
				update_option( 'active_shared_network_snippets', $active_shared_snippets );
			}

			clean_active_snippets_cache( code_snippets()->db->ms_table );
		}

		restore_current_blog();
	}

	return true;
}

/**
 * Activates a snippet.
 * Write operation.
 *
 * @param integer      $id        ID of the snippet to activate.
 * @param boolean|null $multisite Whether the snippets are multisite-wide (true) or site-wide (false).
 *
 * @return Snippet|string Snippet object on success, error message on failure.
 * @since 2.0.0
 */
function activate_snippet( int $id, bool $multisite = null ) {
	global $wpdb;
	$db = code_snippets()->db;
	$table_name = $db->get_table_name( $multisite );

	// Retrieve the snippet code from the database for validation before activating.
	$snippet = get_snippet( $id, $multisite );
	if ( ! $snippet || 0 === $snippet->id ) {
		// translators: %d: snippet identifier.
		return sprintf( __( 'Could not locate snippet with ID %d.', 'code-snippets' ), $id );
	}

	$validator = new Validator( $snippet->code );
	if ( $validator->validate() ) {
		return __( 'Could not activate snippet: code did not pass validation.', 'code-snippets' );
	}

	$result = $wpdb->update(
		$table_name,
		array( 'active' => '1' ),
		array( 'id' => $id ),
		array( '%d' ),
		array( '%d' )
	); // db call ok.

	if ( ! $result ) {
		return __( 'Could not activate snippet.', 'code-snippets' );
	}

	update_shared_network_snippets( [ $snippet ] );
	do_action( 'code_snippets/activate_snippet', $snippet );
	clean_snippets_cache( $table_name );
	return $snippet;
}

/**
 * Activates multiple snippets.
 * Write operation.
 *
 * @param array<integer> $ids       The IDs of the snippets to activate.
 * @param boolean|null   $multisite Whether the snippets are multisite-wide (true) or site-wide (false).
 *
 * @return Snippet[]|null Snippets which were successfully activated, or null on failure.
 *
 * @since 2.0.0
 */
function activate_snippets( array $ids, bool $multisite = null ) {
	global $wpdb;
	$db = code_snippets()->db;
	$table_name = $db->get_table_name( $multisite );
	$snippets = get_snippets( $ids, $multisite );

	if ( ! $snippets ) {
		return null;
	}

	// Loop through each snippet code and validate individually.
	$valid_ids = [];
	$valid_snippets = [];

	foreach ( $snippets as $snippet ) {
		$validator = new Validator( $snippet->code );
		$code_error = $validator->validate();

		if ( ! $code_error ) {
			$valid_ids[] = $snippet->id;
			$valid_snippets = $snippet;
		}
	}

	// If there are no valid snippets, then we're done.
	if ( ! $valid_ids ) {
		return null;
	}

	// Build a SQL query containing all IDs, as wpdb::update does not support OR conditionals.
	$ids_format = implode( ',', array_fill( 0, count( $valid_ids ), '%d' ) );

	// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	$rows_updated = $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET active = 1 WHERE id IN ($ids_format)", $valid_ids ) ); // db call ok.

	if ( ! $rows_updated ) {
		return null;
	}

	update_shared_network_snippets( $valid_snippets );
	do_action( 'code_snippets/activate_snippets', $valid_snippets, $table_name );
	clean_snippets_cache( $table_name );
	return $valid_ids;
}

/**
 * Deactivate a snippet.
 * Write operation.
 *
 * @param int       $id        ID of the snippet to deactivate.
 * @param bool|null $multisite Whether the snippets are multisite-wide (true) or site-wide (false).
 *
 * @return Snippet|null Snippet that was deactivated on success, or null on failure.
 *
 * @since 2.0.0
 */
function deactivate_snippet( int $id, bool $multisite = null ) {
	global $wpdb;
	$db = code_snippets()->db;
	$table = $db->get_table_name( $multisite );

	// Set the snippet to active.
	$result = $wpdb->update(
		$table,
		array( 'active' => '0' ),
		array( 'id' => $id ),
		array( '%d' ),
		array( '%d' )
	); // db call ok.

	if ( ! $result ) {
		return null;
	}

	// Update the recently active list.
	$snippet = get_snippet( $id );
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

	update_shared_network_snippets( [ $snippet ] );
	do_action( 'code_snippets/deactivate_snippet', $id, $multisite );
	clean_snippets_cache( $table );
	return $snippet;
}

/**
 * Deletes a snippet from the database.
 * Write operation.
 *
 * @param integer      $id        ID of the snippet to delete.
 * @param boolean|null $multisite Delete from network-wide (true) or site-wide (false) table.
 *
 * @return boolean Whether the operation completed successfully.
 *
 * @since 2.0.0
 */
function delete_snippet( int $id, bool $multisite = null ): bool {
	global $wpdb;
	$table = code_snippets()->db->get_table_name( $multisite );

	$result = $wpdb->delete(
		$table,
		array( 'id' => $id ),
		array( '%d' )
	); // db call ok.

	if ( $result ) {
		do_action( 'code_snippets/delete_snippet', $id, $multisite );
		clean_snippets_cache( $table );
	}

	return (bool) $result;
}


/**
 * Test snippet code for errors, augmenting the snippet object.
 *
 * @param Snippet $snippet Snippet object.
 */
function test_snippet_code( Snippet $snippet ) {
	$snippet->code_error = null;

	if ( 'php' !== $snippet->type ) {
		return;
	}

	$validator = new Validator( $snippet->code );
	$result = $validator->validate();

	if ( $result ) {
		$snippet->code_error = [ $result['message'], $result['line'] ];
	}

	if ( ! $snippet->code_error && 'single-use' !== $snippet->scope ) {
		$result = execute_snippet( $snippet->code, $snippet->id, true );

		if ( $result instanceof ParseError ) {
			$snippet->code_error = [
				ucfirst( rtrim( $result->getMessage(), '.' ) ) . '.',
				$result->getLine(),
			];
		}
	}
}

/**
 * Saves a snippet to the database.
 * Write operation.
 *
 * @param Snippet|array<string, mixed> $snippet The snippet to add/update to the database.
 *
 * @return Snippet|null Updated snippet.
 *
 * @since 2.0.0
 */
function save_snippet( $snippet ) {
	global $wpdb;
	$table = code_snippets()->db->get_table_name( $snippet->network );

	if ( ! $snippet instanceof Snippet ) {
		$snippet = new Snippet( $snippet );
	}

	// Update the last modification date if necessary.
	$snippet->update_modified();

	if ( 'php' === $snippet->type ) {
		// Remove tags from beginning and end of snippet.
		$snippet->code = preg_replace( '|^\s*<\?(php)?|', '', $snippet->code );
		$snippet->code = preg_replace( '|\?>\s*$|', '', $snippet->code );

		// Deactivate snippet if code contains errors.
		if ( $snippet->active && 'single-use' !== $snippet->scope ) {
			test_snippet_code( $snippet );

			if ( $snippet->code_error ) {
				$snippet->active = 0;
			}
		}
	} elseif ( 'cond' === $snippet->type ) {
		$snippet->code = wp_json_encode( $snippet->conditions );
	}

	// Build the list of data to insert. Shared network snippets are always considered inactive.
	$data = [
		'name'        => $snippet->name,
		'description' => $snippet->desc,
		'code'        => $snippet->code,
		'tags'        => $snippet->tags_list,
		'scope'       => $snippet->scope,
		'priority'    => $snippet->priority,
		'active'      => intval( $snippet->active && ! $snippet->shared_network ),
		'modified'    => $snippet->modified,
	];

	// Create a new snippet if the ID is not set.
	if ( 0 === $snippet->id ) {
		$result = $wpdb->insert( $table, $data, '%s' ); // db call ok.
		if ( false === $result ) {
			return null;
		}

		$snippet->id = $wpdb->insert_id;
		do_action( 'code_snippets/create_snippet', $snippet, $table );
	} else {

		// Otherwise, update the snippet data.
		$result = $wpdb->update( $table, $data, [ 'id' => $snippet->id ], null, [ '%d' ] ); // db call ok.
		if ( false === $result ) {
			return null;
		}

		do_action( 'code_snippets/update_snippet', $snippet, $table );
	}

	update_shared_network_snippets( [ $snippet ] );
	clean_snippets_cache( $table );
	return $snippet;
}

/**
 * Execute a snippet.
 * Execute operation.
 *
 * Code must NOT be escaped, as it will be executed directly.
 *
 * @param string  $code  Snippet code to execute.
 * @param integer $id    Snippet ID.
 * @param boolean $force Force snippet execution, even if save mode is active.
 *
 * @return ParseError|mixed Code error if encountered during execution, or result of snippet execution otherwise.
 *
 * @since 2.0.0
 */
function execute_snippet( string $code, int $id = 0, bool $force = false ) {
	if ( empty( $code ) || ! $force && defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) {
		return false;
	}

	ob_start();

	try {
		$result = eval( $code );
	} catch ( ParseError $parse_error ) {
		$result = $parse_error;
	}

	ob_end_clean();

	do_action( 'code_snippets/after_execute_snippet', $code, $id, $result );
	return $result;
}

/**
 * Run the active snippets.
 * Read-write-execute operation.
 *
 * @return bool true on success, false on failure.
 *
 * @since 2.0.0
 */
function execute_active_snippets(): bool {
	global $wpdb;

	// Bail early if safe mode is active.
	if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ||
	     ! apply_filters( 'code_snippets/execute_snippets', true ) ) {
		return false;
	}

	$db = code_snippets()->db;
	$scopes = array( 'global', 'single-use', is_admin() ? 'admin' : 'front-end' );
	$data = $db->fetch_active_snippets( $scopes );

	foreach ( $data as $table_name => $active_snippets ) {

		// Loop through the returned snippets and execute the PHP code.
		foreach ( $active_snippets as $snippet ) {
			$snippet_id = intval( $snippet['id'] );
			$code = $snippet['code'];

			// If the snippet is a single-use snippet, deactivate it before execution to ensure that the process always happens.
			if ( 'single-use' === $snippet['scope'] ) {
				$active_shared_ids = get_option( 'active_shared_network_snippets', array() );

				if ( $table_name === $db->ms_table && is_array( $active_shared_ids ) && in_array( $snippet_id, $active_shared_ids, true ) ) {
					unset( $active_shared_ids[ array_search( $snippet_id, $active_shared_ids, true ) ] );
					$active_shared_ids = array_values( $active_shared_ids );
					update_option( 'active_shared_network_snippets', $active_shared_ids );
					clean_active_snippets_cache( $table_name );
				} else {
					$wpdb->update(
						$table_name,
						array( 'active' => '0' ),
						array( 'id' => $snippet_id ),
						array( '%d' ),
						array( '%d' )
					); // db call ok.
					clean_snippets_cache( $table_name );
				}
			}

			if ( apply_filters( 'code_snippets/allow_execute_snippet', true, $snippet_id, $table_name ) ) {
				execute_snippet( $code, $snippet_id );
			}
		}
	}

	return true;
}
