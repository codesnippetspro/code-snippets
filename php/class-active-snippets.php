<?php

namespace Code_Snippets;

/**
 * Class for loading active snippets of various types.
 *
 * @package Code_Snippets
 */
class Active_Snippets {

	/**
	 * List of content snippets.
	 *
	 * @var array
	 */
	private $content_snippets = [];

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
		$db = code_snippets()->db;
		$this->content_snippets = $db->fetch_active_snippets( [ 'head-content', 'footer-content' ] );

		add_action( 'wp_head', [ $this, 'load_head_content' ] );
		add_action( 'wp_footer', [ $this, 'load_footer_content' ] );
	}

	/**
	 * Print snippet code fetched from the database from a certain scope.
	 *
	 * @param array  $snippets_list List of data fetched.
	 * @param string $scope         Name of scope to print.
	 */
	private function print_content_snippets( $snippets_list, $scope ) {
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
		$this->print_content_snippets( $this->content_snippets, 'head-content' );
	}

	/**
	 * Print footer content snippets.
	 */
	public function load_footer_content() {
		$this->print_content_snippets( $this->content_snippets, 'footer-content' );
	}
}
