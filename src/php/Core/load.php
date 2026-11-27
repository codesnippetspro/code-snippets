<?php
/**
 * Initialize and load the plugin under the proper namespace.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets;

use Composer\Autoload\ClassLoader;

defined( 'ABSPATH' ) || exit;

/**
 * The version number for this release of the plugin.
 * This will later be used for upgrades and enqueuing files.
 *
 * This should be set to the 'Plugin Version' value defined
 * in the plugin header.
 *
 * @var string A PHP-standardized version number string.
 */
const PLUGIN_VERSION = CODE_SNIPPETS_VERSION;

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
 * Base name of the group used for caching data.
 *
 * @var string
 */
const CACHE_GROUP_BASE = 'code_snippets';

/**
 * Name of the group used for caching data.
 *
 * Scoped to the plugin version, so data cached by one version is never read by another.
 *
 * @var string
 */
const CACHE_GROUP = CACHE_GROUP_BASE . '_' . PLUGIN_VERSION;

/**
 * Namespace used for REST API endpoints.
 *
 * @var string
 */
const REST_API_NAMESPACE = 'code-snippets/v';

/**
 * Load the Composer autoloader.
 *
 * After loading, remove any PSR-4 namespace mappings that do not start with our vendor prefix but have a corresponding
 * prefixed version. These are not removed by Imposter and would cause collisions with other plugins that use the same libraries.
 *
 * @var ClassLoader $code_snippets_autoloader Composer autoloader instance.
 */
$code_snippets_autoloader = require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

if ( $code_snippets_autoloader instanceof ClassLoader ) {
	$code_snippets_vendor_prefix = __NAMESPACE__ . '\\Vendor\\';

	$code_snippets_prefixes = $code_snippets_autoloader->getPrefixesPsr4();

	foreach ( $code_snippets_prefixes as $code_snippets_namespace => $code_snippets_paths ) {
		// Remove any non-Code_Snippets namespace that has a corresponding prefixed version.
		if ( false === strpos( $code_snippets_namespace, $code_snippets_vendor_prefix ) ) {
			if ( isset( $code_snippets_prefixes[ $code_snippets_vendor_prefix . $code_snippets_namespace ] ) ) {
				$code_snippets_autoloader->setPsr4( $code_snippets_namespace, [] );
			}
		}
	}
}

/**
 * Retrieve the instance of the main plugin class.
 *
 * @return Plugin
 * @since 2.6.0
 */
function code_snippets(): Plugin {
	static $plugin;

	if ( is_null( $plugin ) ) {
		$plugin = new Plugin();
	}

	return $plugin;
}

// WordPress deletes the plugin by running uninstall.php without loading the plugin. That file loads this one for the
// plugin's functions and classes, and the plugin must not start while its data is being removed.
if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	code_snippets()->load_for_uninstall();
} else {
	code_snippets()->load_plugin();
}
