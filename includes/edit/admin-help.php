<?php

/**
 * Register and handle the help tabs for the
 * single snippet admin page
 *
 * @package    Code_Snippets
 * @subpackage Contextual_Help
 */

$screen = get_current_screen();

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
		 __( 'More places to find snippets, as well as a selection of example snippets, can be found in the <a href="https://github.com/sheabunge/code-snippets/wiki/Finding-snippets">plugin documentation</a>', 'code-snippets' ) . '</p>'
) );

$screen->add_help_tab( array(
	'id'      => 'adding',
	'title'   => __( 'Adding Snippets', 'code-snippets' ),
	'content' =>
		'<p>' . __( 'You need to fill out the name and code fields for your snippet to be added. While the description field will add more information about how your snippet works, what is does and where you found it, it is completely optional.', 'code-snippets' ) . '</p>' .
		'<p>' . __( 'Please be sure to check that your snippet is valid PHP code and will not produce errors before adding it through this page. While doing so will not become active straight away, it will help to minimise the chance of a faulty snippet becoming active on your site.', 'code-snippets' ) . '</p>'
) );

$screen->set_help_sidebar(
	'<p><strong>' . __( 'For more information:', 'code-snippets' ) . '</strong></p>' .
	'<p>' . __( '<a href="http://wordpress.org/plugins/code-snippets" target="_blank">WordPress Extend</a>', 'code-snippets' ) . '</p>' .
	'<p>' . __( '<a href="http://wordpress.org/support/plugin/code-snippets" target="_blank">Support Forums</a>', 'code-snippets' ) . '</p>' .
	'<p>' . __( '<a href="http://bungeshea.com/plugins/code-snippets/" target="_blank">Project Website</a>', 'code-snippets' ) .  '</p>'
);
