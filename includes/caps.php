<?php

/**
 * Get the required capability to perform a certain action on snippets.
 * Does not check if the user has this capability or not.
 *
 * If multisite, checks if *Enable Administration Menus: Snippets* is active
 * under the *Settings > Network Settings* network admin menu
 *
 * @since 2.0
 * @return string The capability required to manage snippets
 */
function get_snippets_cap() {

	if ( is_multisite() ) {
		$menu_perms = get_site_option( 'menu_items', array() );

		/* If multisite is enabled and the snippet menu is not activated,
		   restrict snippet operations to super admins only */
		if ( empty( $menu_perms['snippets'] ) ) {
			return apply_filters( 'code_snippets_network_cap', 'manage_network_snippets' );
		}
	}

	return apply_filters( 'code_snippets_cap', 'manage_snippets' );
}

/**
 * Add the multisite capabilities to a user
 *
 * @since  2.0
 * @param  integer $user_id The ID of the user to add the cap to
 */
function grant_network_snippets_cap( $user_id ) {

	/* Get the user from the ID */
	$user = new WP_User( $user_id );

	/* Add the capability */
	$user->add_cap( apply_filters( 'code_snippets_network_cap', 'manage_network_snippets' ) );
}

add_action( 'grant_super_admin', 'grant_network_snippets_cap' );

/**
 * Remove the multisite capabilities from a user
 *
 * @since 2.0
 * @param integer $user_id The ID of the user to remove the cap from
 */
function remove_network_snippets_cap( $user_id ) {

	/* Get the user from the ID */
	$user = new WP_User( $user_id );

	/* Remove the capability */
	$user->remove_cap( apply_filters( 'code_snippets_network_cap', 'manage_network_snippets' ) );
}

add_action( 'remove_super_admin', 'remove_network_snippets_cap' );
