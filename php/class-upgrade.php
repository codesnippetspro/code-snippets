<?php

/**
 * Manages upgrade tasks such as deleting and updating options
 */
class Code_Snippets_Upgrade {

	/**
	 * Instance of database class
	 * @var Code_Snippets_DB
	 */
	private $db;

	/**
	 * Class constructor
	 *
	 * @param Code_Snippets_DB $db Instance of database class
	 */
	public function __construct( Code_Snippets_DB $db ) {
		$this->db = $db;
	}

	/**
	 * Run the upgrade functions
	 */
	public function run() {
		$this->do_site_upgrades();
		$this->do_multisite_upgrades();
	}

	/**
	 * Perform upgrades for the current site
	 */
	private function do_site_upgrades() {

		/* Get the current plugin version from the database */
		$prev_version = get_option( 'code_snippets_version' );

		/* Check if we have upgraded from an older version */
		if ( ! version_compare( $prev_version, CODE_SNIPPETS_VERSION, '<' ) ) {
			return;
		}

		/* Upgrade the database tables */
		$this->db->create_table( $this->db->table );

		/* Update the plugin version stored in the database */
		update_option( 'code_snippets_version', CODE_SNIPPETS_VERSION );

		/* Update the scope column of the database */
		if ( version_compare( $prev_version, '2.10.0', '<' ) ) {
			$this->migrate_scope_data( $this->db->table );
		}

		/* Custom capabilities were removed after version 2.9.5 */
		if ( version_compare( $prev_version, '2.9.5', '<=' ) ) {
			$role = get_role( apply_filters( 'code_snippets_role', 'administrator' ) );
			$role->remove_cap( apply_filters( 'code_snippets_cap', 'manage_snippets' ) );
		}

		if ( false === $prev_version ) {
			$this->db->create_sample_content();
		}
	}

	/**
	 * Perform multisite-only upgrades
	 */
	private function do_multisite_upgrades() {

		if ( ! is_multisite() || ! is_main_site() ) {
			return;
		}

		/* Get the current plugin version from the database */
		$prev_ms_version = get_site_option( 'code_snippets_version' );

		/* Check if we have upgraded from an older version */
		if ( ! version_compare( $prev_ms_version, CODE_SNIPPETS_VERSION, '<' ) ) {
			return;
		}

		/* Update the plugin version stored in the database */
		update_site_option( 'code_snippets_version', CODE_SNIPPETS_VERSION );

		/* Update the scope column of the database */
		if ( version_compare( $prev_ms_version, '2.10.0', '<' ) ) {
			$this->migrate_scope_data( $this->db->ms_table );
		}

		/* Custom capabilities were removed after version 2.9.5 */
		if ( version_compare( $prev_ms_version, '2.9.5', '<=' ) ) {
			$network_cap = apply_filters( 'code_snippets_network_cap', 'manage_network_snippets' );

			foreach ( get_super_admins() as $admin ) {
				$user = new WP_User( 0, $admin );
				$user->remove_cap( $network_cap );
			}
		}
	}

	/**
	 * Migrate data from the old integer method of storing scopes to the new string method
	 *
	 * @param string $table_name
	 */
	private function migrate_scope_data( $table_name ) {
		global $wpdb;

		$scopes = array( 0 => 'global', 1 => 'admin', 2 => 'front-end' );

		foreach ( $scopes as $scope_number => $scope_name ) {
			$wpdb->query( sprintf(
				"UPDATE %s SET scope = '%s' WHERE scope = %d",
				$table_name, $scope_name, $scope_number
			) );
		}
	}
}
