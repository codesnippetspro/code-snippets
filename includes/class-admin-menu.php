<?php

abstract class Code_Snippets_Admin_Menu {

	private $slug, $name, $page_title, $hook;

	public $path;

	function __construct( $file, $menu_slug, $menu_name, $page_title ) {
		$this->slug = code_snippets_get_menu_slug( $menu_slug );
		$this->page_title = $page_title;
		$this->name = $menu_name;
		$this->path = plugin_dir_path( $file );

		add_action( 'admin_menu', array( $this, 'register' ) );
		add_action( 'network_admin_menu', array( $this, 'register' ) );
	}

	public function register() {
		$this->hook = add_submenu_page(
			code_snippets_get_menu_slug(),
			$this->page_title,
			$this->name,
			get_snippets_cap(),
			$this->slug,
			array( $this, 'render' )
		);

		add_action( 'load-' . $this->hook, array( $this, 'load' ) );
	}

	/**
	 * Render the menu
	 */
	public function render() {
		include $this->path . 'admin-messages.php';
		include $this->path . 'admin.php';
	}

	protected function load_help_tabs() {
		include $this->path . 'admin-help.php';
	}

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
