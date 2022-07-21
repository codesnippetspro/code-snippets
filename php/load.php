<?php
/**
 * Initialise and load the plugin under the proper namespace
 *
 * @package Code_Snippets
 */

namespace Code_Snippets;

/**
 * The version number for this release of the plugin.
 * This will later be used for upgrades and enqueuing files.
 *
 * This should be set to the 'Plugin Version' value defined
 * in the plugin header.
 *
 * @var string A PHP-standardized version number string.
 */
const PLUGIN_VERSION = '3.2.0';

/**
 * The full path to the main file of this plugin.
 *
 * This can later be used with functions such as
 * plugin_dir_path(), plugins_url() and plugin_basename()
 * to retrieve information about plugin paths.
 *
 * @var string
 */
const PLUGIN_FILE = CODE_SNIPPETS_FILE;

/**
 * Name of the group used for caching data.
 *
 * @var string
 */
const CACHE_GROUP = 'code_snippets';

/* Load dependencies with Composer */
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * Retrieve the instance of the main plugin class.
 *
 * @since 2.6.0
 * @return Plugin
 */
function code_snippets() {
	static $plugin;

	if ( is_null( $plugin ) ) {
		$plugin = new Plugin( PLUGIN_VERSION, PLUGIN_FILE );
	}

	return $plugin;
}

code_snippets()->load_plugin();

/* Execute the snippets once the plugins are loaded */
add_action( 'plugins_loaded', __NAMESPACE__ . '\execute_active_snippets', 1 );
