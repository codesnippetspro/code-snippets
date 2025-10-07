# Changelog

## [3.7.0] (2025-08-29)

### Added
* New 'conditions' feature: control where and when snippets execute with a powerful logic builder. (PRO)

### Changed
* Redesigned edit menu with refreshed look and functionality.
* Updated snippet type badges to be more visually distinct.
* Redesigned tooltips used throughout the plugin.
* Moved content snippet shortcode options into separate modal window.
* Updated snippet tag editor to use built-in WordPress tag editor.
* Created proper form for sharing beta feedback.
* Improved UX of snippet activation toggle.

### Fixed
* Fetching active snippets on a multisite network now respects the 'priority' field above all else when ordering snippets.
* Cloud search appears correctly and allows downloading snippets in the free version of Code Snippets.
* Improved performance of loading admin menu icon.

## [3.6.9] (2025-02-17)

### Changed
* Updated `Cloud_API::get_bundles()` to properly check bundle data and return an empty array if no valid bundles are present.
* Refactored `Cloud_List_Table::fetch_snippets()` to always return a valid `Cloud_Snippets` instance.
* Cleaned up bundle iteration code and improved translation handling in the bundles view.

### Fixed
* Fixed errors in bundle iteration by adding a check for the bundles array before iterating.

## [3.6.8] (2025-02-14)

### Added
* `code_snippets/hide_welcome_banner` filter hook for hiding welcome banner in dashboard.

### Changed
* Updated Freemius SDK to the latest version. (PRO)

### Removed
* Functionality allowing `[code_snippet]` shortcodes to be embedded recursively – it will be re-added in a future version.

### Fixed
* Shortcodes embedded within `[code_snippet]` shortcodes not evaluating correctly.
* Translation functions being called too early in some instances when loading plugin settings.
* 'Generate' button not appearing on some sites. (PRO)
* Incorrect arrow entity used in cloud list table (props to [brandonjp]).
* Removed reference to missing plugins.css file in core plugin version.

## [3.6.7] (2025-01-24)

### Added
* Generated snippet shortcode tags will include the snippet name, for easier identification.
* Admin notices will dismiss automatically after five seconds. ([#208](https://github.com/codesnippetspro/code-snippets/issues/208))

### Changed
* Updated CSS to use latest Sass features.
* Moved theme selector to just above editor preview on settings page (thanks to [brandonjp]). ([#206](https://github.com/codesnippetspro/code-snippets/issues/206)) 
* `[code_snippet]` shortcodes can now be nested within each other. ([#198](https://github.com/codesnippetspro/code-snippets/issues/198))

### Fixed 
* Save buttons above editor did not follow usual validation process in Pro. (PRO) ([#197](https://github.com/codesnippetspro/code-snippets/issues/197))
* Minor inconsistencies in consistent UI elements between Core and Pro.
* Tags input not allowing input. ([#211](https://github.com/codesnippetspro/code-snippets/issues/211))
* Issue with Elementor source code widget. (PRO) ([#205](https://github.com/codesnippetspro/code-snippets/issues/205))
* Snippet descriptions not visible when viewing cloud search results.
* Snippet import page not displaying number of successfully imported snippets.
* Use UTC time when deciding when to display campaign notices.

## [3.6.6.1] (2024-11-27)

### Fixed
* Redeployment of [v3.6.6](#366-2024-11-27) to overcome issue with initial build.
* Type issue when caching cloud links. (PRO)

## [3.6.6] (2024-11-27)

### Changed
* Improved compatability with modern versions of PHP.
* Extended welcome API to include admin notices.
* Action hook `code_snippets/update_snippet` now only includes the snippet ID instead of the full snippet object.
* Action hook `code_snippets/admin/manage` now includes the currently viewed type.

### Fixed
* Memory issue from checking aggregate posts while loading front-end syntax highlighter. 
* Translation functions being called too early on upgrade, resulting in localisation loading errors.
* Bug preventing the 'share on network' status of network snippets from correctly updating.
* Incorrect logic controlling when to display 'Save Changes' or 'Save Changes and Activate' buttons.
* Old notices persisting when switching between editing and creating snippets.

## 3.6.5.1 (2024-05-24)

* Redeployment of [v3.6.5](#365-2024-05-24) to overcome issue with initial build.

## [3.6.5] (2024-05-24)

### Added
* New admin menu providing useful resources and updates on the Code Snippets plugin and community.

## [3.6.4] (2024-03-15)

### Added
* AI generation for all snippet types: HTML, CSS, JS. (PRO)
* Button to create a cloud connection directly from the Snippets menu when disconnected. (PRO)

### Changed
* Increment the revision number of CSS and JS snippet when using the 'Reset Caches' debug action. (PRO)
* UX in generate dialog, such as allowing 'Enter' to submit the form. (PRO)

### Fixed
* Minor type compatability issue with newer versions of PHP.
* Undefined array key issue when initiating cloud sync. (PRO)
* Bug preventing downloading a single snippet from a bundle. (PRO)
* Translations not loading for strings in JavaScript files.

## [3.6.3] (2023-11-13)

### Added
* Added debug action for resetting snippets caches.

### Fixed
* Import error when initialising cloud sync configuration. (PRO)

## [3.6.2] (2023-11-11)

### Removed
* Removed automatic encoding of code content.

### Fixed
* Error when attempting to save shared network snippets marked as active.
* Type error when rendering checkbox fields without a stored or default value.
* Label for snippet sharing input incorrectly linked to input field.
* Error when attempting to download export files from Edit menu.
* Issue loading Freemius string overrides too early. (PRO)
* Fix redirect URL when connecting with OAuth on subdirectory or HTTPS sites. (PRO)
* Import error when attempting to completely uninstall the plugin.

## [3.6.1] (2023-11-07)

### Fixed
* Issue accessing fields on Snippets class.

## [3.6.0] (2023-11-07)

### Added
* Ability to authenticate with Code Snippets Cloud using OAuth. (PRO)
* Integration with GPT AI for generating snippets. (PRO)
* Ability to generate line-by-line descriptions of snippet code with GPT AI. (PRO)
* Ability to generate tags and description text from existing snippet code with GPT AI. (PRO)
* Added debug settings menu for manually performing problem-solving actions.
* Filter to disable scroll-into-view functionality for edit page notices.

### Changed
* Updated minimum PHP requirement to 7.4.
* Ensure that the URL of the edit snippet page changes when adding a new snippet.
* Snippet tags will automatically be added when focus is lost on the tags field.

### Fixed
* Moved active status border on edit name field to left-hand side.
* New notices will not scroll if already at top of page.
* Potential CSRF vulnerability allowing an authenticated user to reset settings.

## [3.5.1] (2023-09-15)

### Fixed
* Undefined array key error when accessing plugin settings page. (PRO)
* Issue registering API endpoints affecting edit post screen. (PRO)
* Snippet ID instead of snippet object being passed to `code_snippets/update_snippet` action hook.

## [3.5.0] (2023-09-13)

### Added
* Support for the Code Snippets Cloud API.
* Search and download public snippets.
* Codevault back-up and synchronisation. (PRO)
* Synchronised local snippets are automatically updated in Cloud. (PRO)
* Bulk actions - 'update' and 'download'.
* Download snippets from public and private codevaults. (PRO)
* Search and download any publicly viewable snippet in Code Snippet Cloud by keyword or name of codevault. (PRO)
* Deploy snippets to plugin from Code Snippets Cloud app. (PRO)
* Bundles of Joy! Search and download Snippet Bundles in one go direct from Code Snippets Cloud. (PRO)

### Changed
* Redirect to snippets table when deleting snippet from the edit menu.
* Scroll new notices into view on edit menu.

### Fixed
* Error when attempting to update network shared snippets after saving. [[#](https://wordpress.org/support/topic/activating-snippets-breaks-on-wordpress-6-3/)]

## [3.4.2] (2023-07-05)

### Fixed
* Issue causing export process to fail with fatal error. [[#](https://wordpress.org/support/topic/critical-error-on-exporting-snippets/)]
* Type issue on `the_posts` filter when no posts available. [[#](https://wordpress.org/support/topic/collision-with-plugin-xml-sitemap-google-news/)]

## [3.4.1] (2023-06-29)

### Added
* Added better debugging when calling REST API methods from the edit menu.

### Changed
* Escape special characters when sending snippet code through AJAX to avoid false-positives from security modules. [[#](https://wordpress.org/support/topic/latest-3-4-0-ajax-bug-cannot-save-snippets-403-error/)]
* Only display the latest update or error notice on the edit page, instead of allowing them to stack.

### Fixed
* Undefined array key error. [[#](https://wordpress.org/support/topic/after-updating-occasionally-getting-undefined-array-key-query/)]
* Potential type issue when loading Prism. [[#](https://wordpress.org/support/topic/code-snippets-fatal-error-breaking-xml-sitemaps/)]
* Potential type issue when sorting snippets. [[#](https://github.com/codesnippetspro/code-snippets/issues/166)]
* Issue preventing asset revision numbers from updating correctly. (PRO) [[#](https://github.com/codesnippetspro/code-snippets/issues/166)]

## [3.4.0] (2023-05-17)

### Added
* Proper WordPress REST API support for retrieving and modifying snippets.
* Added help links to content snippet options.

### Changed
* Better compatibility with modern versions of PHP (7.0+).
* Converted Edit/Add New Snippet page to use React.
  * Converted action buttons to asynchronously use REST API endpoints through AJAX.
  * Load page components dynamically through React.
  * Added action notice queue system.
  * Replaced native alert dialog with proper React modal.
* Catch snippet execution errors to prevent site from crashing.
* Display recent snippet errors in admin dashboard instead.
* Updated editor block to use new REST API endpoints. (PRO)
* Change colour of upgrade notice in Pro plugin. (PRO)
* All available snippet data is included in export files.
* Only import specific fields from export file, even if additional fields specified.
* Pass additional attributes specified in `[code_snippet]` content shortcode to shortcode content.
* Make shortcode attributes available as individual variables.
* Allow boolean attributes to be passed to code snippets shortcodes without specifying a value.
* Replace external links to Pro pricing page with an upgrade modal.

### Fixed
* Issue preventing editor colorpicker from loading correctly. (PRO)
* Issue preventing linting libraries from loading correctly in the code editor.

## [3.3.0] (2023-03-09)

### Added
* Added additional editor shortcuts to list in tooltip.
* Filter for changing Snippets admin menu position. [See this help article for more information.](https://help.codesnippets.pro/article/61-how-can-i-change-the-location-of-the-snippets-admin-menu)
* Ability to filter shortcode output. Thanks to contributions from [Jack Szwergold](https://github.com/JackSzwergold).

### Fixed
* Do not enqueue CSS or JS snippet file if no snippets exist. (PRO)
* Bug causing all snippets to show in site health information instead of those active.
* Unnecessary sanitization of file upload data causing import process to fail on Windows systems.

## [3.2.2] (2022-11-17)

### Fixed
* Plugin lacking a valid header error on activation.

## [3.2.1] (2022-10-05)

### Added
* `Ctrl`+`/` or `Cmd`+`/` as shortcut for commenting out code in the snippet editor.
* Additional hooks to various snippet actions, thanks to contributions made by [ancient-spirit](https://github.com/ancient-spirit).
* Fold markers, additional keyboard shortcuts and keymap options to snippet editor,
  thanks to contributions made by [Amaral Krichman](https://github.com/karmaral).
* WP-CLI commands for retrieving, activating, deactivating, deleting, creating, updating, exporting and importing snippets.

### Changed
* Removed duplicate tables exist query. ([#](https://wordpress.org/support/topic/duplicate-queries-21)).
* Enabled 'add paragraphs and formatting' option by default for newly inserted content snippets.

### Fixed
* Issue making survey reminder notice not dismissible.
* Path to iron visible when updating the pro plugin.

## [3.2.0] (2022-07-22)

### Added
* Option to show and hide line numbers in Gutenberg source code editor block. (PRO)
* Support for highlighting HTML, CSS, JS and embedded code in the front-end PrismJS code highlighter.
* Additional features to front-end PrismJS code highlighter, including automatic links and a copy button.
* Support for multiple code styles in the source code Gutenberg editor block. (PRO)
* Admin notice announcing release of Code Snippets Pro.
* Button for copying shortcode text to clipboard.
* Option to choose from 44 different themes for the Prism code highlighter in the source editor block and Elementor widget. (PRO)

### Changed
* Include Code Snippets CSS and JS source code in distributed package.
* Don't delete data from plugin if deleting Code Snippets Free while Code Snippets Pro is active.
* Streamlined user interface and experience in Gutenberg editor blocks. (PRO)
* Compatibility of Elementor widgets with the latest version of Elementor. (PRO)
* Replace icon font menu icon with embedded SVG icon.

### Removed
* Remove default value from SQL columns to improve compatibility with certain versions of MySQL.

### Fixed
* Delay loading snippets in Gutenberg editor blocks. (PRO)
* Inconsistencies with translations between different plugin versions.
* Issue with Content Snippet shortcode information not displaying.

## [3.1.2] (2022-07-03)

### Changed
* Updated external links and branding for Code Snippets Pro.
* Add link URLs to settings pages, as an alternative to in-page navigation.
* Improved visual style of Gutenberg editor blocks. (PRO)

### Fixed
* Various fixes to block editor scripts. (PRO)

## [3.1.1] (2022-06-13)

### Added
* Added additional parameters to `code_snippets/export/filename` filter.

### Fixed
* Download snippets feature not including snippet content.
* Alignment of 'opens externally' dashicon.

## [3.1.0] (2022-05-17)

### Added
* More comprehensive cache coverage, including for active snippets.
* Icon to 'Go Pro' button indicating it opens an external tab.

### Changed
* Simplified database queries.
* Allow display styles in snippet descriptions.

### Fixed
* Caching inconsistencies preventing snippets and settings from refreshing on sites with persistent object caching.

## [3.0.1] (2022-05-14)

### Fixed
* Incompatibility issue with earlier versions of PHP.

## [3.0.0] (2022-05-14)

### Added
* HTML content snippets for displaying as shortcodes or including in the page head or footer area.
* Notice reminding users to upgrade unsupported PHP versions.
* Visual settings to add attributes to shortcodes.
* Shortcode buttons to the post and page content editors.
* Basic REST API endpoints.
* Snippet type column to the snippets table.
* Snippet type badges to Edit and Add New Snippet pages.
* Setting to control whether the current line of the code editor is highlighted.
* Display a warning when saving a snippet with missing title or code.
* Add suffix to title of cloned snippets.
* Added key for the 'active' and 'scope' database table columns to speed up queries.
* Added snippet type labels to the tabs on the Snippets page.
* Added hover effect to priority settings in the snippets table to show that they are editable.
* CSS style snippets for the site front-end and admin area. (PRO)
* JavaScript snippets for the site head and body area on the front-end. (PRO)
* Browser cache versioning for CSS and JavaScript snippets. (PRO)
* Support for exporting and downloading CSS and JavaScript snippets. (PRO)
* Support for highlighting code on the front-end. (PRO)
* Editor syntax highlighting for CSS, JavaScript and HTML snippets. (PRO)
* Button to preview full file when editing CSS or JavaScript snippets. (PRO)
* Option to minify CSS and JavaScript snippets. (PRO)
* Gutenberg editor block for displaying content snippets. (PRO)
* Gutenberg editor block for displaying snippet source code. (PRO)
* Elementor widget for displaying content snippets. (PRO)
* Elementor widget for displaying snippet source code. (PRO)

### Changed
* Updated plugin code to use namespaces, preventing name collisions with other plugins.
* Redirect from edit menu if not editing a valid snippet.
* Moved activation switch into its own table column.
* Updated code documentation according to WordPress standards.
* Split settings page into tabs.
* Use the version of CodeMirror included with WordPress where possible to inherit the additional built-in features.

### Deprecated
* Deprecated functions and compatibility code for unsupported PHP versions.

### Removed
* Option to disable snippet scopes.

### Fixed
* Snippets table layout on smaller screens.

## [2.14.6] (2022-05-13)

### Fixed
* Issue with processing uploaded import files.
* Issue with processing tag filters.

## [2.14.5] (2022-05-10)

### Fixed
* Incompatibility issue with older versions of PHP.

## [2.14.4] (2022-05-05)

### Fixed
* Prevent array key errors when loading the snippet table with unknown order values.

## [2.14.3] (2021-12-10)

### Fixed
* Potential security issue outputting snippets-safe-mode query variable value as-is. Thanks to Krzysztof Zając for reporting.

## [2.14.2] (2021-09-09)

### Added
* Added translations:
  * Spanish by [Ibidem Group](https://www.ibidemgroup.com)
  * Urdu by [Samuel Badree](https://mobilemall.pk/)
  * Greek by [Toni Bishop from Jrop](https://www.jrop.com/)
* Support for `:class` syntax to the code validator.
* PHP8 support to the code linter.
* Color picker feature to the code editor.
* Failsafe to prevent multiple versions of Code Snippets from running simultaneously.

### Fixed
* Prevent network snippets table from being created on single-site installs.

## [2.14.1] (2021-03-10)

### Added
* Czech translation by [Lukáš Tesař](https://github.com/atomicf4ll).
* Code direction setting for RTL users.
* Additional action hooks and search API thanks to [@Spreeuw](https://github.com/Spreeuw).

### Changed
* Updated CodeMirror to version 5.59.4.

### Fixed
* Code validator now supports `function_exists` and `class_exists` checks.
* Code validator now supports anonymous functions.
* Issue with saving the hidden columns setting.
* Replaced the outdated tag-it library with [tagger](https://github.com/jcubic/tagger) for powering the snippet tags editor.

## [2.14.0] (2020-01-26)

### Added
* Basic error checking for duplicate functions and classes.
* Additional API options for retrieving snippets.
* Store the time and date when each snippet was last modified.
* Basic error checking when activating snippets.

### Changed
* Updated CodeMirror to version 5.50.2.
* Updated Italian translations to fix display issues – thanks to [Francesco Marino](https://360fun.net).
* Changed the indicator color for inactive snippets from red to grey.

### Fixed
* Ordering snippets in the table by name will now be case-insensitive.
* Code editor will now properly highlight embedded HTML, CSS and JavaScript code.
* Fixed a bug preventing the editor theme from being set to default.
* Ensure that imported snippets are always inactive.
* Check the referer on the import menu to prevent CSRF attacks.
  Thanks to [Chloe with the Wordfence Threat Intelligence team](https://www.wordfence.com/blog/author/wfchloe/) for reporting.
* Ensure that individual snippet action links use proper verification.

## [2.13.3] (2019-03-13)

### Added
* Hover effect to activation switches.
* Additional save buttons above snippet editor.
* List save keyboard shortcuts to the help tooltip.
* Change "no items found" message when search filters match nothing.

### Changed
* Updated CodeMirror to version 5.44.0.

### Fixed
* Calling deprecated code in database upgrade process.
* Include snippet priority in export files.
* Use Unix newlines in code export file.
* Correctly register snippet tables with WordPress to prevent database repair errors.
  [[#](https://wordpress.org/support/topic/database-corrupted-4/)]
* CodeMirror indentation settings being applied incorrectly.

## [2.13.2] (2019-01-25)

### Removed
* Removed potentially problematic cursor position saving feature.

## [2.13.1] (2019-01-22)

### Added
* Add menu buttons to settings page for compact menu.
* Help tooltop to snippet editor for keyboard shortcuts, thanks to Michael DeWitt.
* Added button for executing single-use snippets to snippets table.
* Sample snippet for ordering snippets table by name by default.

### Changed
* French translation updated thanks to momo-fr.
* Updated CodeMirror to version 5.43.0.

### Fixed
* Split code editor and tag editor scripts into their own files to prevent dependency errors.
* Handling of single-use shared network snippets.
* Minor translation template issues.

## [2.13.0] (2018-12-17)

### Added
* Search/replace functionality to the snippet editor. [See here for a list of keyboard shortcuts.](https://codemirror.net/demo/search.html) [[#](https://wordpress.org/support/topic/feature-request-codemirror-search-and-replace/)]
* Option to make admin menu more compact.
* Added additional styles to editor settings preview.
* PHP linter to code editor.

### Changed
* Updated CodeMirror to version 5.42.0.
* Integration between plugin and the CodeMirror library, to prevent collisions.
* Use external scripts instead of inline scripts.

### Fixed
* Problem clearing recently active snippet list.
* Missing functionality for 'Auto Close Brackets' and 'Highlight Selection Matches' settings.

## [2.12.1] (2018-11-15)

### Changed
* CodeMirror updated to version 5.41.0.
* Attempt to create database columns that might be missing after a table upgrade.
* Streamlined upgrade process.
* Made search box appear at top of page on mobile. [[#](https://wordpress.org/support/topic/small-modification-for-mobile-ux/)]
* Updated screenshots.

### Fixed
* Interface layout on sites using right-to-left languages.

## [2.12.0] (2018-09-23)

### Added
* Added Import button next to page title on manage page.
* Added coloured banner indicating whether a snippet is active when editing.

### Changed
* Updated import page to improve usability.
* Updated CodeMirror to 5.40.0.

### Removed
* Removed option for including network-wide snippets in subsite lists on multisite.

### Fixed
* Prevented hidden columns setting from reverting to default.

## [2.11.0] (2018-07-24)

### Added
* Ability to assign a priority to snippets, to determine the order in which they are executed.
* Pressing Ctrl/Cmd + S while writing a snippet will save it.
* Shadow opening PHP tag above the code editor.
* Install sample snippets when the plugin is installed.
* Filter hook for controlling the default list table view.
* Action for cloning snippets.

### Changed
* The editor cursor position will be preserved when saving a snippet.
* Updated the message shown when there are no snippets.
* Show all available tags when selecting the tag field.

## [2.10.2] (2018-07-21)

### Added
* Button to reset settings to their default values.
* Belarusian translation by Hrank.com.

### Changed
* Made uninstall cleanup optional through a plugin setting.
* Ordered tags by name in the filter dropdown menu.
* Enabled sorting snippets table by tags.
* Updated CodeMirror to version 5.39.0.

### Fixed
* Applied formatting filters to snippet descriptions in the table.
* Incorrectly translated strings.

## [2.10.1] (2018-02-10)

### Fixed
* Prevent errors when trying to export no snippets.
* Use wp_json_encode() to encode export data.
* Check both the file extension and MIME type of uploaded import files.

## [2.10.0] (2018-01-18)

### Added
* Added support for importing from multiple export files at once.
* New scope for single-use snippets.
* Allowed plugin settings to be unified on multisite through Network Settings option.

### Changed
* Unbold the titles of inactive snippets for greater visual distinction.
* Don't show network snippets on subsites by default, and only to super admins.
* Export snippets to JSON instead of XML.
* More options for importing duplicate snippets.
* Use strings for representing scopes internally instead of numbers.
* Rename 'Export to PHP' to 'Download', and add button to edit snippet page.

### Fixed
* Issue with incorrectly treating network snippets as site-wide for code validation.

## [2.9.6] (2018-01-14)

### Added
* Added Brazilian Portuguese translation by [Bruno Borges](http://brunoborges.info)

### Changed
* Moved scope and other settings on single snippet page to below code area.

### Fixed
* Use standard WordPress capabilities instead of custom capabilities to prevent lockouts.
* Multisite issue with retrieving active shared snippets from the wrong table causing duplicate snippet execution.

## [2.9.5] (2018-01-13)

### Changed
* Updated CodeMirror to version 5.33.
* Made the "Activate By Default" setting enabled by default for new installations.

### Fixed
* Undefined function error when accessing the database on multisite.
* Ensured all admin headings are hierarchical for accessibility.

## [2.9.4] (2017-09-19)

### Changed
* Minor improvements to database creation function.

### Fixed
* Prevented PHP error from occurring when saving a snippet.

## [2.9.3] (2017-09-11)

### Fixed
* Prevent snippets from being executed twice when saving due to invalid ID being passed to allow_execute_snippet filter.
* Re-enabled output suppression when executing snippets.

## [2.9.2] (2017-09-08)

### Fixed
* Do not attempt to combine queries for fetching local and multisite snippets.

## [2.9.1] (2017-09-07)

### Fixed
* Prevent illegal mix of collations errors when fetching snippets from database tables with different collations.
  [[#](https://wordpress.org/support/topic/issue-on-multisite-with-wpml/)]

## [2.9.0] (2017-09-06)

### Changed
* Moved code to disable snippet execution into a filter hook.
* execute_active_snippets() function updated with improved efficiency.
* Renamed Snippet class to avoid name collisions with other plugins.
* Don't hide output when executing a snippet.

### Fixed
* Prevented invalid properties from being set when saving a snippet.
* Use the correct protocol when saving a snippet.
* Active shared snippets not being updated correctly.

## [2.8.7] (2017-05-18)

### Added
* Added French (Canada) translation by Domonic Desbiens.
* Added fixes for Indonesian translation by @zmni.

## [2.8.6] (2017-05-14)

### Fixed
* Fixed snippet description field alias not mapping correctly, causing snippet descriptions to not be displayed in the table or when editing a snippet.
* Ensured that get_snippets() function retrieves snippets with the correct 'network' setting. Fixes snippet edit links in network admin.

## [2.8.5] (2017-05-13)

### Added
* Added Indonesian translation by Jordan Silaen from ChameleonJohn.com .
* Added setting to hide network snippets on subsites.

### Security
* Ensured HTML in snippet titles is escaped in snippets table.
* Disallowed undefined fields to be set on the Snippets class.
* Prevented shared network snippets from being included twice in snippets table on multisite.

## [2.8.4] (2017-04-29)

### Fixed
* Fixed all snippets being treated as network snippets on non-multisite sites.

## [2.8.3] (2017-04-29)

### Added
* Added more compete output escaping to prevent XSS errors.

### Changed
* Updated CodeMirror to version 5.25.0.
* Show network active snippets as read-only on multisite subsites.

## [2.8.2] (2017-02-27)

### Fixed
* Fix bug introduced in 2.8.1 that broke code verification functionality by executing code twice.

## [2.8.1] (2017-02-25)

### Changed
* Updated German translation.
* Updated CodeMirror to version 5.24.0.

### Removed
* Removed possible conflict between Debug Bar Console plugin. (#52)

### Fixed
* Fixed admin menu items not translating.
* Corrected editor alignment on RTL sites. ([#](https://wordpress.org/support/topic/suggestion-css-fix-for-rtl-sites/))
* Fixed bulk actions running when Filter button is clicked. ([#](https://wordpress.org/support/topic/bug-with-filtering-action-buttons/))

## [2.8.0] (2016-12-14)

### Changed
* Renamed 'Manage' admin menu label to 'All Snippets' to keep in line with other admin menu labels.
* Renamed placeholder on snippet name field to 'Enter title here'.
* Updated CodeMirror to version 5.21.0.
* Moved 'Edit Snippet' admin menu above 'Add New' menu.
* Made pressing Ctrl-Enter in the code editor save the snippet.

### Removed
* Removed CodeMirror search functionality.

### Fixed
* Fixed Italian translation errors. Props to @arsenalemusica.

## [2.7.3] (2016-10-24)

### Changed
* Updated CodeMirror to version 5.10.0.

### Fixed
* Fixed a few strings not being translated.

## [2.7.2] (2016-10-01)

### Changed
* Updated German translation by [Mario Siegmann](https://web-alltag.de).

## [2.7.1] (2016-09-30)

### Added
* Added Dutch translation by Sander Spies.

### Changed
* Updated CodeMirror to version 5.19.0.

### Security
* Ensured that the editor theme setting is properly validated. Thanks to [Netsparker](https://www.netsparker.com) for reporting.
* Ensured that snippet tags are properly escaped. Thanks to [Netsparker](https://www.netsparker.com) for reporting.

## [2.7.0] (2016-07-23)

### Added
* Added query var to disable snippet execution. To use, add `?snippets-safe-mode=true` to the URL.

### Changed
* Updated German translation by [Mario Siegmann](https://web-alltag.de).
* Updated CodeMirror to version 5.17.0.
* Increased default snippets per page so that all are usually shown.

### Fixed
* Fixed plugin translations being loaded.
* Fixed description field not being imported.
* Fixed issue with CodeMirror rubyblue theme. [[#](https://wordpress.org/support/topic/a-problem-with-the-cursor-color-and-the-fix-that-worked-for-me)]
* Fixed snippet fields not importing.
* Fixed a minor XSS vulnerability discovered by Burak Kelebek. [[#](https://wordpress.org/support/topic/security-vulnerability-20)]

## [2.6.1] (2016-02-10)

### Changed
* Updated German translation by [Mario Siegmann](https://web-alltag.de).
* Updated error catching to work with snippets including functions and classes.

### Fixed
* Fixed error catching not working correctly.
* Fixed editor autoresizing.

## [2.6.0] (2015-12-31)

### Added
* Added `[code_snippets]` shortcode for embedding snippet code in a post.
* Added front-end syntax highlighting for shortcode using [PrismJS](https://prismjs.com).

### Changed
* Updated CodeMirror to version 5.10.0.

### Removed
* Reconfigured plugin to use classloader and converted a lot of functional code into OOP code.

### Fixed
* Fixed broken snippet search feature. [[#](https://wordpress.org/support/topic/search-is-not-working-6)]

## [2.5.1] (2016-10-11)

### Fixed
* Ensure errors are fatal before catching them during error checking.
* Escape the snippet name on the edit page to ensure it displays correctly.
* Exclude snippets with named functions from error checking so they do not run twice.

## [2.5.0] (2015-10-08)

### Added
* Detect parse and fatal errors in code when saving a snippet, and display a user-friendly message.

### Changed
* Updated access of some methods in Code_Snippets_List_Table class to match updated WP_List_Table class.

## [2.4.2] (2015-09-27)

### Added
* Added query variable to activate safe mode.
* Added settings to disable description and tag editors.

### Changed
* Updated editor preview updating code to use vanilla JavaScript instead of jQuery.

### Fixed
* Fixed settings not saving.
* Fixed snippet descriptions not displaying on manage menu.
* Load CodeMirror after plugin styles to fix error with Zenburn theme.
* Hide snippet scope icons when the scope selector is disabled.
* Fixed description heading on edt snippet menu being hidden when visual editor disabled.
* Deactivate a shared network snippet on all subsites when it looses its sharing status.

## [2.4.1] (2015-09-17)

### Fixed
* Fixed CodeMirror themes not being detected on settings page [[#](https://wordpress.org/support/topic/updated-to-240-now-i-cant-switch-theme)]

## [2.4.0] (2015-09-17)

### Added
* Added ability to share network snippets to individual sites on WordPress multisite.
* Added `code_snippets/after_execute_snippet` filter.
* Added class for individual snippets.

### Changed
* Improved code directory and class structure.
* Improved code for printing admin messages.
* Updated German translation (Joerg Knoerchen)
* Updated `get_snippets()` function to retrieve individual snippets.
* Changed admin page headers to use `<h1>` tags instead of `<h2>` tags.
* Updated CodeMirror to version 5.6.

### Removed
* Remove legacy code for pre-3.6 compatibility.
* Removed scope statuses and added fixed tags to indicate scope.
* Removed snippet settings page from network admin.

## [2.3.0] (2015-05-20)

### Added
* Added icons for admin and front-end snippets to manage table.
* Added filter switch to prevent a snippet from executing. ([#25](https://github.com/codesnippetspro/code-snippets/issues/25))

### Changed
* Improved settings retrieval by caching settings.
* Updated Russian translation by [Alexey Chumakov](http://chumakov.ru/).

### Removed
* Removed nested functions.

### Fixed
* Fixed errors in string translation.
* Fixed bug in import process. ([#32](https://github.com/codesnippetspro/code-snippets/issues/32))

## [2.2.3] (2015-05-13)

### Added
* Added support for importing and exporting snippet scope.

### Changed
* Improved database table structure.

### Fixed
* Fixed broken call to `export_snippet()` function.
* Fixed duplicate primary key database error.

## [2.2.2] (2015-05-11)

### Added
* Added statuses for snippet scopes on manage snippets table.

### Changed
* Updated references to old plugin site.
* Made minor updates to French translation file.

### Fixed
* Resolved JavaScript error on edit snippet pages.
* Added polyfill for array_replace_recursive() function for PHP 5.2.

## [2.2.1] (2015-05-10)

### Fixed
* Fixed the default values of new setting not being applied.
* Fixed missing background of tags input.

## [2.2.0] (2015-05-10)

### Added
* Introduced CodeSniffer testing on code.
* Added snippet scope selector.

### Changed
* Minified all CSS and JS in plugin.
* Made CodeMirror theme names more readable.

### Fixed
* Fixed description heading disappearing when media buttons enabled.
* Fixed bug causing translations to not be loaded.

## [2.1.0] (2015-05-09)

### Added
* Added additional setting descriptions.
* Added settings for code and description editor height.

### Changed
* Updated CodeMirror to version 5.2.
* Improved efficiency of settings component.

### Fixed
* Fixed not escaping the request URL when using query arg functions.

## [2.0.3] (2015-03-17)

### Changed
* Updated German translation by [Joerg Knoerchen](https://www.sensorgrafie.de/).

## [2.0.2] (2015-03-05)

### Removed
* Remove settings database option when plugin is uninstalled.

### Fixed
* Fix error in table creation code.

## [2.0.1] (2015-02-25)

### Fixed
* Fixed table creation code not running on upgrade.
* Fixed snippets per page option not saving.

## [2.0.0] (2015-02-24)

### Highlights
* Better import/export functionality.
* New settings page with code editor settings.
* Code rewritten for cleaner and more efficient code.
* Lots of new translations.

### Added
* Added link to Code Snippets importer under Snippets admin menu.
* Added settings component and admin page.
* Added support for different CodeMirror themes.
* Added Auto Close Brackets CodeMirror addon (props to TronicLabs).
* Added Croatian translation by Borisa Djuraskovic from [Web Hosting Hub](https://www.webhostinghub.com).
* Added Highlight Selection Matches CodeMirror addon (props to TronicLabs).
* Added Chinese translation thanks to Jincheng Shan.
* Added Russian translation by Alexander Samsonov.
* Added Slovak translation by [Ján Fajčák] from [WordPress Slovakia](https://wp.sk).
* Added setting to always save and activate snippets by default.
* Added braces to single-line conditionals in line with [new coding standards](https://make.wordpress.org/core/2013/11/13/proposed-coding-standards-change-always-require-braces/).

### Changed
* Improved plugin file structure.
* Updated CodeMirror library to version 5.0.
* Split up large classes into separate functions.
* Replaced uninstallation hook with single file method.
* Rewritten import/export functionality to use DOMDocument.

### Removed
* Removed old admin style support.
* Removed backwards-compatible support.
* Removed duplicate MySQL primary key indexing.

### Deprecated
* Integrated tags component into main plugin. Current users of the Code Snippets Tags plugin can safely uninstall it.
* Merged Code_Snippets_Export_PHP class into Code_Snippets_Export class.

### Fixed
* Fixed incompatibility errors with PHP 5.2.
* Fixed empty MO translation files.

## [1.9.1.1] (2014-01-03)

### Fixed
* Added capability check to site snippets importer.

## [1.9.1] (2014-01-02)

### Changed
* Use an icon font for menu icon instead of embedded SVG.
* Use Sass (libsass) instead of Compass.
* Unminified CodeMirror scripts.

### Fixed
* Fixes for the WP 3.8 interface.
* Fix 'enable snippets menu for site admins' multisite setting.

## [1.9.0] (2013-11-11)

### Added
* Added French translation thanks to translator [oWEB](http://office-web.net).
* Added 'Save & Deactivate' button to the edit snippet page. ([#](https://wordpress.org/support/topic/deactivate-button-in-edit-snippet-page))
* Added nonce to edit snippet page.
* Added a fallback MP6 icon.

### Changed
* Updated MP6 icon implementation.
* Improved CodeMirror implementation.
* Updated CodeMirror to version 3.19.
* Updated WordPress.org plugin banner.
* Add and remove network capabilities as super admins are added and removed.
* Replaced buggy trim `<?php` and `?>` functionality with a much more reliable regex method. ([#](https://wordpress.org/support/topic/character-gets-cut))
* Make the title of each snippet on the manage page a clickable link to edit the snippet ([#](https://wordpress.org/support/topic/deactivate-button-in-edit-snippet-page?replies=9#post-4682757))
* Hide row actions on manage snippet page by default.
* Use the proper WordPress database APIs consistently.
* Rewritten export functionality.

### Removed
* Removed edit and install capabilities (now only uses the manage capability).
* Removed screenshots from plugin.
* Removed CodeMirror bundled with plugin.

### Fixed
* Fixed snippet failing to save when code contains `%` character, props to [nikan06](https://wordpress.org/support/profile/nikan06). ([#](https://wordpress.org/support/topic/percent-sign-bug))
* Fixed HTML breaking in export files. ([#](https://wordpress.org/support/topic/import-problem-7))
* Fixed incorrect export filename.
* Fixed CodeMirror incompatibility with the WP Editor plugin.
* Fixed CodeMirror incompatibility with the Debug Bar Console plugin.

## [1.8.1.1] (2013-08-18)

## [1.8.1] (2013-07-29)

### Changed
* Updated CodeMirror to 3.15.
* Compiled all CodeMirror scripts into a single file.
* Use Sass + Compass for CSS.
* Use Grunt for build automation.
* Minify CSS.

### Fixed
* Fixed code typo that was breaking export files.

## [1.8.0] (2013-07-09)

### Added
* Added error message handling for import snippets page.

### Changed
* Improved database table creation method: on a single-site install, the snippets table will always be created. On a multisite install, the network snippets table will always be created; the site-specific table will always be created for the main site; for sub-sites the snippets table will only be created on a visit to a snippets admin page.
* Updated to CodeMirror 3.14.
* Allow no snippet name or code to be set.
* Prevented an error on fresh multisite installations.
* Refactored code to use best practices.

### Deprecated
* Changes to filter and action hook API.

### Fixed
* Removed encoding of HTML entities in database.

## [1.7.1.2] (2013-05-03)

### Fixed
* Correct path to admin menu icon. Fixes [#8](https://github.com/codesnippetspro/code-snippets/issues/8)

## [1.7.1.1] (2013-04-29)

### Fixed
* Fixed a bug with custom capabilities and admin menus.

## [1.7.1] (2013-04-22)

### Added
* Added German translation thanks to [David Decker](https://deckerweb.de)
* Allow or deny site administrators access to snippet admin menus. Set your preference in the **Enable Administration Menus** setting under the *Settings > Network Settings* network admin menu.

### Changed
* Updated PHP Documentation completely. [[View online](https://bungeshea.github.io/code-snippets/api)]
* Only load admin functions when viewing dashboard.
* Improve database table creation and upgrade process.
* Optimized to use less database queries.

### Fixed
* Fix a bug with snippet being set as deactivated when saved.

## [1.7.0] (2013-03-26)

### Added
* Added icon for the new MP6 admin UI ([#](https://wordpress.org/support/topic/icon-disappears-with-mp6))
* Allow plugin to be activated on individual sites on multisite ([#](https://wordpress.org/support/topic/dont-work-at-multisite))
* Strip PHP tags from the beginning and end of a snippet on save ([#](https://wordpress.org/support/topic/php-tags))
* Change label in admin menu when editing a snippet.

### Changed
* Improved plugin API.
* Updated CodeMirror to version 3.11.
* Changed to [MIT license](https://opensource.org/licenses/mit-license.php)
* Improved admin styling.
* Slimmed down the description visual editor.
* Made everything leaner, faster, and better.

### Removed
* Removed HTML, CSS and JavaScript CodeMirror modes that were messing things up.

### Fixed
* Fixed a bug with saving snippets per page option ([#](https://wordpress.org/support/topic/plugin-code-snippets-snippets-per-page-does-not-work#post-3710991))

## [1.6.1] (2012-12-29)

### Fixed
* Fixed a bug with permissions not being applied on install ([#](https://wordpress.org/support/topic/permissions-problem-after-install))
* Fixed a bug in the uninstall method ([#](https://wordpress.org/support/topic/bug-in-delete-script))

## [1.6.0] (2012-12-22)

### Added
* Current line of code editor is now highlighted.
* Highlight matches of selected text in code editor.

### Changed
* Code improvements and optimization.
* Updated code editor to use CodeMirror 3.
* Improved compatibility with Clean Options plugin.
* Changed namespace from `cs` to `code_snippets`.
* Improved updating process.
* Move css and js under assets.
* Organized CodeMirror scripts.
* Store multisite only options in site options table.
* Only create snippet tables when needed.

### Fixed
* Fixed compatibility bugs with WordPress 3.5.

## [1.5.0] (2012-09-18)

### Added
* Added custom capabilities.
* Added 'Export to PHP' feature. ([#](https://wordpress.org/support/topic/plugin-code-snippets-suggestion-bulk-export-to-php))
* Added i18n.

### Changed
* Updated CodeMirror to version 2.33.
* Updated the 'Manage Snippets' page to use the WP_List_Table class.
  * Added 'Screen Options' tab to 'Manage Snippets' page.
  * Added search capability to 'Manage Snippets' page.
  * Added views to easily filter activated, deactivated and recently activated snippets.
  * Added ID column to 'Manage Snippets' page.
  * Added sortable name and ID column on 'Manage Snippets' page ([#](https://wordpress.org/support/topic/plugin-code-snippets-suggestion-sort-by-snippet-name))
* Improved API.
* Lengthened snippet name field to 64 characters. ([#](https://wordpress.org/support/topic/plugin-code-snippets-snippet-title-limited-to-36-characters))

## [1.4.0] (2012-08-20)

### Added
* Added interface to Network Dashboard.

### Changed
* Updated uninstall to support multisite.
* Replaced EditArea with [CodeMirror](https://codemirror.net).
* Small improvements.

## [1.3.2] (2012-08-17)

### Fixed
* Fixed a bug with version 1.3.1.

## [1.3.1] (2012-08-17)

### Changed
* Changed plugin website URI.
* Cleaned up some code.

## [1.3.0] (2012-08-01)

### Added
* Added export option to 'Manage Snippets' page.
* Added 'Import Snippets' page.

## [1.2.0] (2012-07-29)

### Added
* Added code highlighting.

### Changed
* Minor improvements.
* Data will now be cleaned up when plugin is deleted through WordPress admin.

### Removed
* Removed 'Uninstall Plugin' page.

## [1.1.0] (2012-06-24)

### Fixed
* Fixed a permissions bug with `DISALLOW_FILE_EDIT` being set to true. ([#](https://wordpress.org/support/topic/plugin-code-snippets-cant-add-new))
* Fixed a bug with the page title reading 'Add New Snippet' on the 'Edit Snippets' page.
* Fixed a bug not allowing the plugin to be Network Activated. ([#](https://wordpress.org/support/topic/plugin-code-snippets-network-activate-does-not-create-snippets-tables))

## [1.0.0] (2012-06-13)

### Added
* Stable version released.

[brandonjp]: https://github.com/brandonjp

[unreleased]: https://github.com/codesnippetspro/code-snippets/tree/core
[3.7.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.7.0
[3.6.7]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.6.7
[3.6.6.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.6.6.1
[3.6.6]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.6.6
[3.6.5]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.6.5
[3.6.4]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.6.4
[3.6.3]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.6.3
[3.6.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.6.2
[3.6.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.6.1
[3.6.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.6.0
[3.5.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.5.1
[3.5.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.5.0
[3.4.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.4.2
[3.4.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.4.1
[3.4.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.4.0
[3.3.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.3.0
[3.2.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.2.2
[3.2.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.2.1
[3.2.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.2.0
[3.1.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.1.2
[3.1.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.1.1
[3.1.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.1.0
[3.0.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.0.1
[3.0.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v3.0.0
[2.14.6]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.14.6
[2.14.5]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.14.5
[2.14.4]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.14.4
[2.14.3]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.14.3
[2.14.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.14.2
[2.14.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.14.1
[2.14.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.14.0
[2.13.3]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.13.3
[2.13.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.13.2
[2.13.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.13.1
[2.13.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.13.0
[2.12.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.12.1
[2.12.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.12.0
[2.11.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.11.0
[2.10.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.10.2
[2.10.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.10.1
[2.10.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.10.0
[2.9.6]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.9.6
[2.9.5]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.9.5
[2.9.4]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.9.4
[2.9.3]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.9.3
[2.9.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.9.2
[2.9.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.9.1
[2.9.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.9.0
[2.8.7]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.8.7
[2.8.6]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.8.6
[2.8.5]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.8.5
[2.8.4]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.8.4
[2.8.3]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.8.3
[2.8.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.8.2
[2.8.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.8.1
[2.8.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.8.0
[2.7.3]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.7.3
[2.7.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.7.2
[2.7.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.7.1
[2.7.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.7.0
[2.6.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.6.1
[2.6.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.6.0
[2.5.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.5.1
[2.5.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.5.0
[2.4.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.4.2
[2.4.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.4.1
[2.4.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.4.0
[2.3.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.3.0
[2.2.3]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.2.3
[2.2.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.2.2
[2.2.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.2.1
[2.2.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.2.0
[2.1.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.1.0
[2.0.3]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.0.3
[2.0.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.0.2
[2.0.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.0.1
[2.0.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v2.0.0
[1.9.1.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.9.1.1
[1.9.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.9.1
[1.9.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.9.0
[1.8.1.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.8.1.1
[1.8.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.8.1
[1.8.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.8.0
[1.7.1.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.7.1.2
[1.7.1.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.7.1.1
[1.7.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.7.1
[1.7.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.7.0
[1.6.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.6.1
[1.6.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.6.0
[1.5.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.5.0
[1.4.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.4.0
[1.3.2]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.3.2
[1.3.1]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.3.1
[1.3.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.3.0
[1.2.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.2.0
[1.1.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.1.0
[1.0.0]: https://github.com/codesnippetspro/code-snippets/releases/tag/v1.0.0
