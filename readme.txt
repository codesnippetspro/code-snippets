=== Code Snippets ===
Contributors: bungeshea
Donate link: http://bungeshea.com/donate/
Tags: code-snippets, snippets, code, php, network, multisite
Requires at least: 3.3
Tested up to: 4.2.2
Stable tag: 2.3.0
License: MIT
License URI: license.txt

An easy, clean and simple way to add code snippets to your site.

== Description ==

Code Snippets is an easy, clean and simple way to add code snippets to your site. It removes the need to add custom snippets to your theme theme's `functions.php` file.

A snippet is a small chunk of PHP code that you can use to extend the functionality of a WordPress-powered website; essentially a mini-plugin with less load on your site.
Most snippet-hosting sites tell you to add snippet code to your active theme's `functions.php` file, which can get rather long and messy after a while.
Code Snippets changes that by providing a GUI interface for adding snippets and **actually running them on your site** just as if they were in your theme's `functions.php` file.

Code Snippets provides graphical interface, similar to the Plugins menu, for managing snippets. Snippets can can be activated and deactivated, just like plugins. The snippet editor includes fields for a name, a visual editor-enabled description, tags to allow you to categorize snippets, and a full-featured code editor. Snippets can be exported for transfer to another side, either in XML for later importing by the Code Snippets plugin, or in PHP for creating your own plugin or theme

If you have any feedback, issues, or suggestions for improvements please leave a topic in the [Support Forum](http://wordpress.org/support/plugin/code-snippets). If you like this plugin, or it is useful to you in any way, please review it on [WordPress.org](http://wordpress.org/support/view/plugin-reviews/code-snippets). If you'd like to contribute to the plugin's code or translate it into another language, please [fork the plugin on GitHub](https://github.com/sheabunge/code-snippets).

= Translations =

Code Snippets can be used in these different languages thanks to the following translators:

* German - [David Decker](http://deckerweb.de) and [Joerg Knoerchen](http://www.sensorgrafie.de/)
* Slovak - [Ján Fajčák](http://wp.sk)
* Russian - [Alexander Samsonov](http://www.wordpressplugins.ru/administration/code-snippets.html)
* Chinese - [Jincheng Shan](http://shanjincheng.com)
* Serbo-Croatian - [Borisa Djuraskovic from Web Hosting Hub](http://www.webhostinghub.com/)
* Japanese - [mt8](http://mt8.biz/)
* French - [oWEB](http://office-web.net)

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

Network Activating Code Snippets through the Network Dashboard will enable a special interface for running snippets across the entire network.

== Frequently Asked Questions ==

= How can I insert my snippet into the post text editor? =
Snippets that you add to this plugin are not meant to be inserted into the text editor. Instead, they are run on your site just as if they were added to your functions.php file.

= Do I need to include the `<?php`, `<?` or `?>` tags in my snippet? =
No, just copy all the content inside those tags. If you accidentally forget (or just like being lazy), the tags will be stripped from the beginning and end of the snippet when you save it. You can, however, use those tags *inside* your snippets to start and end HTML sections

= Help! I just activated a snippet, and my whole site broke! =
You can try activating 'safe mode'. All snippets will not execute while safe mode is active, allowing you to access your site and deactivate the snippet that is causing the error. To activate safe mode, add the following line to your wp-config.php file, just before the line that reads `/* That's all, stop editing! Happy blogging. */`:

    define('CODE_SNIPPETS_SAFE_MODE', true);

 To turn safe mode off, either [comment out](http://php.net/manual/language.basic-syntax.comments.php) this line or delete it.

= Is there a way to add a snippet but not run it right away? =
Yes. Just add it but do not activate it yet.

= What do I use to write my snippets? =
The [CodeMirror](http://codemirror.net) source-code editor will add line numbers, syntax highlighting, bracket matching, search, tabulate and other cool features to the code editor.

= Can I preform search and replace commands in the code editor? =

* __Ctrl-F / Cmd-F__ : Start searching
* __Ctrl-G / Cmd-G__ : Find next
* __Shift-Ctrl-G / Shift-Cmd-G__ : Find previous
* __Shift-Ctrl-F / Cmd-Option-F__ : Replace
* __Shift-Ctrl-R / Shift-Cmd-Option-F__ : Replace all

= Will I lose my snippets if I change the theme or upgrade WordPress? =
No, the snippets are stored in the WordPress database and are independent of the theme and unaffected by WordPress upgrades.

= Can the plugin be completely uninstalled? =
Yes, when you delete Code Snippets using the 'Plugins' menu in WordPress it will clean up the database table and a few other bits of data. Be careful not to remove Code Snippets by deleting it from the Plugins menu unless you want this to happen.

= Can I copy any snippets I've created to another WordPress site? =
Yes! You can individually export a single snippet using the link below the snippet name on the 'Manage Snippets' page or bulk export multiple snippets using the 'Bulk Actions' feature. Snippets can later be imported using the 'Import Snippets' page by uploading the export file.

= Can I export my snippets to PHP for a site where I'm not using the Code Snippets plugin? =
Yes. Click the checkboxes next to the snippets you want to export, and then choose **Export to PHP** from the Bulk Actions menu and click Apply. The generated PHP file will contain the exported snippets' code, as well as their name and description in comments.

= Can I run network-wide snippets on a multisite installation? =
You can run snippets across an entire multisite network by **Network Activating** Code Snippets through the Network Dashboard. You can also activate Code Snippets just on the main site, and then individually on other sites of your choice.

= Where are the snippets stored in my WordPress database? =
Snippets are stored in the `wp_snippets` table in the WordPress database. The table name may differ depending on what your table prefix is set to.

= I need help with Code Snippets / I have an idea for a new feature for Code Snippets =
You can get help with Code Snippets, report bugs or errors, and suggest new features and improvements either on the [WordPress Support Forums](https://wordpress.org/support/plugin/code-snippets) or on [GitHub](https://github.com/sheabunge/code-snippets)

= I want to contribute to and help develop the Code Snippets plugin! =
That's fantastic! Fork the [repository on GitHub](http://github.com/sheabunge/code-snippets) and send me a pull request.

== Screenshots ==

1. Managing existing snippets
2. Adding a new snippet
3. Editing a snippet
4. Importing snippets from an XML file

== Changelog ==

= 2.3.0 =
* Removed nested functions
* Added icons for admin and front-end snippets to manage table
* Improved settings retrieval by caching settings
* Updated Russian translation by [Alexey Chumakov](http://chumakov.ru/)
* Added filter switch to prevent a snippet from executing ([#25](https://github.com/sheabunge/code-snippets/issues/25))
* Fixed errors in string translation
* Fixed bug in import process ([#32](https://github.com/sheabunge/code-snippets/issues/32))

= 2.2.3 =
* Fixed broken call to `export_snippet()` function
* Added support for importing and exporting snippet scope
* Fixed duplicate primary key database error
* Improved database table structure

= 2.2.2 =
* Polyfilled array_replace_recursive() function for PHP 5.2
* Updated references to old plugin site
* Resolved JavaScript error on edit snippet pages
* Made minor updates to French translation file
* Added statuses for snippet scopes on manage snippets table

= 2.2.1 =
* Fixed the default values of new setting not being applied
* Fixed missing background of tags input

= 2.2.0 =
* Introduced CodeSniffer testing on code
* Fixed description heading disappearing when media buttons enabled
* Added snippet scope selector
* Minified all CSS and JS in plugin
* Made CodeMirror theme names more readable
* Fixed bug causing translations to not be loaded

= 2.1.0 =
* Added additional setting descriptions
* Added settings for code and description editor height
* Updated CodeMirror to version 5.2
* Fixed not escaping the request URL when using query arg functions
* Improved efficiency of settings component

= 2.0.3 =
* Updated German translation by [Joerg Knoerchen](http://www.sensorgraphy.net/)

= 2.0.2 =
* Fix error in table creation code
* Remove settings database option when plugin is uninstalled

= 2.0.1 =

* Fix table creation code not running on upgrade
* Fix snippets per page option not saving

= 2.0 =

__Highlights__

* Better import/export functionality
* New settings page with code editor settings
* Code rewritten for cleaner and more efficient code
* Lots of new translations

__Added__

* Added link to Code Snippets importer under Snippets admin menu
* Added settings component and admin page
* Added support for different CodeMirror themes
* Integrated tags component into main plugin. Current users of the Code Snippets Tags plugin can safely uninstall it.
* Added Auto Close Brackets CodeMirror addon (props to TronicLabs)
* Added Serbo-Croatian translation by Borisa Djuraskovic from [Web Hosting Hub](http://www.webhostinghub.com)
* Added Highlight Selection Matches CodeMirror addon (props to TronicLabs)
* Added Chinese translation thanks to Jincheng Shan
* Added Russian translation by Alexander Samsonov
* Added Slovak translation by [Ján Fajčák] from [WordPress Slovakia](http://wp.sk)
* Added setting to always save and activate snippets by default

__Changed__

* Added braces to single-line conditionals in line with [new coding standards](https://make.wordpress.org/core/2013/11/13/proposed-coding-standards-change-always-require-braces/)
* Split up large classes into separate functions
* Improved plugin file structure
* Replaced uninstall hook with single file method
* Updated CodeMirror library to version 5.0
* Rewritten import/export functionality to use DOMDocument
* Merged Code_Snippets_Export_PHP class into Code_Snippets_Export class

__Deprecated__

* Removed old admin style support
* Removed backwards-compatible support

__Fixed__

* Fixed incompatibility errors with PHP 5.2
* Fixed empty MO translation files
* Removed duplicate MySQL primary key indexing

= 1.9 =
* Add and remove network capabilities as super admins are added and removed
* Updated MP6 icon implementation
* Replaced buggy trim `<?php` and `?>` functionality with a much more reliable regex method ([#](http://wordpress.org/support/topic/character-gets-cut))
* Added French translation thanks to translator [oWEB](http://office-web.net)
* Fixed snippet failing to save when code contains `%` character, props to [nikan06](http://wordpress.org/support/profile/nikan06) ([#](http://wordpress.org/support/topic/percent-sign-bug))
* Added 'Save & Deactivate' button to the edit snippet page ([#](http://wordpress.org/support/topic/deactivate-button-in-edit-snippet-page))
* Removed edit and install capabilities (now only uses the manage capability)
* Fixed HTML breaking in export files ([#](http://wordpress.org/support/topic/import-problem-7))
* Make the title of each snippet on the manage page a clickable link to edit the snippet ([#](http://wordpress.org/support/topic/deactivate-button-in-edit-snippet-page?replies=9#post-4682757))
* Added nonce to edit snippet page
* Hide row actions on manage snippet page by default
* Removed screenshots from plugin
* Improved CodeMirror implementation
* Added a fallback MP6 icon
* Use the proper WordPress database APIs all of the time
* Rewritten export functionality
* Fixed incorrect export filename
* Updated CodeMirror to version 3.19
* Removed CodeMirror bundled with plugin
* Updated WordPress.org plugin banner
* Fixed CodeMirror incompatibility with the WP Editor plugin
* Fixed CodeMirror incompatibility with the Debug Bar Console plugin

= 1.8.1 =
* Compiled all CodeMirror scripts into a single file
* Use Sass + Compass for CSS
* Use Grunt for build automation
* Minify CSS
* Fixed code typo that was breaking export files
* Updated CodeMirror to 3.15

= 1.8 =
* Allow no snippet name or code to be set
* Prevented an error on fresh multisite installations
* Refactored code to use best practices
* Improved database table creation method: on a single-site install, the snippets table will always be created. On a multisite install, the network snippets table will always be created; the site-specific table will always be created for the main site; for sub-sites the snippets table will only be created on a visit to a snippets admin page.
* Updated to CodeMirror 3.14
* Changes to action and filter hook API
* Added error message handling for import snippets page
* Don't encode HTML entities in database

= 1.7.1.2 =
* Correct path to admin menu icon ([#](http://wordpress.org/support/topic/icon-disappears-with-mp6?replies=6#post-4148319))

= 1.7.1.1 =
* Fix a minor bug with custom capabilities and admin menus

= 1.7.1 =
* Fix a bug with snippet being set as deactivated when saved
* Updated PHP Documentation completely. [View online](http://bungeshea.github.io/code-snippets/api)
* Only load admin functions when viewing dashboard
* Added German translation thanks to [David Decker](http://deckerweb.de)
* Allow or deny site administrators access to snippet admin menus. Set your preference in the **Enable Administration Menus** setting under the *Settings > Network Settings* network admin menu.
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

== Upgrade Notice ==

= 2.3.0 =
Numerious fixes and Russian translation update. Props to @signo and @`achumakov.

= 2.2.3 =
Fixed error when exporting; snippet scopes can now be imported

= 2.2.2 =
Fix error with PHP 5.2 added statuses for scopes to manage snippets table

= 2.2.1 =
New snippet scope feature; fixed bug with translations not loading; minified all css and js

= 2.1.0 =
Minor improvements to settings component; added description editor settings; minor security fixes

= 2.0.2 =
Fix snippets not saving

= 2.0 =
Improved import/export; new settings page; improved core code; more translations

= 1.9.1.1 =
Add capability check to snippets importer

= 1.9.1 =
UI improvements for WordPress 3.8

= 1.8.1 =
Minimize CSS and JS; updated CodeMirror; fixed export files

= 1.8 =
Setting a snippet name and code are now optional; better table creation method; changes to API; bug fixes

= 1.7.1.2 =
Fixes the admin menu icon not loading

= 1.7.1.1 =
Fixes a minor bug with custom capabilities and admin menus

= 1.7.1 =
Added German translation thanks to David Decker; bug fixes and improvements

= 1.7 =
Many improvements and optimization. Download "Code Snippets Tags" plugin to add tags to snippets

= 1.6 =
Improvements and optimization with WordPress 3.5

= 1.5 =
Improvements on the 'Manage Snippets' page and localization

= 1.4 =
Better code highlighting and improved multisite support

= 1.3 =
Added import/export feature

= 1.2 =
Minor improvements |
Added code highlighting |
Plugin data will now be cleaned up when you delete the plugin.

= 1.1 =
Minor bug fixes and improvements on the the 'Edit Snippet' page
