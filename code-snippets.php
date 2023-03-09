<?php
/**
 * Code Snippets - An easy, clean and simple way to add code snippets to your site.
 *
 * If you're interested in helping to develop Code Snippets, or perhaps contribute
 * to the localization, please see https://github.com/codesnippetspro/code-snippets
 *
 * @package   Code_Snippets
 * @author    Shea Bunge <shea@codesnippets.pro>
 * @copyright 2012-2023 Code Snippets Pro
 * @license   GPL-2.0-or-later https://spdx.org/licenses/GPL-2.0-or-later.html
 * @version   3.3.0
 * @link      https://github.com/codesnippetspro/code-snippets
 */

/*
Plugin Name:  Code Snippets
Plugin URI:   https://codesnippets.pro
Description:  An easy, clean and simple way to run code snippets on your site. No need to edit to your theme's functions.php file again!
Author:       Code Snippets Pro
Author URI:   https://codesnippets.pro
Version:      3.3.0
License:      GPL-2.0-or-later
License URI:  license.txt
Text Domain:  code-snippets
Requires PHP: 5.6
Requires at least: 3.6
*/

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Halt loading here if the plugin is already loaded, or we're running an incompatible version of PHP.
if ( ! defined( 'CODE_SNIPPETS_FILE' ) && version_compare( phpversion(), '5.6', '>=' ) ) {

	/**
	 * The current plugin version.
	 *
	 * Should be set to the same value as set above.
	 *
	 * @const string
	 */
	define( 'CODE_SNIPPETS_VERSION', '3.3.0' );

	/**
	 * The full path to the main file of this plugin.
	 *
	 * This can later be passed to functions such as plugin_dir_path(), plugins_url() and plugin_basename()
	 * to retrieve information about plugin paths.
	 *
	 * @since 2.0.0
	 * @const string
	 */
	define( 'CODE_SNIPPETS_FILE', __FILE__ );

	/**
	 * Used to determine which version of Code Snippets is running.
	 *
	 * @since 3.0.0
	 * @onst  boolean
	 */
	define( 'CODE_SNIPPETS_PRO', true );

	require_once dirname( __FILE__ ) . '/php/load.php';
} else {
	require_once dirname( __FILE__ ) . '/php/deactivation-notice.php';
}
