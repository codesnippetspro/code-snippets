<?php

/**
 * Code Snippets - An easy, clean and simple way to add code snippets to your site.
 *
 * If you're interested in helping to develop Code Snippets, or perhaps
 * contribute to the localization, please see https://github.com/sheabunge/code-snippets
 *
 * @package   Code_Snippets
 * @version   2.3.0
 * @author    Shea Bunge <http://bungeshea.com/>
 * @copyright Copyright (c) 2012-2015, Shea Bunge
 * @link      http://bungeshea.com/plugins/code-snippets/
 * @license   http://opensource.org/licenses/MIT
 */

/*
Plugin Name: Code Snippets
Plugin URI:  http://bungeshea.com/plugins/code-snippets/
Description: An easy, clean and simple way to add code snippets to your site. No need to edit to your theme's functions.php file again!
Author:      Shea Bunge
Author URI:  http://bungeshea.com
Version:     2.3.0
License:     MIT
License URI: license.txt
Text Domain: code-snippets
Domain Path: /languages
*/

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The version number for this release of the plugin.
 * This will later be used for upgrades and enqueueing files
 *
 * This should be set to the 'Plugin Version' value,
 * as defined above in the plugin header
 *
 * @since 2.0
 * @var string A PHP-standardized version number string
 */
define( 'CODE_SNIPPETS_VERSION', '2.3.0' );

/**
 * The full path to the main file of this plugin
 *
 * This can later be passed to functions such as
 * plugin_dir_path(), plugins_url() and plugin_basename()
 * to retrieve information about plugin paths
 *
 * @since 2.0
 * @var string
 */
define( 'CODE_SNIPPETS_FILE', __FILE__ );

/**
 * Load plugin files
 */
foreach ( array(

	/* Database operations functions */
	'db.php',

	/* Capability functions */
	'caps.php',

	/* Snippet operations functions */
	'snippet-ops.php',

	/* Upgrade function */
	'upgrade.php',

	/* General Administration functions */
	'admin.php',

	/* CodeMirror editor functions */
	'editor.php',

	/* Manage snippets component */
	'manage/manage.php',

	/* Edit snippet component */
	'edit/edit.php',

	/* Import snippets component */
	'import/import.php',

	/* Settings component */
	'settings/editor-preview.php',
	'settings/settings-fields.php',
	'settings/settings.php',
	'settings/admin.php',

	) as $include ) {

	require plugin_dir_path( __FILE__ ) . "includes/$include";
}

/* Initialize database table variables */
set_snippet_table_vars();

/* Execute the snippets once the plugins are loaded */
add_action( 'plugins_loaded', 'execute_active_snippets', 1 );

/**
 * Load up the localization file if we're using WordPress in a different language.
 * Place it in this plugin's "languages" folder and name it "code-snippets-[language_COUNTRY].mo"
 *
 * If you wish to contribute a language file to be included in the Code Snippets package,
 * please see create an issue on GitHub: https://github.com/sheabunge/code-snippets/issues
 */
function code_snippets_load_textdomain() {
	load_plugin_textdomain( 'code-snippets', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'plugins_loaded', 'code_snippets_load_textdomain' );
