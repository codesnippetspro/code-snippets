<?php

namespace Code_Snippets;

/**
 * The main plugin class
 *
 * @package Code_Snippets
 */
class Plugin {

	/**
	 * Current plugin version number
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Filesystem path to the main plugin file
	 *
	 * @var string
	 */
	public $file;

	/**
	 * Database class
	 *
	 * @var DB
	 */
	public $db;

	/**
	 * Administration area class
	 *
	 * @var Admin
	 */
	public $admin;

	/**
	 * Front-end functionality class
	 *
	 * @var Frontend
	 */
	public $frontend;

	/**
	 * Class for managing active snippets
	 *
	 * @var Active_Snippets
	 */
	public $active_snippets;

	/**
	 * Class for providing REST API endpoints for snippet data.
	 *
	 * @var REST_API
	 */
	protected $rest_api;

	/**
	 * Class constructor
	 *
	 * @param string $version Current plugin version.
	 * @param string $file    Path to main plugin file.
	 */
	public function __construct( $version, $file ) {
		$this->version = $version;
		$this->file = $file;

		wp_cache_add_global_groups( CACHE_GROUP );

		add_filter( 'code_snippets/execute_snippets', array( $this, 'disable_snippet_execution' ), 5 );

		if ( isset( $_REQUEST['snippets-safe-mode'] ) ) {
			add_filter( 'home_url', array( $this, 'add_safe_mode_query_var' ) );
			add_filter( 'admin_url', array( $this, 'add_safe_mode_query_var' ) );
		}
	}

	/**
	 * Initialise classes and include files
	 */
	public function load_plugin() {
		$includes_path = __DIR__;

		// Database operation functions.
		$this->db = new DB();

		// Snippet operation functions.
		require_once $includes_path . '/snippet-ops.php';

		// CodeMirror editor functions.
		require_once $includes_path . '/editor.php';

		// General Administration functions.
		if ( is_admin() ) {
			$this->admin = new Admin();
		}

		// Settings component.
		require_once $includes_path . '/settings/settings-fields.php';
		require_once $includes_path . '/settings/editor-preview.php';
		require_once $includes_path . '/settings/settings.php';

		$this->rest_api = new REST_API();
		$this->active_snippets = new Active_Snippets();
		$this->frontend = new Frontend();

		$upgrade = new Upgrade( $this->version, $this->db );
		add_action( 'plugins_loaded', array( $upgrade, 'run' ), 0 );
	}

	/**
	 * Disable snippet execution if the necessary query var is set
	 *
	 * @param bool $execute_snippets Current filter value.
	 *
	 * @return bool New filter value.
	 */
	public function disable_snippet_execution( $execute_snippets ) {
		return ! empty( $_REQUEST['snippets-safe-mode'] ) && $this->current_user_can() ? false : $execute_snippets;
	}

	/**
	 * Determine whether the menu is full or compact
	 *
	 * @return bool
	 */
	public function is_compact_menu() {
		return ! is_network_admin() && apply_filters( 'code_snippets_compact_menu', false );
	}

	/**
	 * Fetch the admin menu slug for a menu.
	 *
	 * @param string $menu Name of menu to retrieve the slug for.
	 *
	 * @return string The menu's slug.
	 */
	public function get_menu_slug( $menu = '' ) {
		$add = array( 'single', 'add', 'add-new', 'add-snippet', 'new-snippet', 'add-new-snippet' );
		$edit = array( 'edit', 'edit-snippet' );
		$import = array( 'import', 'import-snippets', 'import-code-snippets' );
		$settings = array( 'settings', 'snippets-settings' );

		if ( in_array( $menu, $edit, true ) ) {
			return 'edit-snippet';
		} elseif ( in_array( $menu, $add, true ) ) {
			return 'add-snippet';
		} elseif ( in_array( $menu, $import, true ) ) {
			return 'import-code-snippets';
		} elseif ( in_array( $menu, $settings, true ) ) {
			return 'snippets-settings';
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
	public function get_menu_url( $menu = '', $context = 'self' ) {
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

		if ( 'network' === $context || 'snippets-settings' === $slug ) {
			return network_admin_url( $url );
		} elseif ( 'admin' === $context ) {
			return admin_url( $url );
		} else {
			return self_admin_url( $url );
		}
	}

	/**
	 * Fetch the admin menu slug for a snippets menu
	 *
	 * @param int    $snippet_id Snippet ID.
	 * @param string $context    URL scheme to use.
	 *
	 * @return string The URL to the edit snippet page for that snippet.
	 */
	public function get_snippet_edit_url( $snippet_id, $context = 'self' ) {
		return add_query_arg(
			'id',
			absint( $snippet_id ),
			$this->get_menu_url( 'edit', $context )
		);
	}

	/**
	 * Determine whether the current user can perform actions on snippets.
	 *
	 * @return boolean Whether the current user has the required capability
	 * @since 2.8.6
	 */
	public function current_user_can() {
		return current_user_can( $this->get_cap() );
	}

	/**
	 * Retrieve the name of the capability required to manage sub-site snippets
	 *
	 * @return string
	 */
	public function get_cap_name() {
		return apply_filters( 'code_snippets_cap', 'manage_options' );
	}

	/**
	 * Retrieve the name of the capability required to manage network snippets
	 *
	 * @return string
	 */
	public function get_network_cap_name() {
		return apply_filters( 'code_snippets_network_cap', 'manage_network_options' );
	}

	/**
	 * Get the required capability to perform a certain action on snippets.
	 * Does not check if the user has this capability or not.
	 *
	 * If multisite, checks if *Enable Administration Menus: Snippets* is active
	 * under the *Settings > Network Settings* network admin menu
	 *
	 * @return string The capability required to manage snippets
	 * @since 2.0
	 */
	public function get_cap() {

		if ( is_multisite() ) {
			$menu_perms = get_site_option( 'menu_items', array() );

			// If multisite is enabled and the snippet menu is not activated, restrict snippet operations to super admins only.
			if ( empty( $menu_perms['snippets'] ) ) {
				return $this->get_network_cap_name();
			}
		}

		return $this->get_cap_name();
	}

	/**
	 * Inject the safe mode query var into URLs
	 *
	 * @param string $url Original URL.
	 *
	 * @return string Modified URL.
	 */
	public function add_safe_mode_query_var( $url ) {

		if ( isset( $_REQUEST['snippets-safe-mode'] ) ) {
			return add_query_arg( 'snippets-safe-mode', (bool) $_REQUEST['snippets-safe-mode'], $url );
		}

		return $url;
	}

	/**
	 * Retrieve a list of available snippet types and their labels.
	 *
	 * @return array<string, string> Snippet types.
	 */
	public static function get_types() {
		return apply_filters(
			'code_snippets_types',
			array(
				'php'  => __( 'Functions', 'code-snippets' ),
				'html' => __( 'Content', 'code-snippets' ),
				'css'  => __( 'Styles', 'code-snippets' ),
				'js'   => __( 'Scripts', 'code-snippets' ),
			)
		);
	}

	/**
	 * Determine whether a snippet type is Pro-only.
	 *
	 * @param string $type Snippet type name.
	 *
	 * @return bool
	 */
	public static function is_pro_type( $type ) {
		return 'css' === $type || 'js' === $type;
	}

	/**
	 * Retrieve the description for a particular snippet type.
	 *
	 * @param string $type Snippet type name.
	 *
	 * @return string
	 */
	public function get_type_description( $type ) {
		$descriptions = array(
			'php'  => __( 'Function snippets are run on your site as if there were in a plugin or theme functions.php file.', 'code-snippets' ),
			'html' => __( 'Content snippets are bits of reusable PHP and HTML content that can be inserted into posts and pages.', 'code-snippets' ),
			'css'  => __( 'Style snippets are written in CSS and loaded in the admin area or on the site front-end, just like the theme style.css.', 'code-snippets' ),
			'js'   => __( 'Script snippets are loaded on the site front-end in a JavaScript file, either in the head or body sections.', 'code-snippets' ),
		);

		$descriptions = apply_filters( 'code_snippets/plugins/type_descriptions', $descriptions );

		return isset( $descriptions[ $type ] ) ? $descriptions[ $type ] : '';
	}
}
