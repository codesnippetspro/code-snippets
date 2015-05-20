<?php

/**
 * Functions to handle the manage snippets menu
 *
 * @package Code_Snippets
 * @subpackage Manage
 */

class Code_Snippets_Manage_Menu extends Code_Snippets_Admin_Menu {

	public $list_table;

	public function __construct() {

		parent::__construct( __FILE__,
			'manage',
			__( 'Manage', 'code-snippets' ),
			__( 'Snippets', 'code-snippets' )
		);

		add_filter( 'set-screen-option', array( $this, 'save_screen_option' ), 10, 3 );
	}

	/**
	 * Register the top-level 'Snippets' menu and associated 'Manage' subpage
	 *
	 * @uses add_menu_page() To register a top-level menu
	 * @uses add_submenu_page() To register a sub-menu
	 */
	function register() {

		$this->hook = add_menu_page(
			__( 'Snippets', 'code-snippets' ),
			__( 'Snippets', 'code-snippets' ),
			get_snippets_cap(),
			code_snippets_get_menu_slug(),
			array( $this, 'render' ),
			'div', // icon is added through CSS
			is_network_admin() ? 21 : 67
		);

		add_submenu_page(
			code_snippets_get_menu_slug(),
			__( 'Snippets', 'code-snippets' ),
			__( 'Manage', 'code-snippets' ),
			get_snippets_cap(),
			code_snippets_get_menu_slug(),
			array( $this, 'render' )
		);

		add_action( 'load-' . $this->hook, 'code_snippets_load_manage_menu' );
	}

	/**
	 * Initializes the list table class and loads the help tabs
	 * for the Manage Snippets page
	 */
	function load() {
		parent::load();

		/* Initialize the snippet table class */
		require_once $dir . 'class-list-table.php';
		$this->list_table = new Code_Snippets_List_Table();
		$this->list_table->prepare_items();
	}

	/**
	 * Handles saving the user's snippets per page preference
	 *
	 * @param  unknown $status
	 * @param  string  $option
	 * @param  unknown $value
	 * @return unknown
	 */
	function save_screen_option( $status, $option, $value ) {
		if ( 'snippets_per_page' === $option ) {
			return $value;
		}

		return $status;
	}
}
