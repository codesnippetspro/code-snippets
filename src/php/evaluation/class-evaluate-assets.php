<?php

namespace Evaluation;

use Code_Snippets\DB;
use Code_Snippets\Snippet;
use MatthiasMullie\Minify;
use Code_Snippets\Conditions;
use Code_Snippets\Settings;
use function Code_Snippets\code_snippets;

/**
 * Class for loading active snippets of various types.
 *
 * @package Code_Snippets
 */
class Evaluate_Assets {

	/**
	 * Database instance.
	 *
	 * @var DB
	 */
	private DB $db;

	/**
	 * Cached list of active snippets.
	 *
	 * @var array[]
	 */
	private array $active_snippets = [];

	/**
	 * Class constructor.
	 *
	 * @param DB $db Database instance.
	 */
	public function __construct( DB $db ) {
		$this->db = $db;
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initialise class functions.
	 */
	public function init() {
		if ( ! code_snippets()->licensing->was_licensed() ) {
			return;
		}

		if ( isset( $_GET['code-snippets-css'] ) ) {
			$this->print_external_code( 'css' );
			exit;
		}

		if ( isset( $_GET['code-snippets-js-snippets'] ) && ! is_admin() ) {
			$this->print_external_code( 'js' );
			exit;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ), 15 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ), 15 );
	}

	/**
	 * Fetch active snippets for a given scope, and cache the data in this class.
	 *
	 * @param string|string[] $scope Snippet scope.
	 *
	 * @return array[][]
	 */
	protected function fetch_active_snippets( $scope ): array {
		$scope_key = is_array( $scope ) ? implode( '|', $scope ) : $scope;

		if ( ! isset( $this->active_snippets[ $scope_key ] ) ) {
			$this->active_snippets[ $scope_key ] = $this->db->fetch_active_snippets( $scope );
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

		$revisions = Settings\get_self_option( $network, 'code_snippets_assets_rev', [] );

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

		$scope_snippets = $this->fetch_active_snippets( [ $scope ] );

		if ( empty( $scope_snippets ) ) {
			return false;
		}

		$revisions = get_option( 'code_snippets_assets_rev' );
		$rev += isset( $revisions[ $scope ] ) ? intval( $revisions[ $scope ] ) : 0;

		if ( is_multisite() ) {
			$ms_revisions = get_site_option( 'code_snippets_assets_rev' );
			$rev += isset( $ms_revisions[ $scope ] ) ? intval( $ms_revisions[ $scope ] ) : 0;
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
	 * Enqueue snippet assets for the site front-end.
	 *
	 * @return void
	 */
	public function enqueue_frontend() {
		$this->enqueue_css( 'site-css' );
		$this->enqueue_js( 'site-head-js' );
		$this->enqueue_js( 'site-footer-js' );
	}

	/**
	 * Enqueue snippet assets for the site admin area.
	 *
	 * @return void
	 */
	public function enqueue_admin() {
		$this->enqueue_css( 'admin-css' );
	}

	/**
	 * Enqueue the active style snippets for the current page
	 *
	 * @param string $scope CSS scope, either 'site-css' or 'admin-css'.
	 */
	private function enqueue_css( string $scope ) {
		$rev = $this->get_rev( $scope );

		if ( ! $rev ) {
			return;
		}

		$url = $this->get_asset_url( $scope );
		$handle = "code-snippets-$scope-styles";

		wp_enqueue_style( $handle, $url, [], $rev );
		wp_add_inline_style( $handle, $this->build_inline_code( $scope ) );
	}

	/**
	 * Enqueue active JavaScript snippets for the current page
	 *
	 * @param string $scope JS scope, either 'site-head-js' or 'site-footer-js'.
	 */
	private function enqueue_js( string $scope ) {
		$rev = $this->get_rev( $scope );

		if ( $rev ) {
			$handle = "code-snippets-$scope";

			wp_enqueue_script(
				$handle,
				$this->get_asset_url( $scope ),
				[],
				$rev,
				[ 'in_footer' => 'site-footer-js' === $scope ]
			);

			wp_add_inline_script( $handle, $this->build_inline_code( $scope ) );
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
	 * @param string $code          Snippet code.
	 * @param string $type_or_scope Snippet type or scope.
	 *
	 * @return string Processed code.
	 *
	 * @noinspection PhpDocMissingThrowsInspection
	 */
	private static function process_code( string $code, string $type_or_scope ): string {
		$minify_types = Settings\get_setting( 'general', 'minify_output' );

		switch ( $type_or_scope ) {
			case 'css':
			case 'site-css':
			case 'admin-css':
				if ( in_array( 'css', $minify_types, true ) ) {
					$minifier = new Minify\CSS( $code );
					$code = $minifier->minify();
				}
				break;

			case 'js':
			case 'site-head-js':
			case 'site-footer-js':
				if ( in_array( 'js', $minify_types, true ) ) {
					$minifier = new Minify\JS( $code );
					$code = $minifier->minify();
				}
				break;

			default:
				if ( function_exists( 'wp_trigger_error' ) ) {
					$message = sprintf( 'Cannot process code for snippet scope: %s', esc_html( $scope ) );
					/* @noinspection PhpUnhandledExceptionInspection E_USER_NOTICE level does not throw an error. */
					wp_trigger_error( __FUNCTION__, $message );
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

		// Concatenate all fetched code together into a single string.
		$active_snippets = $this->db->fetch_active_snippets( [ $current_scope ] );
		$code = implode( "\n\n", array_column( $active_snippets, 'code' ) );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::process_code( $code, $type );
		exit;
	}

	/**
	 * Generate inline code for a given type, paying respect to conditions.
	 *
	 * @param string $scope Code scope.
	 *
	 * @return string Code ready for output.
	 */
	private function build_inline_code( string $scope ): string {
		$snippets = $this->db->fetch_active_snippets( [ $scope, 'condition' ] );
		$conditions = [];

		foreach ( $snippets as $snippet ) {
			if ( 'condition' === $snippet['scope'] ) {
				$condition_id = intval( $snippet['id'] );
				$conditions[ $condition_id ] = Conditions\evaluate_condition( $snippet['code'] );
			}
		}

		$code = '';

		foreach ( $snippets as $snippet ) {
			$condition_id = intval( $snippet['condition_id'] );
			if ( 'condition' !== $snippet['scope'] &&
			     ( ! $condition_id || ! isset( $conditions[ $condition_id ] ) || $conditions[ $condition_id ] ) ) {
				$code .= $snippet['code'] . "\n\n";
			}
		}

		return self::process_code( $code, $scope );
	}
}
