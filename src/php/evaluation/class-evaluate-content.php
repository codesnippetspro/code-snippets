<?php

namespace Evaluation;

use Code_Snippets\DB;
use Code_Snippets\Snippet;
use Code_Snippets\Settings;
use Code_Snippets\Snippet_Files;
use function Code_Snippets\code_snippets;

/**
 * Class for evaluating content snippets.
 *
 * @package Code_Snippets
 */
class Evaluate_Content {

	/**
	 * Database class.
	 *
	 * @var DB
	 */
	private DB $db;

	/**
	 * Cached list of active snippets.
	 *
	 * @var ?Snippet[]
	 */
	private ?array $active_snippets = null;

	/**
	 * Class constructor.
	 *
	 * @param DB $db Database class instance.
	 */
	public function __construct( DB $db ) {
		$this->db = $db;
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialise class functions.
	 */
	public function init() {
		if ( Snippet_Files::is_active() ) {
			add_action( 'wp_head', [ $this, 'load_head_content_from_flat_files' ] );
			add_action( 'wp_footer', [ $this, 'load_footer_content_from_flat_files' ] );
		} else {
			add_action( 'wp_head', [ $this, 'load_head_content' ] );
			add_action( 'wp_footer', [ $this, 'load_footer_content' ] );
		}
	}

	/**
	 * Print snippet code fetched from the database from a certain scope.
	 *
	 * @param string $scope Name of scope to print.
	 */
	private function print_content_snippets( string $scope ) {
		$scopes = [ 'head-content', 'footer-content' ];

		if ( is_null( $this->active_snippets ) ) {
			$this->active_snippets = $this->db->fetch_active_snippets( $scopes );
		}

		foreach ( $this->active_snippets as $snippet ) {
			if ( $scope === $snippet['scope'] ) {
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				echo "\n", $snippet['code'], "\n";
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

	private function populate_active_snippets_from_flat_files() {
		$handler = code_snippets()->snippet_handler_registry->get_handler( 'html' );
		$dir_name = $handler->get_dir_name();
		$ext = $handler->get_file_extension();

		$scopes = [ 'head-content', 'footer-content' ];
		$all_snippets = Snippet_Files::get_active_snippets_from_flat_files( $scopes, $dir_name );

		foreach ( $all_snippets as $snippet ) {
			$scope = $snippet['scope'];

			// Add file path information to the snippet for later use
			$table_name = Snippet_Files::get_hashed_table_name( $snippet['table'] );
			$base_path = Snippet_Files::get_base_dir( $table_name, $dir_name );
			$snippet['file_path'] = $base_path . '/' . $snippet['id'] . '.' . $ext;

			$this->active_snippets[ $scope ][] = $snippet;
		}
	}

	private function load_content_snippets_from_flat_files( string $scope ) {
		if ( is_null( $this->active_snippets ) ) {
			$this->populate_active_snippets_from_flat_files();
		}

		if ( ! isset( $this->active_snippets[ $scope ] ) ) {
			return;
		}

		foreach ( $this->active_snippets[ $scope ] as $snippet ) {
			require_once $snippet['file_path'];
		}
	}
}
