<?php

/**
 * Functions to handle the manage snippets menu
 *
 * @package Code_Snippets
 * @subpackage Manage
 */

/**
 * Register the top-level 'Snippets' menu and associated 'Manage' subpage
 *
 * @since 1.0
 * @access private
 *
 * @uses add_menu_page() To register a top-level menu
 * @uses add_submenu_page() To register a sub-menu
 */
function code_snippets_add_manage_menu() {

	$hook = add_menu_page(
		__( 'Snippets', 'code-snippets' ),
		__( 'Snippets', 'code-snippets' ),
		get_snippets_cap(),
		code_snippets_get_menu_slug(),
		'code_snippets_render_manage_menu',
		'div', // icon is added through CSS
		is_network_admin() ? 21 : 67
	);

	add_submenu_page(
		code_snippets_get_menu_slug(),
		__( 'Snippets', 'code-snippets' ),
		__( 'Manage', 'code-snippets' ),
		get_snippets_cap(),
		code_snippets_get_menu_slug(),
		'code_snippets_render_manage_menu'
	);

	add_action( 'load-' . $hook, 'code_snippets_load_manage_menu' );
}

add_action( 'admin_menu', 'code_snippets_add_manage_menu', 5 );
add_action( 'network_admin_menu', 'code_snippets_add_manage_menu', 5 );

/**
 * Displays the manage snippets menu
 *
 * @since 2.0
 */
function code_snippets_render_manage_menu() {
	require plugin_dir_path( __FILE__ ) . 'admin-messages.php';
	require plugin_dir_path( __FILE__ ) . 'admin.php';
}

/**
 * Initializes the list table class and loads the help tabs
 * for the Manage Snippets page
 *
 * @since 1.0
 * @access private
 */
function code_snippets_load_manage_menu() {

	/* Make sure the user has permission to be here */
	if ( ! current_user_can( get_snippets_cap() ) ) {
		wp_die( __( 'You are not authorized to access this page.', 'code-snippets' ) );
	}

	/* Create the snippet tables if they don't exist */
	create_code_snippets_tables();

	/* Load the screen help tabs */
	require plugin_dir_path( __FILE__ ) . 'admin-help.php';

	/* Initialize the snippet table class */
	require_once plugin_dir_path( __FILE__ ) . 'class-list-table.php';
	global $code_snippets_list_table;
	$code_snippets_list_table = new Code_Snippets_List_Table();
	$code_snippets_list_table->prepare_items();
}

/**
 * Handles saving the user's snippets per page preference
 *
 * @param  unknown $status
 * @param  string  $option
 * @param  unknown $value
 * @return unknown
 */
function code_snippets_set_screen_option( $status, $option, $value ) {
	if ( 'snippets_per_page' === $option ) {
		return $value;
	}

	return $status;
}

add_filter( 'set-screen-option', 'code_snippets_set_screen_option', 10, 3 );
