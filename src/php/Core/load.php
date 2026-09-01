<?php
/**
 * Initialise and load the plugin under the proper namespace.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets;

use Composer\Autoload\ClassLoader;

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
 * Name of the group used for caching data.
 *
 * @var string
 */
const CACHE_GROUP = 'code_snippets';

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
 * prefixed version, as these are not removed by Imposter and would cause collisions with other plugins that use the same
 * libraries.
 *
 * @var ClassLoader $autoloader Composer autoloader instance.
 */
$autoloader = require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

if ( $autoloader instanceof ClassLoader ) {
	$vendor_prefix = __NAMESPACE__ . '\\Vendor\\';

	$prefixes = $autoloader->getPrefixesPsr4();

	foreach ( $prefixes as $namespace => $paths ) {
		// Remove any non-Code_Snippets namespace that has a corresponding prefixed version.
		if ( false === strpos( $namespace, $vendor_prefix ) ) {
			if ( isset( $prefixes[ $vendor_prefix . $namespace ] ) ) {
				$autoloader->setPsr4( $namespace, [] );
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

code_snippets()->load_plugin();
