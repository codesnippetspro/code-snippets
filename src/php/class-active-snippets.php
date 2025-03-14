<?php
namespace Code_Snippets;

use MatthiasMullie\Minify;

/**
 * Class for loading active snippets of various types.
 *
 * @package Code_Snippets
 */
class Active_Snippets {

	/**
	 * Cached list of active snippets.
	 *
	 * @var Snippet[]
	 */
	private array $active_snippets = [];

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialise class functions.
	 */
	public function init() {
		add_action( 'wp_head', [ $this, 'load_head_content' ] );
		add_action( 'wp_footer', [ $this, 'load_footer_content' ] );

		if ( code_snippets()->licensing->was_licensed() ) {
			$this->init_pro();
		}
	}

	/**
	 * Initialise class functions for the pro functionality.
	 */
	protected function init_pro() {
		if ( isset( $_GET['code-snippets-css'] ) ) {
			$this->print_external_code( 'css' );
			exit;
		}

		if ( isset( $_GET['code-snippets-js-snippets'] ) && ! is_admin() ) {
			$this->print_external_code( 'js' );
			exit;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_js' ), 15 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_css' ), 15 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_css' ), 15 );
	}

	/**
	 * Fetch active snippets for a given scope, and cache the data in this class.
	 *
	 * @param string|string[] $scope Snippet scope.
	 *
	 * @return array[][]
	 */
	protected function fetch_active_snippets( $scope ) {
		$scope_key = is_array( $scope ) ? implode( '|', $scope ) : $scope;

		if ( ! isset( $this->active_snippets[ $scope_key ] ) ) {
			$this->active_snippets[ $scope_key ] = code_snippets()->db->fetch_active_snippets( $scope );
		}

		return $this->active_snippets[ $scope_key ];
	}

	/**
	 * Increment the asset revision for a specified snippet.
	 *
	 * @param Snippet $snippet Recently updated snippet.
	 *
	 * @return void
	 */
	public function increment_snippet_rev( Snippet $snippet ) {
		if ( 'css' === $snippet->type || 'js' === $snippet->type ) {
			$this->increment_rev( $snippet->scope, $snippet->network && ! $snippet->shared_network );
		}
	}

	/**
	 * Increment the asset revision for multiple specified snippet.
	 *
	 * @param bool $network Whether to increase for the whole network or the current site.
	 *
	 * @return void
	 */
	public function increment_snippets_rev( bool $network ) {
		$this->increment_rev( 'all', $network );
	}

	/**
	 * Increment the asset revision for a specified scope
	 *
	 * @param string $scope   Name of snippet scope.
	 * @param bool   $network Whether to increase for the whole network or the current site.
	 */
	public function increment_rev( string $scope, bool $network ) {
		if ( $network && ! is_multisite() ) {
			return;
		}

		$revisions = Settings\get_self_option( $network, 'code_snippets_assets_rev', array() );

		if ( 'all' === $scope ) {
			foreach ( $revisions as $i => $v ) {
				++$revisions[ $i ];
			}
		} else {
			if ( ! isset( $revisions[ $scope ] ) ) {
				$revisions[ $scope ] = 0;
			}

			++$revisions[ $scope ];
		}

		Settings\update_self_option( $network, 'code_snippets_assets_rev', $revisions );
	}

	/**
	 * Retrieve the current asset revision number
	 *
	 * @param string $scope Scope name..
	 *
	 * @return int Current asset revision number.
	 */
	public function get_rev( string $scope ) {
		$rev = 0;
		$type = Snippet::get_type_from_scope( $scope );
		$conditional_scope = "conditional-$type";

		$scope_snippets = $this->fetch_active_snippets( [ $scope, $conditional_scope ] );

		if ( empty( $scope_snippets ) ) {
			return false;
		}

		$revisions = get_option( 'code_snippets_assets_rev' );
		$rev += isset( $revisions[ $scope ] ) ? intval( $revisions[ $scope ] ) : 0;
		$rev += isset( $revisions[ $conditional_scope ] ) ? intval( $revisions[ $conditional_scope ] ) : 0;

		if ( is_multisite() ) {
			$ms_revisions = get_site_option( 'code_snippets_assets_rev' );
			$rev += isset( $ms_revisions[ $scope ] ) ? intval( $ms_revisions[ $scope ] ) : 0;
			$rev += isset( $ms_revisions[ $conditional_scope ] ) ? intval( $ms_revisions[ $conditional_scope ] ) : 0;
		}

		return $rev;
	}

	/**
	 * Retrieve the URL to a generated scope asset.
	 *
	 * @param string $scope      Name of the scope to retrieve the asset for.
	 * @param bool   $latest_rev Whether to ensure that the URL is to the latest revision of the asset.
	 *
	 * @return string URL to asset.
	 */
	public function get_asset_url( string $scope, bool $latest_rev = false ): string {
		$base = 'admin-css' === $scope ? self_admin_url( '/' ) : home_url( '/' );

		if ( '-css' === substr( $scope, -4 ) ) {
			$url = add_query_arg( 'code-snippets-css', 1, $base );

		} elseif ( '-js' === substr( $scope, -3 ) ) {
			$key = 'site-head-js' === $scope ? 'head' : 'footer';
			$url = add_query_arg( 'code-snippets-js-snippets', $key, $base );

		} else {
			return '';
		}

		if ( $latest_rev ) {
			$rev = $this->get_rev( $scope );
			$url = $rev ? add_query_arg( 'ver', $rev, $url ) : $url;
		}

		return $url;
	}

	/**
	 * Enqueue the active style snippets for the current page
	 */
	public function enqueue_css() {
		$scope = is_admin() ? 'admin' : 'site';
		$rev = $this->get_rev( "$scope-css" );

		if ( ! $rev ) {
			return;
		}

		$url = $this->get_asset_url( "$scope-css" );
		$handle = "code-snippets-$scope-styles";

		wp_enqueue_style( $handle, $url, [], $rev );
		wp_add_inline_style( $handle, $this->build_inline_code( 'css' ) );
	}

	/**
	 * Enqueue the active javascript snippets for the current page
	 */
	public function enqueue_js() {
		$head_rev = $this->get_rev( 'site-head-js' );
		$footer_rev = $this->get_rev( 'site-footer-js' );

		if ( $head_rev ) {
			wp_enqueue_script(
				'code-snippets-site-head',
				$this->get_asset_url( 'site-head-js' ),
				array(),
				$head_rev,
				false
			);
		}

		if ( $footer_rev ) {
			$handle = 'code-snippets-site-footer';

			wp_enqueue_script(
				$handle,
				$this->get_asset_url( 'site-footer-js' ),
				array(),
				$footer_rev,
				true
			);

			wp_add_inline_script( $handle, $this->build_inline_code( 'js' ) );
		}
	}

	/**
	 * Set the necessary headers to mark this page as an asset
	 *
	 * @param string $mime_type File MIME type used to set Content-Type header.
	 */
	private static function do_asset_headers( string $mime_type ) {
		$expiry = 365 * 24 * 60 * 60; // year in seconds.
		header( 'Content-Type: ' . $mime_type, true, 200 );
		header( sprintf( 'Expires: %s GMT', gmdate( 'D, d M Y H:i:s', time() + $expiry ) ) );
	}

	/**
	 * Output the code from a list of snippets
	 *
	 * @param string $code Snippet code.
	 * @param string $type Code type, 'css' or 'js'.
	 *
	 * @return string Processed code.
	 */
	private static function process_code( string $code, string $type ): string {
		$minify_types = Settings\get_setting( 'general', 'minify_output' );

		switch ( $type ) {
			case 'css':
				if ( in_array( 'css', $minify_types, true ) ) {
					$minifier = new Minify\CSS( $code );
					$code = $minifier->minify();
				}
				break;

			case 'js':
				if ( in_array( 'js', $minify_types, true ) ) {
					$minifier = new Minify\JS( $code );
					$code = $minifier->minify();
				}
				break;
		}

		return $code;
	}

	/**
	 * Fetch and print the active snippets for a given type and the current scope.
	 *
	 * @param string $type Must be either 'css' or 'js'.
	 */
	private function print_external_code( string $type ) {
		if ( 'js' !== $type && 'css' !== $type ) {
			return;
		}

		if ( 'css' === $type ) {
			$this->do_asset_headers( 'text/css' );
			$current_scope = is_admin() ? 'admin-css' : 'site-css';
		} else {
			$this->do_asset_headers( 'text/javascript' );
			$current_scope = isset( $_GET['code-snippets-js-snippets'] ) && 'footer' === $_GET['code-snippets-js-snippets'] ? 'footer' : 'head';
			$current_scope = "site-$current_scope-js";
		}

		$active_snippets = code_snippets()->db->fetch_active_snippets( $current_scope );

		// Concatenate all fetched code together into a single string.
		$code = '';
		foreach ( $active_snippets as $snippets ) {
			$code .= implode( "\n\n", array_column( $snippets, 'code' ) );
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::process_code( $code, $type );
		exit;
	}

	/**
	 * Generate inline code for a given type, paying respect to conditionals.
	 *
	 * @param string $type Type of code, 'css' or 'js'.
	 *
	 * @return string Code ready for output.
	 */
	private function build_inline_code( string $type ): string {
		$current_scope = "conditional-$type";
		$snippets_by_table = code_snippets()->db->fetch_active_snippets( [ $current_scope, 'condition' ] );
		$code = '';

		foreach ( $snippets_by_table as $snippets ) {
			$conditionals = [];

			foreach ( $snippets as $snippet ) {
				if ( 'condition' === $snippet['scope'] ) {
					$conditional_id = intval( $snippet['id'] );
					$conditionals[ $conditional_id ] = evaluate_conditional( $snippet['code'] );
				}
			}

			foreach ( $snippets as $snippet ) {
				$conditional_id = intval( $snippet['conditional'] );
				if ( 'condition' !== $snippet['scope'] &&
				     ( ! $conditional_id || ! isset( $conditionals[ $conditional_id ] ) || $conditionals[ $conditional_id ] ) ) {
					$code .= $snippet['code'] . "\n\n";
				}
			}
		}

		return self::process_code( $code, $type );
	}

	/**
	 * Print snippet code fetched from the database from a certain scope.
	 *
	 * @param string $scope Name of scope to print.
	 */
	private function print_content_snippets( string $scope ) {
		$snippets_list = $this->fetch_active_snippets( [ 'head-content', 'footer-content' ] );

		foreach ( $snippets_list as $snippets ) {
			foreach ( $snippets as $snippet ) {
				if ( $scope === $snippet['scope'] ) {
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					echo "\n", $snippet['code'], "\n";
				}
			}
		}
	}

	/**
	 * Print head content snippets.
	 */
	public function load_head_content() {
		$this->print_content_snippets( 'head-content' );
	}

	/**
	 * Print footer content snippets.
	 */
	public function load_footer_content() {
		$this->print_content_snippets( 'footer-content' );
	}
}
