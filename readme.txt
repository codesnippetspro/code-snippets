=== Code Snippets ===
Contributors: bungeshea
Donate link: http://code-snippets.bungeshea.com/donate/
Tags: code-snippets, snippets, code, php, network, multisite
Requires at least: 3.3
Tested up to: 3.6
Stable tag: 1.7.1
License: MIT
License URI: license.txt

An easy, clean and simple way to add code snippets to your site.

== Description ==

**Code Snippets** is an easy, clean and simple way to add code snippets to your site. No need to edit to your theme's `functions.php` file again!

A snippet is a small chunk of PHP code that you can use to extend the functionality of a WordPress-powered website; essentially a mini-plugin with a *lot* less load on your site.
Most snippet-hosting sites tell you to add snippet code to your active theme's `functions.php` file, which can get rather long and messy after a while.
Code Snippets changes that by providing a GUI interface for adding snippets and **actually running them on your site** as if they were in your theme's `functions.php` file.

You can use a graphical interface, similar to the Plugins menu, to manage, activate, deactivate, edit and delete your snippets. Easily organise your snippets by adding a name and description using the visual editor. Code Snippets includes built-in syntax highlighting and other features to help you write your code. Snippets can be exported for transfer to another side, either in XML for later importing by the Code Snippets plugin, or in PHP for creating your own plugin or theme.

Although Code Snippets is designed to be easy-to-use and its interface looks, feels and acts as if it was a native part of WordPress, each screen includes a help tab, just in case you get stuck.

An addon-plugin for Code Snippets is available: [Code Snippets Tags](http://wordpress.org/extend/plugins/code-snippets-tags) will allow you to assign tags to your snippets and organize them in the table.

Further information, documentation and updates are available on the [plugin homepage](http://code-snippets.bungeshea.com). You can also contribute to the code at [GitHub](https://github.com/bungeshea/code-snippets).

[As featured on the WPMU blog](http://wpmu.org/wordpress-code-snippets)

If you have any feedback, issues, or suggestions for improvements please leave a topic in the [Support Forum](http://wordpress.org/support/plugin/code-snippets). If you like the plugin, or it is useful to you in any way, please review it on [WordPress.org](http://wordpress.org/support/view/plugin-reviews/code-snippets).

== Installation ==

= Automatic installation =

1. Log into your WordPress admin
2. Click __Plugins__
3. Click __Add New__
4. Search for __Code Snippets__
5. Click __Install Now__ under "Code Snippets"
6. Activate the plugin

= Manual installation =

1. Download the plugin
2. Extract the contents of the zip file
3. Upload the contents of the zip file to the `wp-content/plugins/` folder of your WordPress installation
4. Activate the Code Snippets plugin from 'Plugins' page.

**Network Activating** Code Snippets through the Network Dashboard will enable a special interface for running snippets across the entire network.

== Frequently Asked Questions ==

Further documentation available on the [plugin website](http://code-snippets.bungeshea.com/docs/).

= Do I need to include the &lt;?php, &lt;? or ?&gt; tags in my snippet? =
No, just copy all the content inside those tags. If you accidentally forget (or just like being lazy), the tags will be stripped from the beginning and end of the snippet when you save it.  You can, however, use those tags *inside* your snippets to start and end HTML sections.

= Is there a way to add a snippet but not run it right away? =
Yes. Just add it but do not activate it yet.

= How can I insert my snippet into the post text editor? =
Snippets that you add to this plugin are not meant to be inserted into the text editor. Instead, they are run on your site just as if they were added to your functions.php file.

= What do I use to write my snippets? =
The [CodeMirror](http://codemirror.net) source-code editor will add line numbers, syntax highlighting, bracket matching, search, tabulate and other cool features to the code editor.

= Can I preform search and replace commands in the code editor? =

* __Ctrl-F / Cmd-F__ : Start searching
* __Ctrl-G / Cmd-G__ : Find next
* __Shift-Ctrl-G / Shift-Cmd-G__ : Find previous
* __Shift-Ctrl-F / Cmd-Option-F__ : Replace
* __Shift-Ctrl-R / Shift-Cmd-Option-F__ : Replace all

= Will I lose my snippets if I change the theme or upgrade WordPress? =
No, the snippets are added to the WordPress database so are independent of the theme and unaffected by WordPress upgrades.

= Can the plugin be completely uninstalled? =
Yes, when you delete Code Snippets using the 'Plugins' menu in WordPress it will clean up the database table and a few other bits of data. Be careful not to remove Code Snippets using the Plugins menu unless you want this to happen.

= Can I copy any snippets I've created to another WordPress site? =
Yes! You can individually export a single snippet using the link below the snippet name on the 'Manage Snippets' page or bulk export multiple snippets using the 'Bulk Actions' feature. Snippets can later be imported using the 'Import Snippets' page by uploading the export file.

= Can I export my snippets to PHP for a site where I'm not using the Code Snippets plugin? =
Yes. Click the checkboxes next to the snippets you want to export, and then choose **Export to PHP** from the Bulk Actions menu and click Apply. The generated PHP file will contain the exported snippets' code, as well as their name and description in comments.

= Can I run network-wide snippets on a multisite installation? =
You can run snippets across an entire multisite network by **Network Activating** Code Snippets through the Network Dashboard. You can also activate Code Snippets just on the main site, and then individually on other sites of your choice.

= Is there anyway to add categories to snippets? =
Users of Code Snippets version 1.7 and later can install the [Code Snippets Tags](http://wordpress.org/extend/plugins/code-snippets-tags) plugin for the ability to add tags to snippets, and then later filter the snippets by tag for easier organization.

= I need help with Code Snippets =
You can get help with Code Snippets either on the [WordPress Support Forums](http://wordpress.org/support/plugin/code-snippets/), on [GithHub](https://github.com/bungeshea/code-snippets/issues), or on [WordPress Answers](http://wordpress.stackexchange.com).

= I have an idea for a cool feature for Code Snippets! =
That's great! Let me know by starting (or adding to) a topic in the [Support Forums](http://wordpress.org/support/plugin/code-snippets/) or open an issue on [GitHub](https://github.com/bungeshea/code-snippets/issues).

= I want to contribute to and help develop the Code Snippets plugin! =
That's fantastic! Join me on [GitHub](https://github.com/bungeshea/code-snippets), and also be sure to check out the [development page](http://code-snippets.bungeshea.com/development/) on the [project website](http://code-snippets.bungeshea.com).

== Screenshots ==

1. Managing existing snippets
2. Adding a new snippet
3. Editing a snippet
4. Importing snippets from an XML file
5. Managing exiting snippets in the MP6 interface

== Changelog ==

= 1.7.1 =
* Fix a bug with snippet being set as deactivated when saved
* Updated PHP Documentation completely. [[View online](http://bungeshea.github.io/code-snippets/api)]
* Only load admin functions when viewing dashboard
* Added German translation thanks to [David Decker](http://deckerweb.de)
* Allow or deny site administrators access to snippet admin menus. Set your preference in the **Enable Administration Menus* setting under the *Settings > Network Settings* network admin menu.
* Improve database table creation and upgrade process
* Optimized to use less database queries

= 1.7 =
* Improved plugin API
* Fixed a bug with saving snippets per page option ([#](http://wordpress.org/support/topic/plugin-code-snippets-snippets-per-page-does-not-work#post-3710991))
* Updated CodeMirror to version 3.11
* Allow plugin to be activated on individual sites on multisite ([#](http://wordpress.org/support/topic/dont-work-at-multisite))
* Slimmed down the description visual editor
* Added icon for the new MP6 admin UI ([#](http://wordpress.org/support/topic/icon-disappears-with-mp6))
* Strip PHP tags from the beginning and end of a snippet in case someone forgets
* Changed to [MIT license](http://opensource.org/licenses/mit-license.php)
* Removed HTML, CSS and JavaScript CodeMirror modes that were messing things up
* Made everything leaner, faster, and better

= 1.6.1 =
* Fixed a bug with permissions not being applied on install ([#](http://wordpress.org/support/topic/permissions-problem-after-install))
* Fixed a bug in the uninstall method ([#](http://wordpress.org/support/topic/bug-in-delete-script))

= 1.6 =
* Updated code editor to use CodeMirror 3
* Improved compatibility with Clean Options plugin
* Code improvements and optimization
* Changed namespace from `cs` to `code_snippets`
* Move css and js under assets
* Organized CodeMirror scripts
* Improved updating process
* Current line of code editor is now highlighted
* Highlight matches of selected text in code editor
* Only create snippet tables when needed
* Store multisite only options in site options table
* Fixed compatibility bugs with WordPress 3.5

= 1.5 =
* Updated CodeMirror to version 2.33
* Updated the 'Manage Snippets' page to use the WP_List_Table class
	* Added 'Screen Options' tab to 'Manage Snippets' page
	* Added search capability to 'Manage Snippets' page
	* Added views to easily filter activated, deactivated and recently activated snippets
	* Added ID column to 'Manage Snippets' page
	* Added sortable name and ID column on 'Manage Snippets' page ([#](http://wordpress.org/support/topic/plugin-code-snippets-suggestion-sort-by-snippet-name))
* Added custom capabilities
* Improved API
* Added 'Export to PHP' feature ([#](http://wordpress.org/support/topic/plugin-code-snippets-suggestion-bulk-export-to-php))
* Lengthened snippet name field to 64 characters ([#](http://wordpress.org/support/topic/plugin-code-snippets-snippet-title-limited-to-36-characters))
* Added i18n

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
* Fixed a permissions bug with `DISALLOW_FILE_EDIT` being set to true ([#](http://wordpress.org/support/topic/plugin-code-snippets-cant-add-new))
* Fixed a bug with the page title reading 'Add New Snippet' on the 'Edit Snippets' page
* Fixed a bug not allowing the plugin to be Network Activated ([#](http://wordpress.org/support/topic/plugin-code-snippets-network-activate-does-not-create-snippets-tables))

= 1.0 =
* Stable version released.

== Other Notes ==

Plugin updates will be posted on the [plugin's homepage](http://code-snippets.bungeshea.com/) ([RSS](http://code-snippets.bungeshea.com/feed/)).

* Snippets are stored in the `wp_snippets` table in the WordPress database (the table name may differ depending on what your table prefix is set to).
* Code Snippets will automatically clean up its data when deleted through the WordPress dashboard.

== Upgrade Notice ==

= 1.7 =
Many improvments and optimization. Download "Code Snippets Tags" plugin to add tags to snippets

= 1.6 =
Improvements and optimization with WordPress 3.5

= 1.5 =
Improvements on the 'Manage Snippets' page and localization

= 1.4 =
Better code highlighting and improved multisite support

= 1.3.2 =
Code Snippets has a new website: http://code-snippets.bungeshea.com/

= 1.3 =
Added import/export feature

= 1.2 =
Minor improvements |
Added code highlighting |
Plugin data will now be cleaned up when you delete the plugin.

= 1.1 =
Minor bug fixes and improvements on the the 'Edit Snippet' page
