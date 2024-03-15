=== Code Snippets ===
Contributors: bungeshea, ver3, lightbulbman, 0aksmith, codesnippetspro
Donate link: https://codesnippets.pro
Tags: snippets, functions, multisite, code, php, html, css, javascript, js, content, scripts, styles, cloud, shortcode
License: MIT
License URI: license.txt
Stable tag: 3.6.4
Tested up to: 6.5-RC2

An easy, clean and simple way to enhance your site with code snippets.

== Description ==

**✂ Code Snippets** provides an effortless way to enhance your WordPress site.

**🚀 Upgrade to Code Snippets Pro** for complete CSS, JavaScript, Gutenberg, Elementor and cloud synchronisation integrations. **[Elevate your snippets experience now!](https://codesnippets.pro/pricing)**

Say goodbye to the hassle of tweaking your theme's `functions.php` file and downloading endless plugins – Code Snippets simplifies the process!

A snippet is like a mini-plugin for your WordPress site, providing added functionality without the clutter.

Unlike other solutions that involve dumping code into your `functions.php` file, Code Snippets offers an intuitive graphical interface for seamless integration and real-time execution. Managing snippets is as easy as activating and deactivating plugins, only without the bloat and overhead.

**🎥 Watch a quick overview by Imran Siddiq:**

https://youtu.be/EMjIWjcYONk

☁️ Each copy of Code Snippets includes full integration with the community-powered [Code Snippets Cloud](https://codesnippets.cloud/) platform, providing easy access to hundreds of tweaks and enhancements ready to power-up any WordPress site.

**📚 Learn from Ferdy Korpershoek's tutorial:**

https://youtu.be/29jD2BcBX5w

**🌐 Connect with us:**

* [Support Forum](https://wordpress.org/support/plugin/code-snippets)
* [Facebook Community](https://facebook.com/groups/codesnippetsplugin)
* [GitHub Repository](https://github.com/codesnippetspro/code-snippets)

🌟 Like our plugin? Find it useful? Please consider sharing your experience by [leaving a review on WordPress.org](https://wordpress.org/support/view/plugin-reviews/code-snippets). Your feedback is instrumental to shaping our future growth!

🌍 We'd like to thank the wonderful people who have helped contribute translations to allow Code Snippets to be used in different languages. [You can find a full list here](https://github.com/codesnippetspro/code-snippets/blob/core/CREDITS.md#translators).

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

A full list of our Frequently Asked Questions can be found at [help.codesnippets.pro](https://help.codesnippets.pro/collection/3-faq).

= How can I recover my site if it is crashed by a buggy snippet? =
You can recover your site by enabling the Code Snippets safe mode feature. Instructions for how to turn it on are available here: <https://help.codesnippets.pro/article/12-safe-mode>.

= Will I lose my snippets if I change the theme or upgrade WordPress? =
No, the snippets are stored in the WordPress database, independent of the theme and unaffected by WordPress upgrades.

= Can the plugin be completely uninstalled? =
If you enable the 'Complete Uninstall' option on the plugin settings page, Code Snippets will clean up all of its data when deleted through the WordPress 'Plugins' menu. This includes all stored snippets. If you would like to preserve the snippets, ensure they are exported first.

= Can I copy snippets that I have created to another WordPress site? =
Yes! You can individually export a single snippet using the link below the snippet name on the 'Manage Snippets' page or bulk export multiple snippets using the 'Bulk Actions' feature. Snippets can later be imported using the 'Import Snippets' page by uploading the export file.

= Can I export my snippets to PHP for a site where I'm not using the Code Snippets plugin? =
Yes. Click the checkboxes next to the snippets you want to export, and then choose **Export to PHP** from the Bulk Actions menu and click Apply. The generated PHP file will contain the exported snippets' code, as well as their name and description in comments.

= Can I run network-wide snippets on a multisite installation? =
You can run snippets across an entire multisite network by **Network Activating** Code Snippets through the Network Dashboard. You can also activate Code Snippets just on the main site, and then individually on other sites of your choice.

= Where are the snippets stored in my WordPress database? =
Snippets are stored in the `wp_snippets` table in the WordPress database. The table name may differ depending on what your table prefix is set to.

= Where can I go for help or suggest new features? =
You can get help with Code Snippets, report bugs or errors, and suggest new features and improvements either on the [WordPress Support Forums](https://wordpress.org/support/plugin/code-snippets) or on [GitHub](https://github.com/codesnippetspro/code-snippets).

= How can I help contribute to the development of the Code Snippets plugin? =
The best way to do this is to fork the [repository on GitHub](https://github.com/codesnippetspro/code-snippets) and send a pull request.

= How can I report security bugs found in this plugin? =
You can report security bugs found in the source code of this plugin through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/code-snippets). The Patchstack team will assist with verification, triage, and notification of security vulnerabilities.

== Screenshots ==

1. Managing existing snippets
2. Adding a new snippet
3. Editing a snippet
4. Importing snippets from an export file

== Changelog ==

= 3.6.4 (15 Mar 2024) =
* Fixed: Minor type compatability issue with newer versions of PHP.
* Improvement: Increment the revision number of CSS and JS snippet when using the 'Reset Caches' debug action. (PRO)
* Fixed: Undefined array key issue when initiating cloud sync. (PRO)
* Fixed: Bug preventing downloading a single snippet from a bundle. (PRO)
* Added: AI generation for all snippet types: HTML, CSS, JS. (PRO)
* Fixed: Translations not loading for strings in JavaScript files.
* Improved: UX in generate dialog, such as allowing 'Enter' to submit the form. (PRO)
* Added: Button to create a cloud connection directly from the Snippets menu when disconnected. (PRO)

= 3.6.3 (13 Nov 2023) =
* Fixed: Import error when initialising cloud sync configuration. (PRO)
* Improved: Added debug action for resetting snippets caches.

= 3.6.2 (11 Nov 2023) =
* Fixed: Error when attempting to save shared network snippets marked as active.
* Fixed: Type error when rendering checkbox fields without a stored or default value.
* Fixed: Removed automatic encoding of code content.
* Fixed: Label for snippet sharing input incorrectly linked to input field.
* Fixed: Error when attempting to download export files from Edit menu.
* Fixed: Issue loading Freemius string overrides too early. (PRO)
* Fixed: Fix redirect URL when connecting with OAuth on subdirectory or HTTPS sites. (PRO)
* Fixed: Import error when attempting to completely uninstall the plugin.

= 3.6.1 (07 Nov 2023) =
* Fixed: Issue accessing fields on Snippets class.

= 3.6.0 (07 Nov 2023) =
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

= 3.5.1 (15 Sep 2023) =
* Fixed: Undefined array key error when accessing plugin settings page. (PRO)
* Fixed: Issue registering API endpoints affecting edit post screen. (PRO)

= 3.5.0 (14 Sep 2023) =
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

= 3.4.2 (05 Jul 2023) =
* Fixed: Issue causing export process to fail with fatal error. [[#](https://wordpress.org/support/topic/critical-error-on-exporting-snippets/)]
* Fixed: Type issue on `the_posts` filter when no posts available. [[#](https://wordpress.org/support/topic/collision-with-plugin-xml-sitemap-google-news/)]

= 3.4.1 (29 Jun 2023) =
* Fixed: Undefined array key error. [[#](https://wordpress.org/support/topic/after-updating-occasionally-getting-undefined-array-key-query/)]
* Fixed: Potential type issue when loading Prism. [[#](https://wordpress.org/support/topic/code-snippets-fatal-error-breaking-xml-sitemaps/)]
* Improved: Added better debugging when calling REST API methods from the edit menu.
* Improved: Escape special characters when sending snippet code through AJAX to avoid false-positives from security modules. [[#](https://wordpress.org/support/topic/latest-3-4-0-ajax-bug-cannot-save-snippets-403-error/)]
* Improved: Only display the latest update or error notice on the edit page, instead of allowing them to stack.
* Fixed: Potential type issue when sorting snippets. [[#](https://github.com/codesnippetspro/code-snippets/issues/166)]
* Fixed: Issue preventing asset revision numbers from updating correctly. (PRO) [[#](https://github.com/codesnippetspro/code-snippets/issues/166)]

= 3.4.0 (17 May 2023) =
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

= 3.3.0 (09 Mar 2023) =
* Fixed: Do not enqueue CSS or JS snippet file if no snippets exist. (PRO)
* Improved: Added additional editor shortcuts to list in tooltip.
* Added: Filter for changing Snippets admin menu position. [See this help article for more information.](https://help.codesnippets.pro/article/61-how-can-i-change-the-location-of-the-snippets-admin-menu)
* Added: Ability to filter shortcode output. Thanks to contributions from [Jack Szwergold](https://github.com/JackSzwergold).
* Fixed: Bug causing all snippets to show in site health information instead of those active.
* Fixed: Unnecessary sanitization of file upload data causing import process to fail on Windows systems.

= 3.2.2 (17 Nov 2022) =
* Fixed: Plugin lacking a valid header error on activation.

= 3.2.1 (05 Oct 2022) =
* Fixed: Issue making survey reminder notice not dismissible.
* Added: `Ctrl`+`/` or `Cmd`+`/` as shortcut for commenting out code in the snippet editor.
* Added: Additional hooks to various snippet actions, thanks to contributions made by [ancient-spirit](https://github.com/ancient-spirit).
* Added: Fold markers, additional keyboard shortcuts and keymap options to snippet editor,
thanks to contributions made by [Amaral Krichman](https://github.com/karmaral).
* Improved: Removed duplicate tables exist query. ([#](https://wordpress.org/support/topic/duplicate-queries-21)).
* Improved: Enabled 'add paragraphs and formatting' option by default for newly inserted content snippets.
* Added: WP-CLI commands for retrieving, activating, deactivating, deleting, creating, updating, exporting and importing snippets.
* Fixed: Path to iron visible when updating the pro plugin.

= 3.2.0 (22 Jul 2022) =
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

**[The full changelog is available on GitHub](https://github.com/codesnippetspro/code-snippets/blob/master/CHANGELOG.md)**
