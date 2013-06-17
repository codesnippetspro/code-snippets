<?php
/*
	Plugin Name:	Code Snippets
	Plugin URI:		http://cs.bungeshea.com
	Description:	An easy, clean and simple way to add code snippets to your site. No need to edit to your theme's functions.php file again!
	Author:			Shea Bunge
	Version:		1.3.2
	Author URI:		http://bungeshea.com
	License:		GPLv3 or later
	
	Code Snippets - WordPress Plugin
    Copyright (C) 2012  Shea Bunge

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists('Code_Snippets') ) :

class Code_Snippets {

	public $table    = 'snippets';
	public $version	 = '1.3.2';

	var $admin_manage_url = 'snippets';
	var $admin_edit_url   = 'snippet';
	var $admin_import_url = 'import-snippets';

	public function Code_Snippets() {
		$this->setup();			// initialise the varables and run the hooks
		$this->create_table();	// create the snippet tables if they do not exist
		$this->upgrade();		// check if we need to change some stuff
		$this->run_snippets();	// execute the snippets
	}
	
	function setup() {
		global $wpdb;
		$this->file      		=	__FILE__;
		$this->table			=	apply_filters( 'cs_table', $wpdb->prefix . $this->table );
		$this->current_version	=	get_option( 'cs_db_version' );
		
		$this->basename			=	plugin_basename( $this->file );
		$this->plugin_dir		=	plugin_dir_path( $this->file );
		$this->plugin_url		=	plugin_dir_url ( $this->file );
		
		add_action( 'admin_menu', array( $this, 'add_admin_menus' ) );
		add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'settings_link' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_meta' ), 10, 2 );
	}
	
	function create_table() {
		global $wpdb;
		if( $wpdb->get_var( "SHOW TABLES LIKE '$this->table'" ) != $this->table ) {
			$sql = 'CREATE TABLE ' . $this->table . ' (
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
	
	function upgrade() {
		if( $this->current_version < 1.2 ) {
			delete_option( 'cs_complete_uninstall' );
		}
		if( $this->current_version < $this->version ) {
			update_option( 'cs_db_version', $this->version );
		}
	}
	
	function add_admin_menus() {
		$this->admin_manage_page = add_menu_page( __('Snippets'), __('Snippets'), 'install_plugins', $this->admin_manage_url,  array( $this, 'admin_manage' ), $this->plugin_url . 'images/icon16.png', 67 );
		add_submenu_page('snippets', __('Snippets'), __('Manage Snippets') , 'install_plugins', $this->admin_manage_url, array( $this, 'admin_manage_loader') );
		$this->admin_edit_page = add_submenu_page( 'snippets', __('Add New Snippet'), __('Add New'), 'install_plugins', $this->admin_edit_url, array( $this, 'admin_edit' ) );
		$this->admin_import_page = add_submenu_page( 'snippets', __('Import Snippets'), __('Import'), 'install_plugins', $this->admin_import_url, array( $this, 'admin_import' ) );

		$this->admin_manage_url	=	admin_url( 'admin.php?page=' . $this->admin_manage_url );
		$this->admin_edit_url	=	admin_url( 'admin.php?page=' . $this->admin_edit_url );
		$this->admin_import_url	=	admin_url( 'admin.php?page=' . $this->admin_import_url );
		
		add_action( "admin_print_styles-$this->admin_manage_page", array( $this, 'load_stylesheet' ), 5 );
		add_action( "admin_print_styles-$this->admin_edit_page", array( $this, 'load_stylesheet' ), 5 );
		add_action( "admin_print_styles-$this->admin_import_page", array( $this, 'load_stylesheet' ), 5 );
		add_action( "admin_print_scripts-$this->admin_edit_page", array( $this, 'load_editarea' ), 5 );
		add_action( "load-$this->admin_manage_page", array( $this, 'admin_manage_loader' ), 5 );
		add_action( "load-$this->admin_edit_page", array( $this, 'admin_edit_loader' ), 5 );
		add_action( "load-$this->admin_import_page", array( $this, 'admin_import_loader' ), 5 );
	}

	function load_stylesheet() {
		wp_enqueue_style( 'code-snippets', plugins_url( 'css/style.css', $this->file ), false, $this->version );
	}

	function load_editarea() {
		wp_register_script( 'editarea', plugins_url( 'includes/edit_area/edit_area_full.js', $this->file ), false, '0.8.2' );
		wp_enqueue_script( 'editarea' );
	}

	function admin_manage_loader() {
	
		require_once $this->plugin_dir . 'includes/export.php';
		
		if(	isset( $_POST['action'] ) && isset( $_POST['ids'] ) )
			if( $_POST['action'] == 'export' && is_array( $_POST['ids'] ) )
				cs_export( $_POST['ids'], $this->table );
				
		if(	isset( $_POST['action2'] ) && isset( $_POST['ids'] ) )
			if( $_POST['action2'] == 'export' && is_array( $_POST['ids'] ) )
				cs_export( $_POST['ids'], $this->table );
		
		if(	isset( $_GET['action'] ) && isset( $_GET['id'] ) )
			if( $_GET['action'] == 'export' )
				cs_export( $_GET['id'], $this->table );
	
		include $this->plugin_dir . 'includes/help/admin-manage-help.php';
	}
	
	function admin_edit_title( $title ) {
		return str_ireplace( 'Add New Snippet', 'Edit Snippet', $title );
	}
	
	function admin_edit_loader() {
	
		if( isset( $_GET['action'] ) && @$_GET['action'] == 'edit' )
			add_filter( 'admin_title',  array( $this, 'admin_edit_title' ) );
	
		include $this->plugin_dir . 'includes/help/admin-edit-help.php';
	}
	
	function admin_import_loader() {
		include $this->plugin_dir . 'includes/help/admin-import-help.php';
	}
	
	function bulk_action( $action, $ids ) {
		if( ! isset( $action ) && ! isset( $ids ) && ! is_array( $ids ) )
			return false;
		global $wpdb;
		$count = 0;
		switch( $action ) {
				
			case 'activate':
				foreach( $ids as $id ) {
					$wpdb->query('update ' . $this->table . ' set active=1 where id=' . intval( $id ) . ' limit 1' );
					$count++;
				}
				$msg = "Activated $count snippets.";
				break;
					
			case 'deactivate':
				foreach( $ids as $id ) {
					$wpdb->query( 'update ' . $this->table . ' set active=0 where id=' . intval( $id ) . ' limit 1' );
					$count++;
				}
				$msg = "Deactivated $count snippets.";
				break;
					
			case 'delete':
				foreach( $ids as $id ) {
					$wpdb->query( 'delete from ' .  $this->table .  ' where id=' . intval( $id ) . ' limit 1' );
					$count++;
				}
				$msg = "Deleted $count snippets.";
				break;
		}
	}
	
	function admin_manage() {
		global $wpdb;
		
		$this->bulk_action( @$_POST['action'], @$_POST['ids'] );
		$this->bulk_action( @$_POST['action2'], @$_POST['ids'] );
		
		if( isset( $_GET['action'] ) && isset( $_GET['id'] ) ) {
			if( $_GET['action'] == 'delete') {
				$wpdb->query( 'delete from ' . $this->table . ' where id=' . intval( $_GET['id'] ) . ' limit 1' );
				$msg = 'Snippet deleted.';
			}
			elseif( $_GET['action'] == 'activate' ) {
				$wpdb->query('update ' . $this->table . ' set active=1	where id=' . intval( $_GET['id'] ) . ' limit 1' );
				$msg = 'Snippet activated.';
			}
			elseif( $_GET['action'] == 'deactivate' ) {
				$wpdb->query('update ' . $this->table . ' set active=0 where id=' . intval( $_GET['id'] ) . ' limit 1' );
				$msg = 'Snippet deactivated.';
			}
		}

    require $this->plugin_dir . 'includes/admin/admin-manage.php';
}

	function admin_edit() {
		global $wpdb;
		if( isset( $_POST['save_snippet'] ) ) {
			$name			=	mysql_real_escape_string( htmlspecialchars( $_POST['snippet_name' ] ) );
			$description	=	mysql_real_escape_string( htmlspecialchars( $_POST['snippet_description'] ) );
			$code			=	mysql_real_escape_string( htmlspecialchars( $_POST['snippet_code'] ) );

			if( strlen( $name ) && strlen( $code ) ) {
				if( isset( $_POST['edit_id'] ) ) {
					$wpdb->query( "update $this->table set name='".$name."',
							description='".$description."',
							code='".$code."'
							where id=" . intval($_POST["edit_id"]." limit 1"));
					$msg = 'Snippet updated.';
				}
				else {
					$wpdb->query( "insert into $this->table(name,description,code,active) VALUES ('$name','$description','$code',0)" );
					$msg = 'Snippet added.';
				}
			}
			else {
				$msg = 'Please provide a name for the snippet and the code.';
			}
		}
		require $this->plugin_dir . 'includes/admin/admin-edit.php';
	}

	function admin_import() {
		if( file_exists( $_FILES['cs_import_file']['tmp_name'] ) ) {
			global $wpdb;
			$xml = simplexml_load_string( file_get_contents( $_FILES['cs_import_file']['tmp_name'] ) );
			foreach( $xml->children() as $child ) {
				$wpdb->query( "insert into $this->table (name,description,code) VALUES ('$child->name','$child->description','$child->code')" );
			}
			
			$msg =  'Imported ' . $xml->count() . ' snippets';
		}
		require $this->plugin_dir . 'includes/admin/admin-import.php';
	}	
	
	function settings_link( $links ) {
		array_unshift( $links, '<a href="' . $this->admin_manage_url . '" title="Manage your existing snippets">' . __('Manage') . '</a>' );
		return $links;
	}
	
	function plugin_meta( $links, $file ) {
		if ( $file == $this->basename ) {
			return array_merge( $links, array(
				'<a href="http://wordpress.org/extend/plugins/code-snippets/" title="Visit the WordPress.org plugin page">' . __( 'About' ) . '</a>',
				'<a href="http://wordpress.org/support/plugin/code-snippets/" title="Visit the support forums">' . __( 'Support' ) . '</a>'
			) );
		}
		return $links;
	}

	function run_snippets() {
		if( defined( 'CS_SAFE_MODE' ) ) if( CS_SAFE_MODE ) return;
		global $wpdb;
		// grab the active snippets from the database
		$active_snippets = $wpdb->get_results( 'select * FROM `' . $this->table . '` WHERE `active` = 1;' );
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
$cs = new Code_Snippets;

register_uninstall_hook( __FILE__, 'cs_uninstall' );

function cs_uninstall() {
	global $wpdb, $cs;
	$wpdb->query( "DROP TABLE IF EXISTS `$cs->table`" );
	delete_option( 'cs_db_version' );
}