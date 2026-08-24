=== Code Snippets ===
Contributors: bungeshea, ver3, lightbulbman, 0aksmith, johnpixle, louiswol94, carolinaop
Donate link: https://codesnippets.pro
Tags: code, snippets, multisite, php, css
License: GPL-2.0-or-later
License URI: license.txt
Stable tag: 3.10.0
Requires at least: 5.5
Tested up to: 7.0.3
Requires PHP: 7.4

An easy, clean, and simple way to enhance your site with code snippets.

== Description ==

**✂ Code Snippets** provides an effortless way to enhance your WordPress site.

**🚀 Upgrade to Code Snippets Pro** for complete CSS, JavaScript, Gutenberg, Elementor and cloud synchronisation integrations. **[Elevate your snippets experience now!](https://codesnippets.pro/pricing)**

Say goodbye to the hassle of tweaking your theme's `functions.php` file and downloading endless plugins – Code Snippets simplifies the process!

A snippet is like a mini-plugin for your WordPress site, providing added functionality without the clutter.

Unlike other solutions that involve dumping code into your `functions.php` file, Code Snippets offers an intuitive graphical interface for seamless integration and real-time execution. Managing snippets is as easy as activating and deactivating plugins, only without the bloat and overhead.

**🎥 Watch a quick overview by Imran Siddiq:**

https://youtu.be/uzND-wdSCMQ

☁️ Each copy of Code Snippets includes full integration with the community-powered [Code Snippets Cloud](https://codesnippets.cloud/) platform, providing easy access to hundreds of tweaks and enhancements ready to power-up any WordPress site.

**🌐 Connect with us:**

* [Support Forum](https://wordpress.org/support/plugin/code-snippets)
* [Facebook Community](https://facebook.com/groups/codesnippetsplugin)
* [Discord Community](https://snipco.de/discord)
* [GitHub Repository](https://github.com/codesnippetspro/code-snippets)

🌟 Like our plugin? Find it useful? Please consider sharing your experience by [leaving a review on WordPress.org](https://wordpress.org/support/view/plugin-reviews/code-snippets). Your feedback is instrumental to shaping our future growth!

🌍 We'd like to thank the wonderful people who have helped contribute translations to allow Code Snippets to be used in different languages. [You can find a full list here](https://github.com/codesnippetspro/code-snippets/blob/core/CREDITS.md#translators).

== Installation ==

= Automatic Installation =

1. Log into your WordPress admin
2. Click __Plugins__

3. Click __Add New__

4. Search for __Code Snippets__

5. Click __Install Now__ under "Code Snippets"
6. Activate the plugin

= Manual Installation =

1. Download the plugin
2. Extract the contents of the zip file
3. Upload the contents of the zip file to the `wp-content/plugins/` folder of your WordPress installation
4. Activate the Code Snippets plugin from 'Plugins' page.

Network Activating Code Snippets through the Network Dashboard will enable a special interface for running snippets across the entire network.

== Frequently Asked Questions ==

A full list of our Frequently Asked Questions can be found at [codesnippets.pro](https://codesnippets.pro/docs/faq/).

= How can I recover my site if it is crashed by a buggy snippet? =
You can recover your site by enabling the Code Snippets safe mode feature. Instructions for how to turn it on are available here: <https://codesnippets.pro/doc/safe-mode/>.

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

= 3.10.0 (2026-08-24) =

__Added__

* New admin interface for managing snippets, with a cleaner layout, faster interactions, and a more consistent experience across plugin screens.
* Card view for browsing snippets, with a view switcher on the snippets table and Community Cloud.
* Snippet preview modal for viewing snippet code from the snippets table without opening the editor.
* Automatic hiding of unrelated admin notices from other plugins on Code Snippets screens.
* Admin bar snippet drawer, based on the Deckerweb Snippets workflow, for quick access to snippets and Safe Mode from the WordPress admin bar.
* Snippet locking to help prevent accidental edits or deletion of important snippets. Props to https://github.com/mgiannopoulos24.
* Improved screen options on the main snippets table, including controls for visible columns and truncating long snippet names or descriptions.
* Bulk actions and bulk code download support in the redesigned snippets table.
* Featured snippets and improved browsing in Community Cloud.
* WordPress modern theme admin styling compatibility.
* Clearer accessibility labels, headings, tab markup, table checkboxes, sort buttons, copy buttons, and drag-and-drop upload controls.

__Changed__

* Redesigned the main snippets table with improved search, filtering, sorting, pagination, row actions, and bulk selection.
* Improved the snippet import and migration experience, including clearer file upload handling and third-party plugin migration flows.
* Improved snippet error handling so activation failures, validation errors, and stack traces are easier to understand.
* Improved Community Cloud search and filtering, including server-side filters, better result loading, and clearer empty states.
* Updated the welcome screen, toolbar, import screen, and cloud screens to match the new admin experience.
* Updated internal plugin architecture to a cleaner PSR-4 structure for better long-term maintainability.
* Improved accessibility across the snippets table, import screen, migration flow, Community Cloud, welcome screen, toolbar, dialogs, tooltips, and code editor.
* Improved colour contrast and reduced-motion support across admin screens.

__Fixed__

* Fixed REST API server error responses on missing snippets.
* Fixed redundant frontend logic, improving overall performance.
* Fixed Community Cloud search results and pagination to respect WordPress screen options.
* Fixed snippet saving and activation feedback to improve validation and runtime error display.
* Fixed downloaded Community Cloud snippets appearing as not downloaded after a page reload.
* Fixed network snippet lookups using the wrong database table on multisite.
* Fixed the inactive snippets count including trashed snippets.
* Fixed featured Community Cloud snippets failing to load with some cloud API responses.

= 3.9.6 (2026-04-28) =

__Fixed__

* Improved permissions handling with snippets REST API.
* Site admin cannot toggle shared network snippets status.

= 3.9.5 (2026-02-05) =

__Fixed__

* Improved security when handling actions for downloading and updating cloud snippets.

= 3.9.4 (2026-01-14) =

__Added__

* New import functionality to migrate snippets from file uploads with drag-and-drop interface.
* Support for importing snippets from other popular plugins (Header Footer Code Manager, Insert Headers and Footers, Insert PHP Code Snippet).
* Enhanced file based execution support with improved multisite mode compatibility.

__Fixed__

* Fixed multisite capability checks in Plugin class.
* Fixed snippet execution logic for multisite support by centralizing trashed snippet handling.
* Fixed multisite snippet handling to ensure local snippets use correct table and filter out trashed snippets.

= 3.9.3 (2025-12-03) =

__Added__

* End-to-end tests to verify the toggle visual state in the snippets list page, improving UI verification and test reliability.

__Fixed__

* Restored missing styles styling and direction-aware layout from Manage menu.
* Ensure correct transformation value is used when detecting state of activation toggle.

= 3.9.2 (2025-11-17) =

__Changed__

* Introduced a custom scissors icon and updated button title for the TinyMCE extension.
* Improved back-navigation styling on the edit page.
* Refined layout for column names and action buttons in the Cloud Snippets list.
* Enhanced overall styling of cloud-related UI components.
* Optimized cloud search with more efficient pagination and snippet retrieval.
* Introduced groundwork to prevent Composer dependency collisions with other plugins.

__Fixed__

* Improved sanitization and normalization across Cloud API and pagination outputs.
* Resolved various TinyMCE issues reported in the WordPress support forum.

= 3.9.1 (2025-11-14) =

__Changed__

* Migrated to native CSS direction handling (RTL/LTR) for improved compatibility and simpler styling
* Updated dependencies to the latest compatible versions

__Fixed__

* Fixed TinyMCE menu button registration to prevent initialization failure
* Fixed the position of the 'code direction' control in the editor

= 3.9.0 (2025-11-13) =

__Added__

* Added contextual notices in the Snippets list table to surface action results and warnings in the UI
* Expanded Multisite Sharing settings for clearer control over network-wide snippet sharing

__Changed__

* Modernized browser support targets and polished admin UI (clearer row-action badges, improved Pro badge hover, refined active snippet name styling)

__Fixed__

* Fixed REST API pagination to return correct results and page counts
* Resolved styling selector so the active snippet name highlights reliably

= 3.8.2 (2025-10-31) =

__Fixed__

* Improved namespaced PHP snippet handling with file based execution.

= 3.8.1 (2025-10-28) =

__Added__

* Code line explanation widget with apply and remove actions for AI-generated comments. (PRO)

__Changed__

* Improved pagination handling and display structure for cloud search results. (PRO)
* Enhanced styling for codevault rows and inactive tabs in cloud interface. (PRO)

__Removed__

* Removed `guzzlehttp/guzzle` dependency to reduce package conflicts. (PRO)

__Fixed__

* Improved file-based snippet handling for multisite installations.

= 3.8.0 (2025-10-24) =

__Added__

* @CarolinaOP and @louiswol94 join the team as plugin contributors.
* File-based execution mode for snippets (Optional in Plugin Settings).
* Version switch option, to help easily rollback the plugin to an earlier release.
* Minor UI improvements to the editor and sidebar.

__Changed__

* Prefixed Composer packages to reduce collisions with other plugins.
* Snippets REST API now supports pagination via page and per_page query parameters.
* Improved editor preview behavior.

__Fixed__

* Fixed issues with snippet evaluation and front-end initialization in edge cases.
* Improved reliability of snippet evaluation.
* JavaScript and CSS snippets loading twice due to a conditions bug. (PRO)
* Fixed issue where some conditions didn’t work due to loading before the loop. (PRO)

= 3.7.0 (2025-08-29) =

__Added__

* New 'conditions' feature: control where and when snippets execute with a powerful logic builder. (PRO)

__Changed__

* Redesigned edit menu with refreshed look and functionality.
* Updated snippet type badges to be more visually distinct.
* Redesigned tooltips used throughout the plugin.
* Moved content snippet shortcode options into separate modal window.
* Updated snippet tag editor to use built-in WordPress tag editor.
* Created proper form for sharing beta feedback.
* Improved UX of snippet activation toggle.

__Fixed__

* Fetching active snippets on a multisite network now respects the 'priority' field above all else when ordering snippets.
* Cloud search appears correctly and allows downloading snippets in the free version of Code Snippets.
* Improved performance of loading admin menu icon.

= 3.6.9 (2025-02-17) =

__Changed__

* Updated `Cloud_API::get_bundles()` to properly check bundle data and return an empty array if no valid bundles are present.
* Refactored `Cloud_List_Table::fetch_snippets()` to always return a valid `Cloud_Snippets` instance.
* Cleaned up bundle iteration code and improved translation handling in the bundles view.

__Fixed__

* Fixed errors in bundle iteration by adding a check for the bundles array before iterating.

**[The full changelog is available on GitHub](https://github.com/codesnippetspro/code-snippets/blob/core/CHANGELOG.md)**
