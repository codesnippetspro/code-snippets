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
		$plugins[] = plugin_basename( dirname( dirname( __FILE__ ) ) . '/code-snippets.php' );

		printf(
			'<div class="code-snippets-notice error fade" role="region" aria-label="%s"><p><strong>%s</strong>',
			esc_attr__( 'Code Snippets PHP version notice', 'code-snippets' ),
			// translators: %s: required PHP version number.
			esc_html( sprintf( __( 'Code Snippets requires PHP %s or later.', 'code-snippets' ), $required_php_version ) )
		);

		$update_url = function_exists( 'wp_get_default_update_php_url' ) ?
			wp_get_default_update_php_url() :
			'https://wordpress.org/support/update-php/';

		// translators: %s: Update PHP URL.
		$update_text = __( 'Please <a href="%s">upgrade your server to the latest version of PHP</a> to continue using Code Snippets.', 'code-snippets' );

		echo '<br>', wp_kses( sprintf( $update_text, $update_url ), array( 'a' => array( 'href' => array() ) ) );
		echo '</p></div>';
	}

	if ( defined( 'CODE_SNIPPETS_FILE' ) ) {
		$plugins[] = 'code-snippets/code-snippets.php';

		printf(
			'<div class="code-snippets-notice error fade" role="region" aria-label="%s"><p>%s</p></div>',
			esc_attr__( 'Code Snippets duplicate plugin notice', 'code-snippets' ),
			esc_html__( 'Another version of Code Snippets appears to be installed. Deactivating this version.', 'code-snippets' )
		);
	}

	if ( $plugins ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( array_unique( $plugins ) );
	}
}

add_action( 'admin_notices', 'code_snippets_deactivation_notice' );
