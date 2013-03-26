# Changelog

## 1.7
* Improved plugin API
* Fixed a bug with saving snippets per page option ([#](http://wordpress.org/support/topic/plugin-code-snippets-snippets-per-page-does-not-work#post-3710991))
* Updated CodeMirror to version 3.11
* Allow plugin to be activated on individual sites on multisite ([#](http://wordpress.org/support/topic/dont-work-at-multisite))
* Slimmed down the description visual editor
* Added icon for the new MP6 admin UI ([#](http://wordpress.org/support/topic/icon-disappears-with-mp6))
* Strip PHP tags from the beginning and end of a snippet on save ([#](http://wordpress.org/support/topic/php-tags))
* Changed to [MIT license](http://opensource.org/licenses/mit-license.php)
* Removed HTML, CSS and JavaScript CodeMirror modes that were messing things up
* Change label in admin menu when editing a snippet
* Improved admin styling
* Made everything leaner, faster, and better

## 1.6.1
* Fixed a bug with permissions not being applied on install ([#](http://wordpress.org/support/topic/permissions-problem-after-install))
* Fixed a bug in the uninstall method ([#](http://wordpress.org/support/topic/bug-in-delete-script))

## 1.6
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

## 1.5
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

## 1.4
* Added interface to Network Dashboard
* Updated uninstall to support multisite
* Replaced EditArea with [CodeMirror](http://codemirror.net)
* Small improvements

## 1.3.2
* Fixed a bug with version 1.3.1

## 1.3.1
* Changed plugin website URI
* Cleaned up some code

## 1.3
* Added export option to 'Manage Snippets' page
* Added 'Import Snippets' page

## 1.2
* Minor improvements
* Added code highlighting
* Removed 'Uninstall Plugin' page
* Data will now be cleaned up when plugin is deleted through WordPress admin

## 1.1
* Fixed a permissions bug with `DISALLOW_FILE_EDIT` being set to true ([#](http://wordpress.org/support/topic/plugin-code-snippets-cant-add-new))
* Fixed a bug with the page title reading 'Add New Snippet' on the 'Edit Snippets' page
* Fixed a bug not allowing the plugin to be Network Activated ([#](http://wordpress.org/support/topic/plugin-code-snippets-network-activate-does-not-create-snippets-tables))

## 1.0
* Stable version released.
