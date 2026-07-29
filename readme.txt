=== Code Snippets ===
Contributors: bungeshea
Donate link: http://cs.bungeshea.com/donate/
Tags: snippets, code, php, network, multisite
Requires at least: 3.3
Tested up to: 3.4.1
Stable tag: 1.4
License: GPLv3 or later
License URI: http://www.gnu.org/copyleft/gpl.html

An easy, clean and simple way to add code snippets to your site.

== Description ==

**Code Snippets** is an easy, clean and simple way to add code snippets to your site. No need to edit to your theme's `functions.php` file again!

A snippet is a small chunk of PHP code that you can use to extend the functionality of a WordPress-powered website; essentially a mini-plugin with much less load on your site. Most snippet-hosting sites tell you to add snippet code to your active theme's `functions.php` file, which can get rather long and messy after a while. Code Snippets changes that by providing a GUI interface for adding snippets and actually running them on your site as if they were in your active theme's `functions.php` file.

You can use a graphical interface similar to the Plugins menu to manage, activate, deactivate, edit and delete your snippets. Easily organise your snippets by adding add a name an description using the visual editor. Code Snippets includes built-in syntax highlighting and other features to help you write your code.

Although Code Snippets is designed to be easy-to-use and its interface looks, feels and acts as if it was a native part of WordPress, each screen includes a help tab, just in case you get stuck.

Further information, documentation and updates are available on the [plugin homepage](http://cs.bungeshea.com).

[As featured on the WPMU blog](http://wpmu.org/wordpress-code-snippets)

If you have any feedback, issues or suggestions for improvements please leave a topic in the [Support Forum](http://wordpress.org/support/plugin/code-snippets) and if you like the plugin please give it a perfect rating at [WordPress.org](http://wordpress.org/extend/plugins/code-snippets)

== Installation ==

1. Download the `code-snippets.zip` file to your local machine.
2. Either use the automatic plugin installer *(Plugins > Add New)* or Unzip the file and upload the **code-snippets** folder to your `/wp-content/plugins/` directory.
3. Activate the plugin through the Plugins menu
4. Visit the **Add New Snippet** menu page *(Snippets > Add New)* to add or edit Snippets.
5. Activate your snippets through the Manage Snippets page *(Snippets > Manage Snippets)*

'Network Activating' Code Snippets through the Network Dashboard will enable a special interface for running snippets across the entire network.

== Frequently Asked Questions ==

Further documentation available on the [plugin website](http://cs.bungeshea.com/docs/).

= Do I need to include the &lt;?php, &lt;?, ?&gt; tags in my snippet? =
No, just copy all the content inside those tags.

= Is there a way to add a snippet but not run it right away? =
Yes. Just add it but do not activate it yet.

= What do I use to write my snippets? =
The [CodeMirror](http://codemirror.net) source-code editor will add line numbers, syntax highlighting, search, tabulate and other cool features to the code editor.

= Will I lose my snippets if I change the theme or upgrade WordPress? =
No, the snippets are added to the WordPress database so are independent of the theme and unaffected by WordPress upgrades.

= Can the plugin be completely uninstalled? =
Yes, when you delete Code Snippets using the 'Plugins' menu in WordPress it will clean up the database table and a few other bits of data. Be careful not to remove Code Snippets using the Plugins menu unless you want this to happen.

= Can I copy any snippets I've created to another WordPress site? =
Yes! You can individually export a single snippet using the link below the snippet name on the 'Manage Snippets' page or bulk export multiple snippets using the 'Bulk Actions' feature. Snippets can later be imported using the 'Import Snippets' page by uploading the export file.

= Can I run network-wide snippets on a multisite installation? =
You can run snippets across an entire multisite network by 'Network Activating' Code Snippets through the Network Dashboard.

= I have an idea for a cool feature for Code Snippets! =
That's great! Let me know by starting (or adding to) a topic in the [Support Forums](http://wordpress.org/support/plugin/code-snippets/).

== Screenshots ==

1. The Manage Snippets page
2. The Add New Snippet page
3. Editing a snippet
4. Each screen includes a help tab just in case you get stuck.

== Changelog ==

= 1.4 =
* Added interface to Network Dashboard
* Updated uninstall to support multisite
* Replaced EditArea with [CodeMirror](http://codemirror.net)
* Small improvements

= 1.3.2 =
* Fixed a bug with version 1.3.1

= 1.3.1 =
* Changed plugin website URI
* Cleaned up some code

= 1.3 =
* Added export option to 'Manage Snippets' page
* Added 'Import Snippets' page

= 1.2 =
* Minor improvements
* Added code highlighting
* Removed 'Uninstall Plugin' page
* Data will now be cleaned up when plugin is deleted through WordPress admin

= 1.1 =
* Fixed a permissions bug with `DISALLOW_FILE_EDIT` being set to true
* Fixed a bug with the page title reading 'Add New Snippet' on the 'Edit Snippets' page

= 1.0 =
* Stable version released.

== Other Notes ==

Plugin updates will be posted on the [plugin's homepage](http://cs.bungeshea.com) ([RSS](http://cs.bungehea.tk/feed/)).

* Snippets are stored in the `wp_snippets` table in the WordPress database (the table name may differ depending on what your table prefix is set to).
* Code Snippets will automatically clean up its data when deleted through the WordPress dashboard.

You can also contribute to the code at [GitHub](https://github.com/bungeshea/code-snippets).

== Upgrade Notice ==

= 1.4 =
Better code highlighting and improved multisite support

= 1.3.2 =
Check out Code Snippet's new website: http://cs.bungeshea.com

= 1.3 =
Added import/export feature

= 1.2 =
Minor improvements | 
Added code highlighting | 
Plugin data will now be cleaned up when you delete the plugin.

= 1.1 =
Minor bug fixes and improvements on the the 'Edit Snippet' page