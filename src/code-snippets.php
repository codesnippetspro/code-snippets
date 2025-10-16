<?php
/**
 * Plugin Name:  Code Snippets
 * Plugin URI:   https://codesnippets.pro
 * Description:  An easy, clean and simple way to run code snippets on your site. No need to edit to your theme's functions.php file again!
 * Author:       Code Snippets Pro
 * Author URI:   https://codesnippets.pro
 * License:      GPL-2.0-or-later
 * License URI:  license.txt
 * Text Domain:  code-snippets
 * Version:      3.7.0
 * Requires PHP: 7.4
 * Requires at least: 5.0
 *
 * @version   3.7.0
 * @package   Code_Snippets
 * @author    Shea Bunge <shea@codesnippets.pro>
 * @copyright 2012-2024 Code Snippets Pro
 * @license   GPL-2.0-or-later https://spdx.org/licenses/GPL-2.0-or-later.html
 * @link      https://github.com/codesnippetspro/code-snippets
 *
 * phpcs:disable Modernize.FunctionCalls.Dirname.FileConstant
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Halt loading here if the plugin is already loaded, or we're running an incompatible version of PHP.
if ( ! defined( 'CODE_SNIPPETS_FILE' ) && version_compare( phpversion(), '7.4', '>=' ) ) {

	/**
	 * The current plugin version.
	 *
	 * Should be set to the same value as set above.
	 *
	 * @const string
	 */
	define( 'CODE_SNIPPETS_VERSION', '3.7.0' );

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
	define( 'CODE_SNIPPETS_PRO', false );

	require_once dirname( __FILE__ ) . '/php/load.php';
} else {
	require_once dirname( __FILE__ ) . '/php/deactivation-notice.php';
}
