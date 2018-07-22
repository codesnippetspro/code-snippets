<?php

/**
 * This file manages upgrades to the database between plugin versions
 */

/* Bail on direct access */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Preform upgrade tasks such as deleting and updating options
 * @since 2.0
 */
function code_snippets_upgrader() {
	global $wpdb;
	$db = code_snippets()->db;

	/* Get the current plugin version from the database */
	$prev_version = get_option( 'code_snippets_version' );

	/* Check if we have upgraded from an older version */
	if ( version_compare( $prev_version, CODE_SNIPPETS_VERSION, '<' ) ) {

		/* Upgrade the database tables */
		$db->create_tables( true );

		/* Update the plugin version stored in the database */
		update_option( 'code_snippets_version', CODE_SNIPPETS_VERSION );

		/* Update the scope column of the database */
		if ( version_compare( $prev_version, '2.10.0', '<' ) ) {
			$wpdb->query( "UPDATE {$wpdb->snippets} SET scope = 'global' WHERE scope = 0" );
			$wpdb->query( "UPDATE {$wpdb->snippets} SET scope = 'admin' WHERE scope = 1" );
			$wpdb->query( "UPDATE {$wpdb->snippets} SET scope = 'front-end' WHERE scope = 2" );
		}

		/* Custom capabilities were removed after version 2.9.5 */
		if ( version_compare( $prev_version, '2.9.5', '<=' ) ) {
			$role = get_role( apply_filters( 'code_snippets_role', 'administrator' ) );
			$role->remove_cap( apply_filters( 'code_snippets_cap', 'manage_snippets' ) );
		}
	}

	if ( false === $prev_version ) {
		$db->create_sample_content();
	}

	/* Run multisite-only upgrades */

	if ( is_multisite() && is_main_site() ) {

		/* Get the current plugin version from the database */
		$prev_ms_version = get_site_option( 'code_snippets_version' );

		/* Check if we have upgraded from an older version */
		if ( version_compare( $prev_ms_version, CODE_SNIPPETS_VERSION, '<' ) ) {

			/* Update the plugin version stored in the database */
			update_site_option( 'code_snippets_version', CODE_SNIPPETS_VERSION );

			/* Update the scope column of the database */
			if ( version_compare( $prev_version, '2.10.0', '<' ) ) {
				$wpdb->query( "UPDATE {$wpdb->ms_snippets} SET scope = 'global' WHERE scope = 0" );
				$wpdb->query( "UPDATE {$wpdb->ms_snippets} SET scope = 'admin' WHERE scope = 1" );
				$wpdb->query( "UPDATE {$wpdb->ms_snippets} SET scope = 'front-end' WHERE scope = 2" );
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
	}
}

add_action( 'plugins_loaded', 'code_snippets_upgrader', 0 );
