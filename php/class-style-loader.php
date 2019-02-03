<?php

namespace Code_Snippets;

/**
 * Class for loading active style snippets
 * @package Code_Snippets
 */
class Style_Loader {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'print_css' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_css' ), 15 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_css' ), 15 );
	}

	/**
	 * Print the active style snippets for the current scope
	 */
	public function print_css() {
		if ( ! isset( $_GET['code-snippets-css'] ) ) {
			return;
		}

		header( 'Content-Type: text/css', true, 200 );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 31536000 ) . ' GMT' ); // 1 year

		$active_snippets = code_snippets()->db->fetch_active_snippets( is_admin() ? 'admin-css' : 'site-css' );

		foreach ( $active_snippets as $snippets ) {
			echo implode( "\n\n", array_column( $snippets, 'code' ) );
		}

		exit;
	}

	/**
	 * Enqueue the active style snippets for the current page
	 */
	public function enqueue_css() {
		$url = is_admin() ? self_admin_url( '/' ) : home_url( '/' );
		$url = add_query_arg( 'code-snippets-css', 1, $url );
		$scope = is_admin() ? 'admin' : 'site';

		$rev = (int) get_option( "code_snippets_{$scope}_css_rev", 1 );

		if ( is_multisite() ) {
			$rev += (int) get_site_option( "code_snippets_{$scope}_css_rev", 0 );
		}

		wp_enqueue_style( 'code-snippets-styles', $url, array(), $rev );
	}
}
