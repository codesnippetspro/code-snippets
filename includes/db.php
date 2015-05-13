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
	$wpdb->tables[] = 'snippets';
	$wpdb->ms_global_tables[] = 'ms_snippets';

	/* Setup initial table variables */
	$wpdb->snippets = $wpdb->prefix . 'snippets';
	$wpdb->ms_snippets = $wpdb->base_prefix . 'ms_snippets';
}

/**
 * Return the appropriate snippet table name
 *
 * @since 2.0
 * @param string|boolean|null $multisite Retrieve the multisite table name or the site table name?
 * @return string The snippet table name
 */
function get_snippets_table_name( $multisite = null ) {
	global $wpdb;

	/* If $multisite is null, try to base it on the current admin page */
	if ( ! isset( $multisite ) && function_exists( 'get_current_screen' ) ) {
		$multisite = get_current_screen()->is_network;
	}

	/* If the first parameter is a string, assume it is a table name */
	elseif ( is_string( $multisite ) ) {
		return $multisite;
	}

	/* If multisite is not active, always return the site-wide table name */
	if ( ! is_multisite() ) {
		$multisite = false;
	}

	/* Retrieve the table name from $wpdb depending on the above conditionals */
	return ( $multisite ? $wpdb->ms_snippets : $wpdb->snippets );
}

/**
 * Create the snippet tables
 * This function will only execute once per page load, except if $redo is true
 *
 * @since 1.7.1
 * @param boolean $redo Skip the already-done-this check
 * @param boolean $upgrade Run the table creation code even if the table exists
 */
function create_code_snippets_tables( $upgrade = false ) {
	global $wpdb;

	/* Set the table name variables if not yet defined */
	if ( ! isset( $wpdb->snippets, $wpdb->ms_snippets ) ) {
		set_snippets_table_vars();
	}

	if ( is_multisite() ) {

		/* Check if the network snippets table exists */
		$ms_table_exists = ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->ms_snippets'" ) === $wpdb->ms_snippets );

		/* Create the network snippets table if it doesn't exist, or upgrade it */
		if ( $upgrade || ! $ms_table_exists ) {
			create_code_snippets_table( $wpdb->ms_snippets );
		}
	}

	/* Check if the site-specific table exists */
	$table_exists = ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->snippets'" ) === $wpdb->snippets );

	/* Create the table if it doesn't exist, or upgrade it */
	if ( $upgrade || ! $table_exists ) {
		create_code_snippets_table( $wpdb->snippets );
	}
}

/**
 * Create a single snippet table
 *
 * @since 1.6
 * @access private
 *
 * @uses dbDelta() To apply the SQL code
 * @param string $table_name The name of the table to create
 */
function create_code_snippets_table( $table_name ) {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	/* Set the database charset */
	$charset_collate = '';

	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	}

	if ( ! empty( $wpdb->collate ) ) {
		$charset_collate .= " COLLATE $wpdb->collate";
	}

	/* Create the database table */
	$sql = "CREATE TABLE $table_name (
				id          bigint(20) NOT NULL AUTO_INCREMENT,
				name        tinytext NOT NULL default '',
				description text NOT NULL default '',
				code        longtext NOT NULL default '',
				tags        longtext NOT NULL default '',
				scope       tinyint(1) NOT NULL default 0,
				active      tinyint(1) NOT NULL default 0,
				PRIMARY KEY  (id)
			) $charset_collate;";

	dbDelta( $sql );
	do_action( 'code_snippets/create_table', $table_name );
}
