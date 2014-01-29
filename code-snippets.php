<?php

/**
 * Code Snippets - An easy, clean and simple way to add code snippets to your site.
 *
 * If you're interested in helping to develop Code Snippets, or perhaps
 * contribute to the localization, please see http://code-snippets.bungeshea.com
 *
 * @package   Code_Snippets
 * @version   2.0-dev
 * @author    Shea Bunge <http://bungeshea.com/>
 * @copyright Copyright (c) 2012-2014, Shea Bunge
 * @link      http://code-snippets.bungeshea.com
 * @license   http://opensource.org/licenses/MIT
 */

/*
Plugin Name: Code Snippets
Plugin URI:  http://code-snippets.bungeshea.com
Description: An easy, clean and simple way to add code snippets to your site. No need to edit to your theme's functions.php file again!
Author:      Shea Bunge
Author URI:  http://bungeshea.com
Version:     2.0-dev
License:     MIT
License URI: license.txt
Text Domain: code-snippets
Domain Path: /languages/
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
define( 'CODE_SNIPPETS_VERSION', '2.0-dev' );

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

/* Database operations functions */
require_once plugin_dir_path( __FILE__ ) . 'includes/db.php';

/* Capability functions */
require_once plugin_dir_path( __FILE__ ) . 'includes/caps.php';

/* Snippet operations functions */
require_once plugin_dir_path( __FILE__ ) . 'includes/snippet-ops.php';

/* Upgrader function */
require_once plugin_dir_path( __FILE__) . 'includes/upgrade.php';

/* Administration functions */
require_once plugin_dir_path( __FILE__) . 'admin/bootstrap.php';

/* Initialize database table variables */
set_snippet_table_vars();

/* Execute the snippets once the plugins are loaded */
add_action( 'plugins_loaded', 'execute_active_snippets', 1 );

/**
 * Load up the localization file if we're using WordPress in a different language.
 * Place it in this plugin's "languages" folder and name it "code-snippets-[value in wp-config].mo"
 *
 * If you wish to contribute a language file to be included in the Code Snippets package,
 * please see create an issue on GitHub: https://github.com/bungeshea/code-snippets/issues
 */
function code_snippets_load_textdomain() {
	load_plugin_textdomain( 'code-snippets', false, dirname( basename( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'code_snippets_load_textdomain' );
