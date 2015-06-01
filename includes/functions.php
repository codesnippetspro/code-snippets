<?php

/**
 * This file holds general-use non-admin-specific functions
 * @package Code_Snippets
 */

/**
 * Fetch the admin menu slug for a snippets menu
 * @param  string $menu The menu to retrieve the slug for
 * @return string       The menu's slug
 */
function code_snippets_get_menu_slug( $menu = '' ) {
	$add = array( 'single', 'add', 'add-new', 'add-snippet', 'new-snippet', 'add-new-snippet' );
	$edit = array( 'edit', 'edit-snippet' );
	$import = array( 'import', 'import-snippets' );
	$settings = array( 'settings', 'snippets-settings' );

	if ( in_array( $menu, $edit ) ) {
		return 'edit-snippet';
	} elseif ( in_array( $menu, $add ) ) {
		return 'add-snippet';
	} elseif ( in_array( $menu, $import ) ) {
		return 'import-snippets';
	} elseif ( in_array( $menu, $settings ) ) {
		return 'snippets-settings';
	} else {
		return 'snippets';
	}
}

/**
 * Fetch the URL to a snippets admin menu
 * @param  string $menu    The menu to retrieve the URL to
 * @param  string $context The URL scheme to use
 * @return string          The menu's URL
 */
function code_snippets_get_menu_url( $menu = '', $context = 'self' ) {
	$slug = code_snippets_get_menu_slug( $menu );
	$url = 'admin.php?page=' . $slug;

	if ( 'network' === $context ) {
		return network_admin_url( $url );
	} elseif ( 'admin' === $context ) {
		return admin_url( $url );
	} else {
		return self_admin_url( $url );
	}
}

/**
 * Fetch the admin menu hook for a snippets menu
 * @param  string $menu The menu to retrieve the hook for
 * @return string       The menu's hook
 */
function code_snippets_get_menu_hook( $menu = '' ) {
	$slug = code_snippets_get_menu_slug( $menu );
	return get_plugin_page_hookname( $slug, 'snippets' );
}

/**
 * Fetch the admin menu slug for a snippets menu
 * @param  int    $snippet_id The snippet
 * @param  string $context    The URL scheme to use
 * @return string             The URL to the edit snippet page for that snippet
 */
function get_snippet_edit_url( $snippet_id, $context = 'self' ) {
	return add_query_arg(
		'id', absint( $snippet_id ),
		code_snippets_get_menu_url( 'edit', $context )
	);
}
