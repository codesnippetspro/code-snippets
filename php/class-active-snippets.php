<?php

namespace Code_Snippets;

/**
 * Class for loading active style snippets
 * @package Code_Snippets
 */
class Active_Snippets {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'print_js' ) );
		add_action( 'init', array( $this, 'print_css' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_js' ), 15 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_css' ), 15 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_css' ), 15 );
	}

	/**
	 * Increment the asset revision for a specified scope
	 *
	 * @param string $scope
	 * @param bool   $network
	 */
	public function increment_rev( $scope, $network ) {
		if ( $network && ! is_multisite() ) {
			return;
		}

		$revisions = Settings\get_self_option( $network, 'code_snippets_assets_rev', array() );

		if ( 'all' == $scope ) {
			foreach ( $revisions as $i => $v ) {
				$revisions[ $i ]++;
			}

		} else {
			if ( ! isset( $revisions[ $scope ] ) ) {
				$revisions[ $scope ] = 0;
			}

			$revisions[ $scope ]++;
		}

		Settings\update_self_option( $network, 'code_snippets_assets_rev', $revisions );
	}

	/**
	 * Retrieve the current asset revision number
	 *
	 * @param string $scope
	 *
	 * @return int
	 */
	public function get_rev( $scope ) {
		$rev = 0;

		if ( $revisions = get_option( 'code_snippets_assets_rev' ) && isset( $revisions[ $scope ] ) ) {
			$rev += intval( $revisions[ $scope ] );
		}

		if ( is_multisite() && $revisions = get_site_option( 'code_snippets_assets_rev' ) && isset( $revisions[ $scope ] ) ) {
			$rev += intval( $revisions[ $scope ] );
		}

		return $rev;
	}

	/**
	 * Enqueue the active style snippets for the current page
	 */
	public function enqueue_css() {
		$scope = is_admin() ? 'admin' : 'site';

		if ( ! $rev = $this->get_rev( "$scope-css" ) ) {
			return;
		}

		$url = add_query_arg( 'code-snippets-css', 1, is_admin() ? self_admin_url( '/' ) : home_url( '/' ) );
		wp_enqueue_style( "code-snippets-{$scope}-styles", $url, array(), $rev );
	}

	/**
	 * Enqueue the active javascript snippets for the current page
	 */
	public function enqueue_js() {

		if ( $head_rev = $this->get_rev( 'site-head-js' ) ) {
			wp_enqueue_script(
				'code-snippets-site-head-js',
				$url = add_query_arg( 'code-snippets-js-snippets', 'head', home_url( '/' ) ),
				array(), $head_rev, false
			);

		}

		if ( $footer_rev = $this->get_rev( 'site-footer-js' ) ) {
			wp_enqueue_script(
				'code-snippets-site-footer-js',
				$url = add_query_arg( 'code-snippets-js-snippets', 'footer', home_url( '/' ) ),
				array(), $head_rev, true
			);

		}
	}

	private static function do_asset_headers( $mime_type ) {
		header( 'Content-Type: ' . $mime_type, true, 200 );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 31536000 ) . ' GMT' ); // 1 year
	}

	/**
	 * Print the active style snippets for the current scope
	 */
	public function print_css() {
		if ( ! isset( $_GET['code-snippets-css'] ) ) {
			return;
		}

		$this->do_asset_headers( 'text/css' );

		$current_scope = is_admin() ? 'admin-css' : 'site-css';
		$active_snippets = code_snippets()->db->fetch_active_snippets( $current_scope, 'code' );

		foreach ( $active_snippets as $snippets ) {
			echo implode( "\n\n", array_column( $snippets, 'code' ) );
		}

		exit;
	}

	/**
	 * Print the active style snippets for the current scope
	 */
	public function print_js() {
		if ( ! isset( $_GET['code-snippets-js-snippets'] ) || is_admin() ) {
			return;
		}

		$this->do_asset_headers( 'application/javascript' );

		$current_scope = 'site-' . ( 'footer' === $_GET['code-snippets-js-snippets'] ? 'footer' : 'head' ) . '-js';

		$active_snippets = code_snippets()->db->fetch_active_snippets( $current_scope, 'code' );

		foreach ( $active_snippets as $snippets ) {
			echo implode( "\n\n", array_column( $snippets, 'code' ) );
		}

		exit;
	}
}
