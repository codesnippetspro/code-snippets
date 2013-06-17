<?php
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