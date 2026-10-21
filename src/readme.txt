=== Code Snippets ===
Contributors: bungeshea, ver3, lightbulbman, 0aksmith, johnpixle, carolinaop, tallblokeuk
Donate link: https://codesnippets.pro
Tags: code, snippets, php, functions, multisite
License: GPL-2.0-or-later
License URI: license.txt
Stable tag: 4.0.0
Requires at least: 5.5
Tested up to: 7.1
Requires PHP: 7.4

Manage custom WordPress code in one dashboard. Create PHP and HTML snippets, preview code, track snippet totals and reuse code across sites.

== Description ==

**Code Snippets** gives you a dedicated WordPress dashboard for creating, organising and managing custom code. Add PHP functionality and reusable HTML content, choose where PHP snippets run, and activate or deactivate snippets individually.

Find snippets with search, tags and filters, switch between table and card views, and preview code without opening the editor. Lock important snippets to help prevent accidental changes, and use the Stats overview to see snippet totals and breakdowns.

= Watch the Code Snippets 4.0 walkthrough =

See the plugin in use, including features from both the free and Pro editions. The Pro features listed below require the relevant Pro access.

https://www.youtube.com/watch?v=q-QT9PXw2eQ

**Want AI-assisted code creation and advanced controls?** [Code Snippets Pro](https://codesnippets.pro/pricing/) adds the AI Agent, snippet revisions, visual display conditions, CSS and JavaScript snippets, and connected Cloud workflows.

Your snippets stay separate from your theme files. Browse Community Cloud for shared snippets, and use import and export to reuse your code across sites.

Code Snippets has been available on WordPress.org since 2012 and is active on 1 million+ WordPress installations.

= Included in the free plugin =

* **PHP snippets without editing theme files.** Add custom functions and WordPress hooks from your dashboard.
* **Reusable HTML content.** Create content snippets and insert them into posts and pages with shortcodes.
* **Control where PHP runs.** Choose everywhere, administration only or front end only. Run-once snippets are available for manually confirmed one-time tasks.
* **A built-in code editor.** Work with syntax highlighting, code checking and adjustable editor preferences.
* **Organised snippet management.** Use names, descriptions, tags, search, filters, sorting and bulk actions. Browse in table or card view, preview code without opening the editor, and lock important snippets to help prevent accidental edits or deletion.
* **Stats overview.** View snippet totals, active and inactive counts, and breakdowns by type, run location and tags.
* **Import and export.** Move snippets between sites using export files, or export PHP snippets as a PHP file for use without the plugin.
* **Community Cloud browsing.** Search community-shared snippets from inside WordPress and download supported snippet types.
* **Optional file-based execution.** Execute supported snippets from generated files instead of evaluating their code from the database.
* **Multisite support.** Manage network-wide snippets and separate snippets for individual sites.
* **Safe Mode and feedback tools.** Use Safe Mode to troubleshoot snippet-related failures. An optional feedback reporter can be enabled in settings.

PHP snippets execute code on your site. Only use code you trust, review it before activation and keep a current backup. Safe Mode helps with recovery; it does not make every snippet safe or guarantee that every error can be prevented.

= Community-powered Code Snippets Cloud =

Browse the community-powered [Code Snippets Cloud](https://codesnippets.cloud/) library from inside WordPress. Search user-shared snippets, preview their code and download snippet types supported by your edition.

A Cloud account is not required to create and run local PHP and HTML snippets. Personal Cloud library, synchronisation and deployment workflows require a connected account and the relevant Pro access.

= Code Snippets Pro =

[Code Snippets Pro](https://codesnippets.pro/pricing/) extends the free plugin with additional code types, editing tools and connected workflows:

* **CSS and JavaScript snippets**, with dedicated integration for Gutenberg and Elementor.
* **AI Agent.** Describe the functionality you need, review and refine a proposed plan, then approve code generation. Review the generated snippets before activating them.
* **Snippet revisions.** Compare a previous version with the current code and restore an earlier revision.
* **Display conditions.** Define where and when snippets run through a visual condition editor.
* **Form-based Blueprints.** Generate PHP code for common tasks such as custom post types, taxonomies, shortcodes, settings pages and extra user profile fields.
* **Role-based permissions.** Control access to viewing, creating, editing and deleting snippets.
* **Ran on this page.** See which snippets executed or produced output on the current request through the admin bar.
* **Performance and security insights.** Optional PHP execution-time tracking and advisory checks for potentially risky code patterns. These checks are not a security audit or a guarantee that code is safe.
* **Connected Cloud workflows.** Manage your personal Cloud library, synchronise snippets and deploy snippets to connected sites, with drift reporting when site and Cloud copies differ.

Cloud-connected Pro features require the appropriate licence and a connected Code Snippets Cloud account. Local PHP and HTML snippets do not require a Cloud account. The free plugin includes interactive demos of selected Pro workflows; using the full tools requires Pro access.

[Compare Free and Pro](https://codesnippets.pro/pricing/)

= Documentation and community =

* [Documentation](https://codesnippets.pro/docs/)
* [WordPress.org support forum](https://wordpress.org/support/plugin/code-snippets/)
* [Facebook community](https://www.facebook.com/groups/codesnippetsplugin/)
* [Discord community](https://snipco.de/discord)
* [Source code and feature requests](https://github.com/codesnippetspro/code-snippets)

= Share your experience =

Find Code Snippets useful? [Leave a review on WordPress.org](https://wordpress.org/support/plugin/code-snippets/reviews/) to share your experience and help others decide whether the plugin suits their needs.

= Translation Credits =

Thank you to everyone who helps make Code Snippets available in different languages. See the [translation contributors](https://github.com/codesnippetspro/code-snippets/blob/core/CREDITS.md#translators) or [contribute a translation](https://translate.wordpress.org/projects/wp-plugins/code-snippets/).

== Installation ==

= Automatic Installation =

1. Open **Plugins > Add New** and search for **Code Snippets**.
2. Click **Install Now**, then **Activate**.
3. Open the **Snippets** menu to add or manage your code.

= Manual Installation =

1. Download and unzip the plugin.
2. Upload the `code-snippets` directory to `wp-content/plugins/`.
3. Activate **Code Snippets** from the **Plugins** screen.

= Multisite Installation =

Network activate the plugin to manage network-wide snippets from Network Admin. Individual sites can also have their own snippets. Network snippets must be activated or shared with the appropriate settings; simply creating a network snippet does not make it run everywhere.

== Frequently Asked Questions ==

= How can I recover my site if a snippet causes an error? =

Use Code Snippets Safe Mode to prevent snippet execution while you investigate the problem. Follow the [Safe Mode instructions](https://codesnippets.pro/doc/safe-mode/) for the appropriate recovery method, then fix or deactivate the affected snippet.

= Will I lose my snippets if I change theme or update WordPress? =

Snippets are stored separately from your theme in the WordPress database, so changing theme or updating WordPress does not remove them. Code that depends on a particular theme or plugin may still need updating when that dependency changes.

= Can the plugin be completely uninstalled? =

Enable **Complete Uninstall** under **Snippets > Settings > Advanced** before deleting the plugin through WordPress's Plugins screen if you want stored snippets and plugin settings removed. Export snippets you want to keep first. Leave Complete Uninstall disabled if you want to retain your snippet data when deleting the plugin.

= Can I copy snippets to another site? =

Yes. Export a single snippet or select several snippets and use the export bulk action. Import the resulting file through the Snippets import screen on the other site.

= Can I export snippets to PHP for a site without Code Snippets? =

Yes. Select the PHP snippets, choose **Export to PHP** from the bulk actions and apply it. The generated file contains the exported code, with snippet names and descriptions in comments. Review the file before including it in a theme or plugin.

= Can I run snippets across a multisite network? =

Yes. Network activation provides a network-level Snippets screen. Activate a network snippet to run it across the network, subject to its execution scope. Individual sites can also have separate snippets, and shared network snippets can be enabled per site where configured.

= Where are snippets stored in the database? =

Local snippets use the site's table prefix followed by `snippets`, for example `wp_snippets` on a single site or `wp_2_snippets` on a subsite. Network snippets use the network's base prefix followed by `ms_snippets`, for example `wp_ms_snippets`. Your actual prefix may differ.

When optional file-based execution is enabled, the plugin also creates files used to execute snippets. Continue managing those snippets through the plugin.

= Where can I get help or request a feature? =

Use the [documentation](https://codesnippets.pro/docs/) or [WordPress.org support forum](https://wordpress.org/support/plugin/code-snippets/) for help. Report bugs and suggest improvements on [GitHub](https://github.com/codesnippetspro/code-snippets/issues), or discuss ideas in the [Facebook](https://www.facebook.com/groups/codesnippetsplugin/) and [Discord](https://snipco.de/discord) communities.

= How can I contribute? =

Code Snippets is developed on [GitHub](https://github.com/codesnippetspro/code-snippets). Bug reports, pull requests and [translations](https://translate.wordpress.org/projects/wp-plugins/code-snippets/) are welcome.

= How do I report a security issue? =

Report suspected security vulnerabilities privately through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/code-snippets). Do not disclose vulnerability details in a public GitHub issue or support topic.

= What is new in Code Snippets 4.0? =

The free 4.0 release adds the Stats overview and fixes issues with snippet saving feedback, description truncation, Elementor compatibility and translation loading.

The free plugin also includes table and card views, code preview, HTML content snippets and optional file-based execution.

Pro 4.0 adds the AI Agent and snippet revisions, alongside its additional code types, conditions, permissions, Blueprints and connected Cloud tools. See the Pro section above for the current feature split.

= Can I add HTML, CSS and JavaScript snippets? =

The free plugin supports PHP snippets and HTML content snippets. Dedicated CSS and JavaScript snippet types, plus the additional Gutenberg and Elementor integrations, are available in Pro.

= Do I need a Cloud account? =

No account is required to create and run local PHP and HTML snippets. Community Cloud provides access to shared snippets from within the plugin. Personal Cloud library, synchronisation and connected Pro workflows require an account and the relevant Pro access.

= Will I need to recreate my existing snippets after updating? =

No. Updating the plugin does not require you to recreate your stored snippets. Back up your site and test your custom code when updating WordPress, your theme or other plugins. Compatibility still depends on the code you use and its dependencies.

= Can I switch to an earlier plugin version? =

Users with permission to update plugins can open **Snippets > Settings > Advanced** and use **Switch Version** to choose an available version. Use **Refresh Versions** if the list needs updating. Back up your site and export important snippets before switching versions.

== Screenshots ==

1. Managing local snippets in table view.
2. Creating a PHP snippet in the code editor.
3. Browsing local snippets in card view.
4. Stats overview with snippet totals and breakdowns.
5. Browsing Community Cloud snippets, with Pro-only items clearly marked.
6. Importing snippet export files with duplicate-handling options.

== Upgrade Notice ==

= 4.0.0 =
Adds the Stats overview and fixes saving feedback, description display, Elementor compatibility and translation notices. Back up your site before updating.

== Changelog ==

= 4.0.0 (2026-10-12) =

__Added__

* Stats page for viewing snippet totals and breakdowns by type, run location and tags.
* Optional feedback reporter for sending bug reports, feature requests and general feedback from plugin screens.
* Interactive demos of the AI Agent, Blueprints and Cloud Library in the free plugin. The full tools require Pro access.
* AI Agent for building snippets from a description, with a plan to review and refine before code generation. (PRO)
* Snippet revisions, with a history of past versions, a side-by-side comparison and restoration of an earlier revision. (PRO)
* Role-based permissions for viewing, creating, editing and deleting snippets. (PRO)
* "Ran on this page" tracking for snippets that executed or produced output on the current request. (PRO)
* Optional PHP execution-time tracking and advisory checks for potentially risky code patterns. These checks do not certify code as safe. (PRO)
* Support for deploying snippets from Code Snippets Cloud to connected sites, including linking multiple snippets to one shared display condition. Requires a connected Cloud account and the appropriate Pro access. (PRO)
* Support for Cloud drift detection through site snippet inventory and code hashes. Requires a connected Cloud account and the appropriate Pro access. (PRO)

__Fixed__

* Fixed a fatal error in the Elementor editor on Elementor versions older than 3.19.
* Fixed snippet descriptions staying truncated after the "Truncate long snippet names and descriptions" screen option
  was switched off.
* Fixed saving, activating or deactivating a snippet reporting its previous state, which could leave a deactivated
  snippet running under file-based execution.
* Fixed the snippet editor reporting a save as failed and undelivered when no response was received, which it cannot
  determine.
* Fixed translations being loaded too early, with a notice from WordPress, when posts were queried while plugins were
  still loading.

= 3.10.2 (2026-09-01) =

__Added__

* Added a confirmation flow for run-once snippet execution and hardened the handler to prevent failed or confusing actions.

__Changed__

* Snippet names now respect the row truncation Screen Option in the admin list for better readability.
* Version switching AJAX requests now validate the correct nonce, improving reliability when updating snippet versions.

__Fixed__

* Fixed safe mode fatal errors caused by an undefined wp_get_current_user() call.
* Fixed PHP validation being triggered incorrectly when activating snippets in bulk.
* Fixed saving issues after a user session expires.
* Fixed warnings caused by aliased field names when reading modified snippet fields.
* Fixed vendor autoloader issues by stripping unprefixed namespace references.
* Resolved issues where snippet names and version switches could behave inconsistently in the admin UI.

= 3.10.1 (2026-08-28) =

__Changed__

* Enhanced active-link visibility in the toolbar with border-based styling.

__Fixed__

* Fixed a fatal error affecting snippets that use a `namespace` or `declare` statement.
* Fixed the snippets page rendering blank when another plugin's screen settings filter returned an invalid value.
* Fixed snippet saving on hosts that block REST API `PUT` and `PATCH` requests, by sending writes as `POST` with a method override.
* Fixed the Snippets List Order setting not being applied to the snippets list.
* Fixed admin bar snippet scripts failing to load on the free version, including on the site front end.
* Fixed snippet modified dates being sent without the correct UTC offset.
* Fixed the row truncation screen option not persisting across sessions.
* Fixed inconsistent sidebar label sizes.
* Fixed the sticky editor sidebar showing an unwanted horizontal scrollbar.

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
