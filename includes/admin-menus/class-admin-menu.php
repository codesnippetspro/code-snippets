<?php

class Code_Snippets_Admin_Menu {

	public $name, $label, $title;

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

		add_action( 'admin_menu', array( $this, 'register' ) );
		add_action( 'network_admin_menu', array( $this, 'register' ) );
	}

	/**
	 * Add a sub-menu to the Snippets menu
	 * @uses add_submenu_page() to register a submenu
	 * @param string $slug  The slug of the menu
	 * @param string $label The label shown in the admin menu
	 * @param string $title The page title
	 */
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
	 */
	public function register() {
		$this->add_menu( code_snippets_get_menu_slug( $this->name ), $this->label, $this->title );
	}

	/**
	 * Render the menu
	 */
	public function render() {
		$this->print_messages();
		include dirname( plugin_dir_path( __FILE__ ) ) . "/views/$this->name.php";
	}

	/**
	 * Print the status and error messages
	 */
	protected function print_messages() {}

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
	}
}
