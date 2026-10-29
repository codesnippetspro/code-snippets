<?php

namespace Code_Snippets\Integration;

use Code_Snippets\Core\DB;
use Code_Snippets\Flat_Files\Handler_Registry;
use Code_Snippets\Flat_Files\Interfaces\Snippet_Type_Handler;
use Code_Snippets\Flat_Files\Snippet_Files;
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
	 * File handler for content snippets.
	 *
	 * @var Snippet_Type_Handler
	 */
	private Snippet_Type_Handler $file_handler;

	/**
	 * Cached list of active snippets, indexed by scope.
	 *
	 * @var ?array[]
	 */
	private ?array $active_snippets = null;

	/**
	 * Class constructor.
	 *
	 * @param DB                   $db      Database class instance.
	 * @param Snippet_Type_Handler $handler File handler for content snippets.
	 */
	public function __construct( DB $db, Snippet_Type_Handler $handler ) {
		$this->db = $db;
		$this->file_handler = $handler;
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initialize class functions.
	 */
	public function init() {
		add_action( 'wp_head', [ $this, 'load_head_content' ] );
		add_action( 'wp_body_open', [ $this, 'load_body_content' ] );
		add_action( 'wp_footer', [ $this, 'load_footer_content' ] );
	}

	/**
	 * Populate the active snippets cache.
	 *
	 * This function fetches active snippets from the database and stores them in the
	 * $active_snippets property.
	 */
	private function populate_active_snippets() {
		$scopes = [ 'head-content', 'body-content', 'footer-content' ];
		$snippets = $this->db->fetch_active_snippets( $scopes );

		foreach ( $snippets as $snippet ) {
			$scope = $snippet['scope'];

			if ( 'condition' !== $scope ) {
				$this->active_snippets[ $scope ][] = $snippet;
			}
		}
	}

	/**
	 * Print snippet code fetched from the database from a certain scope.
	 *
	 * @param string $scope Name of scope to print.
	 */
	private function print_content_snippets( string $scope ) {
		if ( is_null( $this->active_snippets ) ) {
			if ( Snippet_Files::is_active() ) {
				$this->populate_active_snippets_from_flat_files();
			} else {
				$this->populate_active_snippets();
			}
		}

		if ( ! isset( $this->active_snippets[ $scope ] ) ) {
			return;
		}

		foreach ( $this->active_snippets[ $scope ] as $snippet ) {
			if ( isset( $snippet['id'] ) ) {
				do_action( 'code_snippets/snippet_ran_on_page', (int) $snippet['id'], 'content' );
			}

			if ( Snippet_Files::is_active() ) {
				require_once $snippet['file_path'];
			} else {
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				echo "\n", $snippet['code'], "\n";
			}
		}
	}

	/**
	 * Print head content snippets.
	 *
	 * @return void
	 */
	public function load_head_content() {
		$this->print_content_snippets( 'head-content' );
	}

	/**
	 * Print body content snippets (requires theme to call wp_body_open).
	 *
	 * @return void
	 */
	public function load_body_content() {
		$this->print_content_snippets( 'body-content' );
	}

	/**
	 * Print footer content snippets.
	 *
	 * @return void
	 */
	public function load_footer_content() {
		$this->print_content_snippets( 'footer-content' );
	}

	/**
	 * Populate the active snippets from flat files.
	 *
	 * @return void
	 */
	private function populate_active_snippets_from_flat_files() {
		$dir_name = $this->file_handler->get_dir_name();
		$ext = $this->file_handler->get_file_extension();

		$scopes = [ 'head-content', 'body-content', 'footer-content' ];
		$all_snippets = Snippet_Files::get_active_snippets_from_flat_files( $scopes, $dir_name );

		foreach ( $all_snippets as $snippet ) {
			$scope = $snippet['scope'];

			if ( 'condition' !== $scope ) {
				// Add file path information to the snippet for later use.
				$table_name = Snippet_Files::get_hashed_table_name( $snippet['table'] );
				$base_path = Snippet_Files::get_base_dir( $table_name, $dir_name );
				$snippet['file_path'] = $base_path . '/' . $snippet['id'] . '.' . $ext;

				$this->active_snippets[ $scope ][] = $snippet;
			}
		}
	}
}
