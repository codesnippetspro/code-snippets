<?php

/**
 * Functions used to manage the database tables
 */

/**
 * Register the snippet table names with WordPress
 *
 * @since 2.0
 * @uses $wpdb
 */
function set_snippet_table_vars() {
	global $wpdb;

	/* Register the snippet table names with WordPress */
	$wpdb->tables[]           = 'snippets';
	$wpdb->ms_global_tables[] = 'ms_snippets';

	/* Setup initial table variables */
	$wpdb->snippets           = $wpdb->prefix . 'snippets';
	$wpdb->ms_snippets        = $wpdb->base_prefix . 'ms_snippets';
}

/**
 * Return the appropriate snippet table name
 *
 * @since 2.0
 * @param string $multisite Retrieve the multisite table name or the site table name?
 * @return string The snippet table name
 */
function get_snippets_table_name( $multisite = false ) {
	global $wpdb;

	/* If multisite is not active, always return the site-wide table name */
	if ( ! is_multisite() ) {
		$multisire = false;
	}

	/* Retrieve the table name from $wpdb depending on the above conditionals */
	return ( $multisite ? $wpdb->ms_snippets : $wpdb->snippets );
}

/**
 * Create the snippet tables if they do not already exist
 *
 * @since     1.7.1
 * @staticvar boolean $tables_created       Used to check if we've already done this or not
 * @param     boolean $redo                 Skip the already-done-this check
 * @param     boolean $always_create_table  Always create the site-wide table if it doesn't exist
 */
function create_code_snippets_tables( $redo = false, $always_create_table = false ) {

	/* Bail early if we've done this already */
	if ( ! $redo && true === wp_cache_get( 'snippet_tables_created', 'code_snippets' ) ) {
		return;
	}

	global $wpdb;

	/* Set the table name variables if not yet defined */
	if ( ! isset( $wpdb->snippets, $wpdb->ms_snippets ) ) {
		set_snippets_table_vars();
	}

	/* Always create the network-wide snippet table */
	if ( is_multisite() ) {
		$this->create_table( $wpdb->ms_snippets );
	}

	/* Create the site-specific table if we're on the main site */
	if ( $always_create_table || is_main_site() ) {
		$this->create_table( $wpdb->snippets );
	}

	/* Set the flag so we don't have to do this again */
	wp_cache_set( 'snippet_tables_created', true, 'code_snippets' );
}

/**
 * Create a single snippet table
 * if one of the same name does not already exist
 *
 * @since  1.6
 * @access private
 *
 * @uses   dbDelta()               To add the table to the database
 *
 * @param  string  $table_name     The name of the table to create
 * @param  boolean $force_creation Skip the table exists check
 * @return void
 */
function create_code_snippets_table( $table_name, $force_creation = false ) {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	global $wpdb;

	if ( ! $force_creation && $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->snippets'" ) === $wpdb->snippets ) {
		return; // bail if the table already exists
	}

	/* Set the database charset */

	$charset_collate = '';

	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	}

	if ( ! empty( $wpdb->collate ) ) {
		$charset_collate .= " COLLATE $wpdb->collate";
	}

	/* Set the snippet data columns */

	$table_columns = apply_filters( 'code_snippets/database_table_columns', array(
		'name        tinytext not null',
		'description text',
		'code        longtext not null',
	) );

	$table_columns_sql = implode( ",\n", $table_columns ); // convert the array into SQL code

	/* Create the database table */

	$sql = "CREATE TABLE $table_name (
				id     bigint(20)  unsigned not null auto_increment,
				{$table_columns_sql},
				active tinyint(1)           not null default 0,
			PRIMARY KEY  (id),
				KEY id (id)

			) {$charset_collate};";

	dbDelta( apply_filters( 'code_snippets/table_sql', $sql ) );

	do_action( 'code_snippets/create_table', $table_name );
}
