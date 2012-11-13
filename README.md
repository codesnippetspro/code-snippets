# Code Snippets

* Contributors: [bungeshea](http://profiles.wordpress.org/bungeshea)
* Donate link: http://code-snippets.bungeshea.com/donate/
* Tags: snippets, code, php, network, multisite
* Requires at least: 3.3
* Tested up to: 3.4.2
* Stable tag: 1.5
* License: GPLv3 or later
* License URI: http://www.gnu.org/copyleft/gpl.html

An easy, clean and simple way to add code snippets to your site.

## Description

**Code Snippets** is an easy, clean and simple way to add code snippets to your site. No need to edit to your theme's `functions.php` file again!

A snippet is a small chunk of PHP code that you can use to extend the functionality of a WordPress-powered website; essentially a mini-plugin with a *lot* less load on your site.
Most snippet-hosting sites tell you to add snippet code to your active theme's `functions.php` file, which can get rather long and messy after a while.
Code Snippets changes that by providing a GUI interface for adding snippets and ***actually running them on your site** as if they were in your theme's `functions.php` file.

You can use a graphical interface, similar to the Plugins menu, to manage, activate, deactivate, edit and delete your snippets. Easily organise your snippets by adding a name and description using the visual editor. Code Snippets includes built-in syntax highlighting and other features to help you write your code. Snippets can be exported for transfer to another side, either in XML for later importing by the Code Snippets plugin, or in PHP for creating your own plugin or theme.

Although Code Snippets is designed to be easy-to-use and its interface looks, feels and acts as if it was a native part of WordPress, each screen includes a help tab, just in case you get stuck.

Further information, documentation and updates are available on the [plugin homepage](http://code-snippets.bungeshea.com).

[As featured on the WPMU blog](http://wpmu.org/wordpress-code-snippets)

If you have any feedback, issues, or suggestions for improvements please leave a topic in the [Support Forum](http://wordpress.org/support/plugin/code-snippets). If you like the plugin, or it is useful to you in any way, please review it on [WordPress.org](http://wordpress.org/support/view/plugin-reviews/code-snippets)

## Installation

### Automatic installation

1. Log into your WordPress admin
2. Click __Plugins__
3. Click __Add New__
4. Search for __Code Snippets__
5. Click __Install Now__ under "Code Snippets"
6. Activate the plugin

### Manual installation

1. Download the plugin
2. Extract the contents of the zip file
3. Upload the contents of the zip file to the `wp-content/plugins/` folder of your WordPress installation
4. Activate the Code Snippets plugin from 'Plugins' page.

**Network Activating** Code Snippets through the Network Dashboard will enable a special interface for running snippets across the entire network.

## Frequently Asked Questions

Further documentation available on the [plugin website](http://code-snippets.bungeshea.com/docs/).

### Do I need to include the `<?php`, `<?` or `?>` tags in my snippet?
No, just copy all the content inside those tags.

### Is there a way to add a snippet but not run it right away?
Yes. Just add it but do not activate it yet.

### What do I use to write my snippets?
The [CodeMirror](http://codemirror.net) source-code editor will add line numbers, syntax highlighting, search, tabulate and other cool features to the code editor.

### Will I lose my snippets if I change the theme or upgrade WordPress?
No, the snippets are added to the WordPress database so are independent of the theme and unaffected by WordPress upgrades.

### Can the plugin be completely uninstalled?
Yes, when you delete Code Snippets using the 'Plugins' menu in WordPress it will clean up the database table and a few other bits of data. Be careful not to remove Code Snippets using the Plugins menu unless you want this to happen.

### Can I copy any snippets I've created to another WordPress site?
Yes! You can individually export a single snippet using the link below the snippet name on the 'Manage Snippets' page or bulk export multiple snippets using the 'Bulk Actions' feature. Snippets can later be imported using the 'Import Snippets' page by uploading the export file.

### Can I export my snippets to PHP for a site where I'm not using the Code Snippets plugin?
Yes. Click the checkboxes next to the snippets you want to export, and then choose **Export to PHP** from the Bulk Actions menu and click Apply. The generated PHP file will contain the exported snippets' code, as well as their name and description in comments.

### Can I run network-wide snippets on a multisite installation?
You can run snippets across an entire multisite network by **Network Activating** Code Snippets through the Network Dashboard.

### I have an idea for a cool feature for Code Snippets!
That's great! Let me know by starting (or adding to) a topic in the [Support Forums](http://wordpress.org/support/plugin/code-snippets/).

### I want to contribute to and help develop the Code Snippets plugin!
That's fantastic! Join me on [GitHub](http://github.com/bungeshea/code-snippets), and also be sure to check out the [development page](http://code-snippets.bungeshea.com/dev/) on the [project website](http://code-snippets.bungeshea.com).

## Screenshots

### Managing existing snippets
![Managing existing snippets](https://raw.github.com/bungeshea/code-snippets/master/screenshot-1.jpg "Managing existing snippets")

### Managing network-wide snippets
![Managing network-wide snippets](https://raw.github.com/bungeshea/code-snippets/master/screenshot-2.jpg "Managing network-wide snippets")

### Adding a new snippet
![Adding a new snippet](https://raw.github.com/bungeshea/code-snippets/master/screenshot-3.jpg "Adding a new snippet")

### Editing a snippet
![Editing a snippet](https://raw.github.com/bungeshea/code-snippets/master/screenshot-4.jpg "Editing a snippet")

### Importing snippets from an XML file
![Importing snippets from an XML file](https://raw.github.com/bungeshea/code-snippets/master/screenshot-5.jpg "Importing snippets from an XML file")

## Changelog

### 1.6
* Updated CodeMirror to version 2.35
* Improved compatibility with Clean Options plugin
* Code improvements and optimization
	* Changed namespace from `cs` to `code_snippets`
	* Store multisite only options in site options table
	* Move css and js under assets
	* Organized CodeMirror scripts
	* Improved updating process
* Current line of code editor is now highlighted

### 1.5
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

### 1.4
* Added interface to Network Dashboard
* Updated uninstall to support multisite
* Replaced EditArea with [CodeMirror](http://codemirror.net)
* Small improvements

### 1.3.2
* Fixed a bug with version 1.3.1

### 1.3.1
* Changed plugin website URI
* Cleaned up some code

### 1.3
* Added export option to 'Manage Snippets' page
* Added 'Import Snippets' page

### 1.2
* Minor improvements
* Added code highlighting
* Removed 'Uninstall Plugin' page
* Data will now be cleaned up when plugin is deleted through WordPress admin

### 1.1
* Fixed a permissions bug with `DISALLOW_FILE_EDIT` being set to true ([#](http://wordpress.org/support/topic/plugin-code-snippets-cant-add-new))
* Fixed a bug with the page title reading 'Add New Snippet' on the 'Edit Snippets' page
* Fixed a bug not allowing the plugin to be Network Activated ([#](http://wordpress.org/support/topic/plugin-code-snippets-network-activate-does-not-create-snippets-tables))

### 1.0
* Stable version released.

## Other Notes

Plugin updates will be posted on the [plugin's homepage](http://code-snippets.bungeshea.com) ([RSS](http://code-snippets.bungeshea.com/feed/)).

* Snippets are stored in the `wp_snippets` table in the WordPress database (the table name may differ depending on what your table prefix is set to).
* Code Snippets will automatically clean up its data when deleted through the WordPress dashboard.

You can also contribute to the code at [GitHub](https://github.com/bungeshea/code-snippets).