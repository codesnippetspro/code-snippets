<?php

abstract class Code_Snippets_Admin_Menu {

	public $name, $label, $title;

	protected $includes_dir;

	/**
	 * Constructor
	 * @param string $name  The snippet page shortname
	 * @param string $label The label shown in the admin menu
	 * @param string $title The text used for the page title
	 */
	function __construct( $name, $label, $title ) {
		$this->name = $name;
		$this->label = $label;
		$this->title = $title;

		$this->includes_dir = dirname( plugin_dir_path( __FILE__ ) ) . '/';

		add_action( 'admin_menu', array( $this, 'register' ) );
		add_action( 'network_admin_menu', array( $this, 'register' ) );
	}

	public function add_menu( $slug, $label, $title ) {
		$hook = add_submenu_page(
			code_snippets_get_menu_slug(),
			$title,
			$label,
			get_snippets_cap(),
			$slug,
			array( $this, 'render' )
		);

		add_action( 'load-' . $hook, array( $this, 'load' ) );
	}

	/**
	 * Register the admin menu
	 * @uses add_submenu_page() to register a submenu
	 */
	public function register() {
		$this->add_menu( code_snippets_get_menu_slug( $this->name ), $this->label, $this->title );
	}

	/**
	 * Render the menu
	 */
	public function render() {
		include $this->includes_dir . "admin-messages/$this->name.php";
		include $this->includes_dir . "views/$this->name.php";
	}

	/**
	 * Load the screen contextual help tabs
	 */
	protected function load_help_tabs() {
		include $this->includes_dir . "contextual-help/$this->name.php";
	}

	/**
	 * Executed when the admin page is loaded
	 */
	public function load() {
		/* Make sure the user has permission to be here */
		if ( ! current_user_can( get_snippets_cap() ) ) {
			wp_die( __( 'You are not authorized to access this page.', 'code-snippets' ) );
		}

		/* Create the snippet tables if they don't exist */
		create_code_snippets_tables();

		/* Load the screen help tabs */
		$this->load_help_tabs();
	}
}
