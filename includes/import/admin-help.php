<?php

/**
 * Register and handle the help tabs for the
 * import snippets admin page
 *
 * @package Code_Snippets
 * @subpackage Import
 */

$screen = get_current_screen();
$manage_url = code_snippets_get_menu_url( 'manage' );

$screen->add_help_tab( array(
	'id'      => 'overview',
	'title'   => __( 'Overview', 'code-snippets' ),
	'content' =>
		'<p>' . __( 'Snippets are similar to plugins - they both extend and expand the functionality of WordPress. Snippets are more light-weight, just a few lines of code, and do not put as much load on your server. Here you can load snippets from a Code Snippets (.xml) import file into the database with your existing snippets.', 'code-snippets' ) . '</p>'
) );

$screen->add_help_tab( array(
	'id'      => 'import',
	'title'   => __( 'Importing', 'code-snippets' ),
	'content' =>
		'<p>' . __( 'You can load your snippets from a code snippets (.xml) export file using this page.', 'code-snippets' ) .
		sprintf( __( 'Snippets will be added to the database along with your existing snippets. Regardless of whether the snippets were active on the previous site, imported snippets are always inactive until activated using the <a href="%s">Manage Snippets</a> page.</p>', 'code-snippets' ), $manage_url ) . '</p>'
) );

$screen->add_help_tab( array(
	'id'      => 'export',
	'title'   => __( 'Exporting', 'code-snippets' ),
	'content' =>
		'<p>' . sprintf( __( 'You can save your snippets to a Code Snippets (.xml) export file using the <a href="%s">Manage Snippets</a> page.', 'code-snippets' ), $manage_url ) . '</p>'
) );

$screen->set_help_sidebar(
	'<p><strong>' . __( 'For more information:', 'code-snippets' ) . '</strong></p>' .
	'<p>' . __( '<a href="http://wordpress.org/plugins/code-snippets" target="_blank">WordPress Extend</a>', 'code-snippets' ) . '</p>' .
	'<p>' . __( '<a href="http://wordpress.org/support/plugin/code-snippets" target="_blank">Support Forums</a>', 'code-snippets' ) . '</p>' .
	'<p>' . __( '<a href="http://bungeshea.com/plugins/code-snippets/" target="_blank">Project Website</a>', 'code-snippets' ) .  '</p>'
);
