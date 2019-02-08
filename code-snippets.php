<?php

/**
 * Code Snippets - An easy, clean and simple way to add code snippets to your site.
 *
 * If you're interested in helping to develop Code Snippets, or perhaps contribute
 * to the localization, please see https://github.com/sheabunge/code-snippets
 *
 * @package   Code_Snippets
 * @author    Shea Bunge <shea@sheabunge.com>
 * @copyright 2012-2019 Shea Bunge
 * @license   MIT http://opensource.org/licenses/MIT
 * @version   2.13.2
 * @link      https://github.com/sheabunge/code-snippets
 */

/*
Plugin Name: Code Snippets
Plugin URI:  https://github.com/sheabunge/code-snippets
Description: An easy, clean and simple way to run code snippets on your site. No need to edit to your theme's functions.php file again!
Author:      Shea Bunge
Author URI:  https://sheabunge.com
Version:     3.0.0-dev.1
License:     MIT
License URI: license.txt
Text Domain: code-snippets
Domain Path: /languages
*/

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

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

if ( version_compare( phpversion(), '5.6', '>=' ) ) {
	require_once __DIR__ . '/php/load.php';
	return;
}

/**
 * Display a warning message and deactivate the plugin if the user is using an incompatible version of PHP
 * @since 3.0.0
 */
function code_snippets_php_version_notice() {
	echo '<div class="error fade">';

	echo '<p><strong>', esc_html__( 'Code Snippets requires PHP 5.6 or later.', 'code-snippets' ), '</strong></p>';

	echo '<p>', esc_html__( ' Please upgrade your server to the latest version of PHP. You can contact your web host if you are unsure how to do this.', 'code-snippets' ), '</p>';

	echo '</div>';

	deactivate_plugins( plugin_basename( __FILE__ ) );
}

add_action( 'admin_notices', 'code_snippets_php_version_notice' );
