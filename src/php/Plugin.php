<?php

namespace Code_Snippets;

use Code_Snippets\Admin\Bootstrap_Admin;
use Code_Snippets\Client\Cloud_API;
use Code_Snippets\Core\DB;
use Code_Snippets\Core\Licensing;
use Code_Snippets\Core\Upgrader;
use Code_Snippets\Integration\Classic_Editor\MCE_Plugin;
use Code_Snippets\Integration\Evaluate_Content;
use Code_Snippets\Integration\Evaluate_Functions;
use Code_Snippets\Integration\Promotions\Promotion_Manager;
use Code_Snippets\Integration\Shortcodes;
use Code_Snippets\REST_API\Cloud\Cloud_Snippets_REST_Controller;
use Code_Snippets\REST_API\Import\File_Import_REST_Controller;
use Code_Snippets\REST_API\Import\Plugins_Import_REST_Controller;
use Code_Snippets\REST_API\Snippets\Recently_Active_REST_Controller;
use Code_Snippets\REST_API\Snippets\Snippets_REST_Controller;

/**
 * The main plugin class
 *
 * @package Code_Snippets
 */
class Plugin {

	/**
	 * Current plugin version number
	 *
	 * @deprecated Use the PLUGIN_VERSION constant instead.
	 *
	 * @var string
	 */
	public string $version = PLUGIN_VERSION;

	/**
	 * Filesystem path to the main plugin file
	 *
	 * @deprecated Use the PLUGIN_FILE constant instead.
	 *
	 * @var string
	 */
	public string $file = PLUGIN_FILE;

	/**
	 * Database class
	 *
	 * @var DB
	 */
	public DB $db;

	/**
	 * Class for evaluating function snippets.
	 *
	 * @var Evaluate_Functions
	 */
	public Evaluate_Functions $evaluate_functions;

	/**
	 * Class for evaluating content snippets.
	 *
	 * @var Evaluate_Content
	 */
	public Evaluate_Content $evaluate_content;

	/**
	 * Administration area class
	 *
	 * @var Bootstrap_Admin
	 */
	public Bootstrap_Admin $admin;

	/**
	 * Class for managing cloud API actions.
	 *
	 * @var Cloud_API
	 */
	public Cloud_API $cloud_api;

	/**
	 * Handles licensing and plugin updates.
	 *
	 * @var Licensing
	 */
	public Licensing $licensing;

	/**
	 * Handles snippet handler registration.
	 *
	 * @var Flat_Files\Handler_Registry
	 */
	public Flat_Files\Handler_Registry $snippet_handler_registry;

	/**
	 * Class constructor
	 */
	public function __construct() {
		wp_cache_add_global_groups( CACHE_GROUP );
		add_action( 'allowed_redirect_hosts', [ $this, 'allow_code_snippets_redirect' ] );
	}

	/**
	 * Load the plugin utilities.
	 */
	private function load_utilities() {
		require_once __DIR__ . '/snippet-ops.php';
		require_once __DIR__ . '/Utils/editor.php';
		require_once __DIR__ . '/Utils/options.php';
		require_once __DIR__ . '/Settings/settings.php';
	}

	/**
	 * Initialise the plugin and load all necessary classes.
	 */
	public function load_plugin() {
		$this->load_utilities();

		$this->db = new DB();
		$this->cloud_api = new Cloud_API();
		$this->licensing = new Licensing();
		$this->evaluate_content = new Evaluate_Content( $this->db );
		$this->evaluate_functions = new Evaluate_Functions( $this->db );

		if ( is_admin() ) {
			$this->admin = new Bootstrap_Admin();
		}

		new Snippets_REST_Controller();
		new Cloud_Snippets_REST_Controller();
		new Recently_Active_REST_Controller();
		new Plugins_Import_REST_Controller();
		new File_Import_REST_Controller();

		new Shortcodes();
		new MCE_Plugin();
		new Upgrader( PLUGIN_VERSION, $this->db );
		new Promotion_Manager();

		$this->init_snippet_files();
	}

	/**
	 * Initialises the snippet files component.
	 *
	 * @return void
	 */
	private function init_snippet_files() {
		$this->snippet_handler_registry = new Flat_Files\Handler_Registry(
			[
				'php'  => new Flat_Files\Handlers\Functions_Snippet_Handler(),
				'html' => new Flat_Files\Handlers\Content_Snippet_Handler(),
			]
		);

		$fs = new Flat_Files\WP_Filesystem_Adapter();
		$config_repo = new Flat_Files\Flat_File_Config_Repository( $fs );

		$snippet_files = new Flat_Files\Snippet_Files( $this->snippet_handler_registry, $fs, $config_repo );
		$snippet_files->register_hooks();
	}

	/**
	 * Determine whether the menu is full or compact.
	 *
	 * @return bool
	 */
	public function is_compact_menu(): bool {
		return ! is_network_admin() && apply_filters( 'code_snippets_compact_menu', false );
	}

	/**
	 * Fetch the admin menu slug for a menu.
	 *
	 * @param string $menu Name of menu to retrieve the slug for.
	 *
	 * @return string The menu's slug.
	 */
	public function get_menu_slug( string $menu = '' ): string {
		$add = [ 'single', 'add', 'add-new', 'add-snippet', 'new-snippet', 'add-new-snippet' ];
		$edit = [ 'edit', 'edit-snippet' ];
		$import = [ 'import', 'import-snippets', 'import-code-snippets' ];
		$settings = [ 'settings', 'snippets-settings' ];
		$cloud = [ 'cloud', 'cloud-snippets' ];
		$welcome = [ 'welcome', 'getting-started', 'code-snippets' ];

		if ( in_array( $menu, $edit, true ) ) {
			return 'edit-snippet';
		} elseif ( in_array( $menu, $add, true ) ) {
			return 'add-snippet';
		} elseif ( in_array( $menu, $import, true ) ) {
			return 'import-code-snippets';
		} elseif ( in_array( $menu, $settings, true ) ) {
			return 'snippets-settings';
		} elseif ( in_array( $menu, $cloud, true ) ) {
			return 'snippets&subpage=cloud-community';
		} elseif ( in_array( $menu, $welcome, true ) ) {
			return 'code-snippets-welcome';
		} else {
			return 'snippets';
		}
	}

	/**
	 * Fetch the URL to a snippets admin menu.
	 *
	 * @param string $menu    Name of menu to retrieve the URL to.
	 * @param string $context URL scheme to use.
	 *
	 * @return string The menu's URL.
	 */
	public function get_menu_url( string $menu = '', string $context = 'self' ): string {
		$slug = $this->get_menu_slug( $menu );

		if ( $this->is_compact_menu() && 'network' !== $context ) {
			$base_slug = $this->get_menu_slug();
			$url = 'tools.php?page=' . $base_slug;

			if ( $slug !== $base_slug ) {
				$url .= '&sub=' . $slug;
			}
		} else {
			$url = 'admin.php?page=' . $slug;
		}

		if ( 'network' === $context ) {
			return network_admin_url( $url );
		} elseif ( 'admin' === $context ) {
			return admin_url( $url );
		} else {
			return self_admin_url( $url );
		}
	}

	/**
	 * Allow redirecting to the Code Snippets site.
	 *
	 * @param array<string> $hosts Allowed hosts.
	 *
	 * @return array Modified allowed hosts.
	 */
	public function allow_code_snippets_redirect( array $hosts ): array {
		$hosts[] = 'codesnippets.pro';
		$hosts[] = 'snipco.de';
		return $hosts;
	}

	/**
	 * Determine whether the current user can perform actions on snippets.
	 *
	 * @return bool Whether the current user has the required capability.
	 *
	 * @since 2.8.6
	 */
	public function current_user_can(): bool {
		return current_user_can( $this->get_cap() );
	}

	/**
	 * Retrieve the name of the capability required to manage sub-site snippets.
	 *
	 * @return string
	 */
	public function get_cap_name(): string {
		return apply_filters( 'code_snippets_cap', 'manage_options' );
	}

	/**
	 * Retrieve the name of the capability required to manage network snippets.
	 *
	 * @return string
	 */
	public function get_network_cap_name(): string {
		return apply_filters( 'code_snippets_network_cap', 'manage_network_options' );
	}

	/**
	 * Determine if a subsite user menu is enabled via *Network Settings > Enable administration menus*.
	 *
	 * @return bool
	 */
	public function is_subsite_menu_enabled(): bool {
		if ( ! is_multisite() ) {
			return true;
		}

		$menu_perms = get_site_option( 'menu_items', array() );
		return ! empty( $menu_perms['snippets'] );
	}

	/**
	 * Determine if the current user should have the network snippets capability.
	 *
	 * @return bool
	 */
	public function user_can_manage_network_snippets(): bool {
		return is_super_admin() || current_user_can( $this->get_network_cap_name() );
	}

	/**
	 * Get the required capability to perform a certain action on snippets.
	 * Does not check if the user has this capability or not.
	 *
	 * If multisite, adjusts the capability based on whether the user is viewing
	 * the network dashboard or a subsite and whether the menu is enabled for subsites.
	 *
	 * @return string The capability required to manage snippets.
	 *
	 * @since 2.0
	 */
	public function get_cap(): string {
		return is_multisite() && ( is_network_admin() || ! $this->is_subsite_menu_enabled() )
			? $this->get_network_cap_name()
			: $this->get_cap_name();
	}

	/**
	 * Localise a plugin script to provide the CODE_SNIPPETS object.
	 *
	 * @param string $handle Script handle.
	 *
	 * @return void
	 */
	public function localize_script( string $handle ) {
		wp_localize_script(
			$handle,
			'CODE_SNIPPETS',
			[
				'debug'            => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'isLicensed'       => $this->licensing->is_licensed(),
				'isCloudConnected' => Cloud_API::is_cloud_connection_available(),
				'hideUpsell'       => Settings\get_setting( 'general', 'hide_upgrade_menu' ),
				'restAPI'          => [
					'base'           => esc_url_raw( rest_url() ),
					'snippets'       => esc_url_raw( rest_url( Snippets_REST_Controller::get_base_route() ) ),
					'recentlyActive' => esc_url_raw( rest_url( Recently_Active_REST_Controller::get_base_route() ) ),
					'cloudSearch'    => esc_url_raw( rest_url( Cloud_Snippets_REST_Controller::get_base_route() ) ),
					'importPlugins'  => esc_url_raw( rest_url( Plugins_Import_REST_Controller::get_base_route() ) ),
					'importFiles'    => esc_url_raw( rest_url( File_Import_REST_Controller::get_base_route() ) ),
					'nonce'          => wp_create_nonce( 'wp_rest' ),
					'localToken'     => $this->cloud_api->get_local_token(),
				],
				'urls'             => [
					'plugin'   => esc_url_raw( plugins_url( '', PLUGIN_FILE ) ),
					'manage'   => esc_url_raw( $this->get_menu_url() ),
					'edit'     => esc_url_raw( $this->get_menu_url( 'edit' ) ),
					'addNew'   => esc_url_raw( $this->get_menu_url( 'add' ) ),
					'welcome'  => esc_url_raw( $this->get_menu_url( 'welcome' ) ),
					'settings' => esc_url_raw( $this->get_menu_url( 'settings' ) ),
				],
			]
		);
	}
}
