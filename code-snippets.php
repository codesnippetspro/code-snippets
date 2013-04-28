<?php

/**
 * Code Snippets - An easy, clean and simple way to add code snippets to your site.
 *
 * If you're interested in helping to develop Code Snippets, or perhaps
 * contribute to the localization, please see http://code-snippets.bungeshea.com
 *
 * @package Code Snippets
 * @subpackage Main
 */

/*
 * Plugin Name: Code Snippets
 * Plugin URI: http://code-snippets.bungeshea.com
 * Description: An easy, clean and simple way to add code snippets to your site. No need to edit to your theme's functions.php file again!
 * Author: Shea Bunge
 * Author URI: http://bungeshea.com
 * Version: 1.7.1
 * License: MIT
 * License URI: license.txt
 * Text Domain: code-snippets
 * Domain Path: /languages/
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Code_Snippets' ) ) :

/**
 * The main class for our plugin.
 * It all happens here, folks
 *
 * Please use the global variable $code_snippets to access
 * the methods or variables in this class. Anything you need
 * to access should be publicly available there
 *
 * @since 1.0
 * @package Code Snippets
 * @access private
 */
final class Code_Snippets {

	/**
	 * The version number for this release of the plugin.
	 * This will later be used for upgrades and enqueueing files
	 *
	 * This should be set to the 'Plugin Version' value,
	 * as defined above in the plugin header
	 *
	 * @since 1.0
	 * @access public
	 * @var string A PHP-standardized version number string
	 */
	public $version = '1.7.1';

	/**
	 * Variables to hold plugin paths
	 *
	 * @since 1.0
	 * @access public
	 * @var string
	 */
	public $file, $basename, $plugin_dir, $plugin_url;

	/**
	 * Stores an instance of the list table class
	 *
	 * @since 1.5
	 * @access public
	 * @see Code_Snippets_List_Table
	 */
	public $list_table;

	/**
	 * Stores an instance of the administration class
	 *
	 * @since Code_Snippets 1.7.1
	 * @access public
	 * @see Code_Snippets_Admin
	 */
	public $admin;

	/**
	 * Used by maybe_create_tables() for bailing early
	 * @var boolean
	 */
	static $tables_created = false;

	/**
	 * Stores the snippet table names
	 *
	 * It's better to use $wpdb->snippets and
	 * $wpdb->ms_snippets, but these are maintained
	 * as references for backwards-compatibility
	 *
	 * @var string
	 */
	public $table, $ms_table;

	/**
	 * These are now deprecated in favor of those in
	 * the Code_Snippets_Admin class, but maintained as
	 * references so we don't break existing code
	 *
	 * @since 1.0
	 * @deprecated Moved to the Code_Snippets_Admin class in 1.7.1
	 * @access public
	 * @var string
	 */
	public $admin_manage, $admin_single, $admin_import, $admin_manage_url, $admin_single_url, $admin_import_url;

	/**
	 * The constructor function for our class
	 *
	 * This method is called just as this plugin is included,
	 * so other plugins may not have loaded yet. Only do stuff
	 * here that really can't wait
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return void
	 */
	function __construct() {

		/* Initialize the variables holding the snippet table names */
		$this->set_table_vars();

		/* Add backwards-compatibly for the CS_SAFE_MODE constant */
		if ( defined( 'CS_SAFE_MODE' ) && ! defined( 'CODE_SNIPPETS_SAFE_MODE' ) ) {
			define( 'CODE_SNIPPETS_SAFE_MODE', CS_SAFE_MODE );
		}

		/* Execute the snippets once the plugins are loaded */
		add_action( 'plugins_loaded', array( $this, 'run_snippets' ), 1 );

		/* Hook our initialize function to the plugins_loaded action */
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Load the plugin completely
	 *
	 * This method is called *after* other plugins
	 * have been run
	 *
	 * @since 1.7
	 * @access public
	 *
	 * @return void
	 */
	public function init() {

		/* Initialize core variables */
		$this->setup_vars();

		/* Check if we need to change some stuff */
		$this->upgrade();

		/*
		 * Load up the localization file if we're using WordPress in a different language.
		 * Place it in this plugin's "languages" folder and name it "code-snippets-[value in wp-config].mo"
		 *
		 * If you wish to contribute a language file to be included in the Code Snippets package,
		 * please see create an issue on GitHub: https://github.com/bungeshea/code-snippets/issues
		 */
		load_plugin_textdomain( 'code-snippets', false, dirname( $this->basename ) . '/languages/' );

		/*
		 * Cleanup the plugin data on uninstall
		 */
		register_uninstall_hook( $this->file, array( __CLASS__, 'uninstall' ) );

		/**
		 * Let extension plugins know that it's okay to load
		 */
		do_action( 'code_snippets_init' );
	}

	/**
	 * Initialize variables
	 *
	 * @since 1.2
	 * @access private
	 *
	 * @return void
	 */
	function setup_vars() {

		/* Plugin directory variables */
		$this->file       = __FILE__;
		$this->basename	  = plugin_basename( $this->file );
		$this->plugin_dir = plugin_dir_path( $this->file );
		$this->plugin_url = plugin_dir_url ( $this->file );

		if ( is_admin() ) {

			/* Load our administration class */
			require_once $this->plugin_dir . 'includes/class-admin.php';
			$this->admin = new Code_Snippets_Admin;

			/* Remap deprecated variables */
			$this->admin_manage_url	= &$this->admin->manage_url;
			$this->admin_single_url = &$this->admin->single_url;
			$this->admin_import_url = &$this->admin->import_url;

			$this->admin_manage = &$this->admin->manage_page;
			$this->admin_single = &$this->admin->single_page;
			$this->admin_import = &$this->admin->import_page;
		}
	}

	/**
	 * Register the snippet table names with WordPress
	 *
	 * @since 1.7
	 * @access public
	 *
	 * @uses $wpdb
	 *
	 * @return void
	 */
	public function set_table_vars() {
		global $wpdb;

		/* Register the snippet table names with WordPress */
		$wpdb->tables[] = 'snippets';
		$wpdb->ms_global_tables[] = 'ms_snippets';

		/* Setup initial table variables */
		$wpdb->snippets = $wpdb->prefix . 'snippets';
		$wpdb->ms_snippets = $wpdb->base_prefix . 'ms_snippets';

		/* Add a pointer to the old variables */
		$this->table = &$wpdb->snippets;
		$this->ms_table = &$wpdb->ms_snippets;
	}

	/**
	 * Return the appropriate snippet table name
	 *
	 * @since 1.6
	 * @access public
	 *
	 * @param string  $scope        Retrieve the multisite table name or the site table name?
	 * @param bool    $check_screen Query the current screen if no scope passed?
	 * @return string $table        The snippet table name
	 */
	public function get_table_name( $scope = '', $check_screen = true ) {

		global $wpdb;

		$this->maybe_create_tables(); // create the snippet tables if they do not exist

		if ( ! is_multisite() ) {
			$network = false;
		}
		elseif ( in_array( $scope, array( 'multisite', 'network' ) ) ) {
			$network = true;
		}
		elseif ( empty( $scope ) && $check_screen && function_exists( 'get_current_screen' ) ) {
			/* if no scope is set, query the current screen to see if in network admin */
			$network = get_current_screen()->is_network;
		}
		else {
			$network = false;
		}

		$table = ( $network ? $wpdb->ms_snippets : $wpdb->snippets );

		return $table;
	}

	/**
	 * Create the snippet tables if they do not already exist
	 *
	 * @since 1.7.1
	 * @access public
	 *
	 * @uses $this->create_table() To create a single snippet table
	 * @uses $wpdb->get_var() To test of the table exists
	 * @uses self::$tables_created To check if we've already done this or not
	 *
	 * @param bool $force Force table creation/upgrade
	 *
	 * @return void
	 */
	public function maybe_create_tables( $force = false ) {

		/* Bail early if we've done this already */
		if ( self::$tables_created && ! $force ) return;

		global $wpdb;

		if ( ! isset( $wpdb->snippets, $wpdb->ms_snippets ) )
			$this->set_table_vars();

		$table_exists = $force || $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->snippets'" ) === $wpdb->snippets;

		if ( ! is_multisite() && ! $table_exists ) {
			$table = $wpdb->snippets;
		}
		elseif ( is_multisite() ) {
			$ms_table_exists = $force || $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->ms_snippets'" ) === $wpdb->ms_snippets;

			if ( ! $ms_table_exists && ! $table_exists )
				$table = $wpdb->snippets . ',' . $wpdb->ms_snippets;
			elseif ( ! $table_exists && $ms_table_exists )
				$table = $wpdb->snippets;
			elseif ( $table_exists && ! $ms_table_exists )
				$table = $wpdb->ms_snippets;
		}

		if ( isset( $table ) )
			$this->create_table( $table );

		self::$tables_created = true;
	}

	/**
	 * Create the snippet tables if they do not already exist
	 *
	 * @since Code Snippets 1.2
	 * @deprecated Code Snippets 1.7.1
	 * @access public
	 *
	 * @return void
	 */
	public function create_tables() {
		_deprecated_function(
			'$code_snippets->create_tables()',
			'Code Snippets 1.7.1',
			'$code_snippets->maybe_create_tables()'
		);
		$this->maybe_create_tables();
	}

	/**
	 * Create a single snippet table
	 * if one of the same name does not already exist
	 *
	 * @since 1.6
	 * @access private
	 *
	 * @uses dbDelta() To add the table to the database
	 *
	 * @param string $table_name The name of the table to create
	 * @return void
	 */
	function create_table( $table_name ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;

		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		$table_columns = apply_filters( 'code_snippets_database_table_columns', array(
			'name         tinytext    not null',
			'description  text',
			'code         longtext    not null',
		) );

		$table_columns_sql = implode( ",\n", $table_columns );

		$sql = "CREATE TABLE $table_name (
					id bigint(20)  unsigned not null auto_increment,
					{$table_columns_sql},
					active tinyint(1)  not null default 0,
				PRIMARY KEY  (id),
					KEY id (id)

				) {$charset_collate};";

		dbDelta( apply_filters( 'code_snippets_table_sql', $sql ) );

		do_action( 'code_snippets_create_table', $table_name );
	}

	/**
	 * Preform upgrade tasks such as deleting and updating options
	 *
	 * @since 1.2
	 * @access private
	 *
	 * @return void
	 */
	function upgrade() {
		global $wpdb;

		// get the current plugin version from the database

		$current_version = get_option( 'code_snippets_version' );

		if ( ! $current_version && get_option( 'cs_db_version' ) ) {
			$current_version = get_option( 'cs_db_version' );
			delete_option( 'cs_db_version' );
			add_option( 'code_snippets_version', $current_version );
		}

		$previous_version = ( $current_version ? $current_version : $this->version );

		/* skip this if we're on the latest version */
		if ( version_compare( $current_version, $this->version, '<' ) ) {

			// Register the capabilities once only
			if ( version_compare( $current_version, '1.5',  '<' ) ) {
				$this->setup_roles( true );
			}

			if ( version_compare( $previous_version, '1.2', '<' ) ) {
				// The 'Complete Uninstall' option was removed in version 1.2
				delete_option( 'cs_complete_uninstall' );
			}

			// Update the current version stored in the database
			update_option( 'code_snippets_version', $this->version );
		}

		/* Multisite-only upgrades */

		if ( is_multisite() && is_main_site() ) {

			$current_ms_version = get_site_option( 'code_snippets_version' );

			if ( version_compare( $current_ms_version, $this->version, '<' ) ) {

				if ( version_compare( $current_ms_version, '1.5', '<' ) ) {
					$this->setup_ms_roles( true );
				}

				// migrate the recently_network_activated_snippets to the site options
				if ( get_option( 'recently_network_activated_snippets' ) ) {
					add_site_option( 'recently_activated_snippets', get_option( 'recently_network_activated_snippets', array() ) );
					delete_option( 'recently_network_activated_snippets' );
				}

			}

			update_site_option( 'code_snippets_version', $this->version );
		}

	}

	/**
	 * Register the user roles and capabilities
	 *
	 * @since 1.5
	 * @access private
	 *
	 * @param bool $install true to add the capabilities, false to remove
	 * @return void
	 */
	function setup_roles( $install = true ) {

		$this->caps = apply_filters( 'code_snippets_caps', array(
			'manage_snippets',
			'install_snippets',
			'edit_snippets'
		) );

		$this->role = get_role( apply_filters( 'code_snippets_role', 'administrator' ) );

		foreach( $this->caps as $cap ) {
			if ( $install )
				$this->role->add_cap( $cap );
			else
				$this->role->remove_cap( $cap );
		}
	}

	/**
	 * Register the multisite user roles and capabilities
	 *
	 * @since 1.5
	 * @access private
	 *
	 * @param bool $install true to add the capabilities, false to remove
	 * @return void
	 */
	function setup_ms_roles( $install = true ) {

		if ( ! is_multisite() ) return;

		$this->network_caps = apply_filters( 'code_snippets_network_caps', array(
			'manage_network_snippets',
			'install_network_snippets',
			'edit_network_snippets'
		) );

		$supers = get_super_admins();
		foreach( $supers as $admin ) {
			$user = new WP_User( 0, $admin );
			foreach( $this->network_caps as $cap ) {
				if ( $install )
					$user->add_cap( $cap );
				else
					$user->remove_cap( $cap );
			}
		}
	}

	/**
	 * Check if the current user can perform some action on snippets or not
	 *
	 * @uses   current_user_can() To check if the current user can perform a task
	 * @uses   $this->get_cap() To get the required capability
	 *
	 * @param  string $do_what The task to check against.
	 * @return bool            Whether the current user can perform this task or not
	 *
	 * @since  1.7.2 Moved logic to $this->get_cap method
	 * @since  1.7.1
	 * @access public
	 */
	public function user_can( $do_what ) {
		return current_user_can( $this->get_cap( $do_what ) );
	}

	/**
	 * Get the required capability to perform a certain action on snippets.
	 * Does not check if the user has this capability or not.
	 *
	 * If multisite, checks if *Enable Administration Menus: Snippets* is active
	 * under the *Settings > Network Settings* network admin menu
	 *
	 * @since  1.7.2
	 * @access public
	 */
	public function get_cap( $do_what ) {

		if ( is_multisite() ) {

			if ( in_array( 'snippets', get_site_option( 'menu_items' ) ) )
				return "{$do_what}_snippets";
			else
				return "{$do_what}_network_snippets";

		} else {
			return "{$do_what}_snippets";
		}
	}

	/**
	 * Converts an array of snippet data into a snippet object
	 *
	 * @since 1.7
	 * @access public
	 *
	 * @param mixed $data The snippet data to convert
	 * @return object The resulting snippet object
	 */
	public function build_snippet_object( $data = null ) {

		$snippet = new stdClass;

		// define an empty snippet object (or one with default values )
		$snippet->id = 0;
		$snippet->name = '';
		$snippet->description = '';
		$snippet->code = '';
		$snippet->active = 0;
		$snippet = apply_filters( 'code_snippets_build_default_snippet', $snippet );

		if ( ! isset( $data ) ) {
			return $snippet;
		}
		elseif ( is_object( $data ) ) {
			/* If we already have a snippet object, merge it with the default */
			return (object) array_merge( (array) $snippet, (array) $data );
		}
		elseif ( is_array( $data ) ) {

			if ( isset( $data['id'] ) )
				$snippet->id = $data['id'];
			elseif ( isset( $data['snippet_id'] ) )
				$snippet->id = $data['snippet_id'];

			if ( isset( $data['name'] ) )
				$snippet->name = $data['name'];
			elseif ( isset( $data['snippet_name'] ) )
				$snippet->name = $data['snippet_name'];

			if ( isset( $data['description'] ) )
				$snippet->description = $data['description'];
			elseif ( isset( $data['snippet_description'] ) )
				$snippet->description = $data['snippet_description'];

			if ( isset( $data['code'] ) )
				$snippet->code = $data['code'];
			elseif ( isset( $data['snippet_code'] ) )
				$snippet->code = $data['snippet_code'];


			if ( isset( $data['active' ] ) )
				$snippet->active = $data['active'];
			elseif ( isset( $data['snippet_active'] ) )
				$snippet->active = $data['snippet_active'];

			return apply_filters( 'code_snippets_build_snippet_object', $snippet, $data );
		}

		return $snippet;
	}

	/**
	 * Retrieve a list of snippets from the database
	 *
	 * @since 1.7
	 * @access public
	 *
	 * @uses $wpdb To query the database for snippets
	 * @uses $this->get_table_name() To dynamically retrieve the snippet table name
	 *
	 * @param string $scope Retrieve multisite-wide or site-wide snippets?
	 * @return array An array of snippet objects
	 */
	 public function get_snippets( $scope = '' ) {
		global $wpdb;

		$table = $this->get_table_name( $scope );
		$snippets = $wpdb->get_results( "SELECT * FROM $table", ARRAY_A );

		foreach( $snippets as $index => $snippet ) {
			$snippets[ $index ] = $this->unescape_snippet_data( $snippet );
		}

		return apply_filters( 'code_snippets_get_snippets', $snippets, $scope );
	}

	/**
	 * Escape snippet data for inserting into the database
	 *
	 * @since 1.7
	 * @access public
	 *
	 * @param mixed $snippet An object or array containing the data to escape
	 * @return object The resulting snippet object, with data escaped
	 */
	public function escape_snippet_data( $snippet ) {

		$snippet = $this->build_snippet_object( $snippet );

		/* remove the <?php and ?> tags from the snippet */
		$snippet->code = trim ( $snippet->code );
		$snippet->code = ltrim( $snippet->code, '<?php' );
		$snippet->code = ltrim( $snippet->code, '<?' );
		$snippet->code = rtrim( $snippet->code, '?>' );

		/* escape the data */
		$snippet->name = mysql_real_escape_string( htmlspecialchars( $snippet->name ) );
		$snippet->description = mysql_real_escape_string( htmlspecialchars( $snippet->description ) );
		$snippet->code = mysql_real_escape_string( htmlspecialchars( $snippet->code ) );
		$snippet->id = intval( $snippet->id );

		return apply_filters( 'code_snippets_escape_snippet_data', $snippet );
	}

	/**
	 * Unescape snippet data after retrieval from the database
	 * ready for use
	 *
	 * @since 1.7
	 * @access public
	 *
	 * @param mixed $snippet An object or array containing the data to unescape
	 * @return object The resulting snippet object, with data unescaped
	 */
	public function unescape_snippet_data( $snippet ) {

		$snippet = $this->build_snippet_object( $snippet );

		$snippet->name = htmlspecialchars_decode( stripslashes( $snippet->name ) );
		$snippet->code = htmlspecialchars_decode( stripslashes( $snippet->code ) );
		$snippet->description = htmlspecialchars_decode( stripslashes( $snippet->description ) );

		return apply_filters( 'code_snippets_unescape_snippet_data', $snippet );
	}

	/**
	 * Retrieve a single snippets from the database.
	 * Will return empty snippet object if no snippet
	 * ID is specified
	 *
	 * @since 1.7
	 * @access public
	 *
	 * @uses $wpdb To query the database for snippets
	 * @uses $this->get_table_name() To dynamically retrieve the snippet table name
	 *
	 * @param  int $id The ID of the snippet to retrieve. 0 to build a new snippet
	 * @param  string $scope Retrieve a multisite-wide or site-wide snippet?
	 * @return object A single snippet object
	 */
	 public function get_snippet( $id = 0, $scope = '' ) {
		global $wpdb;
		$table = $this->get_table_name( $scope );

		if ( intval( $id ) > 0 ) {
			/* Retrieve the snippet from the database */
			$snippet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
			/* Unescape the snippet data, ready for use */
			$snippet = $this->unescape_snippet_data( $snippet );
		} else {
			/* Get an empty snippet object */
			$snippet = $this->build_snippet_object();
		}
		return apply_filters( 'code_snippets_get_snippet', $snippet, $id, $scope );
	}

	/**
	 * Activates a snippet
	 *
	 * @since 1.5
	 * @access public
	 *
	 * @uses $wpdb To set the snippets' active status
	 *
	 * @param array $ids The ids of the snippets to activate
	 * @param string $scope Are the snippets multisite-wide or site-wide?
	 * @return void
	 */
	public function activate( $ids, $scope = '' ) {
		global $wpdb;

		$ids = (array) $ids;

		$table = $this->get_table_name( $scope );

		foreach( $ids as $id ) {
			$wpdb->update(
				$table,
				array( 'active' => '1' ),
				array( 'id' => $id ),
				array( '%d' ),
				array( '%d' )
			);

			do_action( 'code_snippets_activate_snippet', $id, $scope );
		}

		do_action( 'code_snippets_activate', $ids, $scope );
	}

	/**
	 * Deactivates selected snippets
	 *
	 * @since 1.5
	 * @access public
	 *
	 * @uses $wpdb To set the snippets' active status
	 *
	 * @param array $ids The IDs of the snippets to deactivate
	 * @param string $scope Are the snippets multisite-wide or site-wide?
	 * @return void
	 */
	public function deactivate( $ids, $scope = '' ) {
		global $wpdb;

		$ids = (array) $ids;
		$recently_active = array();

		$table = $this->get_table_name( $scope );

		foreach( $ids as $id ) {
			$wpdb->update(
				$table,
				array( 'active' => '0' ),
				array( 'id' => $id ),
				array( '%d' ),
				array( '%d' )
			);
			$recently_active = array( $id => time() ) + (array) $recently_active;

			do_action( 'code_snippets_deactivate_snippet', $id, $scope );
		}

		if ( $table === $wpdb->ms_snippets )
			update_site_option(
				'recently_activated_snippets',
				$recently_active + (array) get_site_option( 'recently_activated_snippets' )
			);
		elseif ( $table === $wpdb->snippets )
			update_option(
				'recently_activated_snippets',
				$recently_active + (array) get_option( 'recently_activated_snippets' )
			);

		do_action( 'code_snippets_deactivate', $ids, $scope );
	}

	/**
	 * Deletes a snippet from the database
	 *
	 * @since 1.5
	 * @access public
	 *
	 * @uses $wpdb To access the database
	 * @uses $this->get_table_name() To dynamically retrieve the name of the snippet table
	 *
	 * @param int $id The ID of the snippet to delete
	 * @param string $scope Delete from site-wide or network-wide table?
	 */
	public function delete_snippet( $id, $scope = '' ) {
		global $wpdb;

		$table = $this->get_table_name( $scope );

		$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE id='%d' LIMIT 1", intval( $id ) ) );

		do_action( 'code_snippets_delete_snippet', $id, $scope );
	}

	/**
	 * Saves a snippet to the database.
	 *
	 * @since 1.5
	 * @access public
	 *
	 * @uses $wpdb To update/add the snippet to the database
	 * @uses $this->get_table_name() To dynamically retrieve the name of the snippet table
	 *
	 * @param object $snippet The snippet to add/update to the database
	 * @param string $scope Save the snippet to the site-wide or network-wide table?
	 * @return int|bool The ID of the snippet on success, false on failure
	 */
	public function save_snippet( $snippet, $scope = '' ) {
		global $wpdb;

		$snippet = $this->escape_snippet_data( $snippet );

		if ( empty( $snippet->name ) or empty( $snippet->code ) )
			return false;

		$table = $this->get_table_name( $scope );

		$fields = '';
		foreach ( get_object_vars( $snippet ) as $field => $value ) {
			if ( 'id' !== $field ) {
				$fields .= "{$field}='{$value}',";
			}
		}
		$fields = rtrim( $fields, ',' );

		if ( isset( $snippet->id ) && 0 !== $snippet->id ) {

			if ( $fields ) {
				$wpdb->query( $wpdb->prepare( "UPDATE $table SET $fields WHERE id='%d' LIMIT 1", $snippet->id ) );
			}

			do_action( 'code_snippets_update_snippet', $snippet, $table );
			return $snippet->id;

		} else {

			if ( $fields ) {
				$wpdb->query( "INSERT INTO $table SET $fields" );
			}

			do_action( 'code_snippets_create_snippet', $snippet, $table );
			return $wpdb->insert_id;
		}
	}

	/**
	 * Imports snippets from an XML file
	 *
	 * @since 1.5
	 * @access public
	 *
 	 * @uses $this->save_snippet() To add the snippets to the database
	 *
	 * @param file $file The path to the XML file to import
	 * @param string $scope Import into network-wide table or site-wide table?
	 * @return mixed The number of snippets imported on success, false on failure
	 */
	public function import( $file, $scope = '' ) {

		if ( ! file_exists( $file ) || ! is_file( $file ) )
			return false;

		$xml = simplexml_load_file( $file );

		foreach ( $xml->children() as $snippet ) {
			/* force manual build of object to strip out unsupported fields
			   by converting snippet object into array */
			$this->save_snippet( get_object_vars( $snippet ), $scope );
		}

		do_action( 'code_snippets_import', $xml, $scope );

		return $xml->count();
	}

	/**
	 * Exports snippets as an XML file
	 *
	 * @since 1.5
	 * @access public
	 *
	 * @uses code_snippets_export() To export selected snippets
	 * @uses $this->get_table_name() To dynamically retrieve the name of the snippet table
	 *
	 * @param array $ids The IDs of the snippets to export
	 * @param string $scope Is the snippet a network-wide or site-wide snippet?
	 * @return void
	 */
	public function export( $ids, $scope = '' ) {

		$table = $this->get_table_name( $scope );

		if ( ! function_exists( 'code_snippets_export' ) )
			require_once $this->plugin_dir . 'includes/export.php';

		code_snippets_export( $ids, 'xml', $table );
	}

	/**
	 * Exports snippets as a PHP file
	 *
	 * @since 1.5
	 * @access public
	 *
	 * @uses code_snippets_export() To export selected snippets
	 * @uses $this->get_table_name() To dynamically retrieve the name of the snippet table
	 *
	 * @param array $ids The IDs of the snippets to export
	 * @param string $scope Is the snippet a network-wide or site-wide snippet?
	 * @return void
	 */
	public function export_php( $ids, $scope = '' ) {

		$table = $this->get_table_name( $scope );

		if ( ! function_exists( 'code_snippets_export' ) )
			require_once $this->plugin_dir . 'includes/export.php';

		code_snippets_export( $ids, 'php', $table );
	}

	/**
	 * Execute a snippet
	 *
	 * Code must NOT be escaped, as
	 * it will be executed directly
	 *
	 * @since 1.5
	 * @access public
	 *
	 * @param string $code The snippet code to execute
	 * @return $result The result of the code execution
	 */
	public function execute_snippet( $code ) {
		ob_start();
		$result = eval( $code );
		$output = ob_get_contents();
		ob_end_clean();
		do_action( 'code_snippets_execute_snippet', $code );
		return $result;
	}

	/**
	 * Run the active snippets
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @uses $wpdb To grab the active snippets from the database
	 * @uses $this->execute_snippet() To execute a snippet
	 *
	 * @return bool true on success, false on failure
	 */
	public function run_snippets() {

		/* Bail early if safe mode is active */
		if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE )
			return false;

		global $wpdb;

		if ( ! isset( $wpdb->snippets, $wpdb->ms_snippets ) )
			$this->set_table_vars();

		/* Check if the snippets table exists */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->snippets'" ) === $wpdb->snippets )
			$sql = "SELECT code FROM {$wpdb->snippets} WHERE active=1";

		/* Check if the multisite snippets table exists */
		if ( is_multisite() && $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->ms_snippets'" ) === $wpdb->ms_snippets ) {
			$sql = ( isset( $sql ) ? $sql . "\nUNION ALL\n" : '' );
			$sql .= "SELECT code FROM {$wpdb->ms_snippets} WHERE active=1;";
		}

		if ( ! empty( $sql ) ) {

			/* Grab the active snippets from the database */
			$active_snippets = $wpdb->get_col( $sql );

			if ( count( $active_snippets ) ) {
				foreach( $active_snippets as $snippet ) {
					/* Execute the PHP code */
					$this->execute_snippet( htmlspecialchars_decode( stripslashes( $snippet ) ) );
				}
				return true;
			}
		}

		/* If we're made it this far without returning true, assume failure */
		return false;
	}

	/**
	 * Cleans up data created by the Code_Snippets class
	 *
	 * @since 1.2
	 * @access private
	 *
	 * @uses $wpdb To remove tables from the database
	 * @uses $code_snippets->get_table_name() To find out which table to drop
	 * @uses is_multisite() To check the type of installation
	 * @uses switch_to_blog() To switch between blogs
	 * @uses restore_current_blog() To switch between blogs
	 * @uses delete_option() To remove site options
	 *
	 * @return void
	 */
	static function uninstall() {
		global $wpdb, $code_snippets;
		if ( is_multisite() ) {
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			if ( $blog_ids ) {
				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					$wpdb->query( "DROP TABLE IF EXISTS $wpdb->snippets" );
					delete_option( 'cs_db_version' );
					delete_option( 'recently_activated_snippets' );
					delete_option( 'code_snippets_version' );
					$code_snippets->setup_roles( false );
				}
				restore_current_blog();
			}
			$wpdb->query( "DROP TABLE IF EXISTS $wpdb->ms_snippets" );
			delete_site_option( 'code_snippets_version' );
			delete_site_option( 'recently_activated_snippets' );
			$code_snippets->setup_ms_roles( false );
		} else {
			$wpdb->query( "DROP TABLE IF EXISTS $wpdb->snippets" );
			delete_option( 'code_snippets_version' );
			delete_option( 'recently_activated_snippets' );
			$code_snippets->setup_roles( false );
		}
	}

}

/**
 * The global variable in which the Code_Snippets class is stored
 *
 * @since 1.0
 * @access public
 */
global $code_snippets;
$code_snippets = new Code_Snippets;

/* set up a pointer in the old variable (for backwards-compatibility) */
global $cs;
$cs = &$code_snippets;

endif; // class exists check
