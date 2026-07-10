<?php
/**
 * Functions to perform snippet operations
 *
 * @package Code_Snippets
 */

namespace Code_Snippets;

use Code_Snippets\Core\DB;
use Code_Snippets\Flat_Files\Snippet_Files;
use Exception;
use Code_Snippets\Model\Snippet;
use Code_Snippets\Utils\Validator;
use Throwable;
use function Code_Snippets\Utils\get_self_option;
use function Code_Snippets\Utils\update_self_option;

/**
 * Get the locked status for a snippet from wp_options.
 *
 * @param int       $snippet_id Snippet ID.
 * @param bool|null $network    Whether the snippet is network-wide (true) or site-wide (false).
 *
 * @return bool Whether the snippet is locked.
 */
function is_snippet_locked( int $snippet_id, ?bool $network = null ): bool {
	$network = DB::validate_network_param( $network );
	$locked_snippets = get_self_option( $network, 'code_snippets_locked', [] );

	return isset( $locked_snippets[ $snippet_id ] ) && $locked_snippets[ $snippet_id ];
}

/**
 * Set the locked status for a snippet in wp_options.
 *
 * @param int       $snippet_id Snippet ID.
 * @param bool      $locked     Whether the snippet should be locked.
 * @param bool|null $network    Whether the snippet is network-wide (true) or site-wide (false).
 *
 * @return void
 */
function set_snippet_locked( int $snippet_id, bool $locked, ?bool $network = null ): void {
	$network = DB::validate_network_param( $network );
	$locked_snippets = get_self_option( $network, 'code_snippets_locked', [] );

	if ( $locked ) {
		$locked_snippets[ $snippet_id ] = true;
	} else {
		unset( $locked_snippets[ $snippet_id ] );
	}

	update_self_option( $network, 'code_snippets_locked', $locked_snippets );
}

/**
 * Clean the cache where active snippets are stored.
 *
 * @param string              $table_name Snippets table name.
 * @param array<string>|false $scopes     List of scopes. Optional. If not provided, will flush the cache for all scopes.
 *
 * @return void
 */
function clean_active_snippets_cache( string $table_name, $scopes = false ) {
	$scope_groups = $scopes
		? [ $scopes ]
		: [
			[ 'head-content', 'body-content', 'footer-content' ],
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
 * @param array<string> $ids     The IDs of the snippets to fetch.
 * @param bool|null     $network Retrieve multisite-wide snippets (true) or site-wide snippets (false).
 *
 * @return Snippet[] List of Snippet objects.
 *
 * @since 2.0
 */
function get_snippets( array $ids = [], ?bool $network = null ): array {
	global $wpdb;

	// If only one ID has been passed in, defer to the get_snippet() function.
	$ids_count = count( $ids );
	if ( 1 === $ids_count ) {
		return [ get_snippet( $ids[0], $network ) ];
	}

	$network = DB::validate_network_param( $network );
	$table_name = code_snippets()->db->get_table_name( $network );

	$snippets = wp_cache_get( "all_snippets_$table_name", CACHE_GROUP );

	// Fetch all snippets from the database if none are cached.
	if ( ! is_array( $snippets ) ) {
		$results = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );

		$snippets = $results
			? array_map(
				function ( $snippet_data ) use ( $network ) {
					$snippet_data['network'] = $network;
					$snippet = new Snippet( $snippet_data );
					// Load locked from wp_options.
					if ( $snippet->id > 0 ) {
						$snippet->locked = is_snippet_locked( $snippet->id, $network );
					}
					return $snippet;
				},
				$results
			)
			: [];

		$snippets = apply_filters( 'code_snippets/get_snippets', $snippets, $network );

		if ( 0 === $ids_count ) {
			wp_cache_set( "all_snippets_$table_name", $snippets, CACHE_GROUP );
		}
	}

	// If a list of IDs are provided, narrow down the snippets list.
	if ( $ids_count > 0 ) {
		$ids = array_map( 'intval', $ids );
		return array_values(
			array_filter(
				$snippets,
				function ( Snippet $snippet ) use ( $ids ) {
					return in_array( $snippet->id, $ids, true );
				}
			)
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
	$all_tags = $wpdb->get_col( "SELECT tags FROM $table_name" );

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
 * @param int       $id      The ID of the snippet to retrieve. 0 to build a new snippet.
 * @param bool|null $network Retrieve a multisite-wide snippet (true) or site-wide snippet (false).
 *
 * @return ?Snippet A single snippet object.
 *
 * @since 2.0.0
 */
function get_snippet( int $id = 0, ?bool $network = null ): ?Snippet {
	global $wpdb;

	$id = absint( $id );
	$network = DB::validate_network_param( $network );
	$table_name = code_snippets()->db->get_table_name( $network );

	if ( 0 === $id ) {
		// If an invalid ID is provided, then return an empty snippet object.
		$snippet = new Snippet();

	} else {
		$cached_snippets = wp_cache_get( "all_snippets_$table_name", CACHE_GROUP );

		// Attempt to fetch snippet from the cached list, if it exists.
		if ( is_array( $cached_snippets ) ) {
			foreach ( $cached_snippets as $snippet ) {
				if ( $snippet->id === $id ) {
					return apply_filters( 'code_snippets/get_snippet', $snippet, $id, $network );
				}
			}
		}

		// Otherwise, retrieve the snippet from the database.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		$snippet_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
		$snippet = new Snippet( $snippet_data );
	}

	$snippet->network = $network;

	// Load locked from wp_options if snippet has an ID.
	if ( $snippet->id > 0 ) {
		$snippet->locked = is_snippet_locked( $snippet->id, $network );
	}

	return apply_filters( 'code_snippets/get_snippet', $snippet, $id, $network );
}


/**
 * Ensure the list of shared network snippets is correct if one has been recently active or deactivated.
 * Write operation.
 *
 * @access private
 *
 * @param Snippet[] $snippets Snippets that was recently updated.
 *
 * @return bool Whether an update was performed.
 */
function update_shared_network_snippets( array $snippets ): bool {
	$shared_ids = [];
	$unshared_ids = [];

	if ( ! is_multisite() ) {
		return false;
	}

	foreach ( $snippets as $snippet ) {
		if ( $snippet->network ) {
			if ( $snippet->shared_network ) {
				$shared_ids[] = $snippet->id;
			} else {
				$unshared_ids[] = $snippet->id;
			}
		}
	}

	if ( ! $shared_ids && ! $unshared_ids ) {
		return false;
	}

	$existing_shared_ids = get_site_option( 'shared_network_snippets', [] );
	$updated_shared_ids = array_values( array_diff( array_merge( $existing_shared_ids, $shared_ids ), $unshared_ids ) );

	if ( $existing_shared_ids === $updated_shared_ids ) {
		return false;
	}

	update_site_option( 'shared_network_snippets', $updated_shared_ids );

	// Deactivate the snippet on all sites if necessary.
	if ( $unshared_ids ) {
		$sites = get_sites( [ 'fields' => 'ids' ] );

		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			$active_shared_ids = get_option( 'active_shared_network_snippets' );

			if ( is_array( $active_shared_ids ) ) {
				$active_shared_ids = array_diff( $active_shared_ids, $unshared_ids );
				update_option( 'active_shared_network_snippets', $active_shared_ids );
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
 * @param int       $id      ID of the snippet to activate.
 * @param bool|null $network Whether the snippets are multisite-wide (true) or site-wide (false).
 *
 * @return Snippet|string Snippet object on success, error message on failure.
 * @since 2.0.0
 */
function activate_snippet( int $id, ?bool $network = null ) {
	global $wpdb;
	$network = DB::validate_network_param( $network );
	$table_name = code_snippets()->db->get_table_name( $network );

	// Retrieve the snippet code from the database for validation before activating.
	$snippet = get_snippet( $id, $network );

	if ( 0 === $snippet->id ) {
		// translators: %d: snippet identifier.
		return sprintf( __( 'Could not locate snippet with ID %d.', 'code-snippets' ), $id );
	}

	if ( 'php' === $snippet->type ) {
		$validator = new Validator( $snippet->code );
		if ( $validator->validate() ) {
			return __( 'Could not activate snippet: code did not pass validation.', 'code-snippets' );
		}
	}

	$result = $wpdb->update(
		$table_name,
		array( 'active' => '1' ),
		array( 'id' => $id ),
		array( '%d' ),
		array( '%d' )
	);

	if ( ! $result ) {
		return __( 'Could not activate snippet.', 'code-snippets' );
	}

	update_shared_network_snippets( [ $snippet ] );
	do_action( 'code_snippets/activate_snippet', $snippet, $network );
	clean_snippets_cache( $table_name );
	return $snippet;
}

/**
 * Activates multiple snippets.
 * Write operation.
 *
 * @param array<int> $ids     The IDs of the snippets to activate.
 * @param bool|null  $network Whether the snippets are multisite-wide (true) or site-wide (false).
 *
 * @return Snippet[]|null Snippets which were successfully activated, or null on failure.
 *
 * @since 2.0.0
 */
function activate_snippets( array $ids, ?bool $network = null ): ?array {
	global $wpdb;
	$network = DB::validate_network_param( $network );
	$table_name = code_snippets()->db->get_table_name( $network );

	$snippets = get_snippets( $ids, $network );

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
			$valid_snippets[] = $snippet;
		}
	}

	// If there are no valid snippets, then we're done.
	if ( ! $valid_ids ) {
		return null;
	}

	// Build a SQL query containing all IDs, as wpdb::update does not support OR conditionals.
	$ids_format = implode( ',', array_fill( 0, count( $valid_ids ), '%d' ) );

	// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	$rows_updated = $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET active = 1 WHERE id IN ($ids_format)", $valid_ids ) );

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
 * @param int       $id      ID of the snippet to deactivate.
 * @param bool|null $network Whether the snippets are multisite-wide (true) or site-wide (false).
 *
 * @return Snippet|null Snippet that was deactivated on success, or null on failure.
 *
 * @since 2.0.0
 */
function deactivate_snippet( int $id, ?bool $network = null ): ?Snippet {
	global $wpdb;
	$network = DB::validate_network_param( $network );
	$table = code_snippets()->db->get_table_name( $network );

	// Set the snippet to inactive.
	$result = $wpdb->update(
		$table,
		array( 'active' => '0' ),
		array( 'id' => $id ),
		array( '%d' ),
		array( '%d' )
	);

	if ( ! $result ) {
		return null;
	}

	// Update the recently active list.
	$snippet = get_snippet( $id );
	$recently_active = [ $id => time() ] + get_self_option( $network, 'recently_active_snippets', [] );
	update_self_option( $network, 'recently_active_snippets', $recently_active );

	update_shared_network_snippets( [ $snippet ] );
	do_action( 'code_snippets/deactivate_snippet', $id, $network );
	clean_snippets_cache( $table );

	return $snippet;
}

/**
 * Deletes a snippet from the database.
 * Write operation.
 *
 * @param int       $id      ID of the snippet to delete.
 * @param bool|null $network Delete from network-wide (true) or site-wide (false) table.
 *
 * @return bool Whether the snippet was deleted successfully.
 *
 * @since 2.0.0
 */
function delete_snippet( int $id, ?bool $network = null ): bool {
	global $wpdb;
	$network = DB::validate_network_param( $network );
	$table = code_snippets()->db->get_table_name( $network );

	$snippet = get_snippet( $id, $network );

	// Prevent deletion of locked snippets.
	if ( $snippet->locked ) {
		return false;
	}

	$result = $wpdb->delete(
		$table,
		array( 'id' => $id ),
		array( '%d' )
	);

	if ( $result ) {
		do_action( 'code_snippets/delete_snippet', $snippet, $network );
		clean_snippets_cache( $table );

		$recently_active = get_self_option( $network, 'recently_active_snippets', [] );

		if ( isset( $recently_active[ $id ] ) ) {
			unset( $recently_active[ $id ] );
			update_self_option( $network, 'recently_active_snippets', $recently_active );
		}
	}

	return (bool) $result;
}

/**
 * Trashes a snippet from the database.
 * Write operation.
 *
 * @param int       $id      ID of the snippet to trash.
 * @param bool|null $network Trash from network-wide (true) or site-wide (false) table.
 *
 * @return bool Whether the snippet was trashed successfully.
 *
 * @since 3.8.0
 */
function trash_snippet( int $id, ?bool $network = null ): bool {
	global $wpdb;
	$network = DB::validate_network_param( $network );
	$table = code_snippets()->db->get_table_name( $network );

	$snippet = get_snippet( $id, $network );

	// Prevent trashing of locked snippets.
	if ( $snippet->locked ) {
		return false;
	}

	$wpdb->update( $table, [ 'active' => '-1' ], [ 'id' => $id ], [ '%d' ] );

	do_action( 'code_snippets/trash_snippet', $snippet, $network );
	clean_snippets_cache( $table );

	return true;
}

/**
 * Restore a trashed snippet by setting its active status back to 0 (inactive).
 * Write operation.
 *
 * @param int       $id      Snippet ID to restore.
 * @param bool|null $network Whether the snippet is multisite-wide (true) or site-wide (false).
 *
 * @return bool Whether the restore was successful.
 *
 * @since 3.8.0
 */
function restore_snippet( int $id, ?bool $network = null ): bool {
	global $wpdb;
	$network = DB::validate_network_param( $network );
	$table = code_snippets()->db->get_table_name( $network );

	$result = $wpdb->update( $table, [ 'active' => '0' ], [ 'id' => $id ], [ '%d' ] );

	if ( $result ) {
		do_action( 'code_snippets/restore_snippet', $id, $network );
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
	$snippet->code_error_trace = null;

	if ( 'php' !== $snippet->type ) {
		return;
	}

	$validator = new Validator( $snippet->code );
	$result = $validator->validate();

	if ( $result ) {
		$snippet->code_error = [ $result['message'], $result['line'] ];
		$snippet->code_error_trace = ( new Exception() )->getTraceAsString();
	}

	if ( ! $snippet->code_error && 'single-use' !== $snippet->scope ) {
		$result = execute_snippet( $snippet->code, $snippet->id, true );

		if ( $result instanceof Throwable ) {
			$snippet->code_error = [
				ucfirst( rtrim( $result->getMessage(), '.' ) ) . '.',
				$result->getLine(),
			];
			$snippet->code_error_trace = $result->getTraceAsString();
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
function save_snippet( $snippet ): ?Snippet {
	global $wpdb;
	$table = code_snippets()->db->get_table_name( $snippet->network );

	if ( ! $snippet instanceof Snippet ) {
		$snippet = new Snippet( $snippet );
	}

	// Prevent modification of locked snippets (allow unlocking itself).
	if ( 0 !== $snippet->id ) {
		$old_snippet = get_snippet( $snippet->id, $snippet->network );

		if ( $old_snippet->locked && $snippet->locked ) {
			// If it was locked and the new request still wants it locked,
			// prevent changes to sensitive fields (code and name).
			$snippet->code = $old_snippet->code;
			$snippet->name = $old_snippet->name;
		}
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
	}

	// Increment the revision number unless revision = 1 or revision is not set.
	if ( $snippet->revision && $snippet->revision > 1 ) {
		$snippet->increment_revision();
	}

	// Increment the revision number unless revision = 1 or revision is not set.
	if ( $snippet->revision && $snippet->revision > 1 ) {
		$snippet->increment_revision();
	}

	// Shared network snippets are always considered inactive.
	$snippet->active = $snippet->active && ! $snippet->shared_network;

	// Build the list of data to insert (excluding locked, which is stored in wp_options).
	$data = [
		'name'         => $snippet->name,
		'description'  => $snippet->desc,
		'code'         => $snippet->code,
		'tags'         => $snippet->tags_list,
		'scope'        => $snippet->scope,
		'condition_id' => intval( $snippet->condition_id ),
		'priority'     => $snippet->priority,
		'active'       => intval( $snippet->active ),
		'modified'     => $snippet->modified,
		'revision'     => $snippet->revision,
		'cloud_id'     => $snippet->cloud_id_owner ? $snippet->cloud_id_owner : null,
	];

	// Create a new snippet if the ID is not set.
	if ( 0 === $snippet->id ) {
		$result = $wpdb->insert( $table, $data, '%s' );
		if ( false === $result ) {
			return null;
		}

		$snippet->id = $wpdb->insert_id;
		$updated = get_snippet( $snippet->id, $snippet->network );
		$updated->code_error = $snippet->code_error;
		$updated->code_error_trace = $snippet->code_error_trace;
		do_action( 'code_snippets/create_snippet', $updated, $table );

		if ( $updated->id > 0 ) {
			set_snippet_locked( $updated->id, $updated->locked, $updated->network );
		}
	} else {
		// Otherwise, update the snippet data.
		$existing = get_snippet( $snippet->id, $snippet->network );

		set_snippet_locked( $snippet->id, $snippet->locked, $snippet->network );
		$wpdb->update( $table, $data, [ 'id' => $snippet->id ], null, [ '%d' ] );

		$updated = get_snippet( $snippet->id, $snippet->network );
		$updated->code_error = $snippet->code_error;
		$updated->code_error_trace = $snippet->code_error_trace;

		do_action( 'code_snippets/update_snippet', $updated, $table, $existing, $snippet );

		if ( ! $updated->active && $existing->active ) {
			$recently_active = [ $updated->id => time() ] + get_self_option( $updated->network, 'recently_active_snippets', [] );
			update_self_option( $updated->network, 'recently_active_snippets', $recently_active );
		} elseif ( ! $updated->active ) {
			$recently_active = get_self_option( $updated->network, 'recently_active_snippets', [] );

			if ( isset( $recently_active[ $updated->id ] ) ) {
				unset( $recently_active[ $updated->id ] );
				update_self_option( $updated->network, 'recently_active_snippets', $recently_active );
			}
		}
	}

	update_shared_network_snippets( [ $updated ] );
	clean_snippets_cache( $table );
	return $updated;
}

/**
 * Execute a snippet.
 * Execute operation.
 *
 * Code must NOT be escaped, as it will be executed directly.
 *
 * @param string $code  Snippet code to execute.
 * @param int    $id    Snippet ID.
 * @param bool   $force Force snippet execution, even if save mode is active.
 *
 * @return Throwable|mixed Code error if encountered during execution, or result of snippet execution otherwise.
 *
 * @since        2.0.0
 * @noinspection PhpUndefinedConstantInspection
 *
 * phpcs:disable Squiz.PHP.Eval.Discouraged
 */
function execute_snippet( string $code, int $id = 0, bool $force = false ) {
	/**
	 * Do not continue if safe mode is active.
	 *
	 * @noinspection PhpUndefinedConstantInspection
	 */
	if ( empty( $code ) || ( ! $force && defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) ) {
		return false;
	}

	ob_start();

	try {
		$result = eval( $code );
	} catch ( Throwable $throwable ) {
		$result = $throwable;
	}

	ob_end_clean();

	do_action( 'code_snippets/after_execute_snippet', $code, $id, $result );
	return $result;
}

/**
 * Retrieve a single snippets from the database using its cloud ID.
 *
 * Read operation.
 *
 * @param string    $cloud_id  The Cloud ID of the snippet to retrieve.
 * @param bool|null $multisite Retrieve a multisite-wide snippet (true) or site-wide snippet (false).
 *
 * @return Snippet|null A single snippet object or null if no snippet was found.
 *
 * @since 3.5.0
 */
function get_snippet_by_cloud_id( string $cloud_id, ?bool $multisite = null ): ?Snippet {
	global $wpdb;

	$multisite = DB::validate_network_param( $multisite );
	$table_name = code_snippets()->db->get_table_name( $multisite );

	$cached_snippets = wp_cache_get( "all_snippets_$table_name", CACHE_GROUP );

	// Attempt to fetch snippet from the cached list, if it exists.
	if ( is_array( $cached_snippets ) ) {
		foreach ( $cached_snippets as $snippet ) {
			if ( $snippet->cloud_id === $cloud_id ) {
				return apply_filters( 'code_snippets/get_snippet_by_cloud_id', $snippet, $cloud_id, $multisite );
			}
		}
	}

	// Otherwise, search for the snippet from the database.
	$snippet_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE cloud_id = %s", $cloud_id ) ); // cache pass, db call ok.
	$snippet = $snippet_data ? new Snippet( $snippet_data ) : null;

	// Load locked from wp_options if snippet exists.
	if ( $snippet && $snippet->id > 0 ) {
		$snippet->network = $multisite;
		$snippet->locked = is_snippet_locked( $snippet->id, $multisite );
	}

	return apply_filters( 'code_snippets/get_snippet_by_cloud_id', $snippet, $cloud_id, $multisite );
}

/**
 * Update a snippet entry given a list of fields.
 * Write operation.
 *
 * @param int                  $snippet_id ID of the snippet to update.
 * @param array<string, mixed> $fields     An array of fields mapped to their values.
 * @param bool|null            $network    Update in network-wide (true) or site-wide (false) table.
 */
function update_snippet_fields( int $snippet_id, array $fields, ?bool $network = null ) {
	global $wpdb;

	$network = DB::validate_network_param( $network );
	$table = code_snippets()->db->get_table_name( $network );

	// Build a new snippet object for the validation.
	$snippet = new Snippet();
	$snippet->id = $snippet_id;

	// Validate fields through the snippet class and copy them into a clean array.
	$clean_fields = array();
	$locked_value = null;

	foreach ( $fields as $field => $value ) {
		// Handle locked separately (stored in wp_options).
		if ( 'locked' === $field ) {
			if ( $snippet->set_field( $field, $value ) ) {
				$locked_value = $snippet->$field;
			}
			continue;
		}

		if ( $snippet->set_field( $field, $value ) ) {
			$clean_fields[ $field ] = $snippet->$field;
		}
	}

	// Update the snippet in the database (excluding locked).
	if ( ! empty( $clean_fields ) ) {
		$wpdb->update( $table, $clean_fields, array( 'id' => $snippet->id ), null, array( '%d' ) );
	}

	// Save locked to wp_options if it was provided.
	if ( null !== $locked_value ) {
		set_snippet_locked( $snippet->id, $locked_value, $network );
	}

	clean_snippets_cache( $table );
	$updated = get_snippet( $snippet->id, $network );
	if ( $updated->id ) {
		do_action( 'code_snippets/update_snippet', $updated, $table );
	}
}

/**
 *  Evaluate a snippet by loading it from the filesystem.
 *
 * @param string $code  Snippet code.
 * @param string $file  Snippet filename.
 * @param int    $id    Snippet ID.
 * @param bool   $force Force snippet execution, even if save mode is active.
 *
 * @return bool|Exception|Throwable|null Code error if encountered during execution, or result of snippet execution otherwise.
 */
function execute_snippet_from_flat_file( string $code, string $file, int $id = 0, bool $force = false ) {
	if ( ! is_file( $file ) ) {
		execute_snippet( $code, $id, $force );
		return true;
	}

	/* @noinspection PhpUndefinedConstantInspection */
	if ( ! $force && defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) {
		return false;
	}

	ob_start();

	try {
		require_once $file;
		$result = null;
	} catch ( Throwable $throwable ) {
		$result = $throwable;
	}

	ob_end_clean();

	do_action( 'code_snippets/after_execute_snippet_from_flat_file', $file, $id );

	return $result ?? null;
}
