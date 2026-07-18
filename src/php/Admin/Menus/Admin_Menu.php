<?php

namespace Code_Snippets\Admin\Menus;

use function Code_Snippets\code_snippets;

/**
 * Base class for a plugin admin menu.
 */
abstract class Admin_Menu {

	/**
	 * The snippet page short name.
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * The label shown in the admin menu.
	 *
	 * @var string
	 */
	public string $label;

	/**
	 * The text used for the page title.
	 *
	 * @var string
	 */
	public string $title;

	/**
	 * The base slug for the top-level admin menu.
	 *
	 * @var string
	 */
	protected string $base_slug;

	/**
	 * The slug for this admin menu.
	 *
	 * @var string
	 */
	protected string $slug;

	/**
	 * Common JavaScript dependencies required for React components.
	 *
	 * @var string[]
	 */
	public static array $script_deps = [
		'react',
		'react-dom',
		'react-jsx-runtime',
		'wp-url',
		'wp-i18n',
		'wp-date',
		'wp-element',
		'wp-components',
	];

	/**
	 * Common CSS dependencies required for React components.
	 *
	 * @var string[]
	 */
	public static array $style_deps = [
		'wp-components',
	];

	/**
	 * Constructor.
	 *
	 * @param string $name  The snippet page short name.
	 * @param string $label The label shown in the admin menu.
	 * @param string $title The text used for the page title.
	 */
	public function __construct( string $name, string $label, string $title ) {
		$this->name = $name;
		$this->label = $label;
		$this->title = $title;

		$this->base_slug = code_snippets()->get_menu_slug();
		$this->slug = code_snippets()->get_menu_slug( $name );

		if ( ! code_snippets()->is_compact_menu() ) {
			add_action( 'admin_menu', array( $this, 'register' ) );
			add_action( 'network_admin_menu', array( $this, 'register' ) );
		}
	}

	/**
	 * Add a sub-menu to the Snippets menu.
	 *
	 * @param string $slug  Menu slug.
	 * @param string $label Label shown in admin menu.
	 * @param string $title Page title.
	 *
	 * @return void
	 */
	public function add_menu( string $slug, string $label, string $title ) {
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
	 * Retrieve the WordPress hookname computed for this menu's admin page.
	 *
	 * @return string
	 */
	public function get_hookname(): string {
		return get_plugin_page_hookname( $this->slug, $this->base_slug );
	}

	/**
	 * Retrieve every WordPress hookname registered by this menu, including any
	 * additional pages it registers beyond its primary slug.
	 *
	 * @return string[]
	 */
	public function get_hooknames(): array {
		return [ $this->get_hookname() ];
	}

	/**
	 * Render the navigation bar at the top of the admin page.
	 */
	protected function render_navigation() {
		echo '<div id="code-snippets-toolbar-container" class="wrap"></div>';
	}

	/**
	 * Render the menu
	 */
	abstract public function render();

	/**
	 * Executed when the admin page is loaded
	 */
	public function load() {
		// Make sure the user has permission to be here.
		if ( ! current_user_can( code_snippets()->get_cap() ) ) {
			wp_die( esc_html__( 'You are not authorized to access this page.', 'code-snippets' ) );
		}

		// Create the snippet tables if they are missing.
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
}
