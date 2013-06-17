<?php
$screen = get_current_screen();
$screen->add_help_tab( array(
	'id'		=> 'overview',
	'title'		=> 'Overview',
	'content'	=>
		"<p>Snippets are similar to plugins - they both extend and expand the functionality of WordPress. Snippets are more light-weight, just a few lines of code, and do not put as much load on your server. Here you can load snippets from a Code Snippets (.xml) import file into the database with your existing snippets.</p>"
) );
	
$screen->add_help_tab( array(
	'id'		=> 'import',
	'title'		=> 'Importing',
	'content'	=>
		"<p>You can load your snippets from a Code Snippets (.xml) import file using this page. Snippets will be added to the database along with your existing snippets. Regardless of whether the snippets were active on the previous site, imported snippets are always inactive until activated using the <a href='$this->admin_manage_url'>Manage Snippets</a> page.</p>"
) );
	
$screen->add_help_tab( array(
	'id'		=> 'export',
	'title'		=> 'Exporting',
	'content'	=>
		"<p>You can save your snippets to a Code Snippets (.xml) export file using the <a href='$this->admin_manage_url'>Manage Snippets</a> page.</p>"
) );

$screen->set_help_sidebar(
	"<p><strong>For more information:</strong></p>" .
	"<p><a href='http://wordpress.org/extend/plugins/code-snippets' target='_blank'>WordPress Extend</a></p>" .
	"<p><a href='http://wordpress.org/support/plugin/code-snippets' target='_blank'>Support Forums</a></p>" .
	"<p><a href='http://bungeshea.wordpress.com/plugins/code-snippets' target='_blank'>SheaPress</a></p>"
);