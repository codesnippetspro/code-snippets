<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Admin\Contextual_Help;
use Code_Snippets\Utils\Code_Highlighter;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippets;
use function Code_Snippets\Settings\get_setting;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * This class handles the manage snippets menu
 *
 * @since   2.4.0
 * @package Code_Snippets
 */
class Manage_Menu extends Admin_Menu {

	/**
	 * Handle for JavaScript asset file.
	 */
	public const JS_HANDLE = 'code-snippets-manage-menu';

	/**
	 * Handle for CSS asset file.
	 */
	public const CSS_HANDLE = 'code-snippets-manage';

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			'manage',
			_x( 'All Snippets', 'menu label', 'code-snippets' ),
			__( 'Snippets', 'code-snippets' )
		);

		if ( code_snippets()->is_compact_menu() ) {
			add_action( 'admin_menu', array( $this, 'register_compact_menu' ), 2 );
			add_action( 'network_admin_menu', array( $this, 'register_compact_menu' ), 2 );
		}

		add_action( 'admin_menu', array( $this, 'register_upgrade_menu' ), 500 );
		add_filter( 'set-screen-option', array( $this, 'save_screen_option' ), 10, 3 );
		add_action( 'wp_ajax_update_code_snippet', array( $this, 'ajax_callback' ) );
	}

	/**
	 * Register the top-level 'Snippets' menu and associated 'Manage' subpage
	 */
	public function register() {
		$icon_xml = '<svg xmlns="http://www.w3.org/2000/svg" width="1024" height="1024"><path fill="transparent" d="M191.968 464.224H350.88c23.68 0 42.656 19.2 42.656 42.656 0 11.488-4.48 21.984-11.968 29.632l.192.448-108.768 108.736c-75.104 75.136-75.104 196.512 0 271.584 74.88 74.848 196.448 74.848 271.552 0 74.88-75.104 74.88-196.48 0-271.584-21.76-21.504-47.36-37.12-74.464-46.272l28.608-28.576h365.248c87.04 0 157.856-74.016 159.968-166.4 0-1.472.224-2.752 0-4.256-2.112-23.904-22.368-42.656-46.912-42.656h-264.96L903.36 166.208c17.504-17.504 18.56-45.024 3.2-63.36-1.024-1.28-2.08-2.144-3.2-3.2-66.528-63.552-169.152-65.92-230.56-4.48L410.432 357.536h-46.528c12.8-25.6 20.032-54.624 20.032-85.344 0-106.016-85.952-192-192-192-106.016 0-191.968 85.984-191.968 192 .032 106.08 85.984 192.032 192 192.032zm85.344-191.968c0 47.136-38.176 85.344-85.344 85.344-47.136 0-85.312-38.176-85.312-85.344s38.176-85.344 85.312-85.344c47.168 0 85.344 38.208 85.344 85.344zm191.776 449.056c33.28 33.248 33.28 87.264 0 120.512-33.28 33.472-87.264 33.472-120.736 0-33.28-33.248-33.28-87.264 0-120.512 33.472-33.504 87.456-33.504 120.736 0z"/></svg>';
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$encoded_icon = base64_encode( $icon_xml );

		// Register the top-level menu.
		add_menu_page(
			__( 'Snippets', 'code-snippets' ),
			_x( 'Snippets', 'top-level menu label', 'code-snippets' ),
			code_snippets()->get_cap(),
			code_snippets()->get_menu_slug(),
			array( $this, 'render' ),
			"data:image/svg+xml;base64,$encoded_icon",
			apply_filters( 'code_snippets/admin/menu_position', is_network_admin() ? 21 : 67 )
		);

		// Register the sub-menu.
		parent::register();
	}

	/**
	 * Register the 'upgrade' menu item.
	 *
	 * @return void
	 */
	public function register_upgrade_menu() {
		if ( code_snippets()->licensing->is_licensed() || get_setting( 'general', 'hide_upgrade_menu' ) ) {
			return;
		}

		$menu_title = sprintf(
			'<span class="button button-primary code-snippets-upgrade-button">%s %s</span>',
			_x( 'Go Pro', 'top-level menu label', 'code-snippets' ),
			'<span class="dashicons dashicons-external"></span>'
		);

		$hook = add_submenu_page(
			code_snippets()->get_menu_slug(),
			__( 'Upgrade to Pro', 'code-snippets' ),
			$menu_title,
			code_snippets()->get_cap(),
			'code_snippets_upgrade',
			'__return_empty_string',
			100
		);

		add_action( "load-$hook", [ $this, 'load_upgrade_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_menu_button_css' ] );
	}

	/**
	 * Print CSS required for the upgrade button.
	 *
	 * @return void
	 */
	public function enqueue_menu_button_css() {
		wp_enqueue_style(
			'code-snippets-menu-button',
			plugins_url( 'dist/menu-button.css', PLUGIN_FILE ),
			[],
			PLUGIN_VERSION
		);
	}

	/**
	 * Redirect the user upon opening the upgrade menu.
	 *
	 * @return void
	 */
	public function load_upgrade_menu() {
		wp_safe_redirect( 'https://snipco.de/JE2f' );
		exit;
	}

	/**
	 * Add menu pages for the compact menu
	 */
	public function register_compact_menu() {

		if ( ! code_snippets()->is_compact_menu() ) {
			return;
		}

		$sub = code_snippets()->get_menu_slug( isset( $_GET['sub'] ) ? sanitize_key( $_GET['sub'] ) : 'snippets' );

		$classmap = array(
			'snippets'             => 'manage',
			'add-snippet'          => 'edit',
			'edit-snippet'         => 'edit',
			'import-code-snippets' => 'import',
			'snippets-settings'    => 'settings',
		);

		$menus = code_snippets()->admin->menus;
		$class = isset( $classmap[ $sub ], $menus[ $classmap[ $sub ] ] ) ? $menus[ $classmap[ $sub ] ] : $this;

		/* Add a submenu to the Tools menu */
		$hook = add_submenu_page(
			'tools.php',
			__( 'Snippets', 'code-snippets' ),
			_x( 'Snippets', 'tools submenu label', 'code-snippets' ),
			code_snippets()->get_cap(),
			code_snippets()->get_menu_slug(),
			array( $class, 'render' )
		);

		add_action( 'load-' . $hook, array( $class, 'load' ) );
	}

	/**
	 * Executed when the admin page is loaded.
	 */
	public function load() {
		parent::load();

		$contextual_help = new Contextual_Help( 'edit' );
		$contextual_help->load();

		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Snippets per page', 'code-snippets' ),
				'default' => 999,
				'option'  => 'snippets_per_page',
			)
		);
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page.
	 */
	public function enqueue_assets() {
		$plugin = code_snippets();
		$rtl = is_rtl() ? '-rtl' : '';

		wp_enqueue_style(
			self::CSS_HANDLE,
			plugins_url( "dist/manage$rtl.css", $plugin->file ),
			self::$style_deps,
			$plugin->version
		);

		wp_enqueue_script(
			self::JS_HANDLE,
			plugins_url( 'dist/manage.js', $plugin->file ),
			self::$script_deps,
			$plugin->version,
			true
		);

		Code_Highlighter::enqueue_all_prism_themes();

		wp_set_script_translations( self::JS_HANDLE, 'code-snippets' );
		$plugin->localize_script( self::JS_HANDLE );

		wp_localize_script(
			self::JS_HANDLE,
			'CODE_SNIPPETS_MANAGE',
			[
				'hasNetworkCap'    => current_user_can( code_snippets()->get_network_cap_name() ),
				'snippetsPerPage'  => $this->get_snippets_per_page(),
				'isSafeModeActive' => code_snippets()->evaluate_functions->is_safe_mode_active(),
				'snippetsList'     => array_map(
					function ( $snippet ) {
						return $snippet->get_fields();
					},
					get_snippets()
				),
			]
		);
	}

	/**
	 * Get the number of snippets to show per page.
	 *
	 * @return int
	 */
	protected function get_snippets_per_page(): int {
		$per_page = (int) get_user_option( 'snippets_per_page' );

		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = 999;
		}

		return (int) apply_filters( 'snippets_per_page', $per_page );
	}

	/**
	 * Render the snippets table interface.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="manage-snippets-container"></div>';
	}

	/**
	 * Handles saving the user's snippets per page preference
	 *
	 * @param mixed  $status Current screen option status.
	 * @param string $option The screen option name.
	 * @param mixed  $value  Screen option value.
	 *
	 * @return mixed
	 */
	public function save_screen_option( $status, string $option, $value ) {
		return 'snippets_per_page' === $option ? $value : $status;
	}
}
