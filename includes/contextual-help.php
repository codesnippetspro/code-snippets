<?php

/**
 * This file holds all of the content for the contextual help screens
 * @package Code_Snippets
 */

/* Exit from file if not loaded from inside WordPress admin */
if ( ! function_exists( 'is_admin' ) || ! is_admin() ) {
	return;
}

/**
 * Load the help sidebar
 * @param WP_Screen $screen Screen object
 */
function code_snippets_load_help_sidebar( $screen ) {
	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'code-snippets' ) . '</strong></p>' .
		'<p><a href="https://wordpress.org/plugins/code-snippets">' . __( 'About Plugin', 'code-snippets' ) . '</a></p>' .
		'<p><a href="https://wordpress.org/plugins/code-snippets/faq">' . __( 'FAQ', 'code-snippets' ) . '</a></p>' .
		'<p><a href="https://wordpress.org/support/plugin/code-snippets">' . __( 'Support Forums', 'code-snippets' ) . '</a></p>' .
		'<p><a href="http://bungeshea.com/plugins/code-snippets/">' . __( 'Plugin Website</a>', 'code-snippets' ) .  '</a></p>'
	);
}

/**
 * Register and handle the help tabs for the manage snippets admin page
 */
function code_snippets_load_manage_help() {
	$screen = get_current_screen();
	code_snippets_load_help_sidebar( $screen );

	$screen->add_help_tab( array(
		'id'      => 'overview',
		'title'   => __( 'Overview', 'code-snippets' ),
		'content' =>
			'<p>' . __( 'Snippets are similar to plugins - they both extend and expand the functionality of WordPress. Snippets are more light-weight, just a few lines of code, and do not put as much load on your server. Here you can manage your existing snippets and preform tasks on them such as activating, deactivating, deleting and exporting.', 'code-snippets' ) . '</p>',
	) );

	$screen->add_help_tab( array(
		'id'      => 'safe-mode',
		'title'   => __( 'Safe Mode', 'code-snippets' ),
		'content' =>
			'<p>' . __( 'Be sure to check your snippets for errors before you activate them, as a faulty snippet could bring your whole blog down. If your site starts doing strange things, deactivate all your snippets and activate them one at a time.', 'code-snippets' ) . '</p>' .
			'<p>' . __( "If something goes wrong with a snippet and you can't use WordPress, you can cause all snippets to stop executing by adding <code>define('CODE_SNIPPETS_SAFE_MODE', true);</code> to your <code>wp-config.php</code> file. After you have deactivated the offending snippet, you can turn off safe mode by removing this line or replacing <strong>true</strong> with <strong>false</strong>.", 'code-snippets' ) . '</p>',
	) );

	$screen->add_help_tab( array(
		'id'      => 'uninstall',
		'title'   => __( 'Uninstall', 'code-snippets' ),
		'content' =>
			'<p>' . sprintf( __( 'When you delete Code Snippets through the Plugins menu in WordPress it will clear up the <code>%1$s</code> table and a few other bits of data stored in the database. If you want to keep this data (ie: you are only temporally uninstalling Code Snippets) then remove the <code>%2$s</code> folder using FTP.', 'code-snippets' ), get_snippets_table_name(), dirname( CODE_SNIPPETS_FILE ) ) .
			'<p>' . __( "Even if you're sure that you don't want to use Code Snippets ever again on this WordPress installation, you may want to use the export feature to back up your snippets.", 'code-snippets' ) . '</p>',
	) );
}

/**
 * Register and handle the help tabs for the single snippet admin page
 */
function code_snippets_load_edit_help() {
	$screen = get_current_screen();
	code_snippets_load_help_sidebar( $screen );

	$screen->add_help_tab( array(
		'id'      => 'overview',
		'title'   => __( 'Overview', 'code-snippets' ),
		'content' =>
			'<p>' . __( 'Snippets are similar to plugins - they both extend and expand the functionality of WordPress. Snippets are more light-weight, just a few lines of code, and do not put as much load on your server. Here you can add a new snippet, or edit an existing one.', 'code-snippets' ) . '</p>',
	) );

	$screen->add_help_tab( array(
		'id'      => 'finding',
		'title'   => __( 'Finding Snippets', 'code-snippets' ),
		'content' =>
			'<p>' . __( 'Here are some links to websites which host a large number of snippets that you can add to your site.
			<ul>
				<li><a href="http://wp-snippets.com" title="WordPress Snippets">WP-Snippets</a></li>
				<li><a href="http://wpsnipp.com" title="WP Snipp">WP Snipp</a></li>
				<li><a href="http://www.catswhocode.com/blog/snippets" title="Cats Who Code Snippet Library">Cats Who Code</a></li>
				<li><a href="http://www.wpfunction.me">WP Function Me</a></li>
			</ul>', 'code-snippets' ) .
			 __( 'More places to find snippets, as well as a selection of example snippets, can be found in the <a href="https://github.com/sheabunge/code-snippets/wiki/Finding-snippets">plugin documentation</a>', 'code-snippets' ) . '</p>',
	) );

	$screen->add_help_tab( array(
		'id'      => 'adding',
		'title'   => __( 'Adding Snippets', 'code-snippets' ),
		'content' =>
			'<p>' . __( 'You need to fill out the name and code fields for your snippet to be added. While the description field will add more information about how your snippet works, what is does and where you found it, it is completely optional.', 'code-snippets' ) . '</p>' .
			'<p>' . __( 'Please be sure to check that your snippet is valid PHP code and will not produce errors before adding it through this page. While doing so will not become active straight away, it will help to minimise the chance of a faulty snippet becoming active on your site.', 'code-snippets' ) . '</p>',
	) );
}

/**
 * Register and handle the help tabs for the import snippets admin page
 */
function code_snippets_load_import_help() {
	$screen = get_current_screen();
	$manage_url = code_snippets_get_menu_url( 'manage' );
	code_snippets_load_help_sidebar( $screen );

	$screen->add_help_tab( array(
		'id'      => 'overview',
		'title'   => __( 'Overview', 'code-snippets' ),
		'content' =>
			'<p>' . __( 'Snippets are similar to plugins - they both extend and expand the functionality of WordPress. Snippets are more light-weight, just a few lines of code, and do not put as much load on your server. Here you can load snippets from a Code Snippets (.xml) import file into the database with your existing snippets.', 'code-snippets' ) . '</p>',
	) );

	$screen->add_help_tab( array(
		'id'      => 'import',
		'title'   => __( 'Importing', 'code-snippets' ),
		'content' =>
			'<p>' . __( 'You can load your snippets from a code snippets (.xml) export file using this page.', 'code-snippets' ) .
			sprintf( __( 'Snippets will be added to the database along with your existing snippets. Regardless of whether the snippets were active on the previous site, imported snippets are always inactive until activated using the <a href="%s">Manage Snippets</a> page.</p>', 'code-snippets' ), $manage_url ) . '</p>',
	) );

	$screen->add_help_tab( array(
		'id'      => 'export',
		'title'   => __( 'Exporting', 'code-snippets' ),
		'content' =>
			'<p>' . sprintf( __( 'You can save your snippets to a Code Snippets (.xml) export file using the <a href="%s">Manage Snippets</a> page.', 'code-snippets' ), $manage_url ) . '</p>',
	) );
}
