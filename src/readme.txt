=== Code Snippets ===
Contributors: bungeshea, ver3, lightbulbman, 0aksmith, johnpixle
Donate link: https://codesnippets.pro
Tags: code, snippets, multisite, php, css
License: GPL-2.0-or-later
License URI: license.txt
Stable tag: 3.7.0
Tested up to: 6.8.2

An easy, clean and simple way to enhance your site with code snippets.

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

= 3.6.8 (2025-02-14) =

__Added__

* `code_snippets/hide_welcome_banner` filter hook for hiding welcome banner in dashboard.

__Changed__

* Updated Freemius SDK to the latest version. (PRO)

__Removed__

* Functionality allowing `[code_snippet]` shortcodes to be embedded recursively – it will be re-added in a future version.

__Fixed__

* Shortcodes embedded within `[code_snippet]` shortcodes not evaluating correctly.
* Translation functions being called too early in some instances when loading plugin settings.
* 'Generate' button not appearing on some sites. (PRO)
* Incorrect arrow entity used in cloud list table (props to [brandonjp]).
* Removed reference to missing plugins.css file in core plugin version.

= 3.6.7 (2025-01-24) =

__Added__

* Generated snippet shortcode tags will include the snippet name, for easier identification.
* Admin notices will dismiss automatically after five seconds. ([#208](https://github.com/codesnippetspro/code-snippets/issues/208))

__Changed__

* Updated CSS to use latest Sass features.
* Moved theme selector to just above editor preview on settings page (thanks to brandonjp). ([#206](https://github.com/codesnippetspro/code-snippets/issues/206))
* `[code_snippet]` shortcodes can now be nested within each other. ([#198](https://github.com/codesnippetspro/code-snippets/issues/198))

__Fixed__

* Save buttons above editor did not follow usual validation process in Pro. (PRO) ([#197](https://github.com/codesnippetspro/code-snippets/issues/197))
* Minor inconsistencies in consistent UI elements between Core and Pro.
* Tags input not allowing input. ([#211](https://github.com/codesnippetspro/code-snippets/issues/211))
* Issue with Elementor source code widget. (PRO) ([#205](https://github.com/codesnippetspro/code-snippets/issues/205))
* Snippet descriptions not visible when viewing cloud search results.
* Snippet import page not displaying number of successfully imported snippets.
* Use UTC time when deciding when to display campaign notices.

= 3.6.6.1 (2024-11-27) =

__Fixed__

* Redeployment of v3.6.6 to overcome issue with initial build.
* Type issue when caching cloud links. (PRO)

= 3.6.6 (2024-11-27) =

__Changed__

* Improved compatability with modern versions of PHP.
* Extended welcome API to include admin notices.

__Fixed__

* Memory issue from checking aggregate posts while loading front-end syntax highlighter.
* Translation functions being called too early on upgrade, resulting in localisation loading errors.
* Bug preventing the 'share on network' status of network snippets from correctly updating.
* Incorrect logic controlling when to display 'Save Changes' or 'Save Changes and Activate' buttons.
* Old notices persisting when switching between editing and creating snippets.

= 3.6.5.1 (2024-05-24) =

* Redeployment of v3.6.5 to overcome issue with initial build.

= 3.6.5 (2024-05-24) =

__Added__

* New admin menu providing useful resources and updates on the Code Snippets plugin and community.

= 3.6.4 (2024-03-15) =

__Added__

* AI generation for all snippet types: HTML, CSS, JS. (PRO)
* Button to create a cloud connection directly from the Snippets menu when disconnected. (PRO)

__Changed__

* Increment the revision number of CSS and JS snippet when using the 'Reset Caches' debug action. (PRO)
* UX in generate dialog, such as allowing 'Enter' to submit the form. (PRO)

__Fixed__

* Minor type compatability issue with newer versions of PHP.
* Undefined array key issue when initiating cloud sync. (PRO)
* Bug preventing downloading a single snippet from a bundle. (PRO)
* Translations not loading for strings in JavaScript files.

= 3.6.3 (2023-11-13) =

__Added__

* Added debug action for resetting snippets caches.

__Fixed__

* Import error when initialising cloud sync configuration. (PRO)

= 3.6.2 (2023-11-11) =

__Removed__

* Removed automatic encoding of code content.

__Fixed__

* Error when attempting to save shared network snippets marked as active.
* Type error when rendering checkbox fields without a stored or default value.
* Label for snippet sharing input incorrectly linked to input field.
* Error when attempting to download export files from Edit menu.
* Issue loading Freemius string overrides too early. (PRO)
* Fix redirect URL when connecting with OAuth on subdirectory or HTTPS sites. (PRO)
* Import error when attempting to completely uninstall the plugin.

= 3.6.1 (2023-11-07) =

__Fixed__

* Issue accessing fields on Snippets class.

= 3.6.0 (2023-11-07) =

__Added__

* Ability to authenticate with Code Snippets Cloud using OAuth. (PRO)
* Integration with GPT AI for generating snippets. (PRO)
* Ability to generate line-by-line descriptions of snippet code with GPT AI. (PRO)
* Ability to generate tags and description text from existing snippet code with GPT AI. (PRO)
* Added debug settings menu for manually performing problem-solving actions.
* Filter to disable scroll-into-view functionality for edit page notices.

__Changed__

* Updated minimum PHP requirement to 7.4.
* Ensure that the URL of the edit snippet page changes when adding a new snippet.
* Snippet tags will automatically be added when focus is lost on the tags field.

__Fixed__

* Moved active status border on edit name field to left-hand side.
* New notices will not scroll if already at top of page.
* Potential CSRF vulnerability allowing an authenticated user to reset settings.

= 3.5.1 (2023-09-15) =

__Fixed__

* Undefined array key error when accessing plugin settings page. (PRO)
* Issue registering API endpoints affecting edit post screen. (PRO)
* Snippet ID instead of snippet object being passed to `code_snippets/update_snippet` action hook.

= 3.5.0 (2023-09-13) =

__Added__

* Support for the Code Snippets Cloud API.
* Search and download public snippets.
* Codevault back-up and synchronisation. (PRO)
* Synchronised local snippets are automatically updated in Cloud. (PRO)
* Bulk actions - 'update' and 'download'.
* Download snippets from public and private codevaults. (PRO)
* Search and download any publicly viewable snippet in Code Snippet Cloud by keyword or name of codevault. (PRO)
* Deploy snippets to plugin from Code Snippets Cloud app. (PRO)
* Bundles of Joy! Search and download Snippet Bundles in one go direct from Code Snippets Cloud. (PRO)

__Changed__

* Redirect to snippets table when deleting snippet from the edit menu.
* Scroll new notices into view on edit menu.

__Fixed__

* Error when attempting to update network shared snippets after saving. [[#](https://wordpress.org/support/topic/activating-snippets-breaks-on-wordpress-6-3/)]

**[The full changelog is available on GitHub](https://github.com/codesnippetspro/code-snippets/blob/core/CHANGELOG.md)**
