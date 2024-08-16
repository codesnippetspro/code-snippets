# Changelog

## 3.6.5 (24 May 2024)
* Added: New admin menu providing useful resources and updates on the Code Snippets plugin and community.

## 3.6.4 (15 Mar 2024)
* Fixed: Minor type compatability issue with newer versions of PHP.
* Improved: Increment the revision number of CSS and JS snippet when using the 'Reset Caches' debug action. (PRO)
* Fixed: Undefined array key issue when initiating cloud sync. (PRO)
* Fixed: Bug preventing downloading a single snippet from a bundle. (PRO)
* Added: AI generation for all snippet types: HTML, CSS, JS. (PRO)
* Fixed: Translations not loading for strings in JavaScript files.
* Improved: UX in generate dialog, such as allowing 'Enter' to submit the form. (PRO)
* Added: Button to create a cloud connection directly from the Snippets menu when disconnected. (PRO)

## 3.6.3 (13 Nov 2023)
* Fixed: Import error when initialising cloud sync configuration. (PRO)
* Improved: Added debug action for resetting snippets caches.

## 3.6.2 (11 Nov 2023)
* Fixed: Error when attempting to save shared network snippets marked as active.
* Fixed: Type error when rendering checkbox fields without a stored or default value.
* Fixed: Removed automatic encoding of code content.
* Fixed: Label for snippet sharing input incorrectly linked to input field.
* Fixed: Error when attempting to download export files from Edit menu.
* Fixed: Issue loading Freemius string overrides too early. (PRO)
* Fixed: Fix redirect URL when connecting with OAuth on subdirectory or HTTPS sites. (PRO)
* Fixed: Import error when attempting to completely uninstall the plugin.

## 3.6.1 (07 Nov 2023)
* Fixed: Issue accessing fields on Snippets class.

## 3.6.0 (07 Nov 2023)
* Updated minimum PHP requirement to 7.4.

* Added: Ability to authenticate with Code Snippets Cloud using OAuth. (PRO)
* Added: Integration with GPT AI for generating snippets. (PRO)
* Added: Ability to generate line-by-line descriptions of snippet code with GPT AI. (PRO)
* Added: Ability to generate tags and description text from existing snippet code with GPT AI. (PRO)

* Improved: Ensure that the URL of the edit snippet page changes when adding a new snippet.
* Improved: Snippet tags will automatically be added when focus is lost on the tags field.
* Improved: Added debug settings menu for manually performing problem-solving actions.
* Fixed: Moved active status border on edit name field to left-hand side.
* Added: Filter to disable scroll-into-view functionality for edit page notices.
* Fixed: New notices will not scroll if already at top of page.
* Fixed: Potential CSRF vulnerability allowing an authenticated user to reset settings.

## 3.5.1 (15 Sep 2023)
* Fixed: Undefined array key error when accessing plugin settings page. (PRO)
* Fixed: Issue registering API endpoints affecting edit post screen. (PRO)
* Fixed: Snippet ID instead of snippet object being passed to `code_snippets/update_snippet` action hook.

## 3.5.0 (13 Sep 2023)
* Added: Support for the Code Snippets Cloud API.
* Added: Search and download public snippets.
* Added: Codevault back-up and synchronisation. (PRO)
* Added: Synchronised local snippets are automatically updated in Cloud. (PRO)
* Added: Bulk actions - 'update' and 'download'.
* Added: Download snippets from public and private codevaults. (PRO)
* Added: Search and download any publicly viewable snippet in Code Snippet Cloud by keyword or name of codevault. (PRO)
* Added: Deploy snippets to plugin from Code Snippets Cloud app. (PRO)
* Added: Bundles of Joy! Search and download Snippet Bundles in one go direct from Code Snippets Cloud. (PRO)
* Fixed: Error when attempting to update network shared snippets after saving. [[#](https://wordpress.org/support/topic/activating-snippets-breaks-on-wordpress-6-3/)]
* Improved: Redirect to snippets table when deleting snippet from the edit menu.
* Improved: Scroll new notices into view on edit menu.

## 3.4.2 (05 Jul 2023)
* Fixed: Issue causing export process to fail with fatal error. [[#](https://wordpress.org/support/topic/critical-error-on-exporting-snippets/)]
* Fixed: Type issue on `the_posts` filter when no posts available. [[#](https://wordpress.org/support/topic/collision-with-plugin-xml-sitemap-google-news/)]

## 3.4.1 (29 Jun 2023)
* Fixed: Undefined array key error. [[#](https://wordpress.org/support/topic/after-updating-occasionally-getting-undefined-array-key-query/)]
* Fixed: Potential type issue when loading Prism. [[#](https://wordpress.org/support/topic/code-snippets-fatal-error-breaking-xml-sitemaps/)]
* Improved: Added better debugging when calling REST API methods from the edit menu.
* Improved: Escape special characters when sending snippet code through AJAX to avoid false-positives from security modules. [[#](https://wordpress.org/support/topic/latest-3-4-0-ajax-bug-cannot-save-snippets-403-error/)]
* Improved: Only display the latest update or error notice on the edit page, instead of allowing them to stack.
* Fixed: Potential type issue when sorting snippets. [[#](https://github.com/codesnippetspro/code-snippets/issues/166)]
* Fixed: Issue preventing asset revision numbers from updating correctly. (PRO) [[#](https://github.com/codesnippetspro/code-snippets/issues/166)]

## 3.4.0 (17 May 2023)
* Added: Proper WordPress REST API support for retrieving and modifying snippets.
* Improved: Better compatibility with modern versions of PHP (7.0+).
* Improved: Converted Edit/Add New Snippet page to use React.
    * Converted action buttons to asynchronously use REST API endpoints through AJAX.
    * Load page components dynamically through React.
    * Added action notice queue system.
    * Replaced native alert dialog with proper React modal.
* Improved: Catch snippet execution errors to prevent site from crashing.
* Improved: Display recent snippet errors in admin dashboard instead.
* Improved: Updated editor block to use new REST API endpoints. (PRO)
* Improved: Change colour of upgrade notice in Pro plugin. (PRO)
* Improved: All available snippet data is included in export files.
* Improved: Only import specific fields from export file, even if additional fields specified.
* Fixed: Issue preventing editor colorpicker from loading correctly. (PRO)
* Improved: Added help links to content snippet options.
* Improved: Pass additional attributes specified in `[code_snippet]` content shortcode to shortcode content.
* Improved: Make shortcode attributes available as individual variables.
* Improved: Allow boolean attributes to be passed to code snippets shortcodes without specifying a value.
* Improved: Replace external links to Pro pricing page with an upgrade modal.
* Fixed: Issue preventing linting libraries from loading correctly in the code editor.

## 3.3.0 (09 Mar 2023)
* Fixed: Do not enqueue CSS or JS snippet file if no snippets exist. (PRO)
* Improved: Added additional editor shortcuts to list in tooltip.
* Added: Filter for changing Snippets admin menu position. [See this help article for more information.](https://help.codesnippets.pro/article/61-how-can-i-change-the-location-of-the-snippets-admin-menu)
* Added: Ability to filter shortcode output. Thanks to contributions from [Jack Szwergold](https://github.com/JackSzwergold).
* Fixed: Bug causing all snippets to show in site health information instead of those active.
* Fixed: Unnecessary sanitization of file upload data causing import process to fail on Windows systems.

## 3.2.2 (17 Nov 2022)
* Fixed: Plugin lacking a valid header error on activation.

## 3.2.1 (05 Oct 2022)
* Fixed: Issue making survey reminder notice not dismissible.
* Added: `Ctrl`+`/` or `Cmd`+`/` as shortcut for commenting out code in the snippet editor.
* Added: Additional hooks to various snippet actions, thanks to contributions made by [ancient-spirit](https://github.com/ancient-spirit).
* Added: Fold markers, additional keyboard shortcuts and keymap options to snippet editor,
  thanks to contributions made by [Amaral Krichman](https://github.com/karmaral).
* Improved: Removed duplicate tables exist query. ([#](https://wordpress.org/support/topic/duplicate-queries-21)).
* Improved: Enabled 'add paragraphs and formatting' option by default for newly inserted content snippets.
* Added: WP-CLI commands for retrieving, activating, deactivating, deleting, creating, updating, exporting and importing snippets.
* Fixed: Path to iron visible when updating the pro plugin.

## 3.2.0 (22 Jul 2022)
* Fixed: Remove default value from SQL columns to improve compatibility with certain versions of MySQL.
* Fixed: Delay loading snippets in Gutenberg editor blocks. (PRO)
* Added: Option to show and hide line numbers in Gutenberg source code editor block. (PRO)
* Added: Support for highlighting HTML, CSS, JS and embedded code in the front-end PrismJS code highlighter.
* Added: Additional features to front-end PrismJS code highlighter, including automatic links and a copy button.
* Added: Support for multiple code styles in the source code Gutenberg editor block. (PRO)
* Added: Admin notice announcing release of Code Snippets Pro.
* Fixed: Inconsistencies with translations between different plugin versions.
* Fixed: Issue with Content Snippet shortcode information not displaying.
* Added: Button for copying shortcode text to clipboard.
* Improved: Include Code Snippets CSS and JS source code in distributed package.
* Improved: Don't delete data from plugin if deleting Code Snippets Free while Code Snippets Pro is active.
* Improved: Streamlined user interface and experience in Gutenberg editor blocks. (PRO)
* Added: Option to choose from 44 different themes for the Prism code highlighter in the source editor block and Elementor widget. (PRO)
* Improved: Compatibility of Elementor widgets with the latest version of Elementor. (PRO)
* Improved: Replace icon font menu icon with embedded SVG icon.

## 3.1.2 (03 Jul 2022)
* Updated external links and branding for Code Snippets Pro.
* Improved: Add link URLs to settings pages, as an alternative to in-page navigation.
* Fixed: Various fixes to block editor scripts. (PRO)
* Fixed: Improved visual style of Gutenberg editor blocks. (PRO)

## 3.1.1 (13 Jun 2022)
* Fixed: Download snippets feature not including snippet content.
* Fixed: Alignment of 'opens externally' dashicon.
* Improved: Added additional parameters to `code_snippets/export/filename` filter.

## 3.1.0 (17 May 2022)
* Fixed: Caching inconsistencies preventing snippets and settings from refreshing on sites with persistent object caching.
* Improved: Simplified database queries.
* Added: More comprehensive cache coverage, including for active snippets.
* Added: Icon to 'Go Pro' button indicating it opens an external tab.
* Improved: Allow display styles in snippet descriptions.

## 3.0.1 (14 May 2022)
* Fixed: Incompatibility issue with earlier versions of PHP.

## 3.0.0 (14 May 2022)

### Added
* Added: HTML content snippets for displaying as shortcodes or including in the page head or footer area.
* Added: Notice reminding users to upgrade unsupported PHP versions.
* Added: Visual settings to add attributes to shortcodes.
* Added: Shortcode buttons to the post and page content editors.
* Added: Basic REST API endpoints.
* Added: Snippet type column to the snippets table.
* Added: Snippet type badges to Edit and Add New Snippet pages.
* Added: Setting to control whether the current line of the code editor is highlighted.
* Added: Display a warning when saving a snippet with missing title or code.
* Added: Add suffix to title of cloned snippets.

### Changed
* Improved: Updated plugin code to use namespaces, preventing name collisions with other plugins.
* Improved: Added key for the 'active' and 'scope' database table columns to speed up queries.
* Improved: Redirect from edit menu if not editing a valid snippet.
* Improved: Moved activation switch into its own table column.
* Improved: Updated code documentation according to WordPress standards.
* Improved: Added snippet type labels to the tabs on the Snippets page.
* Improved: Split settings page into tabs.
* Improved: Use the version of CodeMirror included with WordPress where possible to inherit the additional built-in features.
* Improved: Added hover effect to priority settings in the snippets table to show that they are editable.
* Fixed: Snippets table layout on smaller screens.

### Deprecated
* Removed: Deprecated functions and compatibility code for unsupported PHP versions.
* Removed: Option to disable snippet scopes.

### New in Pro
* Added: CSS style snippets for the site front-end and admin area.
* Added: JavaScript snippets for the site head and body area on the front-end.
* Added: Browser cache versioning for CSS and JavaScript snippets.
* Added: Support for exporting and downloading CSS and JavaScript snippets.
* Added: Support for highlighting code on the front-end.
* Added: Editor syntax highlighting for CSS, JavaScript and HTML snippets.
* Added: Button to preview full file when editing CSS or JavaScript snippets.
* Added: Option to minify CSS and JavaScript snippets.
* Added: Gutenberg editor block for displaying content snippets.
* Added: Gutenberg editor block for displaying snippet source code.
* Added: Elementor widget for displaying content snippets.
* Added: Elementor widget for displaying snippet source code.

## 2.14.6 (13 May 2022)
* Fixed: Issue with processing uploaded import files.
* Fixed: Issue with processing tag filters.

## 2.14.5 (10 May 2022)
* Fixed: Incompatibility issue with older versions of PHP.

## 2.14.4 (5 May 2022)
* Fixed: Prevent array key errors when loading the snippet table with unknown order values.

## 2.14.3 (10 Dec 2021)
* Fixed: Potential security issue outputting snippets-safe-mode query variable value as-is. Thanks to Krzysztof Zając for reporting.

## 2.14.2 (09 Sep 2021)
* Fixed: Prevent network snippets table from being created on single-site installs.
* Added translations:
    - Spanish by [Ibidem Group](https://www.ibidemgroup.com)
    - Urdu by [Samuel Badree](https://mobilemall.pk/)
    - Greek by [Toni Bishop from Jrop](https://www.jrop.com/)
* Added: Support for `:class` syntax to the code validator.
* Added: PHP8 support to the code linter.
* Added: Color picker feature to the code editor.
* Added: Failsafe to prevent multiple versions of Code Snippets from running simultaneously.

## 2.14.1 (10 Mar 2021)
* Added: Czech translation by [Lukáš Tesař](https://github.com/atomicf4ll).
* Fixed: Code validator now supports `function_exists` and `class_exists` checks.
* Fixed: Code validator now supports anonymous functions.
* Fixed: Issue with saving the hidden columns setting.
* Fixed: Replaced the outdated tag-it library with [tagger](https://github.com/jcubic/tagger) for powering the snippet tags editor.
* Added: Code direction setting for RTL users.
* Updated CodeMirror to version 5.59.4.
* Added: Additional action hooks and search API thanks to [@Spreeuw](https://github.com/Spreeuw).

## 2.14.0 (26 Jan 2020)
* Updated CodeMirror to version 5.50.2.
* Added: Basic error checking for duplicate functions and classes.
* Updated Italian translations to fix display issues – thanks to [Francesco Marino](https://360fun.net).
* Fixed: Ordering snippets in the table by name will now be case-insensitive.
* Added: Additional API options for retrieving snippets.
* Fixed: Code editor will now properly highlight embedded HTML, CSS and JavaScript code.
* Changed the indicator color for inactive snippets from red to grey.
* Fixed a bug preventing the editor theme from being set to default.
* Added: Store the time and date when each snippet was last modified.
* Added: Basic error checking when activating snippets.
* Fixed: Ensure that imported snippets are always inactive.
* Fixed: Check the referer on the import menu to prevent CSRF attacks.
  Thanks to [Chloe with the Wordfence Threat Intelligence team](https://www.wordfence.com/blog/author/wfchloe/) for reporting.
* Fixed: Ensure that individual snippet action links use proper verification.

## 2.13.3 (13 Mar 2019)
* Added: Hover effect to activation switches.
* Added: Additional save buttons above snippet editor.
* Added: List save keyboard shortcuts to the help tooltip.
* Added: Change "no items found" message when search filters match nothing.
* Fixed: Calling deprecated code in database upgrade process.
* Fixed: Include snippet priority in export files.
* Fixed: Use Unix newlines in code export file.
* Updated CodeMirror to version 5.44.0.
* Fixed: Correctly register snippet tables with WordPress to prevent database repair errors.
  [[#](https://wordpress.org/support/topic/database-corrupted-4/)]
* Fixed: CodeMirror indentation settings being applied incorrectly.

## 2.13.2 (25 Jan 2019)
* Removed potentially problematic cursor position saving feature.

## 2.13.1 (22 Jan 2019)
* Added: Add menu buttons to settings page for compact menu.
* Updated: French translation updated thanks to momo-fr.
* Fixed: Split code editor and tag editor scripts into their own files to prevent dependency errors.
* Fixed: Handling of single-use shared network snippets.
* Fixed: Minor translation template issues.
* Added: Help tooltop to snippet editor for keyboard shortcuts, thanks to Michael DeWitt.
* Improved: Added button for executing single-use snippets to snippets table.
* Added: Sample snippet for ordering snippets table by name by default.
* Updated CodeMirror to version 5.43.0.

## 2.13.0 (17 Dec 2018)
* Added: Search/replace functionality to the snippet editor. [See here for a list of keyboard shortcuts.](https://codemirror.net/demo/search.html) [[#](https://wordpress.org/support/topic/feature-request-codemirror-search-and-replace/)]
* Updated CodeMirror to version 5.42.0.
* Added: Option to make admin menu more compact.
* Fixed: Problem clearing recently active snippet list.
* Improved: Integration between plugin and the CodeMirror library, to prevent collisions.
* Improved: Added additional styles to editor settings preview.
* Added: PHP linter to code editor.
* Improved: Use external scripts instead of inline scripts.
* Fixed: Missing functionality for 'Auto Close Brackets' and 'Highlight Selection Matches' settings.

## 2.12.1 (15 Nov 2018)
* Improved: CodeMirror updated to version 5.41.0.
* Improved: Attempt to create database columns that might be missing after a table upgrade.
* Improved: Streamlined upgrade process.
* Fixed: Interface layout on sites using right-to-left languages.
* Improved: Made search box appear at top of page on mobile. [[#](https://wordpress.org/support/topic/small-modification-for-mobile-ux/)]
* Updated screenshots.

## 2.12.0 (23 Sep 2018)
* Removed option for including network-wide snippets in subsite lists on multisite.
* Fixed: Prevented hidden columns setting from reverting to default.
* Improved: Updated import page to improve usability.
* Improved: Added Import button next to page title on manage page.
* Improved: Added coloured banner indicating whether a snippet is active when editing.
* Update CodeMirror to 5.40.0.

## 2.11.0 (24 Jul 2018)
* Added: Ability to assign a priority to snippets, to determine the order in which they are executed.
* Improvement: The editor cursor position will be preserved when saving a snippet.
* Added: Pressing Ctrl/Cmd + S while writing a snippet will save it.
* Added: Shadow opening PHP tag above the code editor.
* Improved: Updated the message shown when there are no snippets.
* Added: Install sample snippets when the plugin is installed.
* Improved: Show all available tags when selecting the tag field.
* Added: Filter hook for controlling the default list table view.
* Added: Action for cloning snippets.

## 2.10.2 (21 Jul 2018)
* Added: Button to reset settings to their default values.
* Improved: Made uninstall cleanup optional through a plugin setting.
* Fixed: Applied formatting filters to snippet descriptions in the table.
* Improved: Ordered tags by name in the filter dropdown menu.
* Fixed: Incorrectly translated strings.
* Added: Belarusian translation by Hrank.com.
* Improved: Enabled sorting snippets table by tags.
* Updated CodeMirror to version 5.39.0.

## 2.10.1 (10 Feb 2018)
* Fixed: Prevent errors when trying to export no snippets.
* Fixed: Use wp_json_encode() to encode export data.
* Fixed: Check both the file extension and MIME type of uploaded import files.

## 2.10.0 (18 Jan 2018)
* Improved: Added support for importing from multiple export files at once.
* Improved: Unbold the titles of inactive snippets for greater visual distinction.
* Added: New scope for single-use snippets.
* Improved: Don't show network snippets on subsites by default, and only to super admins.
* Improved: Export snippets to JSON instead of XML.
* Improved: More options for importing duplicate snippets.
* Improved: Use strings for representing scopes internally instead of numbers.
* Added: Allowed plugin settings to be unified on multisite through Network Settings option.
* Fixed: Issue with incorrectly treating network snippets as site-wide for code validation.
* Improved: Rename 'Export to PHP' to 'Download', and add button to edit snippet page.

## 2.9.6 (14 Jan 2018)
* Added Brazilian Portuguese translation by [Bruno Borges](http://brunoborges.info)
* Fixed: Use standard WordPress capabilities instead of custom capabilities to prevent lockouts.
* Fixed: Multisite issue with retrieving active shared snippets from the wrong table causing duplicate snippet execution.
* Moved scope and other settings on single snippet page to below code area.

## 2.9.5 (13 Jan 2018)
* Fixed: Undefined function error when accessing the database on multisite.
* Fixed: Ensured all admin headings are hierarchical for accessibility.
* Made the "Activate By Default" setting enabled by default for new installs.
* Updated CodeMirror to version 5.33.

## 2.9.4 (19 Sep 2017)
* Fixed: Prevented PHP error from occurring when saving a snippet.
* Minor improvements to database creation function.

## 2.9.3 (11 Sep 2017)
* Fixed: Prevent snippets from being executed twice when saving due to invalid ID being passed to allow_execute_snippet filter.
* Fixed: Re-enabled output suppression when executing snippets.

## 2.9.2 (08 Sep 2017)
* Fixed: Do not attempt to combine queries for fetching local and multisite snippets.

## 2.9.1 (07 Sep 2017)
* Fixed: Prevent illegal mix of collations errors when fetching snippets from database tables with different collations.
  [[#](https://wordpress.org/support/topic/issue-on-multisite-with-wpml/)]

## 2.9.0 (06 Sep 2017)
* Fixed: Prevented invalid properties from being set when saving a snippet.
* Fixed: Use the correct protocol when saving a snippet.
* Improved: Moved code to disable snippet execution into a filter hook.
* Fixed: Active shared snippets not being updated correctly.
* Improved: execute_active_snippets() function updated with improved efficiency.
* Improved: Renamed Snippet class to avoid name collisions with other plugins.
* Improved: Don't hide output when executing a snippet.

## 2.8.7 (18 May 2017)
* Added French (Canada) translation by Domonic Desbiens.
* Added fixes for Indonesian translation by @zmni.

## 2.8.6 (14 May 2017)
* Ensured that get_snippets() function retrieves snippets with the correct 'network' setting. Fixes snippet edit links in network admin.
* Fixed snippet description field alias not mapping correctly, causing snippet descriptions to not be displayed in the table or when editing a snippet.

## 2.8.5 (13 May 2017)
* Ensured HTML in snippet titles is escaped in snippets table.
* Added Indonesian translation by Jordan Silaen from ChameleonJohn.com .
* Disallowed undefined fields to be set on the Snippets class.
* Prevented shared network snippets from being included twice in snippets table on multisite.
* Added setting to hide network snippets on subsites.

## 2.8.4 (29 April 2017)
* Fixed all snippets being treated as network snippets on non-multisite sites.

## 2.8.3 (29 April 2017)
* Updated CodeMirror to version 5.25.0.
* Show network active snippets as read-only on multisite subsites.
* Added more compete output escaping to prevent XSS errors.

## 2.8.2 (27 Feb 2017)
* Fix bug introduced in 2.8.1 that broke code verification functionality by executing code twice.

## 2.8.1 (25 Feb 2017)
* Updated German translation.
* Fixed admin menu items not translating.
* Removed possible conflict between Debug Bar Console plugin. (#52)
* Corrected editor alignment on RTL sites. ([#](https://wordpress.org/support/topic/suggestion-css-fix-for-rtl-sites/))
* Fixed bulk actions running when Filter button is clicked. ([#](https://wordpress.org/support/topic/bug-with-filtering-action-buttons/))
* Updated CodeMirror to version 5.24.0.

## 2.8.0 (14 Dec 2016)
* Fixed Italian translation errors. Props to @arsenalemusica.
* Renamed 'Manage' admin menu label to 'All Snippets' to keep in line with other admin menu labels.
* Renamed placeholder on snippet name field to 'Enter title here'.
* Removed CodeMirror search functionality.
* Moved 'Edit Snippet' admin menu above 'Add New' menu.
* Made pressing Ctrl-Enter in the code editor save the snippet.
* Updated CodeMirror to version 5.21.0.

## 2.7.3 (24 Oct 2016)
* Updated CodeMirror to version 5.10.0.
* Fixed a few strings not being translated.

## 2.7.2 (01 Oct 2016)
* Updated German translation by [Mario Siegmann](https://web-alltag.de).

## 2.7.1 (30 Sep 2016)
* Added Dutch translation by Sander Spies.
* Ensured that the editor theme setting is properly validated. Thanks to [Netsparker](https://www.netsparker.com) for reporting.
* Ensured that snippet tags are properly escaped. Thanks to [Netsparker](https://www.netsparker.com) for reporting.
* Updated CodeMirror to version 5.19.0.

## 2.7.0 (23 July 2016)
* Fixed plugin translations being loaded.
* Increase default snippets per page so that all are usually shown.
* Fixed description field not being imported.
* Updated German translation by [Mario Siegmann](https://web-alltag.de).
* Fixed issue with CodeMirror rubyblue theme. [[#](https://wordpress.org/support/topic/a-problem-with-the-cursor-color-and-the-fix-that-worked-for-me)]
* Added query var to disable snippet execution. To use, add `?snippets-safe-mode=true` to the URL.
* Fixed snippet fields not importing.
* Updated CodeMirror to version 5.17.0.
* Fixed a minor XSS vulnerability discovered by Burak Kelebek. [[#](https://wordpress.org/support/topic/security-vulnerability-20)]

## 2.6.1 (10 Feb 2016)
* Updated German translation by [Mario Siegmann](https://web-alltag.de).
* Fixed error catching not working correctly.
* Updated error catching to work with snippets including functions and classes.
* Fixed editor autoresizing.

## 2.6.0 (31 Dec 2015)
* Reconfigured plugin to use classloader and converted a lot of functional code into OOP code.
* Updated CodeMirror to version 5.10.0.
* Added `[code_snippets]` shortcode for embedding snippet code in a post.
* Fixed broken snippet search feature. [[#](https://wordpress.org/support/topic/search-is-not-working-6)]
* Added front-end syntax highlighting for shortcode using [PrismJS](https://prismjs.com).

## 2.5.1 (11 Oct 2016)
* Fixed: Ensure errors are fatal before catching them during error checking.
* Fixed: Escape the snippet name on the edit page to ensure it displays correctly.
* Fixed: Exclude snippets with named functions from error checking so they do not run twice.

## 2.5.0 (8 Oct 2015)
* Added: Detect parse and fatal errors in code when saving a snippet, and display a user-friendly message.
* Fixed: Updated access of some methods in Code_Snippets_List_Table class to match updated WP_List_Table class.

## 2.4.2 (27 Sep 2015)
* Added query variable to activate safe mode.
* Fixed settings not saving.
* Fixed snippet descriptions not displaying on manage menu.
* Added settings to disable description and tag editors.
* Fixed: Load CodeMirror after plugin styles to fix error with Zenburn theme.
* Fixed: Hide snippet scope icons when the scope selector is disabled.
* Fixed description heading on edt snippet menu being hidden when visual editor disabled.
* Updated editor preview updating code to use vanilla JavaScript instead of jQuery.
* Fixed: Deactivate a shared network snippet on all subsites when it looses its sharing status.

## 2.4.1 (17 Sep 2015)
* Fixed CodeMirror themes not being detected on settings page [[#](https://wordpress.org/support/topic/updated-to-240-now-i-cant-switch-theme)]

## 2.4.0 (17 Sep 2015)
* Added ability to share network snippets to individual sites on WordPress multisite.
* Improved code directory and class structure.
* Remove legacy code for pre-3.6 compatibility.
* Improved code for printing admin messages.
* Updated German translation (Joerg Knoerchen)
* Added `code_snippets/after_execute_snippet` filter.
* Added class for individual snippets.
* Updated `get_snippets()` function to retrieve individual snippets.
* Removed scope statuses and added fixed tags to indicate scope.
* Changed admin page headers to use `<h1>` tags instead of `<h2>` tags.
* Updated CodeMirror to version 5.6.
* Removed snippet settings page from network admin.

## 2.3.0 (20 May 2015)
* Removed nested functions.
* Added icons for admin and front-end snippets to manage table.
* Improved settings retrieval by caching settings.
* Updated Russian translation by [Alexey Chumakov](http://chumakov.ru/).
* Added filter switch to prevent a snippet from executing. ([#25](https://github.com/codesnippetspro/code-snippets/issues/25))
* Fixed errors in string translation.
* Fixed bug in import process. ([#32](https://github.com/codesnippetspro/code-snippets/issues/32))

## 2.2.3 (13 May 2015)
* Fixed broken call to `export_snippet()` function.
* Added support for importing and exporting snippet scope.
* Fixed duplicate primary key database error.
* Improved database table structure.

## 2.2.2 (11 May 2015)
* Polyfilled array_replace_recursive() function for PHP 5.2.
* Updated references to old plugin site.
* Resolved JavaScript error on edit snippet pages.
* Made minor updates to French translation file.
* Added statuses for snippet scopes on manage snippets table.

## 2.2.1 (10 May 2015)
* Fixed the default values of new setting not being applied.
* Fixed missing background of tags input.

## 2.2.0 (10 May 2015)
* Introduced CodeSniffer testing on code.
* Fixed description heading disappearing when media buttons enabled.
* Added snippet scope selector.
* Minified all CSS and JS in plugin.
* Made CodeMirror theme names more readable.
* Fixed bug causing translations to not be loaded.

## 2.1.0 (09 May 2015)
* Added additional setting descriptions.
* Added settings for code and description editor height.
* Updated CodeMirror to version 5.2.
* Fixed not escaping the request URL when using query arg functions.
* Improved efficiency of settings component.

## 2.0.3 (17 Mar 2015)
* Updated German translation by [Joerg Knoerchen](https://www.sensorgrafie.de/).

## 2.0.2 (05 Mar 2015)
* Fix error in table creation code.
* Remove settings database option when plugin is uninstalled.

## 2.0.1 (25 Feb 2015)
* Fixed table creation code not running on upgrade.
* Fixed snippets per page option not saving.

## 2.0 (24 Feb 2015)

### Highlights.
* Better import/export functionality.
* New settings page with code editor settings.
* Code rewritten for cleaner and more efficient code.
* Lots of new translations.

### Added.
* Added link to Code Snippets importer under Snippets admin menu.
* Added settings component and admin page.
* Added support for different CodeMirror themes.
* Integrated tags component into main plugin. Current users of the Code Snippets Tags plugin can safely uninstall it.
* Added Auto Close Brackets CodeMirror addon (props to TronicLabs).
* Added Croatian translation by Borisa Djuraskovic from [Web Hosting Hub](https://www.webhostinghub.com).
* Added Highlight Selection Matches CodeMirror addon (props to TronicLabs).
* Added Chinese translation thanks to Jincheng Shan.
* Added Russian translation by Alexander Samsonov.
* Added Slovak translation by [Ján Fajčák] from [WordPress Slovakia](https://wp.sk).
* Added setting to always save and activate snippets by default.

### Changed
* Added braces to single-line conditionals in line with [new coding standards](https://make.wordpress.org/core/2013/11/13/proposed-coding-standards-change-always-require-braces/).
* Split up large classes into separate functions.
* Improved plugin file structure.
* Replaced uninstall hook with single file method.
* Updated CodeMirror library to version 5.0.
* Rewritten import/export functionality to use DOMDocument.
* Merged Code_Snippets_Export_PHP class into Code_Snippets_Export class.

### Deprecated
* Removed old admin style support.
* Removed backwards-compatible support.

### Fixed
* Fixed incompatibility errors with PHP 5.2.
* Fixed empty MO translation files.
* Removed duplicate MySQL primary key indexing.

## 1.9.1.1 (03 Jan 2014)
* Add capability check to site snippets importer.

## 1.9.1 (02 Jan 2014)
* Use an icon font for menu icon instead of embedded SVG.
* Use Sass (libsass) instead of Compass.
* Unminify CodeMirror scripts.
* Fixes for the WP 3.8 interface.
* Fix 'enable snippets menu for site admins' multisite setting.

## 1.9 (11 Nov 2013)
* Add and remove network capabilities as super admins are added and removed.
* Updated MP6 icon implementation.
* Replaced buggy trim `<?php` and `?>` functionality with a much more reliable regex method. ([#](https://wordpress.org/support/topic/character-gets-cut))
* Added French translation thanks to translator [oWEB](http://office-web.net).
* Fixed snippet failing to save when code contains `%` character, props to [nikan06](https://wordpress.org/support/profile/nikan06). ([#](https://wordpress.org/support/topic/percent-sign-bug))
* Added 'Save & Deactivate' button to the edit snippet page. ([#](https://wordpress.org/support/topic/deactivate-button-in-edit-snippet-page))
* Removed edit and install capabilities (now only uses the manage capability).
* Fixed HTML breaking in export files. ([#](https://wordpress.org/support/topic/import-problem-7))
* Make the title of each snippet on the manage page a clickable link to edit the snippet ([#](https://wordpress.org/support/topic/deactivate-button-in-edit-snippet-page?replies=9#post-4682757))
* Added nonce to edit snippet page.
* Hide row actions on manage snippet page by default.
* Removed screenshots from plugin.
* Improved CodeMirror implementation.
* Added a fallback MP6 icon.
* Use the proper WordPress database APIs all of the time.
* Rewritten export functionality.
* Fixed incorrect export filename.
* Updated CodeMirror to version 3.19.
* Removed CodeMirror bundled with plugin.
* Updated WordPress.org plugin banner.
* Fixed CodeMirror incompatibility with the WP Editor plugin.
* Fixed CodeMirror incompatibility with the Debug Bar Console plugin.

## 1.8.1.1 (18 Aug 2013)

## 1.8.1 (29 July 2013)
* Compiled all CodeMirror scripts into a single file.
* Use Sass + Compass for CSS.
* Use Grunt for build automation.
* Minify CSS.
* Fixed code typo that was breaking export files.
* Updated CodeMirror to 3.15.

## 1.8 (09 Jul 2013)
* Allow no snippet name or code to be set.
* Prevented an error on fresh multisite installations.
* Refactored code to use best practices.
* Improved database table creation method: on a single-site install, the snippets table will always be created. On a multisite install, the network snippets table will always be created; the site-specific table will always be created for the main site; for sub-sites the snippets table will only be created on a visit to a snippets admin page.
* Updated to CodeMirror 3.14.
* Changes to filter and action hook API.
* Added error message handling for import snippets page.
* Don't encode HTML entities in database.

## 1.7.1.2 (03 May 2013)
* Correct path to admin menu icon. Fixes [#8](https://github.com/codesnippetspro/code-snippets/issues/8)

## 1.7.1.1 (29 Apr 2013)
* Fixed a bug with custom capabilities and admin menus.

## 1.7.1 (22 Apr 2013)
* Fix a bug with snippet being set as deactivated when saved.
* Updated PHP Documentation completely. [[View online](https://bungeshea.github.io/code-snippets/api)]
* Only load admin functions when viewing dashboard.
* Added German translation thanks to [David Decker](https://deckerweb.de)
* Allow or deny site administrators access to snippet admin menus. Set your preference in the **Enable Administration Menus** setting under the *Settings > Network Settings* network admin menu.
* Improve database table creation and upgrade process.
* Optimized to use less database queries.

## 1.7 (26 Mar 2013)
* Improved plugin API.
* Fixed a bug with saving snippets per page option ([#](https://wordpress.org/support/topic/plugin-code-snippets-snippets-per-page-does-not-work#post-3710991))
* Updated CodeMirror to version 3.11.
* Allow plugin to be activated on individual sites on multisite ([#](https://wordpress.org/support/topic/dont-work-at-multisite))
* Slimmed down the description visual editor.
* Added icon for the new MP6 admin UI ([#](https://wordpress.org/support/topic/icon-disappears-with-mp6))
* Strip PHP tags from the beginning and end of a snippet on save ([#](https://wordpress.org/support/topic/php-tags))
* Changed to [MIT license](https://opensource.org/licenses/mit-license.php)
* Removed HTML, CSS and JavaScript CodeMirror modes that were messing things up.
* Change label in admin menu when editing a snippet.
* Improved admin styling.
* Made everything leaner, faster, and better.

## 1.6.1 (29 Dec 2012)
* Fixed a bug with permissions not being applied on install ([#](https://wordpress.org/support/topic/permissions-problem-after-install))
* Fixed a bug in the uninstall method ([#](https://wordpress.org/support/topic/bug-in-delete-script))

## 1.6 (22 Dec 2012)
* Updated code editor to use CodeMirror 3.
* Improved compatibility with Clean Options plugin.
* Code improvements and optimization.
* Changed namespace from `cs` to `code_snippets`.
* Move css and js under assets.
* Organized CodeMirror scripts.
* Improved updating process.
* Current line of code editor is now highlighted.
* Highlight matches of selected text in code editor.
* Only create snippet tables when needed.
* Store multisite only options in site options table.
* Fixed compatibility bugs with WordPress 3.5.

## 1.5 (18 Sep 2012)
* Updated CodeMirror to version 2.33.
* Updated the 'Manage Snippets' page to use the WP_List_Table class.
    * Added 'Screen Options' tab to 'Manage Snippets' page.
    * Added search capability to 'Manage Snippets' page.
    * Added views to easily filter activated, deactivated and recently activated snippets.
    * Added ID column to 'Manage Snippets' page.
    * Added sortable name and ID column on 'Manage Snippets' page ([#](https://wordpress.org/support/topic/plugin-code-snippets-suggestion-sort-by-snippet-name))
* Added custom capabilities.
* Improved API.
* Added 'Export to PHP' feature. ([#](https://wordpress.org/support/topic/plugin-code-snippets-suggestion-bulk-export-to-php))
* Lengthened snippet name field to 64 characters. ([#](https://wordpress.org/support/topic/plugin-code-snippets-snippet-title-limited-to-36-characters))
* Added i18n.

## 1.4 (20 Aug 2012)
* Added interface to Network Dashboard.
* Updated uninstall to support multisite.
* Replaced EditArea with [CodeMirror](https://codemirror.net).
* Small improvements.

## 1.3.2 (17 Aug 2012)
* Fixed a bug with version 1.3.1.

## 1.3.1 (17 Aug 2012)
* Changed plugin website URI.
* Cleaned up some code.

## 1.3 (01 Aug 2012)
* Added export option to 'Manage Snippets' page.
* Added 'Import Snippets' page.

## 1.2 (29 July 2012)
* Minor improvements.
* Added code highlighting.
* Removed 'Uninstall Plugin' page.
* Data will now be cleaned up when plugin is deleted through WordPress admin.

## 1.1 (24 June 2012)
* Fixed a permissions bug with `DISALLOW_FILE_EDIT` being set to true. ([#](https://wordpress.org/support/topic/plugin-code-snippets-cant-add-new))
* Fixed a bug with the page title reading 'Add New Snippet' on the 'Edit Snippets' page.
* Fixed a bug not allowing the plugin to be Network Activated. ([#](https://wordpress.org/support/topic/plugin-code-snippets-network-activate-does-not-create-snippets-tables))

## 1.0 (13 June 2012)
* Stable version released.
