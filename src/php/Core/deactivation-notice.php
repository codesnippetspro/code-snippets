<?php
/**
 * File loaded when the plugin cannot be activated.
 *
 * All code in this file should be compatible with PHP 5.2 or later.
 *
 * @package      Code_Snippets
 *
 * @noinspection PhpNestedDirNameCallsCanBeReplacedWithLevelParameterInspection
 *
 * phpcs:disable Modernize.FunctionCalls.Dirname.FileConstant
 */

if ( ! defined( 'ABSPATH' ) || function_exists( 'code_snippets_deactivation_notice' ) ) {
	return;
}

/**
 * Deactivate the plugin and display a notice informing the user that this has happened.
 *
 * @return void
 *
 * @since 3.3.0
 */
function code_snippets_deactivation_notice() {
	$plugins = array();
	$required_php_version = '7.4';

	if ( version_compare( phpversion(), $required_php_version, '<' ) ) {
		echo '<div class="error fade"><p><strong>';
		// translators: %s: required PHP version number.
		echo esc_html( sprintf( __( 'Code Snippets requires PHP %s or later.', 'code-snippets' ), $required_php_version ) );
		echo '</strong><br>';

		$update_url = function_exists( 'wp_get_default_update_php_url' ) ?
			wp_get_default_update_php_url() :
			'https://wordpress.org/support/update-php/';

		// translators: %s: Update PHP URL.
		$text = __( 'Please <a href="%s">upgrade your server to the latest version of PHP</a> to continue using Code Snippets.', 'code-snippets' );

		echo wp_kses( sprintf( $text, $update_url ), array( 'a' => array( 'href' => array() ) ) );
		echo '</p></div>';

		$plugins[] = plugin_basename( dirname( dirname( __FILE__ ) ) . '/code-snippets.php' );
	}

	if ( defined( 'CODE_SNIPPETS_FILE' ) ) {
		echo '<div class="error fade"><p>';
		esc_html_e( 'Another version of Code Snippets appears to be installed. Deactivating this version.', 'code-snippets' );
		echo '</p></div>';

		$plugins[] = 'code-snippets/code-snippets.php';
	}

	if ( $plugins ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( array_unique( $plugins ) );
	}
}

add_action( 'admin_notices', 'code_snippets_deactivation_notice' );
