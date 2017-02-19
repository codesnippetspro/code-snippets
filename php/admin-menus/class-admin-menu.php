<?php

/**
 * Base class for a snippets admin menu
 */
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
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
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
			code_snippets()->get_menu_slug(),
			$title,
			$label,
			code_snippets()->get_cap(),
			$slug,
			array( $this, 'render' )
		);

		add_action( 'load-' . $hook, array( $this, 'load' ) );
	}

	/**
	 * Register the admin menu
	 */
	public function register() {
		$this->add_menu( code_snippets()->get_menu_slug( $this->name ), $this->label, $this->title );
	}

	/**
	 * Render the menu
	 */
	public function render() {
		$this->print_messages();
		include dirname( dirname( __FILE__ ) ) . "/views/{$this->name}.php";
	}

	/**
	 * Print the status and error messages
	 */
	protected function print_messages() {}

	/**
	 * Retrieve a result message based on a posted status
	 *
	 * @param array  $messages
	 * @param string $request_var
	 * @param string $class
	 *
	 * @return string|bool The result message if a valid status was received, otherwise false
	 */
	protected function get_result_message( $messages, $request_var = 'result', $class = 'updated' ) {

		if ( empty( $_REQUEST[ $request_var ] ) ) {
			return false;
		}

		$result = $_REQUEST[ $request_var ];

		if ( isset( $messages[ $result ] ) ) {
			return sprintf(
				'<div id="message" class="%2$s fade"><p>%1$s</p></div>',
				$messages[ $result ], $class
			);
		}

		return false;
	}

	/**
	 * Executed when the admin page is loaded
	 */
	public function load() {
		/* Make sure the user has permission to be here */
		if ( ! current_user_can( code_snippets()->get_cap() ) ) {
			wp_die( __( 'You are not authorized to access this page.', 'code-snippets' ) );
		}

		/* Create the snippet tables if they don't exist */
		code_snippets()->db->create_tables();
	}
}
