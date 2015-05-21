<?php

/**
 * This class handles the manage snippets menu
 * @since 2.4.0
 * @package Code_Snippets
 */
class Code_Snippets_Manage_Menu extends Code_Snippets_Admin_Menu {

	/**
	 * Holds the list table class
	 * @var Code_Snippets_List_Table
	 */
	public $list_table;

	/**
	 * Class constructor
	 */
	public function __construct() {

		parent::__construct( 'manage',
			__( 'Manage', 'code-snippets' ),
			__( 'Snippets', 'code-snippets' )
		);

		add_filter( 'set-screen-option', array( $this, 'save_screen_option' ), 10, 3 );
	}

	/**
	 * Register the top-level 'Snippets' menu and associated 'Manage' subpage
	 *
	 * @uses add_menu_page() to register a top-level menu
	 * @uses add_submenu_page() to register a sub-menu
	 */
	function register() {

		/* Register the top-level menu */
		add_menu_page(
			__( 'Snippets', 'code-snippets' ),
			__( 'Snippets', 'code-snippets' ),
			get_snippets_cap(),
			code_snippets_get_menu_slug(),
			array( $this, 'render' ),
			'div', // icon is added through CSS
			is_network_admin() ? 21 : 67
		);

		/* Register the sub-menu */
		parent::register();
	}

	/**
	 * Executed when the admin page is loaded
	 */
	function load() {
		parent::load();

		/* Load the contextual help tabs */
		code_snippets_load_manage_help();

		/* Initialize the list table class */
		require_once $this->includes_dir . 'class-list-table.php';
		$this->list_table = new Code_Snippets_List_Table();
		$this->list_table->prepare_items();
	}

	/**
	 * Print the status and error messages
	 */
	protected function print_messages() {

		/* Output a warning if safe mode is active */
		if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) {
			echo '<div id="message" class="error fade"><p>';
			_e( '<strong>Warning:</strong> Safe mode is active and snippets will not execute! Remove the <code>CODE_SNIPPETS_SAFE_MODE</code> constant from <code>wp-config.php</code> to turn off safe mode. <a href="https://github.com/sheabunge/code-snippets/wiki/Safe-Mode" target="_blank">Help</a>', 'code-snippets' ),
			echo '</p></div>';
		}

		$messages = array(
			'activate' => __( 'Snippet <strong>activated</strong>.',
			'activate-multi' => __( 'Selected snippets <strong>activated</strong>.', 'code-snippets' ),
			'deactivate' => __( 'Snippet <strong>deactivated</strong>.', 'code-snippets' ),
			'deactivate-multi' => __( 'Selected snippets <strong>deactivated</strong>.', 'code-snippets' ),
			'delete' => __( 'Snippet <strong>deleted</strong>.', 'code-snippets' ),
			'delete-multi' => __( 'Selected snippets <strong>deleted</strong>.', 'code-snippets' ),
		);

		foreach ( $messages as $key => $message ) {
			if ( isset( $_REQUEST[ $key ] ) && $_REQUEST[ $key ] ) {
				echo '<div id="message" class="updated fade"><p>';
				echo $message;
				echo '</p></div>';
				return; // only print one status message per request
			}
		}
	}

	/**
	 * Handles saving the user's snippets per page preference
	 *
	 * @param  unknown $status
	 * @param  string  $option The screen option name
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
