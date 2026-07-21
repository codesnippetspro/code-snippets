<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Admin\Contextual_Help;
use Code_Snippets\Controller\Cloud_Search_Controller;
use function Code_Snippets\code_snippets;

/**
 * Provides the manage snippets admin menu.
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
	 * Default number of snippets shown per page in the manage table.
	 */
	public const DEFAULT_SNIPPETS_PER_PAGE = 100;

	/**
	 * Manage menu asset service.
	 *
	 * @var Manage_Menu_Assets
	 */
	private Manage_Menu_Assets $assets;

	/**
	 * Manage menu bulk download service.
	 *
	 * @var Manage_Menu_Bulk_Download
	 */
	private Manage_Menu_Bulk_Download $bulk_download;

	/**
	 * Manage menu registration service.
	 *
	 * @var Manage_Menu_Registration
	 */
	private Manage_Menu_Registration $registration;

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
		$this->screen_options = new Manage_Menu_Screen_Options();
		$this->assets = new Manage_Menu_Assets( $this->screen_options, new Snippet_Type_Counter() );
		$this->bulk_download = new Manage_Menu_Bulk_Download();
		$this->registration = new Manage_Menu_Registration();

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
		add_action( 'admin_init', array( $this, 'handle_bulk_download_request' ) );
		add_action( 'admin_init', array( $this, 'save_truncation_preference' ) );
		add_filter( 'set-screen-option', array( $this, 'save_screen_option' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_menu_css' ) );
		add_action( 'wp_ajax_update_code_snippet', array( $this, 'ajax_callback' ) );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_menu_css' ] );
	}

	/**
	 * Register the top-level 'Snippets' menu and associated 'Manage' subpage
	 */
	public function register() {
		$this->registration->register_top_level( $this );
		parent::register();
	}

	/**
	 * Register the 'upgrade' menu item.
	 *
	 * @return void
	 */
	public function register_upgrade_menu() {
		$this->registration->register_upgrade_menu( $this );
	}

	/**
	 * Print CSS required for the admin menu icon.
	 *
	 * @return void
	 */
	public function enqueue_menu_css() {
		$this->registration->enqueue_menu_css();
	}

	/**
	 * Redirect the user upon opening the upgrade menu.
	 *
	 * @return void
	 */
	public function load_upgrade_menu() {
		$this->registration->load_upgrade_menu();
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
		$this->registration->register_compact_menu( $this );
	}

	/**
	 * Executed when the admin page is loaded.
	 */
	public function load() {
		parent::load();

		$screen = get_current_screen();

		if ( $screen && ! $this->screen_options->is_cloud_community_view() ) {
			add_filter( "manage_{$screen->id}_columns", [ $this, 'get_screen_columns' ] );
			add_filter( 'screen_settings', [ $this, 'render_screen_settings' ] );
		}

		add_screen_option(
			'per_page',
			[
				'label'   => __( 'Snippets per page', 'code-snippets' ),
				'default' => $this->get_default_snippets_per_page(),
				'option'  => 'snippets_per_page',
			]
		);

		$contextual_help = new Contextual_Help( 'edit' );
		$contextual_help->load();
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page.
	 */
	public function enqueue_assets() {
		$this->assets->enqueue( self::$script_deps, self::$style_deps );
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

	/**
	 * Render the snippets table interface.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="manage-snippets-container" class="wrap"></div>';
	}

	/**
	 * Return the columns available in Screen Options for the snippets table.
	 *
	 * @param string[] $columns Existing columns.
	 *
	 * @return string[]
	 */
	public function get_screen_columns( array $columns = array() ): array {
		return $this->screen_options->get_columns( $columns );
	}

	/**
	 * Render extra Screen Options controls for the snippets table.
	 *
	 * @param string $screen_settings Existing screen settings HTML.
	 *
	 * @return string
	 */
	public function render_screen_settings( string $screen_settings ): string {
		return $this->screen_options->render( $screen_settings );
	}

	/**
	 * Persist the snippets table truncation preference from Screen Options.
	 *
	 * @return void
	 */
	public function save_truncation_preference(): void {
		$this->screen_options->save_truncation_preference();
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
		return $this->screen_options->save_per_page_option( $status, $option, $value );
	}

	/**
	 * Handle bulk snippet code downloads from the manage screen.
	 *
	 * @return void
	 */
	public function handle_bulk_download_request(): void {
		$this->bulk_download->handle();
	}
}
