<?php

namespace Evaluation;

use Code_Snippets\DB;
use Code_Snippets\Snippet;
use function Code_Snippets\Conditions\evaluate_condition;

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
	 * Cached list of active snippets, indexed by scope.
	 *
	 * @var ?array[]
	 */
	private ?array $active_snippets = null;

	/**
	 * Cached list of condition results, indexed by condition ID.
	 *
	 * @var ?array{int, bool}
	 */
	private ?array $conditions = null;

	/**
	 * Class constructor.
	 *
	 * @param DB $db Database class instance.
	 */
	public function __construct( DB $db ) {
		$this->db = $db;
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initialise class functions.
	 */
	public function init() {
		add_action( 'wp_head', [ $this, 'load_head_content' ] );
		add_action( 'wp_footer', [ $this, 'load_footer_content' ] );
	}

	/**
	 * Populate the active snippets cache.
	 *
	 * This function fetches active snippets from the database and stores them in the
	 * $active_snippets property. It also evaluates conditions and stores them in the
	 * $conditions property.
	 */
	private function populate_active_snippets() {
		$scopes = [ 'head-content', 'footer-content', 'condition' ];
		$snippets = $this->db->fetch_active_snippets( $scopes );

		foreach ( $snippets as $snippet ) {
			$id = $snippet['id'];
			$scope = $snippet['scope'];

			if ( 'condition' === $scope ) {
				$this->conditions[ $id ] = evaluate_condition( $snippet['code'] );
			} else {
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
			$this->populate_active_snippets();
		}

		if ( ! isset( $this->active_snippets[ $scope ] ) ) {
			return;
		}

		foreach ( $this->active_snippets[ $scope ] as $snippet ) {
			$condition_id = $snippet['condition_id'];

			if ( ! $condition_id || ! isset( $this->conditions[ $condition_id ] ) || $this->conditions[ $condition_id ] ) {
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
}
