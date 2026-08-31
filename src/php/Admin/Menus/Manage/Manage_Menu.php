<?php

namespace Code_Snippets\Admin\Menus\Manage;

use Code_Snippets\Admin\Contextual_Help;
use Code_Snippets\Admin\Menus\Admin_Menu;
use Code_Snippets\Controller\Cloud_Search_Controller;
use function Code_Snippets\activate_snippet;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippet;
use function Code_Snippets\Settings\get_setting;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Provides the manage snippets admin menu.
 */
class Manage_Menu extends Admin_Menu {

	/**
	 * Default number of snippets shown per page in the manage table.
	 */
	private const DEFAULT_SNIPPETS_PER_PAGE = 100;

	/**
	 * Manage menu Screen Options.
	 *
	 * @var Manage_Menu_Screen_Options
	 */
	private Manage_Menu_Screen_Options $screen_options;

	/**
	 * Class constructor.
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

		$this->screen_options = new Manage_Menu_Screen_Options();
		new Manage_Menu_Bulk_Download();

		add_action( 'admin_menu', array( $this, 'register_upgrade_menu' ), 500 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_menu_css' ) );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_menu_css' ] );
	}

	/**
	 * Register the top-level 'Snippets' menu and associated 'Manage' subpage
	 */
	public function register() {
		add_menu_page(
			__( 'Snippets', 'code-snippets' ),
			_x( 'Snippets', 'top-level menu label', 'code-snippets' ),
			code_snippets()->get_cap(),
			code_snippets()->get_menu_slug(),
			[ $this, 'render' ],
			'none', // Added through CSS as a mask to prevent loading 'blinking'.
			apply_filters( 'code_snippets/admin/menu_position', is_network_admin() ? 21 : 67 )
		);

		parent::register();
	}

	/**
	 * Render the snippets table interface.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="manage-snippets-container" class="wrap"></div>';
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
			'<span class="dashicons dashicons-external" aria-hidden="true"></span>'
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
	}

	/**
	 * Print CSS required for the admin menu icon.
	 *
	 * @return void
	 */
	public function enqueue_menu_css() {
		wp_enqueue_style(
			'code-snippets-menu',
			plugins_url( 'dist/menu.css', PLUGIN_FILE ),
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
	 * Retrieve every hookname registered by this menu, including the compact
	 * Tools submenu hookname when compact mode is active.
	 *
	 * @return string[]
	 */
	public function get_hooknames(): array {
		$hooknames = parent::get_hooknames();

		if ( code_snippets()->is_compact_menu() ) {
			$hooknames[] = get_plugin_page_hookname( $this->base_slug, 'tools.php' );
		}

		return $hooknames;
	}

	/**
	 * Add menu pages for the compact menu
	 */
	public function register_compact_menu() {
		if ( ! code_snippets()->is_compact_menu() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Value is matched to known classes.
		$sub = code_snippets()->get_menu_slug( isset( $_GET['sub'] ) ? sanitize_key( $_GET['sub'] ) : 'snippets' );
		$classmap = [
			'snippets'             => 'manage',
			'add-snippet'          => 'edit',
			'edit-snippet'         => 'edit',
			'import-code-snippets' => 'import',
			'snippets-settings'    => 'settings',
		];
		$menus = code_snippets()->admin->menus;
		$class = isset( $classmap[ $sub ], $menus[ $classmap[ $sub ] ] ) ? $menus[ $classmap[ $sub ] ] : $this;

		$hook = add_submenu_page(
			'tools.php',
			__( 'Snippets', 'code-snippets' ),
			_x( 'Snippets', 'tools submenu label', 'code-snippets' ),
			code_snippets()->get_cap(),
			code_snippets()->get_menu_slug(),
			[ $class, 'render' ]
		);

		add_action( 'load-' . $hook, [ $class, 'load' ] );
	}

	/**
	 * Nonce action guarding the run-once request.
	 */
	public const RUN_ONCE_NONCE = 'code_snippets_run_once';

	/**
	 * Run a single-use snippet, when asked to by the snippets list.
	 *
	 * Activating the snippet is all that is required: single-use snippets are
	 * executed and then deactivated again on the next page load, so redirecting
	 * afterwards both runs the code and returns the snippet to its resting
	 * state. This mirrors what the list table did before the snippets list
	 * moved to the REST API, at which point the button was left pointing at a
	 * URL that nothing handled.
	 *
	 * @return void
	 */
	private function handle_run_once(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified immediately below.
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

		if ( 'run-once' !== $action ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified immediately below.
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::RUN_ONCE_NONCE ) || ! code_snippets()->current_user_can() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified above.
		$snippet_id = isset( $_REQUEST['snippet'] ) ? absint( wp_unslash( $_REQUEST['snippet'] ) ) : 0;

		if ( ! $snippet_id ) {
			return;
		}

		// The network context comes from the current screen, never the request,
		// so a subsite administrator cannot target a network snippet.
		$network = is_network_admin();
		$snippet = get_snippet( $snippet_id, $network );

		// Only single-use snippets are run this way. Activating anything else
		// would leave it permanently on while the notice claimed it ran once.
		if ( ! $snippet || 0 === $snippet->id || 'single-use' !== $snippet->scope ) {
			wp_safe_redirect( remove_query_arg( [ 'action', 'snippet', 'network', '_wpnonce', 'result' ] ) );
			exit;
		}

		// An already-active snippet has effectively run, so treat it as success.
		$result = $snippet->active ? $snippet : activate_snippet( $snippet_id, $network );

		wp_safe_redirect(
			add_query_arg(
				[ 'result' => is_string( $result ) ? 'run-once-failed' : 'executed' ],
				remove_query_arg( [ 'action', 'snippet', 'network', '_wpnonce', 'result' ] )
			)
		);
		exit;
	}

	/**
	 * Executed when the admin page is loaded.
	 */
	public function load() {
		parent::load();

		$this->handle_run_once();
		$this->screen_options->load();

		if ( $this->screen_options->is_upsell_view() ) {
			return;
		}

		$contextual_help = new Contextual_Help( 'edit' );
		$contextual_help->load();
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page.
	 */
	public function enqueue_assets() {
		$assets = new Manage_Menu_Assets( $this->screen_options );
		$assets->enqueue( self::$script_deps, self::$style_deps );
	}

	/**
	 * Get the number of snippets to show per page in the cloud search.
	 *
	 * The value defaults to the user's local snippets-per-page preference but can
	 * be overridden independently via the `code_snippets/cloud_search/per_page` filter.
	 * The result is clamped to the REST API's per_page maximum of 100.
	 *
	 * @return int
	 */
	public static function get_cloud_search_per_page(): int {
		$per_page = intval( apply_filters( 'code_snippets/cloud_search/per_page', self::get_snippets_per_page() ) );
		return min( Cloud_Search_Controller::MAX_RESULTS_PER_PAGE, max( 1, $per_page ) );
	}

	/**
	 * Get the default number of snippets to show per page.
	 *
	 * @return int
	 */
	public static function get_default_snippets_per_page(): int {
		$default = apply_filters( 'code_snippets/snippets_per_page_default', self::DEFAULT_SNIPPETS_PER_PAGE );
		return max( 1, intval( $default ) );
	}

	/**
	 * Get the number of snippets to show per page.
	 *
	 * @return int
	 */
	public static function get_snippets_per_page(): int {
		$per_page = intval( get_user_option( 'snippets_per_page' ) );

		return intval(
			apply_filters(
				'snippets_per_page',
				$per_page > 0 ? $per_page : self::get_default_snippets_per_page()
			)
		);
	}
}
