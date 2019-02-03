<?php

namespace Code_Snippets;

/**
 * Functions used to manage the database tables
 *
 * @package Code_Snippets
 */
class DB {

	public $table;

	public $ms_table;

	/**
	 * Class constructor
	 */
	function __construct() {
		$this->set_table_vars();
	}

	/**
	 * Register the snippet table names with WordPress
	 *
	 * @since 2.0
	 * @uses  $wpdb
	 */
	function set_table_vars() {
		global $wpdb;

		$this->table = $wpdb->prefix . 'snippets';
		$this->ms_table = $wpdb->base_prefix . 'ms_snippets';

		/* Register the snippet table names with WordPress */
		$wpdb->tables[] = $wpdb->snippets = $this->table;
		$wpdb->ms_global_tables[] = $wpdb->ms_snippets = $this->ms_table;
	}

	/**
	 * Validate the multisite parameter of the get_table_name() function
	 *
	 * @param bool|null $network
	 *
	 * @return bool
	 */
	function validate_network_param( $network ) {

		/* If multisite is not active, then the parameter should always be false */
		if ( ! is_multisite() ) {
			return false;
		}

		/* If $multisite is null, try to base it on the current admin page */
		if ( is_null( $network ) && function_exists( 'is_network_admin' ) ) {
			$network = is_network_admin();
		}

		return $network;
	}

	/**
	 * Return the appropriate snippet table name
	 *
	 * @since 2.0
	 *
	 * @param string|bool|null $multisite Retrieve the multisite table name or the site table name?
	 *
	 * @return string The snippet table name
	 */
	function get_table_name( $multisite = null ) {

		/* If the first parameter is a string, assume it is a table name */
		if ( is_string( $multisite ) ) {
			return $multisite;
		}

		/* Validate the multisite parameter */
		$multisite = $this->validate_network_param( $multisite );

		/* Retrieve the table name from $wpdb depending on the value of $multisite */

		return ( $multisite ? $this->ms_table : $this->table );
	}

	/**
	 * Determine whether a database table exists
	 *
	 * @param string $table_name
	 *
	 * @return bool
	 */
	public static function table_exists( $table_name ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		return $wpdb->get_var( sprintf( "SHOW TABLES LIKE '%s'", $table_name ) ) === $table_name;
	}

	/**
	 * Create the snippet tables if they do not already exist
	 */
	public function create_missing_tables() {
		global $wpdb;

		/* Create the network snippets table if it doesn't exist */
		if ( is_multisite() && ! self::table_exists( $this->ms_table ) ) {
			$this->create_table( $this->ms_table );
		}

		/* Create the table if it doesn't exist */
		if ( ! self::table_exists( $this->table ) ) {
			$this->create_table( $this->table );
		}
	}

	/**
	 * Create the snippet tables, or upgrade them if they already exist
	 */
	public function create_or_upgrade_tables() {
		if ( is_multisite() ) {
			$this->create_table( $this->ms_table );
		}

		$this->create_table( $this->table );
	}

	/**
	 * Create a snippet table if it does not already exist
	 *
	 * @param $table_name
	 */
	public static function create_missing_table( $table_name ) {

		if ( self::table_exists( $table_name ) ) {
			return;
		}

		self::create_table( $table_name );
	}

	/**
	 * Create a single snippet table
	 *
	 * @since 1.6
	 * @uses  dbDelta() to apply the SQL code
	 *
	 * @param string $table_name The name of the table to create
	 *
	 * @return bool whether the table creation was successful
	 */
	public static function create_table( $table_name ) {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		/* Create the database table */
		$sql = "CREATE TABLE $table_name (
				id          BIGINT(20)  NOT NULL AUTO_INCREMENT,
				name        TINYTEXT    NOT NULL DEFAULT '',
				description TEXT        NOT NULL DEFAULT '',
				code        LONGTEXT    NOT NULL DEFAULT '',
				tags        LONGTEXT    NOT NULL DEFAULT '',
				scope       VARCHAR(15) NOT NULL DEFAULT 'global',
				priority    SMALLINT    NOT NULL DEFAULT 10,
				active      TINYINT(1)  NOT NULL DEFAULT 0,
				PRIMARY KEY  (id)
			) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$success = empty( $wpdb->last_error );

		if ( $success ) {
			do_action( 'code_snippets/create_table', $table_name );
		}

		return $success;
	}

	/**
	 * @param int    $count
	 * @param string $placeholder
	 *
	 * @return string
	 */
	private static function build_format_list( $count, $placeholder = '%s' ) {
		return implode( ',', array_fill( 0, $count, $placeholder ) );
	}

	/**
	 * Generate the SQL for fetching active snippets from the database
	 *
	 * @param array|string $scopes      List of scopes to retrieve in
	 * @param string       $select_list List of table columns in SQL format
	 *
	 * @return array List of SQL queries
	 */
	public function fetch_active_snippets( $scopes, $select_list = 'id, code' ) {
		/** @var wpdb $wpdb */
		global $wpdb;
		$db = code_snippets()->db;

		$queries = array();

		if ( ! is_array( $scopes ) ) {
			$scopes = array( $scopes );
		}

		$scopes_format = self::build_format_list( count( $scopes ) );
		$select = 'SELECT id, code, scope FROM';
		$where = "WHERE scope IN ($scopes_format)";
		$order = 'ORDER BY priority ASC, id ASC';

		/* Fetch snippets from site table */
		if ( DB::table_exists( $db->table ) ) {
			$queries[ $db->table ] = $wpdb->prepare( "$select {$db->table} $where AND active=1 $order", $scopes );
		}

		/* Fetch snippets from the network table */
		if ( is_multisite() && DB::table_exists( $db->ms_table ) ) {
			$active_shared_ids = get_option( 'active_shared_network_snippets', array() );

			/* If there are active shared snippets, include them in the query */
			if ( is_array( $active_shared_ids ) && count( $active_shared_ids ) ) {
				$ids_format = self::build_format_list( count( $active_shared_ids ), '%d' );
				$sql = "$select $db->ms_table $where AND (active=1 OR id IN ($ids_format)) $order";

				$queries[ $db->ms_table ] = $wpdb->prepare( $sql, array_merge( $scopes, $active_shared_ids ) );

			} else {
				$queries[ $db->ms_table ] = $wpdb->prepare( "$select $db->ms_table $where AND active=1 $order", $scopes );
			}
		}

		$active_snippets = array();

		foreach ( $queries as $table => $query ) {
			$results = $wpdb->get_results( $query, 'ARRAY_A' );
			$active_snippets[ $table ] = is_array( $results ) ? $results : array();
		}

		return $active_snippets;
	}
}
