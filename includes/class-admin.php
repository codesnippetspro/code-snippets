<?php

/**
 * Contains the class for handling the administration interface
 *
 * @package    Code_Snippets
 * @subpackage Administration
 */

/**
 * This class handles the admin interface for Code Snippets
 *
 * Don't directly access the methods in this class or attempt to
 * re-initialize it. Instead, use the instance in $code_snippets->admin
 *
 * @since      1.7.1
 * @package    Code_Snippets
 * @subpackage Administration
 */
class Code_Snippets_Admin {

	/**
	 * The full URLs to the admin pages
	 *
	 * @var    string
	 * @since  1.7.1
	 * @access public
	 */
	public $manage_url, $single_url, $import_url = '';

	/**
	 * The hooks for the admin pages
	 * Used primarily for enqueueing scripts and styles
	 *
	 * @var    string
	 * @since  1.7.1
	 * @access public
	 */
	public $manage_page, $single_page, $import_page = '';

	/**
	 * Initializes the variables and
	 * loads everything needed for the class
	 *
	 * @since 1.7.1
	 */
	function __construct() {
		global $code_snippets;

		$this->manage_slug = apply_filters( 'code_snippets_admin_manage', 'snippets' );
		$this->single_slug = apply_filters( 'code_snippets_admin_single', 'snippet' );

		$this->manage_url  = self_admin_url( 'admin.php?page=' . $this->manage_slug );
		$this->single_url  = self_admin_url( 'admin.php?page=' . $this->single_slug );

		$this->setup_hooks();
	}

	/**
	 * Register action and filter hooks
	 *
	 * @since  1.7.1
	 * @access private
	 * @return void
	 */
	function setup_hooks() {
		global $code_snippets;

		/* add the administration menus */
		add_action( 'admin_menu', array( $this, 'add_admin_menus' ), 5 );
		add_action( 'network_admin_menu', array( $this, 'add_admin_menus' ), 5 );

		/* register the importer */
		add_action( 'admin_init', array( $this, 'load_importer' ) );
		add_action( 'network_admin_menu', array( $this, 'add_import_admin_menu' ) );

		/* add helpful links to the Plugins menu */
		add_filter( 'plugin_action_links_' . $code_snippets->basename, array( $this, 'settings_link' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_meta' ), 10, 2 );

		/* Add a custom icon to Snippets menu pages */
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_icon_style' ) );

		/* Add the description editor to the Snippets > Add New page */
		add_action( 'code_snippets_admin_single', array( $this, 'description_editor_box' ), 5 );

		/* Handle saving the user's screen option preferences */
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );

		/* Allow super admins to control site admins access to snippet admin menus */
		add_filter( 'mu_menu_items', array( $this, 'mu_menu_items') );
	}

	/**
	 * Handles saving the user's snippets per page preference
	 *
	 * @param  unknown $status
	 * @param  string  $option
	 * @param  unknown $value
	 * @return unknown
	 */
	function set_screen_option( $status, $option, $value ) {
		if ( 'snippets_per_page' === $option )
			return $value;
	}

	/**
	 * Allow super admins to control site admin access to
	 * snippet admin menus
	 *
	 * Adds a checkbox to the *Settings > Network Settings*
	 * network admin menu
	 *
	 * @since  1.7.1
	 * @access private
	 *
	 * @param  array $menu_items The current mu menu items
	 * @return array             The modified mu menu items
	 */
	function mu_menu_items( $menu_items ) {
		$menu_items['snippets'] = __('Snippets', 'code-snippets');
		return $menu_items;
	}

	/**
	 * Load the Code Snippets importer
	 *
	 * Add both an importer to the Tools menu
	 * and an Import Snippets page to the network admin menu
	 *
	 * @since  1.6
	 * @access private
	 * @return void
	 */
	function load_importer() {

		if ( defined( 'WP_LOAD_IMPORTERS' ) ) {

			/* Load Importer API */
			require_once ABSPATH . 'wp-admin/includes/import.php';

			if ( ! class_exists( 'WP_Importer' ) ) {
				$class_wp_importer = ABSPATH .  'wp-admin/includes/class-wp-importer.php';
				if ( file_exists( $class_wp_importer ) )
					require_once $class_wp_importer;
			}

			/* Register the Code Snippets importer with WordPress */

			register_importer(
				'code-snippets',
				__('Code Snippets', 'code-snippets'),
				__('Import snippets from a Code Snippets export file', 'code-snippets'),
				array( $this, 'display_import_menu' )
			);
		}

		$this->import_url = self_admin_url( 'admin.php?import=code-snippets' );
		add_action( 'load-importer-code-snippets', array( $this, 'load_import_menu' ) );
	}

	/**
	 * Load contextual help tabs for an admin screen.
	 *
	 * @since  1.7.2
	 * @access public
	 * @param  string $slug The file handle (filename with no path or extension) to load
	 * @return void
	 */
	public function load_help_tabs( $slug ) {
		global $code_snippets;
		include $code_snippets->plugin_dir . "admin/help/{$slug}.php";
	}

	/**
	 * Load an admin view template
	 *
	 * @since  1.7.2
	 * @access public
	 * @param  string $slug The file handle (filename with no path or extension) to load
	 * @return void
	 */
	public function get_view( $slug ) {
		global $code_snippets;
		require $code_snippets->plugin_dir . "admin/views/{$slug}.php";
	}

	/**
	 * Display the admin status and error messages
	 *
	 * @since  1.7.2
	 * @access public
	 * @param  string $slug The file handle (filename with no path or extension) to load
	 * @return void
	 */
	public function get_messages( $slug ) {
		global $code_snippets;
		require $code_snippets->plugin_dir . "admin/messages/{$slug}.php";
	}

	/**
	 * Add the dashboard admin menu and subpages
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @uses   add_menu_page()    To register a top-level menu
	 * @uses   add_submenu_page() To register a submenu page
	 * @uses   apply_filters()    To retrieve the current menu slug
	 * @uses   plugins_url()      To retrieve the URL to a resource
	 * @return void
	 */
	function add_admin_menus() {
		global $code_snippets;

		/* Use a different screen icon for the MP6 interface */
		if ( get_user_option( 'admin_color' )  !== 'mp6' )
			$menu_icon = apply_filters( 'code_snippets_menu_icon', plugins_url( 'assets/menu-icon.png', $code_snippets->file ) );
		else
			$menu_icon = 'div';

		/* Add the top-level menu and associated subpage */
		$this->manage_page = add_menu_page(
			__('Snippets', 'code-snippets'),
			__('Snippets', 'code-snippets'),
			$code_snippets->get_cap( 'manage' ),
			$this->manage_slug,
			array( $this, 'display_manage_menu' ),
			$menu_icon,
			is_network_admin() ? 21 : 67
		);

		add_submenu_page(
			$this->manage_slug,
			__('Snippets', 'code-snippets'),
			__('Manage', 'code-snippets'),
			$code_snippets->get_cap( 'manage' ),
			$this->manage_slug,
			array( $this, 'display_manage_menu')
		);

		/* Add the Edit/Add New Snippet page */
		$editing = ( isset( $_REQUEST['page'], $_REQUEST['edit'] ) && $this->single_slug === $_REQUEST['page'] );

		$this->single_page = add_submenu_page(
			$this->manage_slug,
			$editing ? __('Edit Snippet', 'code-snippets') : __('Add New Snippet', 'code-snippets'),
			$editing ? __('Edit', 'code-snippets') : __('Add New', 'code-snippets'),
			$code_snippets->get_cap( 'install' ),
			$this->single_slug,
			array( $this, 'display_single_menu' )
		);

		add_action( "load-$this->manage_page", array( $this, 'load_manage_menu' ) );
		add_action( "load-$this->single_page", array( $this, 'load_single_menu' ) );

		add_action( "load-$this->manage_page", array( $code_snippets, 'maybe_create_tables' ) );
		add_action( "load-$this->single_page", array( $code_snippets, 'maybe_create_tables' ) );
	}

	/**
	 * Add an Import Snippets page to the network admin menu.
	 * We need to do this as there is no Tools menu in the network
	 * admin, and so we cannot register an importer
	 *
	 * @since  1.6
	 * @access private
	 * @uses   add_submenu_page() To register the menu page
	 * @uses   apply_filters()    To retrieve the current menu slug
	 * @uses   add_action()       To enqueue scripts and styles
	 * @return void
	 */
	function add_import_admin_menu() {
		global $code_snippets;

		$this->import_page = add_submenu_page(
			$this->manage_slug,
			__('Import Snippets', 'code-snippets'),
			__('Import', 'code-snippets'),
			$code_snippets->get_cap( 'import' ),
			'import-code-snippets',
			array( $this, 'display_import_menu' )
		);

		$this->import_url = self_admin_url( 'admin.php?page=import-code-snippets' );
		add_action( "load-$this->import_page", array( $this, 'load_import_menu' ) );
		add_action( "load-$this->import_page", array( $code_snippets, 'maybe_create_tables' ) );
	}

	/**
	 * Enqueue the icon stylesheet
	 *
	 * @since  1.0
	 * @access private
	 * @uses   wp_enqueue_style() To add the stylesheet to the queue
	 * @uses   get_user_option()  To check if MP6 mode is active
	 * @uses   plugins_url        To retrieve a URL to assets
	 * @return void
	 */
	function load_admin_icon_style() {
		global $code_snippets;

		$stylesheet = ( 'mp6' === get_user_option( 'admin_color' ) ? 'menu-icon.mp6' : 'screen-icon' );

		wp_enqueue_style(
			'icon-snippets',
			plugins_url( "assets/{$stylesheet}.css", $code_snippets->file ),
			false,
			$code_snippets->version
		);
	}

	/**
	 * Initializes the list table class and loads the help tabs
	 * for the Manage Snippets page
	 *
	 * @since  1.0
	 * @access private
	 * @return void
	 */
	function load_manage_menu() {
		global $code_snippets;

		/* Load the screen help tabs */
		$this->load_help_tabs( 'manage' );

		/* Initialize the snippet table class */
		$code_snippets->get_include( 'class-list-table' );
		$code_snippets->list_table = new Code_Snippets_List_Table();
		$code_snippets->list_table->prepare_items();
	}

	/**
	 * Loads the help tabs for the Edit Snippets page
	 *
	 * @since  1.0
	 * @access private
	 * @return void
	 *
	 * @uses   $wpdb       To save the posted snippet to the database
	 * @uses   wp_redirect To pass the results to the page
	 */
	function load_single_menu() {
		global $code_snippets;

		$screen = get_current_screen();

		/* Don't let the user pass if they can't edit (install check is done by WP) */
		if ( isset( $_REQUEST['edit'] ) && ! $code_snippets->user_can( 'edit' ) )
			wp_die( __("Sorry, you're not allowed to edit snippets", 'code-snippets') );

		/* Save the snippet if one has been submitted */
		if ( isset( $_REQUEST['save_snippet'] ) || isset( $_REQUEST['save_snippet_activate'] ) ) {

			/* Set the snippet to active if we used the 'Save Changed & Activate' button */
			if ( isset( $_REQUEST['save_snippet_activate'] ) )
				$_POST['snippet_active'] = 1;

			/* Save the snippet to the database */
			$result = $code_snippets->save_snippet( $_POST );

			/* Strip old status query vars from URL */
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'added', 'updated', 'activated', 'invalid' ) );

			/* Build the status message and redirect */

			if ( isset( $_REQUEST['save_snippet_activate'] ) && $result ) {
				/* Snippet was activated */
				$_SERVER['REQUEST_URI'] = add_query_arg( 'activated', true );
			}

			if ( ! $result || $result < 1 ) {
				/* An error occurred */
				wp_redirect( add_query_arg( 'invalid', true ) );
			}
			elseif ( isset( $_REQUEST['snippet_id'] ) ) {
				/* Existing snippet was updated */
				wp_redirect( add_query_arg(	array( 'edit' => $result, 'updated' => true ) ) );
			}
			else {
				/* New snippet was added */
				wp_redirect( add_query_arg( array( 'edit' => $result, 'added' => true ) ) );
			}
		}

		/* Load the screen help tabs */
		$this->load_help_tabs( 'single' );

		/* Enqueue the code editor and other scripts and styles */
		add_filter( 'admin_enqueue_scripts', array( $this, 'single_menu_enqueue_scripts' ) );
	}

	/**
	 * Registers and loads the code editor's scripts
	 *
	 * @since  1.7
	 * @access private
	 *
	 * @uses   wp_register_script()
	 * @uses   wp_register_style()
	 * @uses   wp_enqueue_script() To add the scripts to the queue
	 * @uses   wp_enqueue_style()  To add the stylesheets to the queue
	 *
	 * @param  string $hook        The current page hook, to be compared with the single snippet page hook
	 * @return void
	 */
	function single_menu_enqueue_scripts( $hook ) {
		global $code_snippets;

		/* If we're not on the right admin page, bail early */
		if ( $hook !== $this->single_page )
			return;

		/* CodeMirror package version */
		$codemirror_version = '3.13';

		/* CodeMirror base framework */

		wp_register_script(
			'codemirror',
			plugins_url( 'vendor/codemirror/lib/codemirror.js', $code_snippets->file ),
			false,
			$codemirror_version
		);

		wp_register_style(
			'codemirror',
			plugins_url( 'vendor/codemirror/lib/codemirror.css', $code_snippets->file ),
			false,
			$codemirror_version
		);

		/* CodeMirror modes */

		$modes = array( 'php', 'clike' );

		foreach ( $modes as $mode ) {

			wp_register_script(
				"codemirror-mode-$mode",
				plugins_url( "vendor/codemirror/mode/$mode.js", $code_snippets->file ),
				array( 'codemirror' ),
				$codemirror_version
			);
		}

		/* CodeMirror addons */

		$addons = array( 'dialog', 'searchcursor', 'search', 'matchbrackets' );

		foreach ( $addons as $addon ) {

			wp_register_script(
				"codemirror-addon-$addon",
				plugins_url( "vendor/codemirror/addon/$addon.js", $code_snippets->file ),
				array( 'codemirror' ),
				$codemirror_version
			);
		}

		wp_register_style(
			'codemirror-addon-dialog',
			plugins_url( 'vendor/codemirror/addon/dialog.css', $code_snippets->file ),
			array( 'codemirror' ),
			$codemirror_version
		);

		/* Enqueue the registered scripts */
		wp_enqueue_script( array(
			'codemirror-addon-matchbrackets',
			'codemirror-mode-clike',
			'codemirror-mode-php',
			'codemirror-addon-search',
		) );

		/* Enqueue the registered stylesheets */
		wp_enqueue_style( array(
			'codemirror',
			'codemirror-addon-dialog',
		) );

		/* Enqueue custom styling */
		wp_enqueue_style(
			'code-snippets-admin-single',
			plugins_url( 'assets/admin-single.css', $code_snippets->file ),
			false,
			$code_snippets->version
		);

		/* Enqueue custom scripts */
		wp_enqueue_script(
			'code-snippets-admin-single',
			plugins_url( 'assets/admin-single.js', $code_snippets->file ),
			false,
			$code_snippets->version,
			true // Load in footer
		);
	}

	/**
	 * Processes import files and loads the help tabs for the Import Snippets page
	 *
	 * @since  1.3
	 *
	 * @uses   $code_snippets->import() To process the import file
	 * @uses   wp_redirect()            To pass the import results to the page
	 * @uses   add_query_arg()          To append the results to the current URI
	 * @uses   $this->load_help_tabs()  To load the screen contextual help tabs
	 *
	 * @param  string $file             A filesystem path to the import file
	 * @return void
	 */
	function load_import_menu() {
		global $code_snippets;

		/* Process import files */

		if ( isset( $_FILES['code_snippets_import_file']['tmp_name'] ) ) {

			/* Import the snippets. The result is the number of snippets that were imported */
			$imported = $code_snippets->import( $_FILES['code_snippets_import_file']['tmp_name'] );

			/* Send the amount of imported snippets to the page */
			if ( $imported ) {
				wp_redirect( add_query_arg( 'imported', $imported ) );
			}
		}

		/* Load the screen help tabs */
		$this->load_help_tabs( 'import' );
	}

	/**
	 * Displays the manage snippets page
	 *
	 * @since  1.0
	 * @access private
	 * @uses   $this->get_view() To load an admin view template
	 * @return void
	 */
	function display_manage_menu() {
		$this->get_view( 'manage' );
	}

	/**
	 * Displays the single snippet page
	 *
	 * @since  1.0
	 * @access private
	 * @uses   $this->get_view() To load an admin view template
	 * @return void
	 */
	function display_single_menu() {
		$this->get_view( 'single' );
	}

	/**
	 * Displays the import snippets page
	 *
	 * @since  1.3
	 * @access private
	 * @uses   $this->get_view() To load an admin view template
	 * @return void
	 */
	function display_import_menu() {
		$this->get_view( 'import' );
	}

	/**
	 * Add a description editor to the single snippet page
	 *
	 * @since  1.7
	 * @access private
	 * @param  object $snippet The snippet being used for this page
	 * @return void
	 */
	function description_editor_box( $snippet ) {

		?>

		<label for="snippet_description">
			<h3><div style="position: absolute;"><?php _e('Description', 'code-snippets'); ?></div></h3>
		</label>

		<?php

		remove_editor_styles(); // stop custom theme styling interfering with the editor

		wp_editor(
			$snippet->description,
			'description',
			apply_filters( 'code_snippets_description_editor_settings', array(
				'textarea_name' => 'snippet_description',
				'textarea_rows' => 10,
				'teeny' => true,
				'media_buttons' => false,
			) )
		);
	}

	/**
	 * Adds a link pointing to the Manage Snippets page
	 *
	 * @since  1.0
	 * @access private
	 * @param  array $links The existing plugin action links
	 * @return array        The modified plugin action links
	 */
	function settings_link( $links ) {
		array_unshift( $links, sprintf(
			'<a href="%1$s" title="%2$s">%3$s</a>',
			$this->manage_url,
			__('Manage your existing snippets', 'code-snippets'),
			__('Manage', 'code-snippets')
		) );
		return $links;
	}

	/**
	 * Adds extra links related to the plugin
	 *
	 * @since  1.2
	 * @access private
	 * @param  array  $links The existing plugin info links
	 * @param  string $file  The plugin the links are for
	 * @return array         The modified plugin info links
	 */
	function plugin_meta( $links, $file ) {
		global $code_snippets;

		/* We only want to affect the Code Snippets plugin listing */
		if ( $file !== $code_snippets->basename )
			return $links;

		$format = '<a href="%1$s" title="%2$s">%3$s</a>';

		/* array_merge appends the links to the end */
		return array_merge( $links, array(
			sprintf( $format,
				'http://wordpress.org/extend/plugins/code-snippets/',
				__('Visit the WordPress.org plugin page', 'code-snippets'),
				__('About', 'code-snippets')
			),
			sprintf( $format,
				'http://wordpress.org/support/plugin/code-snippets/',
				__('Visit the support forums', 'code-snippets'),
				__('Support', 'code-snippets')
			),
			sprintf( $format,
				'http://code-snippets.bungeshea.com/donate/',
				__("Support this plugin's development", 'code-snippets'),
				__('Donate', 'code-snippets')
			)
		) );
	}

} // end of class
