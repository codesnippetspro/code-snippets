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
			_x( 'All Snippets', 'menu label', 'code-snippets' ),
			__( 'Snippets', 'code-snippets' )
		);
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		parent::run();
		add_filter( 'set-screen-option', array( $this, 'save_screen_option' ), 10, 3 );
		add_action( 'wp_ajax_update_code_snippet_priority', array( $this, 'update_priority_ajax_action' ) );
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
			_x( 'Snippets', 'top-level menu label', 'code-snippets' ),
			code_snippets()->get_cap(),
			code_snippets()->get_menu_slug(),
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
		$contextual_help = new Code_Snippets_Contextual_Help( 'manage' );
		$contextual_help->load();

		/* Initialize the list table class */
		$this->list_table = new Code_Snippets_List_Table();
		$this->list_table->prepare_items();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		$plugin = code_snippets();

		wp_enqueue_script(
			'code-snippets-manage-js',
			plugins_url( 'js/min/manage.js', $plugin->file ),
			array(), $plugin->version, true
		);
	}

	/**
	 * Print the status and error messages
	 */
	protected function print_messages() {

		/* Output a warning if safe mode is active */
		if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) {
			echo '<div id="message" class="error fade"><p>';
			_e( '<strong>Warning:</strong> Safe mode is active and snippets will not execute! Remove the <code>CODE_SNIPPETS_SAFE_MODE</code> constant from <code>wp-config.php</code> to turn off safe mode. <a href="https://github.com/sheabunge/code-snippets/wiki/Safe-Mode" target="_blank">Help</a>', 'code-snippets' );
			echo '</p></div>';
		}

		echo $this->get_result_message(
			array(
				'executed' => __( 'Snippet <strong>executed</strong>.', 'code-snippets' ),
				'activated' => __( 'Snippet <strong>activated</strong>.', 'code-snippets' ),
				'activated-multi' => __( 'Selected snippets <strong>activated</strong>.', 'code-snippets' ),
				'deactivated' => __( 'Snippet <strong>deactivated</strong>.', 'code-snippets' ),
				'deactivated-multi' => __( 'Selected snippets <strong>deactivated</strong>.', 'code-snippets' ),
				'deleted' => __( 'Snippet <strong>deleted</strong>.', 'code-snippets' ),
				'deleted-multi' => __( 'Selected snippets <strong>deleted</strong>.', 'code-snippets' ),
			)
		);
	}

	/**
	 * Handles saving the user's snippets per page preference
	 *
	 * @param  mixed  $status
	 * @param  string $option The screen option name
	 * @param  mixed  $value
	 * @return mixed
	 */
	function save_screen_option( $status, $option, $value ) {
		if ( 'snippets_per_page' === $option ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Handle the AJAX action to update a snippet priority
	 */
	public function update_priority_ajax_action() {
		check_ajax_referer( 'code_snippets_manage' );

		if ( ! isset( $_POST['snippet_id'], $_POST['snippet_priority'], $_POST['snippet_network'] ) ) {
			echo 'Snippet data not provided';
			wp_die();
		}

		$id = intval( $_POST['snippet_id'] );
		$priority = intval( $_POST['snippet_priority'] );
		$network = ( 'true' === $_POST['snippet_network'] || '1' === $_POST['snippet_network'] ) ? true :
			( 'false' === $_POST['snippet_network'] || '0' === $_POST['snippet_network'] ? false : null );

		if ( $id <= 0 || ! is_numeric( $_POST['snippet_priority'] ) || is_null( $network ) ) {
			echo 'Invalid snippet data';
			wp_die();
		}

		global $wpdb;

		$wpdb->update(
			code_snippets()->db->get_table_name( $network ),
			array( 'priority' => $priority ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);

		wp_die();
	}

}
