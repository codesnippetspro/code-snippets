<?php
/**
 * Code Snippets - An easy, clean and simple way to add code snippets to your site.
 *
 * If you're interested in helping to develop Code Snippets, or perhaps contribute
 * to the localization, please see https://github.com/sheabunge/code-snippets
 *
 * @package   Code_Snippets
 * @author    Shea Bunge <shea@codesnippets.pro>
 * @copyright 2012-2022 Shea Bunge
 * @license   GPL-2.0-or-later https://spdx.org/licenses/GPL-2.0-or-later.html
 * @version   3.2.0
 * @link      https://github.com/sheabunge/code-snippets
 */

/*
Plugin Name:  Code Snippets
Plugin URI:   https://codesnippets.pro
Description:  An easy, clean and simple way to run code snippets on your site. No need to edit to your theme's functions.php file again!
Author:       Code Snippets Pro
Author URI:   https://codesnippets.pro
Version:      3.2.0
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

/* If a version of code snippets has already been loaded, then deactivate this plugin. */
if ( defined( 'CODE_SNIPPETS_FILE' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	deactivate_plugins( array( 'code-snippets/code-snippets.php' ), true );

	if ( ! function_exists( 'code_snippets_deactivated_old_version_notice' ) ) {
		/**
		 * Display a message informing the user that this plugin has been deactivated.
		 */
		function code_snippets_deactivated_old_version_notice() {
			echo '<div class="error fade"><p>';
			esc_html_e( 'Another version of Code Snippets appears to be installed. Deactivating this version.', 'code-snippets' );
			echo '</p></div>';
		}
	}

	add_action( 'admin_notices', 'code_snippets_deactivated_old_version_notice', 11 );
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

if ( ! function_exists( 'code_snippets_php_version_notice' ) ) {

	/**
	 * Display a warning message and deactivate the plugin if the user is using an incompatible version of PHP
	 *
	 * @since 3.0.0
	 */
	function code_snippets_php_version_notice() {
		echo '<div class="error fade"><p>';
		echo '<p><strong>', esc_html__( 'Code Snippets requires PHP 5.6 or later.', 'code-snippets' ), '</strong><br>';

		$update_url = function_exists( 'wp_get_default_update_php_url' ) ?
			wp_get_default_update_php_url() :
			'https://wordpress.org/support/update-php/';

		/* translators: %s: Update PHP URL */
		$text = __( 'Please <a href="%s">upgrade your server to the latest version of PHP</a> to continue using Code Snippets.', 'code-snippets' );

		echo wp_kses( sprintf( $text, $update_url ), array( 'a' => array( 'href' => array() ) ) );
		echo '</p></div>';

		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	add_action( 'admin_notices', 'code_snippets_php_version_notice' );
}
