<?php
/**
 * Code Snippets - An easy, clean and simple way to add code snippets to your site.
 *
 * If you're interested in helping to develop Code Snippets, or perhaps contribute
 * to the localization, please see https://github.com/sheabunge/code-snippets
 *
 * @package   Code_Snippets
 * @author    Shea Bunge <shea@codesnippets.pro>
 * @copyright 2012-2020 Shea Bunge
<<<<<<< HEAD
 * @license   GPL-2.0-or-later https://spdx.org/licenses/GPL-2.0-or-later.html
 * @version   3.0.0-alpha.2
=======
 * @license   MIT http://opensource.org/licenses/MIT
 * @version   2.14.0
>>>>>>> develop
 * @link      https://github.com/sheabunge/code-snippets
 */

/*
Plugin Name: Code Snippets
Plugin URI:  https://codesnippets.pro
Description: An easy, clean and simple way to run code snippets on your site. No need to edit to your theme's functions.php file again!
Author:      Code Snippets Pro
Author URI:  https://codesnippets.pro
Version:     3.0.0-alpha.2
License:     GPL-2.0-or-later
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
 *
 * @since 3.0.0
 */
function code_snippets_php_version_notice() {
	echo '<div class="error fade"><p>';

	echo '<p><strong>', esc_html__( 'Code Snippets requires PHP 5.6 or later.', 'code-snippets' ), '</strong><br>';

	/* translators: %s: Update PHP URL */
	$text = __( 'Please <a href="%s">upgrade your server to the latest version of PHP</a> to continue using Code Snippets.', 'code-snippets' );
	$text = sprintf( $text, function_exists( 'wp_get_default_update_php_url' ) ?
		wp_get_default_update_php_url() :
		'https://wordpress.org/support/update-php/'
	);
	echo wp_kses( $text, [ 'a' => [ 'href' => [] ] ] );

	echo '</p></div>';

	deactivate_plugins( plugin_basename( __FILE__ ) );
}

add_action( 'admin_notices', 'code_snippets_php_version_notice' );
