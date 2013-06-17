<?php

/*
	Plugin Name:	Code Snippets
	Plugin URI:		http://bungeshea.wordpress.com/plugins/code-snippets/
	Description:	Provides an easy-to-manage GUI interface for adding code snippets to your blog.
	Author:			Shea Bunge
	Version:		1.2
	Author URI:		http://bungeshea.wordpress.com/
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
if ( !defined( 'ABSPATH' ) ) exit;

if( !class_exists('Code_Snippets') ) :

class Code_Snippets {

	public $table			= 'snippets';
	public $version			= '1.2';
	
	public $file;
	public $plugin_dir;
	public $plugin_url;
	public $basename;

	var $admin_manage_url	= 'snippets';
	var $admin_edit_url		= 'snippet';

	public function Code_Snippets() {
		$this->setup();			// initialise the varables and run the hooks
		$this->create_table();	// create the snippet tables if they do not exist
		$this->upgrade();		// check if we need to change some stuff
		$this->run_snippets();	// execute the snippets
	}
	
	function setup() {
		global $wpdb;
		$this->file      		=	__FILE__;
		$this->table			=	$wpdb->prefix . $this->table;
		$this->current_version	=	get_option( 'cs_db_version' );

		$this->basename			=	plugin_basename( $this->file );
		$this->plugin_dir		=	plugin_dir_path( $this->file );
		$this->plugin_url		=	plugin_dir_url ( $this->file );

		$this->admin_manage_url	=	admin_url( 'admin.php?page=' . $this->admin_manage_url );
		$this->admin_edit_url	=	admin_url( 'admin.php?page=' . $this->admin_edit_url );

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
		if( $this->current_version < $this->version ) {
			delete_option( 'cs_complete_uninstall' );
			update_option( 'cs_db_version', $this->version );
		}
	}
	
	function add_admin_menus() {
		$this->admin_manage_page = add_menu_page( __('Snippets'), __('Snippets'), 'install_plugins', 'snippets',  array( $this, 'admin_manage_loader' ), $this->plugin_url . 'images/icon16.png', 67 );
		add_submenu_page('snippets', __('Snippets'), __('Manage Snippets') , 'install_plugins', 'snippets', array( $this, 'admin_manage_loader') );
		$this->admin_edit_page = add_submenu_page( 'snippets', __('Add New Snippet'), __('Add New'), 'install_plugins', 'snippet', array( $this, 'admin_edit_loader' ) );

		add_action( "admin_print_styles-$this->admin_manage_page", array( $this, 'load_stylesheet' ), 5 );
		add_action( "admin_print_styles-$this->admin_edit_page", array( $this, 'load_stylesheet' ), 5 );
		add_action( "admin_print_scripts-$this->admin_edit_page", array( $this, 'load_editarea' ), 5 );
		add_action( "load-$this->admin_manage_page", array( $this, 'admin_manage_help' ), 5 );
		add_action( "load-$this->admin_edit_page", array( $this, 'admin_edit_help' ), 5 );
	}

	function load_stylesheet() {
		wp_enqueue_style('code-snippets-admin-style', plugins_url( 'css/style.css', $this->file), false, $this->version );
	}

	function load_editarea() {
		wp_register_script( 'editarea', plugins_url( 'includes/edit_area/edit_area_full.js', $this->file ), array( 'jquery' ), '0.8.2' );
		wp_enqueue_script( 'editarea' );
	}

	function admin_manage_help() {
	
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
				"<p>If something goes wrong with a snippet and you can&#8217;t use WordPress, you can use a database manager like phpMyAdmin to access the <code>$this->table</code> table in your WordPress database. Locate the offending snippet (if you know which one is the trouble) and change the 1 in the 'active' column into a 0. If this doesn't work try doing this for all snippets.<br/>You can also delete or rename the <code>$this->table</code> table and the table will automaticly be reconstructed so you can re-add snippets one at a time.</p>"
		) );
		
		$screen->add_help_tab( array(
			'id'		=> 'uninstall',
			'title'		=> 'Uninstall',
			'content'	=>
				"<p>When you delete Code Snippets through the Plugins menu in WordPress it will clear up the <code>$this->table</code> table and a few other bits of data stored in the database. If you want to keep this data (ie you are only temporally uninstalling Code Snippets) then remove the <code>".dirname(__FILE__)."</code> folder using FTP." .
				"<p>Even if you're sure that you don't want to use Code Snippets ever again on this WordPress installaion, you may want to use phpMyAdmin to back up the <code>$this->table</code> table in the database. You can later use phpMyAdmin to import it back.</p>"
		) );

		$screen->set_help_sidebar(
			"<p><strong>For more information:</strong></p>" .
			"<p><a href='http://wordpress.org/extend/plugins/code-snippets' target='_blank'>WordPress Extend</a></p>" .
			"<p><a href='http://wordpress.org/support/plugin/code-snippets' target='_blank'>Support Forums</a></p>" .
			"<p><a href='http://bungeshea.wordpress.com/plugins/code-snippets' target='_blank'>SheaPress</a></p>"
		);
	}
	
	function admin_edit_title( $title ) {
		return str_ireplace( 'Add New Snippet', 'Edit Snippet', $title );
	}
	
	function admin_edit_help() {
	
		if( isset( $_GET['action'] ) && @$_GET['action'] == 'edit' )
			add_filter( 'admin_title',  array( $this, 'admin_edit_title' ) );
	
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
					<li><a href='http://wp-snippets.com' title='WordPress Snippets'>WP-Snippets</a></li>
					<li><a href='http://wpsnipp.com' title='WP Snipp'>WP Snipp</a></li>
					<li><a href='http://www.catswhocode.com/blog/snippets' title='Cats Who Code Snippet Library'>Cats Who Code</a></li>
					<li><a href='http://wpmu.org'>WPMU - The WordPress Experts</a></li>
				</ul>
				And below is a selection of snippets to get you started:
				<ul>
					<li><a title='Track post views using post meta' href='http://wpsnipp.com/index.php/functions-php/track-post-views-without-a-plugin-using-post-meta/' >Track post views using post meta</a></li>
					<li><a title='Disable Admin Bar' href='http://wp-snippets.com/disable-wp-3-1-admin-bar/'>Disable Admin Bar</a></li>
					<li><a title='Disable the Visual Editor' href='http://wp-snippets.com/disable-the-visual-editor/'>Disable the Visual Editor</a></li>
					<li><a title='Change Admin Logo' href='http://wp-snippets.com/change-admin-logo/'>Change Admin Logo</a></li>
					<li><a title='Display Code in Posts' href='http://wp-snippets.com/code-in-posts/'>Display Code in Posts</a></li>
					<li><a title='Grab Tweets from Twitter Feed' href='http://www.catswhocode.com/blog/snippets/grab-tweets-from-twitter-feed'>Grab Tweets from Twitter Feed</a></li>
					<li><a title='Watermark images on the fly' href='http://www.catswhocode.com/blog/snippets/watermark-images-on-the-fly'>Watermark images on the fly</a></li>
					<li><a title='Display number of Facebook fans in full text' href='http://www.catswhocode.com/blog/snippets/display-number-of-facebook-fans-in-full-text'>Display number of Facebook fans in full text</a></li>
				</ul>
				Snippets can be installed through the <a href='$this->admin_edit_url'>Add New Snippet</a> page or by addng them to the <code>$this->table</code> table in the database (Warning: for advanced users only). Once a snippet has been installed, you can activate it here.</p>"
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
			"<p><a href='http://bungeshea.wordpress.com/plugins/code-snippets' target='_blank'>SheaPress</a></p>"
		);
	}
	
	function bulk_action( $action, $ids ) {
		if( !isset( $action ) && !isset( $ids ) && !is_array( $ids ) )
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
	
	function admin_manage_loader() {
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

    require_once $this->plugin_dir . 'includes/admin-manage.php';
}

	function admin_edit_loader() {
		global $wpdb;
		if( isset( $_POST['save_snippet'] ) ) {
			$name			=	mysql_real_escape_string( htmlspecialchars( $_POST['snippet_name' ] ) );
			$description	=	mysql_real_escape_string( htmlspecialchars( $_POST['snippet_description'] ) );
			$code			=	mysql_real_escape_string( htmlspecialchars( $_POST['snippet_code'] ) );

			if( strlen( $name ) && strlen( $code ) ) {
				if( isset($_POST['edit_id'] ) ) {
					$wpdb-> query( "update $this->table set name='".$name."',
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
		require_once $this->plugin_dir . 'includes/admin-edit.php';
	}

	function settings_link( $links ) {
		array_unshift( $links, '<a href="' . $this->admin_manage_url . '" title="Manage your existing snippets">' . __('Manage') . '</a>' );
		return $links;
	}
	
	function plugin_meta( $links, $file ) {
		if ( $file == $this->basename ) {
			return array_merge( $links, array(
				'<a href="http://wordpress.org/support/plugin/code-snippets/" title="Visit the WordPress.org plugin page">' . __( 'About' ) . '</a>',
				'<a href="http://wordpress.org/support/plugin/code-snippets/" title="Visit the support forums">' . __( 'Support' ) . '</a>'
			) );
		}
		return $links;
	}

	function run_snippets() {
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