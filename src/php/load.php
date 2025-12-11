<?php
/**
 * Initialise and load the plugin under the proper namespace.
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

// Load dependencies with Composer.
$code_snippets_autoloader = require dirname( __DIR__ ) . '/vendor/autoload.php';

// Remove all original (non-prefixed) vendor namespace mappings to prevent collisions with other plugins.
// Since Imposter rewrites namespaces to Code_Snippets\Vendor\*, we need to remove the original PSR-4
// mappings that Composer generates so other plugins can load their own copies of these libraries.
if ( $code_snippets_autoloader instanceof \Composer\Autoload\ClassLoader ) {
	$code_snippets_prefixes = $code_snippets_autoloader->getPrefixesPsr4();
	$code_snippets_vendor_prefix = 'Code_Snippets\\Vendor\\';
	
	foreach ( $code_snippets_prefixes as $code_snippets_namespace => $code_snippets_paths ) {
		// Remove any non-Code_Snippets namespace that has a corresponding prefixed version
		if ( strpos( $code_snippets_namespace, $code_snippets_vendor_prefix ) === false ) {
			$code_snippets_prefixed_namespace = $code_snippets_vendor_prefix . $code_snippets_namespace;
			if ( isset( $code_snippets_prefixes[ $code_snippets_prefixed_namespace ] ) ) {
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
		$plugin = new Plugin( PLUGIN_VERSION, PLUGIN_FILE );
	}

	return $plugin;
}

code_snippets()->load_plugin();
