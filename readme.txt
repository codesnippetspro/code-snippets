=== Code Snippets ===
Contributors: bungeshea
Donate link: http://bungeshea.wordpress.com/donate/
Tags: snippets, code, php
Requires at least: 3.3
Tested up to: 3.4
Stable tag: 1.1
License: GPLv3 or later
License URI: http://www.gnu.org/copyleft/gpl.html

Allows you to easily add code snippets through a GUI interface.

== Description ==

**Code Snippets** is a easy, clean and simple way to add code snippets to your site. 

Use the top level menu to manage your snippets. You can activate, deactivate, edit and delete snippets using a page similar to the Plugins menu. You can add a name for your snippet through the visual editor and the code through a tab-enabled text-area.

Snippets are stored in the `wp_snippets` table in the WordPress database (the table name may differ depending on what your table prefix is set to).

Code Snippets includes an option to clean up its data when deactivated. Each screen includes a help tab just in case you get stuck.

Further information and screenshots are available on the [plugin homepage]( http://bungeshea.wordpress.com/plugins/code-snippets).

Code Snippets was featured on WPMU.org - [WordPress Code Snippets: Keep them Organized with this Plugin!](http://wpmu.org/wordpress-code-snippets/)

If you have any feedback, issues or suggestions for improvements please start a topic in the [Support Forum](http://wordpress.org/support/plugin/code-snippets) and if you like the plugin please give it a rating at [WordPress.org](http://wordpress.org/extend/plugins/code-snippets) :-)

== Installation ==

1. Download the `code-snippets.zip` file to your local machine.
2. Either use the automatic plugin installer *(Plugins > Add New)* or Unzip the file and upload the **code-snippets** folder to your `/wp-content/plugins/` directory.
3. Activate the plugin through the Plugins menu
4. Visit the Add New Snippet menu page *(Snippets > Add New)* to add or edit Snippets.
5. Activate your snippets through the Manage Snippets page *(Snippets > Manage Snippets)*

== Frequently Asked Questions ==

= Do I need to include the &lt;?php, &lt;?, ?&gt; tags in my snippet? =
No, just copy all the content inside those tags.

= Is there a way to add a snippet but not run it right away? =
Yes. Just add it but do not activate it yet.

= Can I use the TAB key inside the code text-area to indent my code? =
Yes! Thanks to Ted Devito's [Tabby jQuery plugin](http://teddevito.com/demos/textarea.html), the TAB key will add an indent instead of switching to the next object.

= Will I lose my snippets if I change the theme or upgrade WordPress? =
No, the snippets are added to the WordPress database so are independent of the theme and unaffected by WordPress upgrades.

= Can the plugin be completely uninstalled? =
Yes, there is an option to delete the database table and if you want to completely remove the plugin.

= Can I copy any snippets I've created to another WordPress site? =
The import/export feature is currently in development. You can however, use the export feature of phpMyAdmin to copy the `wp_snippets` table to another WordPress database.

== Screenshots ==

1. The Manage Snippets page
2. The Add New Snippet page
3. Editing a snippet
4. The Uninstall Plugin page
5. Each screen includes a help tab just in case you get stuck.

== Changelog ==

= 1.1 =
* Fixed a permissions bug with `DISALLOW_FILE_EDIT` being set to true
* Fixed a bug with the page title reading 'Add New Snippet' on the 'Edit Snippets' page

= 1.0 =
* Stable version released.

== Upgrade Notice ==

= 1.1 =
* Minor bug fixes and improvments on the the 'Edit Snippet' page