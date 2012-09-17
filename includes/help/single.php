<?php
$screen = get_current_screen();
$screen->add_help_tab( array(
	'id'		=> 'overview',
	'title'		=> __('Overview', 'code-snippets'),
	'content'	=>
		'<p>' . __('Snippets are similar to plugins - they both extend and expand the functionality of WordPress. Snippets are more light-weight, just a few lines of code, and do not put as much load on your server. Here you can add a new snippet, or edit an existing one.', 'code-snippets') . '</p>',
) );
$screen->add_help_tab( array(
	'id'		=> 'finding',
	'title'		=> __('Finding Snippets', 'code-snippets'),
	'content'	=>
		'<p>' . __('Here are some links to websites which host a large number of snippets that you can add to your site.
		<ul>
			<li><a href="http://wp-snippets.com" title="WordPress Snippets">WP-Snippets</a></li>
			<li><a href="http://wpsnipp.com" title="WP Snipp">WP Snipp</a></li>
			<li><a href="http://www.catswhocode.com/blog/snippets" title="Cats Who Code Snippet Library">Cats Who Code</a></li>
			<li><a href="http://wpmu.org">WPMU - The WordPress Experts</a></li>
		</ul>
		And below is a selection of snippets to get you started:
		<ul>
			<li><a title="Track post views using post meta" href="http://wpsnipp.com/index.php/functions-php/track-post-views-without-a-plugin-using-post-meta/" >Track post views using post meta</a></li>
			<li><a title="Disable Admin Bar" href="http://wp-snippets.com/disable-wp-3-1-admin-bar/">Disable Admin Bar</a></li>
			<li><a title="Disable the Visual Editor" href="http://wp-snippets.com/disable-the-visual-editor/">Disable the Visual Editor</a></li>
			<li><a title="Change Admin Logo" href="http://wp-snippets.com/change-admin-logo/">Change Admin Logo</a></li>
			<li><a title="Display Code in Posts" href="http://wp-snippets.com/code-in-posts/">Display Code in Posts</a></li>
			<li><a title="Grab Tweets from Twitter Feed" href="http://www.catswhocode.com/blog/snippets/grab-tweets-from-twitter-feed">Grab Tweets from Twitter Feed</a></li>
			<li><a title="Watermark images on the fly" href="http://www.catswhocode.com/blog/snippets/watermark-images-on-the-fly">Watermark images on the fly</a></li>
			<li><a title="Display number of Facebook fans in full text" href="http://www.catswhocode.com/blog/snippets/display-number-of-facebook-fans-in-full-text">Display number of Facebook fans in full text</a></li>
		</ul>', 'code-snippets'),
		sprintf( __('Snippets can be installed through the <a href="%s">Add New Snippet</a> page. Once a snippet has been installed, you can activate it here.', 'code-snippets'), $this->admin_single_url ) . '</p>'
) );
$screen->add_help_tab( array(
	'id'		=> 'adding',
	'title'		=> __('Adding Snippets', 'code-snippets'),
	'content'	=>
		'<p>' . __('You need to fill out the name and code fields for your snippet to be added. While the description field will add more information about how your snippet works, what is does and where you found it, it is completely optional.', 'code-snippets') . '</p>' .
		'<p>' . __('Make sure that you don\'t add the <code>&lt;?php</code>, <code>&lt;?</code> or <code>?&gt;</code> the beginning and end of the code. You can however use these tags in the code to stop and start PHP sections', 'code-snippets') . '</p>' .
		'<p>' . __('Please be sure to check that your snippet is valid PHP code and will not produce errors before adding it through this page. While doing so will not become active straight away, it will help to minimise the chance of a faulty snippet becoming active on your site.', 'code-snippets') . '</p>'
) );

$screen->set_help_sidebar(
	'<p><strong>' . __('For more information:', 'code-snippets') . '</strong></p>' .
	'<p>' . __('<a href="http://wordpress.org/extend/plugins/code-snippets" target="_blank">WordPress Extend</a>', 'code-snippets') . '</p>' . 
	'<p>' . __('<a href="http://wordpress.org/support/plugin/code-snippets" target="_blank">Support Forums</a>', 'code-snippets') . '</p>' .
	'<p>' . __('<a href="http://cs.bungeshea.com" target="_blank">Project Website</a>', 'code-snippets') .  '</p>'
);