<?php
namespace Code_Snippets;

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
		$should_use_flat_files = Settings\get_setting( 'general', 'enable_flat_files' );
		
		if ( ! $should_use_flat_files ) {
			add_action( 'wp_head', [ $this, 'load_head_content' ] );
			add_action( 'wp_footer', [ $this, 'load_footer_content' ] );
		} else {
			add_action( 'wp_head', [ $this, 'load_head_content_from_flat_files' ] );
			add_action( 'wp_footer', [ $this, 'load_footer_content_from_flat_files' ] );
		}
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

	public function load_head_content_from_flat_files() {
		$this->load_content_snippets_from_flat_files( 'head-content' );
	}

	public function load_footer_content_from_flat_files() {
		$this->load_content_snippets_from_flat_files( 'footer-content' );
	}

	private function load_content_snippets_from_flat_files( string $scope ) {
		$handler = code_snippets()->snippet_handler_registry->get_handler( 'html' );
		$dir_name = $handler->get_dir_name();
		$ext = $handler->get_file_extension();
		$snippets = Snippet_Files::get_active_snippets_from_flat_files( [ $scope ], $dir_name );

		foreach ( $snippets as $table_name => $active_snippets ) {
			$active_snippets = cs_sort_snippets_by_priority( $active_snippets );
			$base_path = Snippet_Files::get_base_dir( $table_name, $dir_name );

			foreach ( $active_snippets as $snippet ) {
				require_once $base_path . '/' . $snippet['id'] . '.' . $ext;
			}
		}
	}
}
