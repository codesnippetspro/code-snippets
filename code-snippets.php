<?php

/**
 * Plugin Name:	Code Snippets
 * Plugin URI: http://wordpress.org/extend/plugins/code-snippets
 * Description:	Provides an easy-to-manage GUI interface for adding code snippets to your blog.
 * Author: Shea Bunge
 * Version: 1.0
 * Author URI: http://bungeshea.wordpress.com/plugins/code-snippets/
 * License: GPLv3 or later
 *  
 * Code Snippets - WordPress Plugin
 * Copyright (C) 2012  Shea Bunge
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if( !class_exists('code_snippets') ) :

class code_snippets {

	public $table_name				=	'';
	public $version					=	'0.1';
	public $current_version			=	'';
	public $plugin_url				=	'';
	public $plugin_dir				=	'';
	public $dirname					=	'';

	public $manage_snippets_url		=	'';
	public $edit_snippets_url		=	'';
	public $uninstall_plugin_url	=	'';

	public $manage_snippets_page;
	public $edit_snippets_page;
	public $uninstall_plugin_page;

	public function code_snippets(){
		$this->__construct();
	}
	
	function  __construct() {
		$this->setup_vars();		// initialise the varables
		$this->setup_actions();		// run the actions and filters
		$this->run_snippets();		// execute the snippets
	}
	
	function setup_vars(){
		global $wpdb;
		$this->table_name			=	$wpdb->prefix . 'snippets';
		$this->current_version		=	get_option( 'cs_db_version' );
		$this->file      			=	__FILE__;
		$this->basename  			=	plugin_basename( $this->file );
		$this->plugin_dir			=	plugin_dir_path( $this->file );
		$this->plugin_url			=	plugin_dir_url ( $this->file );
		$this->dirname				=	dirname( $this->file );
		$this->manage_snippets_url	=	admin_url( 'admin.php?page=snippets' );
		$this->edit_snippets_url	=	admin_url( 'admin.php?page=snippet-new' );
		$this->uninstall_plugin_url	=	admin_url( 'admin.php?page=uninstall-cs' );
	}

	private function setup_actions(){
		add_action( 'activate_'   . $this->basename, array( &$this, 'install' )  );
		add_action( 'deactivate_' . $this->basename, array( &$this, 'uninstall' ) );
		add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );
	}
	
	function install() {
		global $wpdb;
		if($wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") != $this->table_name) {
			$sql = 'CREATE TABLE ' . $this->table_name . ' (
				id			mediumint(9)	NOT NULL AUTO_INCREMENT,
				name		varchar(36)		NOT NULL,
				description	text			NOT NULL,
				code		text			NOT NULL,
				active		tinyint(1)		NOT NULL DEFAULT 0,
				UNIQUE KEY id (id)
			);';
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			add_option( 'cs_db_version', $this->version );
		}
	}
	
	function uninstall() {
		if( get_option( 'cs_complete_uninstall', 0 ) == 1 ) {
			global $wpdb;
			if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name ) {
				$sql = 'DROP TABLE ' . $table_name;
				$wpdb->query( $sql );
				delete_option( 'cs_db_version' );
				delete_option( 'cs_complete_uninstall' );
			}
		}
	}
	
	function add_admin_menus() {
		$this->manage_snippets_page = add_menu_page( 'Snippets', 'Snippets', 'activate_plugins', 'snippets',  array( &$this, 'manage_snippets' ), $this->plugin_url . 'img/icon16.png', 67 );
		add_submenu_page('snippets', 'Snippets', 'Manage Snippets' , 'install_plugins', 'snippets', array( &$this, 'manage_snippets') );
		$this->edit_snippets_page = add_submenu_page( 'snippets', 'Add New Snippet', 'Add New', 'edit_plugins', 'snippet-new', array( &$this, 'edit_snippets' ) );
		$this->uninstall_plugin_page = add_submenu_page( 'snippets', 'Uninstall Code Snippets', 'Uninstall', 'install_plugins', 'uninstall-cs', array( &$this, 'uninstall_plugin' ) );

		add_action( 'admin_print_styles-' . $this->manage_snippets_page,	array( $this, 'load_stylesheet' ),	5 );
		add_action( 'admin_print_styles-' . $this->edit_snippets_page,		array( $this, 'load_stylesheet' ),	5 );
		add_action( 'admin_print_styles-' . $this->uninstall_plugin_page,	array( $this, 'load_stylesheet' ),	5 );
		add_action( 'admin_print_scripts-' . $this->edit_snippets_page,		array( $this, 'load_tabby' ),		5 );
		add_action( 'load-' . $this->manage_snippets_page,	array( $this, 'manage_snippets_help' ),				5 );
		add_action( 'load-' . $this->edit_snippets_page,	array( $this, 'edit_snippets_help' ),				5 );
		add_action( 'load-' . $this->uninstall_plugin_page,	array( $this, 'uninstall_plugin_help' ),				5 );
	}
	
	function load_stylesheet() {
		wp_enqueue_style('code-snippets-admin-style', plugins_url( 'css/style.css', $this->file), false, $this->version );
	}
	
	function load_tabby() {
		wp_enqueue_script( 'tabby', plugins_url( 'js/jquery.textarea.js', $this->file), array( 'jquery' ), 0.12 );
	}

	function manage_snippets_help() {
		$screen = get_current_screen();
		$screen->add_help_tab( array(
			'id'		=> 'overview',
			'title'		=> 'Overview',
			'content'	=>
				"<p>Snippets are similar to plugins - they both extend and expand the functionality of WordPress. Snippets are more light-weight, just a few lines of code, and do not put as much load on your server. Here you can manage your existing snippets and preform tasks on them such as activating, deactivating, deleting and exporting.</p>"
		) );
		$screen->add_help_tab( array(
			'id'		=> 'compatibility-problems',
			'title'		=> 'Troubleshooting',
			'content'	=>
				"<p>Be sure to check your snippets for errors before you activate them as a faulty snippet could bring your whole blog down. If your site starts doing strange things, deactivate all your snippets and activate them one at a time.</p>" .
				"<p>If something goes wrong with a snippet and you can&#8217;t use WordPress, you can use a database manager like phpMyAdmin to access the <code>$this->table_name</code> table in your WordPress database. Locate the offending snippet (if you know which one is the trouble) and change the 1 in the 'active' column into a 0. If this doesn't work try doing this for all snippets.</p>"
		) );

		$screen->set_help_sidebar(
			"<p><strong>For more information:</strong></p>" .
			"<p><a href='http://wordpress.org/extend/plugins/code-snippets' target='_blank'>WordPress Extend</a></p>" .
			"<p><a href='http://wordpress.org/support/plugin/code-snippets' target='_blank'>Support Forums</a></p>" .
			"<p><a href='http://bungeshea.wordpress.org/plugins/code-snippets' target='_blank'>SheaPress</a></p>"
		);
	}
	
	function edit_snippets_help() {
		$screen = get_current_screen();
		$screen->add_help_tab( array(
			'id'		=> 'overview',
			'title'		=> 'Overview',
			'content'	=>
				"<p>Snippets are similar to plugins - they both extend and expand the functionality of WordPress. Snippets are more light-weight, just a few lines of code, and do not put as much load on your server. Here you can add a new snippet or edit an existing one.</p>"
		) );
		$screen->add_help_tab( array(
			'id'		=> 'finding',
			'title'		=> 'Finding Snippets',
			'content'	=>
				"<p>Here are some links to websites which host a large number of snippets that you can add to your site.
				<ul>
					<li><a href='http://code-snippets.com'>WordPress Snippets</a></li>
					<li><a href='http://wpsnipp.com'>WP Snipp</a></li>
					<li><a href='http://catswhocode.com/blog/snippets'>Cats Who Code Snippet Library</a></li>
					<li><a href='http://wpmu.org'>WPMU - The WordPress Experts</a></li>
				</ul>
				Snippets can be installed through the <a href='$this->edit_snippets_url'>Add New Snippet</a> page or by addng them to the <code>$this->table_name</code> table in the database (Warning: for advanced users only). Once a snippet has been installed, you can activate it here.</p>"
		) );
		$screen->add_help_tab( array(
			'id'		=> 'adding',
			'title'		=> 'Adding Snippets',
			'content'	=>
				"<p>You need to fill out the name and code fields for your snippet to be added. While the description field will add more information about how your snippet works, what is does and where you found it, it is completely optional.</p>" .
				"<p>Make sure that you don't add the <code>&lt;?php</code>, <code>&lt;?</code> or <code>?&gt;</code> the beginning and end of the code. You can however use these tags in the code to stop and start PHP sections</p>" .
				"<p>Please be sure to check thst your snippet is valid PHP code and will not produce errors before adding it through this page. While doing so will not become active straght away, it will help to minimise the chance of a faulty snippet becoming active on your site.</p>"
		) );

		$screen->set_help_sidebar(
			"<p><strong>For more information:</strong></p>" .
			"<p><a href='http://wordpress.org/extend/plugins/code-snippets' target='_blank'>WordPress Extend</a></p>" .
			"<p><a href='http://wordpress.org/support/plugin/code-snippets' target='_blank'>Support Forums</a></p>" .
			"<p><a href='http://bungeshea.wordpress.org/plugins/code-snippets' target='_blank'>SheaPress</a></p>"
		);
	}
	
	function uninstall_plugin_help() {
		$screen = get_current_screen();
		$screen->add_help_tab( array(
			'id'		=> 'overview',
			'title'		=> 'Overview',
			'content'	=>
				"<p>If you are absolutly sure that you will never, ever want to use the Code Snippets plugin ever again in your entire life on this WordPress installation, you can use this page to tell Code Snippets to clear all of its data when deactivated. Simply check the box below and click on the submit button. If you realise what a cool plugin Code Snippets actually is before you get around to deactivating the plugin you can come back here and uncheck the box. If the box is selected when Code Snippets is deactivated it will clear up the <code>$this->table_name</code> table and a few other bits of data stored in the database.</p>" .
				"<p>Even if you're sure that you don't want to use Code Snippets on this WordPress installaion, you may want to use phpMyAdmin to back up the <code>$this->table_name</code> table in the database. You can later use phpMyAdmin to import it back.</p>"
		) );

		$screen->set_help_sidebar(
			"<p><strong>For more information:</strong></p>" .
			"<p><a href='http://wordpress.org/extend/plugins/code-snippets' target='_blank'>WordPress Extend</a></p>" .
			"<p><a href='http://wordpress.org/support/plugin/code-snippets' target='_blank'>Support Forums</a></p>" .
			"<p><a href='http://bungeshea.wordpress.org/plugins/code-snippets' target='_blank'>SheaPress</a></p>"
		);
	}
	
	function manage_snippets() {
		global $wpdb;
		$msg = '';
		if( isset( $_POST['action'] ) && isset( $_POST['snippets'] ) && is_array( $_POST['snippets'] ) ) {
			$count = 0;
			switch( $_POST['action'] ) {
				
				case 'activate':
					foreach($_POST['snippets'] as $bd) {
						$wpdb->query('update ' . $this->table_name . ' set active=1 where id=' . intval( $bd ) . ' limit 1' );
						$count++;
					}
					$msg = "Activated $count snippets.";
					break;
					
				case 'deactivate':
					foreach($_POST['snippets'] as $bd) {
						$wpdb->query('update ' . $this->table_name . ' set active=0 where id=' . intval( $bd ) . ' limit 1' );
						$count++;
					}
					$msg = "Deactivated $count snippets.";
					break;
					
				case 'delete':
					foreach( $_POST['snippets'] as $bd) {
						$wpdb->query("delete from ".$wpdb->prefix."snippets where id=".intval($bd)." limit 1");
						$count++;
					}
					$msg = "Deleted $count snippets.";
					break;
			}
		}
		
		if( isset( $_POST['action2'] ) && isset( $_POST['snippets'] ) && is_array( $_POST['snippets'] ) ) {
			$count = 0;
			switch( $_POST['action2'] ) {
				
				case 'activate':
					foreach($_POST['snippets'] as $bd) {
						$wpdb->query('update ' . $this->table_name . ' set active=1 where id=' . intval( $bd ) . ' limit 1' );
						$count++;
					}
					$msg = "Activated $count snippets.";
					break;
					
				case 'deactivate':
					foreach($_POST['snippets'] as $bd) {
						$wpdb->query('update ' . $this->table_name . ' set active=0 where id=' . intval( $bd ) . ' limit 1' );
						$count++;
					}
					$msg = "Deactivated $count snippets.";
					break;
					
				case 'delete':
					foreach( $_POST['snippets'] as $bd) {
						$wpdb->query("delete from ".$wpdb->prefix."snippets where id=".intval($bd)." limit 1");
						$count++;
					}
					$msg = "Deleted $count snippets.";
					break;
			}
		}
		
		if( isset( $_GET['action'] ) && isset( $_GET['id'] ) ) {
			if( $_GET['action'] == 'delete') {
				$wpdb->query( 'delete from ' . $this->table_name . ' where id=' . intval( $_GET['id'] ) . ' limit 1' );
				$msg = 'Snippet deleted.';
			}
			elseif( $_GET['action'] == 'activate' ) {
				$wpdb->query('update ' . $this->table_name . ' set active=1	where id=' . intval( $_GET['id'] ) . ' limit 1' );
				$msg = 'Snippet activated.';
			}
			elseif( $_GET['action'] == 'deactivate' ) {
				$wpdb->query('update ' . $this->table_name . ' set active=0 where id=' . intval( $_GET['id'] ) . ' limit 1' );
				$msg = 'Snippet deactivated.';
			}
		}
    
    require_once( $this->dirname . '/inc/manage-snippets.php');
}

	function edit_snippets() {
		global $wpdb;
		$msg = '';
		if( isset( $_POST['save_snippet'] ) ) {
			$name			=	mysql_real_escape_string( htmlspecialchars( $_POST['snippet_name' ] ) );
			$description	=	mysql_real_escape_string( htmlspecialchars( $_POST['snippet_description'] ) );
			$code			=	mysql_real_escape_string( htmlspecialchars( $_POST['snippet_code'] ) );

			if( strlen( $name ) && strlen( $code ) ) {
				if( isset($_POST['edit_id'] ) ) {
					$wpdb-> query( "update $this->table_name set name='".$name."',
							description='".$description."',
							code='".$code."'
							where id=" . intval($_POST["edit_id"]." limit 1"));
					$msg = 'Snippet updated.';
				}
				else {
					$wpdb->query( "insert into $this->table_name(name,description,code,active) VALUES ('$name','$description','$code',0)" );
					$msg = 'Snippet added.';
				}
			}
			else {
				$msg = 'Please provide a name for the snippet and the code.';
			}
		}
		require_once( $this->dirname . '/inc/edit-snippets.php');
	}
	
	function uninstall_plugin(){
		$msg = '';
		if( isset( $_POST['uninstall'] ) )	{
			if(isset( $_POST['ch_unin']) ) {
				update_option('cs_complete_uninstall' , 1);
				$msg = 'Option updated. Please deactivate the Code Snippets plugin to clear all data.';
			}
			else {
				update_option('cs_complete_uninstall', 0);
				$msg = 'Option updated. Code Snippets will retain its data when deactivated';
			}
		}
		require_once( $this->dirname . '/inc/uninstall-plugin.php');
	}

	function settings_link( $links, $file ){
		static $this_plugin;
		if ( ! $this_plugin ) {
			$this_plugin = plugin_basename( __FILE__ );
		}
		if ( $file == $this_plugin ) {
			$settings_link = '<a href="' . $this->manage_snippets_url . '">Settings</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
	
	function run_snippets() {
		global $wpdb;
		// grab the active snippets from the database
		$active_snippets = $wpdb->get_results( 'select * FROM `' . $this->table_name . '` WHERE `active` = 1;' );
		if( count( $active_snippets ) ) {
			foreach( $active_snippets as $snippet ) {
				// execute the php code        
				$result = @eval( htmlspecialchars_decode( stripslashes( $snippet->code ) ) );
			}
		}
	}
}

endif; // class exists check

global $cs;
$cs = new code_snippets;

?>