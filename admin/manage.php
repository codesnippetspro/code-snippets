<?php

/**
 * Functions to handle the manage snippets menu
 *
 * @package    Code_Snippets
 * @subpackage Administration
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
	require plugin_dir_path( __FILE__ ) . 'messages/manage.php';
	require plugin_dir_path( __FILE__ ) . 'views/manage.php';
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
		wp_die( __( 'You are not access this page.', 'code-snippets' ) );
	}

	/* Load stylesheet for this page */
	add_action( 'admin_enqueue_scripts', 'code_snippets_manage_menu_assets' );

	/* Create the snippet tables if they don't exist */
	create_code_snippets_tables( true, true );

	/* Load the screen help tabs */
	require plugin_dir_path( __FILE__ ) . 'help/manage.php';

	/* Initialize the snippet table class */
	require_once plugin_dir_path( CODE_SNIPPETS_FILE ) . 'includes/class-list-table.php';
	global $code_snippets_list_table;
	$code_snippets_list_table = new Code_Snippets_List_Table();
	$code_snippets_list_table->prepare_items();
}

/**
 * Enqueue the manage menu stylesheet
 *
 * @since 2.0
 * @uses wp_enqueue_style() To add the stylesheet to the queue
 * @param string $hook The current page hook, to be compared with the manage snippets page hook
 */
function code_snippets_manage_menu_assets( $hook ) {

	/* Only load the stylesheet on the manage snippets page */
	if ( $hook !== code_snippets_get_menu_hook() ) {
		return;
	}

	wp_enqueue_style(
		'code-snippets-admin-manage',
		plugins_url( 'styles/min/admin-manage.css', __FILE__ ),
		false,
		CODE_SNIPPETS_VERSION
	);
}
