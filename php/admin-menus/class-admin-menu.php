<?php

namespace Code_Snippets;

/**
 * Base class for a plugin admin menu.
 */
abstract class Admin_Menu {

	/**
	 * The snippet page short name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The label shown in the admin menu
	 *
	 * @var string
	 */
	public $label;

	/**
	 * The text used for the page title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * The base slug for the top-level admin menu.
	 *
	 * @var string
	 */
	protected $base_slug;

	/**
	 * The slug for this admin menu.
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Constructor
	 *
	 * @param string $name  The snippet page short name.
	 * @param string $label The label shown in the admin menu.
	 * @param string $title The text used for the page title.
	 */
	public function __construct( $name, $label, $title ) {
		$this->name = $name;
		$this->label = $label;
		$this->title = $title;

		$this->base_slug = code_snippets()->get_menu_slug();
		$this->slug = code_snippets()->get_menu_slug( $name );
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		if ( ! code_snippets()->is_compact_menu() ) {
			add_action( 'admin_menu', array( $this, 'register' ) );
			add_action( 'network_admin_menu', array( $this, 'register' ) );
		}
	}

	/**
	 * Add a sub-menu to the Snippets menu
	 *
	 * @param string $slug  Menu slug.
	 * @param string $label Label shown in admin menu.
	 * @param string $title Page title.
	 *
	 * @uses add_submenu_page() to register a submenu
	 */
	public function add_menu( $slug, $label, $title ) {
		$hook = add_submenu_page(
			$this->base_slug,
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
		$this->add_menu( $this->slug, $this->label, $this->title );
	}

	/**
	 * Render the content of a vew template
	 *
	 * @param string $name Name of view template to render.
	 */
	protected function render_view( $name ) {
		include dirname( PLUGIN_FILE ) . '/php/views/' . $name . '.php';
	}

	/**
	 * Render the menu
	 */
	public function render() {
		$this->render_view( $this->name );
	}

	/**
	 * Print the status and error messages
	 */
	abstract protected function print_messages();

	/**
	 * Retrieve a result message based on a posted status
	 *
	 * @param array<string, string> $messages    List of possible messages to display.
	 * @param string                $request_var Name of $_REQUEST variable to check.
	 * @param string                $class       Class to use on buttons. Default 'updated'.
	 *
	 * @return bool Whether a result message was printed.
	 */
	protected function print_result_message( $messages, $request_var = 'result', $class = 'updated' ) {

		if ( empty( $_REQUEST[ $request_var ] ) ) {
			return false;
		}

		$result = sanitize_key( $_REQUEST[ $request_var ] );

		if ( isset( $messages[ $result ] ) ) {
			printf(
				'<div id="message" class="%2$s fade"><p>%1$s</p></div>',
				wp_kses_post( $messages[ $result ] ),
				esc_attr( $class )
			);

			return true;
		}

		return false;
	}

	/**
	 * Executed when the admin page is loaded
	 */
	public function load() {
		/* Make sure the user has permission to be here */
		if ( ! current_user_can( code_snippets()->get_cap() ) ) {
			wp_die( esc_html__( 'You are not authorized to access this page.', 'code-snippets' ) );
		}

		/* Create the snippet tables if they don't exist */
		$db = code_snippets()->db;

		if ( is_multisite() ) {
			$db->create_missing_table( $db->ms_table );
		}
		$db->create_missing_table( $db->table );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page, if necessary
	 */
	abstract public function enqueue_assets();

	/**
	 * Render a list of links to other pages in the page title
	 *
	 * @param array<string> $actions List of actions to render as links, as array values.
	 */
	protected function page_title_actions( $actions ) {

		foreach ( $actions as $action ) {
			if ( 'settings' === $action && ! isset( code_snippets()->admin->menus['settings'] ) ) {
				continue;
			}

			$url = code_snippets()->get_menu_url( $action );

			if ( isset( $_GET['type'] ) && in_array( $_GET['type'], Snippet::get_types(), true ) ) {
				$url = add_query_arg( 'type', sanitize_key( wp_unslash( $_GET['type'] ) ), $url );
			}

			printf( '<a href="%s" class="page-title-action">', esc_url( $url ) );

			switch ( $action ) {
				case 'manage':
					echo esc_html_x( 'Manage', 'snippets', 'code-snippets' );
					break;
				case 'add':
					echo esc_html_x( 'Add New', 'snippet', 'code-snippets' );
					break;
				case 'import':
					echo esc_html_x( 'Import', 'snippets', 'code-snippets' );
					break;
				case 'settings':
					echo esc_html_x( 'Settings', 'snippets', 'code-snippets' );
					break;
			}

			echo '</a>';
		}
	}
}
