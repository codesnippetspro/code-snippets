<?php

/**
 * Code Snippets - An easy, clean and simple way to add code snippets to your site.
 *
 * If you're interested in helping to develop Code Snippets, or perhaps contribute
 * to the localization, please see http://cs.bungeshea.com/dev.
 *
 * @package Code Snippets
 * @subpackage Main
 *
 *
 * Plugin Name: Code Snippets
 * Plugin URI: http://cs.bungeshea.com
 * Description: An easy, clean and simple way to add code snippets to your site. No need to edit to your theme's functions.php file again!
 * Author: Shea Bunge
 * Author URI: http://bungeshea.com
 * Version: 1.5
 * License: GPLv3 or later
 * Network: true
 * Text Domain: code-snippets
 * Domain Path: /languages/
 *
 *
 * Code Snippets - WordPress Plugin
 * Copyright (C) 2012  Shea Bunge

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses>.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Code_Snippets' ) ) :

/**
 * The main class for our plugin
 * It all happens here, folks
 *
 * Please use the global variable $cs to access
 * the methods in this class. Anything you need
 * to access should be publicly available there
 *
 * @since Code Snippets 1.0
 * @access private
 */
class Code_Snippets {
	
	/**
	 * The base name for the snippets table in the database
	 * This will later be prepended with the WordPress table prefix
	 *
	 * DO NOT EDIT THIS VARIABLE!
	 * Instead, use the 'cs_table' filter
	 *
	 * @since Code Snippets 1.0
	 * @access public
	 */
	public $table = 'snippets';
	
	/**
	 * The base name for the network snippets table in the database
	 * This will later be prepended with the WordPress base table prefix
	 *
	 * DO NOT EDIT THIS VARIABLE!
	 * Instead, use the 'cs_ms_table' filter
	 *
	 * @since Code Snippets 1.4
	 * @access public
	 */
	public $ms_table = 'ms_snippets';
	
	/**
	 * The version number for this release of the plugin.
	 * This will later be used for upgrades and enqueueing files
	 *
	 * This should be set to the 'Plugin Version' value, 
	 * as defined above
	 *
	 * @since Code Snippets 1.0
	 * @access public
	 */
	public $version  = 1.5;
	
	/**
	 * The base URLs for the admin pages
	 *
	 * DO NOT EDIT THESE VARIABLES!
	 * Instead, use the 'cs_admin_manage', 'cs_admin_single'
	 * and 'cs_admin_import' filters
	 *
	 * @since Code Snippets 1.0
	 * @access public
	 */
	public $admin_manage_url, $admin_single_url, $admin_import_url;
	
	/**
	 * The hooks for the admin pages
	 *
	 * @since Code Snippets 1.0
	 * @access public
	 */
	public $admin_manage, $admin_single, $admin_import;

	/**
	 * The constructor function for our class
	 *
	 * @since Code Snippets 1.0
	 * @access public
	 */
	public function Code_Snippets() {
		$this->__construct();
	}
	
	/**
	 * The main function for our class
	 *
	 * @since Code Snippets 1.0
	 * @access private
	 */
	function __construct() {
		$this->setup();			// initialise the variables and run the hooks
		$this->create_table();	// create the snippet tables if they do not exist
		$this->upgrade();		// check if we need to change some stuff
		if ( is_multisite() ) {  // perform multisite-specific actions
			$this->create_table( true );
			$this->run_snippets( true );
		}
		$this->run_snippets();	// execute the snippets
	}
	
	/**
	 * Initialise variables and add actions and filters
	 *
	 * @since Code Snippets 1.2
	 * @access private
	 */
	function setup() {
		global $wpdb;
		$this->file             = __FILE__;
		$this->table            = apply_filters( 'cs_table', $wpdb->prefix . $this->table );
		$this->ms_table         = apply_filters( 'cs_ms_table', $wpdb->base_prefix . $this->ms_table );
		$this->current_version  = get_option( 'cs_db_version', $this->version );
		
		$wpdb->snippets         = $this->table;
		$wpdb->ms_snippets      = $this->ms_table;
		
		$this->basename	        = plugin_basename( $this->file );
		$this->plugin_dir       = plugin_dir_path( $this->file );
		$this->plugin_url       = plugin_dir_url ( $this->file );
		
		$this->admin_manage_url = apply_filters( 'cs_manage_url', 'snippets' );
		$this->admin_single_url = apply_filters( 'cs_single_url', 'snippet' );
		$this->admin_import_url = apply_filters( 'cs_import_url', 'import-snippets' );
		
		if ( ! get_option( 'cs_db_version' ) ) {
			
			// This is the first time the plugin has run
			
			$this->add_caps(); // register the capabilities ONCE ONLY
			
			if ( is_multisite() ) {
				$this->add_caps( true ); // register the multisite capabilities ONCE ONLY
			}
		}
		
		add_action( 'admin_menu', array( $this, 'add_admin_menus' ) );
		add_action( 'network_admin_menu', array( $this, 'add_network_admin_menus' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'settings_link' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_meta' ), 10, 2 );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );	
	}
	
	/**
	 * Create the snippet table if it does not already exist
	 *
	 * @since Code Snippets 1.2
	 * @access private
	 */
	function create_table( $network = false ) {
		
		$table = ( $network ? $this->ms_table : $this->table );
		
		global $wpdb;
		
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table ) {
			$sql = "CREATE TABLE $table (
				id			MEDIUMINT	NOT NULL AUTO_INCREMENT,
				name		VARCHAR(64)	NOT NULL,
				description	TEXT,
				code		TEXT		NOT NULL,
				active		TINYINT(1)	NOT NULL DEFAULT 0,
				UNIQUE KEY id (id)
			);";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			add_option( 'cs_db_version', $this->version );
		}
	}
	
	/**
	 * Preform upgrade tasks such as deleting and updating options
	 *
	 * @since Code Snippets 1.2
	 * @access private
	 */
	function upgrade() {
		if ( $this->current_version < 1.5 ) {
			global $wpdb;
			
			// Let's alter the name column to accept up to 64 characters
			$wpdb->query( "ALTER TABLE $this->table CHANGE COLUMN name name VARCHAR(64) NOT NULL" );
			
			if ( is_multisite() ) {
				// We must not forget the multisite table!
				$wpdb->query( "ALTER TABLE $this->ms_table CHANGE COLUMN name name VARCHAR(64) NOT NULL" );
			}
			
			// Add the custom capabilities that were introduced in version 1.5.
			$this->add_roles();
		}
		
		if ( $this->current_version < 1.2 ) {
			// The 'Complete Uninstall' option was removed in version 1.2
			delete_option( 'cs_complete_uninstall' );
		}
		
		if ( $this->current_version < $this->version ) {
			// Update the current version
			update_option( 'cs_db_version', $this->version );
		}
	}
	
	/**
	 * Load up the localization file if we're using WordPress in a different language
	 * Place it in this plugin's "languages" folder and name it "code-snippets-[value in wp-config].mo"
	 *
	 * If you wish to contribute a language file to be included in the Code Snippets package,
	 * please see the project's development website at http://cs.bungeshea.com/dev
	 *
	 * @since Code Snippets 1.5
	 * @access private
	 */
	function load_textdomain() {
		load_plugin_textdomain( 'code-snippets', false, dirname( $this->basename ) . '/languages/' );
	}
	
	/**
	 * Add the user capabilities
	 *
	 * @since Code Snippets 1.5
	 * @access public
	 *
	 * @uses $this->setup_roles() To register the capabilities
	 *
	 * @param bool $multisite Add site-specific or multisite-specific capabilities?
	 */
	public function add_caps( $multisite = false ) {
		if ( $multisite && is_multisite() )
			$this->setup_ms_roles( true );
		else
			$this->setup_roles( true );
	}
	
	/**
	 * Remove the user capabilities
	 *
	 * @since Code Snippets 1.5
	 * @access public
	 *
	 * @uses $this->setup_roles() To register the capabilities
	 *
	 * @param bool $multisite Add site-specific or multisite-specific capabilities?
	 */
	public function remove_caps( $multisite = false ) {
		if ( $multisite && is_multisite() )
			$this->setup_ms_roles( false );
		else
			$this->setup_roles( false );
	}
	
	/**
	 * Register the user roles and capabilities
	 *
	 * @since Code Snippets 1.5
	 * @access private
	 *
	 * @param bool $install True to add the capabilities, false to remove 
	 */
	function setup_roles( $install = true ) {
		
		$this->caps = apply_filters( 'cs_caps', array(
			'manage_snippets',
			'install_snippets',
			'edit_snippets'
		) );
		
		$this->role = get_role( apply_filters( 'cs_role', 'administrator' ) );
		
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
	 * @since Code Snippets 1.5
	 * @access private
	 *
	 * @param bool $install True to add the capabilities, false to remove 
	 */
	function setup_ms_roles( $install = true ) {
	
		if ( ! is_multisite() ) return;
		
		$this->network_caps = apply_filters( 'cs_network_caps', array(
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
	 * Add the dashboard admin menu and subpages
	 *
	 * @since Code Snippets 1.0
	 * @access private
	 */
	function add_admin_menus() {
		$this->admin_manage = add_menu_page(
			__('Snippets', 'code-snippets'),
			__('Snippets', 'code-snippets'),
			'manage_snippets',
			$this->admin_manage_url,
			array( $this, 'display_admin_manage' ),
			$this->plugin_url . 'images/icon16.png',
			67
		);
		add_submenu_page(
			$this->admin_manage_url,
			__('Snippets', 'code-snippets'),
			__('Manage Snippets', 'code-snippets'),
			'manage_snippets',
			$this->admin_manage_url,
			array( $this, 'display_admin_manage')
		);
		$this->admin_single = add_submenu_page(
			$this->admin_manage_url,
			__('Add New Snippet', 'code-snippets'),
			__('Add New', 'code-snippets'),
			'install_snippets',
			$this->admin_single_url,
			array( $this, 'display_admin_single' )
		);
		$this->admin_import = add_submenu_page(
			$this->admin_manage_url,
			__('Import Snippets', 'code-snippets'),
			__('Import', 'code-snippets'),
			'install_snippets',
			$this->admin_import_url,
			array( $this, 'display_admin_import' )
		);
		
		$this->after_admin_menu();
	}
	
	/**
	 * Add the network dashboard admin menu and subpages
	 *
	 * @since Code Snippets 1.4
	 * @access private
	 */
	function add_network_admin_menus() {
		$this->table = $this->ms_table;
		$this->is_network = true;
		
		$this->admin_manage = add_menu_page(
			__('Snippets', 'code-snippets'),
			__('Snippets', 'code-snippets'),
			'manage_network_snippets',
			$this->admin_manage_url,
			array( $this, 'display_admin_manage' ),
			$this->plugin_url . 'images/icon16.png',
			21
		);
		add_submenu_page(
			$this->admin_manage_url,
			__('Snippets', 'code-snippets'),
			__('Manage Snippets', 'code-snippets'),
			'manage_network_snippets',
			$this->admin_manage_url,
			array( $this, 'display_admin_manage' )
		);
		$this->admin_single = add_submenu_page(
			$this->admin_manage_url,
			__('Add New Snippet', 'code-snippets'),
			__('Add New', 'code-snippets'),
			'install_network_snippets',
			$this->admin_single_url,
			array( $this, 'display_admin_single' )
		);
		$this->admin_import = add_submenu_page(
			$this->admin_manage_url,
			__('Import Snippets', 'code-snippets'),
			__('Import', 'code-snippets'),
			'install_network_snippets',
			$this->admin_import_url,
			array( $this, 'display_admin_import' )
		);
		
		$this->after_admin_menu();
	}
	
	/**
	 * Preform necessary tasks on the subpages such as setting the URLs
	 * and enqueueing the styles and scripts
	 *
	 * @since Code Snippets 1.5
	 * @access private
	 */
	function after_admin_menu() {
		$this->admin_manage_url	= self_admin_url( 'admin.php?page=' . $this->admin_manage_url );
		$this->admin_single_url = self_admin_url( 'admin.php?page=' . $this->admin_single_url );
		$this->admin_import_url = self_admin_url( 'admin.php?page=' . $this->admin_import_url );

		add_action( "admin_print_styles-$this->admin_single",  array( $this, 'load_editor_styles' ) );
		add_action( "admin_print_scripts-$this->admin_single", array( $this, 'load_editor_scripts' ) );
		
		add_action( "admin_print_styles-$this->admin_manage", array( $this, 'load_stylesheet' ) );
		add_action( "admin_print_styles-$this->admin_single", array( $this, 'load_stylesheet' ) );
		add_action( "admin_print_styles-$this->admin_import", array( $this, 'load_stylesheet' ) );
		
		add_action( "load-$this->admin_manage", array( $this, 'load_admin_manage' ) );
		add_action( "load-$this->admin_single", array( $this, 'load_admin_single' ) );
		add_action( "load-$this->admin_import", array( $this, 'load_admin_import' ) );
	}
	
	/**
	 * Enqueue the admin stylesheet
	 *
	 * @since Code Snippets 1.0
	 * @access private
	 *
	 * @uses wp_enqueue_style() To add the stylesheet to the queue
	 */
	function load_stylesheet() {
		wp_enqueue_style(
			'code-snippets',
			plugins_url( 'css/style.css', $this->file ),
			false,
			$this->version
		);
	}
	
	/**
	 * Registers and loads the code editor's scripts
	 *
	 * @since Code Snippets 1.4
	 * @access private
	 *
	 * @uses wp_register_script()
	 * @uses wp_enqueue_style() To add the scripts to the queue
	 */
	function load_editor_scripts() {
		$version = 2.35;
		wp_register_script(
			'codemirror',
			plugins_url( 'assets/lib/codemirror.js', $this->file ),
			false,
			$version
		);
		wp_register_script(
			'codemirror-php',
			plugins_url( 'assets/mode/php.js', $this->file ),
			array( 'codemirror' ),
			$version
		);
		wp_register_script(
			'codemirror-xml',
			plugins_url( 'assets/mode/xml.js', $this->file ),
			array( 'codemirror' ),
			$version
		);
		wp_register_script(
			'codemirror-js',
			plugins_url( 'assets/mode/javascript.js', $this->file ),
			array( 'codemirror' ),
			$version
		);
		wp_register_script(
			'codemirror-css',
			plugins_url( 'assets/mode/css.js', $this->file ),
			array( 'codemirror' ),
			$version
		);
		wp_register_script(
			'codemirror-clike',
			plugins_url( 'assets/mode/clike.js', $this->file ),
			array( 'codemirror' ),
			$version
		);
		
		/* CodeMirror utilities */
		
		wp_register_script(
			'codemirror-dialog.js',
			plugins_url( 'assets/util/dialog.js', $this->file ),
			array( 'codemirror' ),
			$version
		);
		wp_register_script(
			'codemirror-searchcursor.js',
			plugins_url( 'assets/util/searchcursor.js', $this->file ),
			array( 'codemirror-dialog.js' ),
			$version
		);
		wp_register_script(
			'codemirror-search.js',
			plugins_url( 'assets/util/search.js', $this->file ),
			array( 'codemirror-searchcursor.js' ),
			$version
		);
		
		wp_enqueue_script( array(
			'codemirror-xml',
			'codemirror-js',
			'codemirror-css',
			'codemirror-clike',
			'codemirror-php',
			'codemirror-search.js',
		) );
	}
	
	/**
	 * Registers and loads the code editor's styles
	 *
	 * @since Code Snippets 1.4
	 * @access private
	 *
	 * @uses wp_register_style()
	 * @uses wp_enqueue_style() To add the stylesheets to the queue
	 */
	function load_editor_styles() {
		$version = 2.35;
		wp_register_style(
			'codemirror',
			plugins_url( 'assets/lib/codemirror.css', $this->file ),
			false,
			$version
		);
		wp_register_style(
			'codemirror-dialog',
			plugins_url( 'assets/util/dialog.css', $this->file ),
			array( 'codemirror' ),
			$version
		);
		wp_enqueue_style( array(
			'codemirror',
			'codemirror-dialog'
		) );
	}
	
	/**
	 * Activates a snippet
	 *
	 * @since Code Snippets 1.5
	 * @access public
	 *
	 * @uses $wpdb To set the snippets' active status
	 *
	 * @param array $ids The IDs of the snippets to activate
	 * @param bool $network Are the snippets network-wide (true) or site-wide (false)?
	 */
	public function activate( $ids, $network = null ) {
		global $wpdb;
		
		$ids = (array) $ids;
		
		if ( ! isset( $network ) ) {
			$screen = get_current_screen();
			$network = $screen->is_network;
		}
		
		foreach( $ids as $id ) {
			$wpdb->update(
				( $network ? $this->ms_table : $this->table ),
				array( 'active' => '1' ),
				array( 'id' => $id ),
				array( '%d' ),
				array( '%d' )
			);
		}
	}
	
	/**
	 * Deactivates selected snippets
	 *
	 * @since Code Snippets 1.5
	 * @access public
	 *
	 * @uses $wpdb To set the snippets' active status
	 *
	 * @param array $ids The IDs of the snippets to deactivate
	 * @param bool $network Are the snippets network-wide (true) or site-wide (false)?
	 */
	public function deactivate( $ids, $network = null ) {
		global $wpdb;
		
		$ids = (array) $ids;
		$recently_active = array();
		
		if ( ! isset( $network ) ) {
			$screen = get_current_screen();
			$network = $screen->is_network;
		}
		
		foreach( $ids as $id ) {
			$wpdb->update(
				( $network ? $this->ms_table : $this->table ),
				array( 'active' => '0' ),
				array( 'id' => $id ),
				array( '%d' ),
				array( '%d' )
			);
			$recently_active = array( $id => time() ) + (array) $recently_active;
		}
		
		if ( $network )
			update_option(
				'recently_network_activated_snippets',
				$recently_active + (array) get_option( 'recently_network_activated_snippets' )
			);
		else
			update_option(
				'recently_activated_snippets',
				$recently_active + (array) get_option( 'recently_activated_snippets' )
			);
	}
	
	public function delete_snippet( $id, $network = null ) {
		global $wpdb;
		
		if ( ! isset( $network ) ) {
			$screen = get_current_screen();
			$network = $screen->is_network;
		}
		
		$table = ( $network ? $this->ms_table : $this->table );
		$id = intval( $id );
		
		$wpdb->query( "DELETE FROM $table WHERE id='$id' LIMIT 1" );
	}
	
	/**
	 * Saves a snippet to the database.
	 *
	 * @since Code Snippets 1.5
	 * @access public
	 *
	 * @uses $wpdb To update/add the snippet to the database
	 *
	 * @param array $snippet The snippet to add/update to the database
	 * @return int|bool The ID of the snippet on success, false on failure
	 */
	public function save_snippet( $snippet, $network = null ) {	
		global $wpdb;
		
		$name = mysql_real_escape_string( htmlspecialchars( $snippet['name'] ) );
		$description = mysql_real_escape_string( htmlspecialchars( $snippet['description'] ) );
		$code = mysql_real_escape_string( htmlspecialchars( $snippet['code'] ) );

		if ( empty( $name ) or empty( $code ) )
			return false;
		
		if ( ! isset( $network ) ) {
			$screen = get_current_screen();
			$network = $screen->is_network;
		}
		
		$table = ( $network ? $this->ms_table : $this->table );
		
		if ( isset( $snippet['id'] ) && ( intval( $snippet['id'] ) != 0 )  ) {
			$wpdb->query( "UPDATE $table SET
				name='$name',
				description='$description',
				code='$code' WHERE id=" . intval( $snippet['id'] ) . "
				LIMIT 1"
			);
			return intval( $snippet['id'] );
		} else {
			$wpdb->query(
				"INSERT INTO $table(name,description,code)
				VALUES ('$name','$description','$code')"
			);
			return $wpdb->insert_id;
		}
	}
	
	/**
	 * Imports snippets from an XML file
	 *
	 * @since Code Snippets 1.5
	 * @access public
	 *
	 * @uses $this->save_snippet() To add the snippets to the database
	 *
	 * @param file $file The XML file to import
	 * @return mixed The number of snippets imported on success, false on failure
	 */
	public function import( $file, $network = null ) {
	
		if ( ! file_exists( $file ) || ! is_file( $file ) )
			return false;
		
		$xml = simplexml_load_file( $file );
		
		foreach ( $xml->children() as $child ) {
			$this->save_snippet( array(
				'name' => $child->name,
				'description' => $child->description,
				'code' => $child->code,
			), $network );
		}
		return $xml->count();
	}
	
	/**
	 * Exports snippets as an XML file
	 *
	 * @since Code Snippets 1.5
	 * @access public
	 *
	 * @uses cs_export() To export selected snippets
	 *
	 * @param array $id An array if the IDs of the snippets to export
	 * @param bool $network Is the snippet a network-wide (true) or site-wide (false) snippet?
	 */	
	public function export( $ids, $network = null ) {
		
		if ( ! isset( $network ) ) {
			$screen = get_current_screen();
			$network = $screen->is_network;
		}
		
		$table = ( $network ? $this->ms_table : $this->table );
		
		if ( ! function_exists( 'cs_export' ) )
			require_once $this->plugin_dir . 'includes/export.php';
			
		cs_export( $ids, 'xml', $table );
	}
	
	/**
	 * Exports snippets as a PHP file
	 *
	 * @since Code Snippets 1.5
	 * @access public
	 *
	 * @uses cs_export() To export selected snippets
	 *
	 * @param array $id An array if the IDs of the snippets to export
	 * @param bool $network Is the snippet a network-wide (true) or site-wide (false) snippet?
	 */	
	public function exportphp( $ids, $network = null ) {
		
		if ( ! isset( $network ) ) {
			$screen = get_current_screen();
			$network = $screen->is_network;
		}
		
		$table = ( $network ? $this->ms_table : $this->table );
		
		if ( ! function_exists( 'cs_export' ) )
			require_once $this->plugin_dir . 'includes/export.php';
			
		cs_export( $ids, 'php', $table );
	}
	
	/**
	 * Execute a snippet
	 *
	 * Code must NOT be escaped, as
	 * it will be executed directly
	 *
	 * @since Code Snippets 1.5
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
		return $result;
	}
	
	/**
	 * Replaces the text 'Add New Snippet' with 'Edit Snippet'
	 *
	 * @since Code Snippets 1.1
	 * @access private
	 */
	function admin_single_title( $title ) {
		return str_ireplace(
			__('Add New Snippet', 'code-snippets'),
			__('Edit Snippet', 'code-snippets'),
			$title
		);
	}
	
	/**
	 * Handles saving the user's screen option preference
	 *
	 * @since Code Snippets 1.5
	 * @access private
	 */
	function set_screen_option( $status, $option, $value ) {
		if ( 'snippets_per_page' === $option ) return $value;
	}
	
	/**
	 * Processes any action command and loads the help tabs
	 *
	 * @since Code Snippets 1.0
	 * @access private
	 *
	 * @uses $wpdb To activate, deactivate and delete snippets
	 */
	function load_admin_manage() {
		global $wpdb;
		
		if ( isset( $_GET['action'], $_GET['id'] ) ) :
		
			$id = intval( $_GET['id'] );
			
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'action', 'id' ) );
		
			if ( 'activate' == $_GET['action'] ) {
				$this->activate( $id );
				wp_redirect( add_query_arg( 'activate', true ) );
			}
			elseif ( 'deactivate' == $_GET['action'] ) {
				$this->deactivate( $id );
				wp_redirect( add_query_arg( 'deactivate', true ) );
			}
			elseif ( 'delete' == $_GET['action'] ) {
				$this->delete_snippet( $id );
				wp_redirect( add_query_arg( 'delete', true ) );
			}
			elseif ( 'export' == $_GET['action'] ) {
				$this->export( $id );
			}
			elseif ( 'exportphp' == $_GET['action'] ) {
				$this->exportphp( $id );
			}
			
		endif;
	
		include $this->plugin_dir . 'includes/help/manage.php'; // Load the help tabs
		
		/**
		 * Initialize the snippet table class
		 */
		require_once $this->plugin_dir . 'includes/class-list-table.php';
		
		global $cs_list_table;
		$cs_list_table = new Code_Snippets_List_Table();
		$cs_list_table->prepare_items();
	}
	
	/**
	 * Loads the help tabs for the Edit Snippets page
	 *
	 * @since Code Snippets 1.0
	 * @access private
	 *
	 * @uses $wpdb To save the posted snippet to the database
	 * @uses wp_redirect To pass the results to the page
	 */
	function load_admin_single() {
	
		if ( isset( $_REQUEST['save_snippet'] ) ) {
		
			if ( isset( $_REQUEST['snippet_id'] ) ) {
				$result = $this->save_snippet( array(
					'name' => $_REQUEST['snippet_name'],
					'description' => $_REQUEST['snippet_description'],
					'code' => $_REQUEST['snippet_code'],
					'id' => $_REQUEST['snippet_id'],
				) );
			} else {
				$result = $this->save_snippet( array(
					'name' => $_REQUEST['snippet_name'],
					'description' => $_REQUEST['snippet_description'],
					'code' => $_REQUEST['snippet_code'],
				) );
			}
			
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'added', 'updated', 'invalid' ) );
				
			if ( ! $result || $result < 1 ) {
				wp_redirect( add_query_arg( 'invalid', true ) );
			}
			elseif ( isset( $_REQUEST['snippet_id'] ) ) {
				wp_redirect( add_query_arg(	array(
					'edit' => $result,
					'updated' => true
				) ) );
			}
			else {
				wp_redirect( add_query_arg( array(
					'edit' => $result,
					'added' => true
				) ) );
			}
		}
			
		if ( isset( $_GET['edit'] ) )
			add_filter( 'admin_title',  array( $this, 'admin_single_title' ) );
	
		include $this->plugin_dir . 'includes/help/single.php'; // Load the help tabs
	}
	
	/**
	 * Processes import files and loads the help tabs for the Import Snippets page
	 *
	 * @since Code Snippets 1.3
	 *
	 * @uses $this->import() To process the import file
	 * @uses wp_redirect() To pass the import results to the page
	 * @uses add_query_arg() To append the results to the current URI
	 */
	function load_admin_import() {
		if ( isset( $_FILES['cs_import_file']['tmp_name'] ) ) {
			$count = $this->import( $_FILES['cs_import_file']['tmp_name'] );
			if ( $count ) {
				wp_redirect( add_query_arg( 'imported', $count ) );
			}
		}
		include $this->plugin_dir . 'includes/help/import.php'; // Load the help tabs
	}
	
	/**
	 * Displays the Manage Snippets page
	 *
	 * @since Code Snippets 1.0
	 * @access private
	 */
	function display_admin_manage() {
		require_once $this->plugin_dir . 'includes/admin/manage.php';
	}

	/**
	 * Displays the Add New/Edit Snippet page
	 *
	 * @since Code Snippets 1.0
	 * @access private
	 */
	function display_admin_single() {
		require_once $this->plugin_dir . 'includes/admin/single.php';
	}
	
	/**
	 * Displays the Import Snippets page
	 *
	 * @since Code Snippets 1.3
	 */
	function display_admin_import() {
		require_once $this->plugin_dir . 'includes/admin/import.php';
	}
	
	/**
	 * Adds a link pointing to the Manage Snippets page
	 *
	 * @since Code Snippets 1.0
	 * @access private
	 */
	function settings_link( $links ) {
		array_unshift( $links, sprintf(
			'<a href="%1$s" title="%2$s">%3$s</a>',
			$this->admin_manage_url,
			__('Manage your existing snippets', 'code-snippets'),
			__('Manage', 'code-snippets')
		) );
		return $links;
	}
	
	/**
	 * Adds extra links related to the plugin
	 * 
	 * @since Code Snippets 1.2
	 */
	function plugin_meta( $links, $file ) {
	
		if ( $file != $this->basename ) return $links;
		
		$format = '<a href="%1$s" title="%2$s">%3$s</a>';
		
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
				'http://cs.bungeshea.com/donate/',
				__('Support this plugin\'s development', 'code-snippets'),
				__('Donate', 'code-snippets')
			)
		) );
	}
	
	/**
	 * Run the active snippets
	 *
	 * @since Code Snippets 1.0
	 * @access private
	 *
	 * @uses $wpdb To grab the active snippets from the database
	 * @uses $this->execute_snippet() To execute a snippet
	 */
	function run_snippets( $network = false ) {
		if ( defined( 'CS_SAFE_MODE' ) && CS_SAFE_MODE ) return;
		
		$table = ( $network ? $this->ms_table : $this->table );
		
		global $wpdb;
		
		// grab the active snippets from the database
		$active_snippets = $wpdb->get_results( "SELECT code FROM $table WHERE active=1;");
		if ( count( $active_snippets ) ) {
			foreach( $active_snippets as $snippet ) {
				// execute the php code
				$this->execute_snippet( htmlspecialchars_decode( stripslashes( $snippet->code ) ) );
			}
		}
	}
}

endif; // class exists check

/**
 * The global variable in which the Code Snippets class is stored
 *
 * @since Code Snippets 1.0
 * @access public
 */
global $cs;
$cs = new Code_Snippets;

register_uninstall_hook( $cs->file, 'cs_uninstall' );

/**
 * Cleans up data created by the Code_Snippets class
 *
 * @since Code Snippets 1.2
 * @access private
 *
 * @uses $wpdb To remove tables from the database
 * @uses $cs To find out which table to drop
 * @uses is_multisite() To check the type of installation
 * @uses switch_to_blog() To switch between blogs
 * @uses restore_current_blog() To switch between blogs
 * @uses delete_option() To remove site options
 */
function cs_uninstall() {
	global $wpdb, $cs;
	if ( is_multisite() ) {
		$blogs = $wpdb->get_results( "SELECT blog_id FROM $wpdb->blogs", ARRAY_A );
		if ( $blogs ) {
			foreach( $blogs as $blog ) {
				switch_to_blog( $blog['blog_id'] );
				$table = apply_filters( 'cs_table', $wpdb->prefix . 'snippets' );
				$wpdb->query( "DROP TABLE IF EXISTS $table" );
				delete_option( 'cs_db_version' );
				delete_option( 'recently_activated_snippets' );
				$cs->remove_caps();
			}
			restore_current_blog();
		}
		$wpdb->query( "DROP TABLE IF EXISTS $cs->ms_table" );
		delete_option( 'recently_network_activated_snippets' );
		$cs->remove_caps( true );
	} else {
		$wpdb->query( "DROP TABLE IF EXISTS $cs->table" );
		delete_option( 'cs_db_version' );
		delete_option( 'recently_activated_snippets' );
		$cs->remove_caps();
	}
}