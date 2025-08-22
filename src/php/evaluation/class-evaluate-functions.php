<?php

namespace Evaluation;

use Code_Snippets\DB;
use Code_Snippets\REST_API\Snippets_REST_Controller;
use function Code_Snippets\clean_active_snippets_cache;
use function Code_Snippets\clean_snippets_cache;
use function Code_Snippets\Conditions\evaluate_condition;
use function Code_Snippets\execute_snippet;

/**
 * Class for evaluating functions snippets.
 *
 * @package Code_Snippets
 */
class Evaluate_Functions {

	/**
	 * Database class.
	 *
	 * @var DB
	 */
	private DB $db;

	/**
	 * Snippets with an attached condition that should be evaluated later.
	 *
	 * @var array[]
	 */
	private array $snippets_with_condition = [];

	/**
	 * List of conditions that should be evaluated later.
	 *
	 * @var array[]
	 */
	private array $conditions = [];

	/**
	 * Class constructor.
	 *
	 * @param DB $db Database class instance.
	 */
	public function __construct( DB $db ) {
		$this->db = $db;
		add_action( 'plugins_loaded', [ $this, 'evaluate_early' ], 1 );
		add_action( 'init', [ $this, 'evaluate_conditional_snippets' ], 1 );
	}

	/**
	 * Retrieve details about the currently edited snippet, if any.
	 *
	 * @return ?array{id: int, table: string}
	 */
	private function get_currently_editing_snippet(): ?array {
		if ( wp_is_json_request() && ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$url = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );

			if ( isset( $url['path'] ) && false !== strpos( $url['path'], Snippets_REST_Controller::get_prefixed_base_route() ) ) {
				$path_parts = explode( '/', $url['path'] );
				$edit_id = intval( end( $path_parts ) );

				if ( ! empty( $url['query'] ) ) {
					wp_parse_str( $url['query'], $path_params );
					$edit_table = isset( $path_params['network'] ) && rest_sanitize_boolean( $path_params['network'] )
						? $this->db->ms_table
						: $this->db->table;
				}

				return [
					'id'    => $edit_id,
					'table' => $edit_table ?? $this->db->table,
				];
			}
		}

		return null;
	}

	/**
	 * Check if the plugin is running in safe mode.
	 *
	 * @return bool
	 *
	 * @noinspection PhpUndefinedConstantInspection
	 */
	public function is_safe_mode_active(): bool {
		return ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) ||
		       ! apply_filters( 'code_snippets/execute_snippets', true );
	}

	/**
	 * Quickly deactivate a snippet with minimal overhead.
	 *
	 * @param int    $snippet_id ID of the snippet to deactivate.
	 * @param string $table_name Name of the table where the snippet is stored.
	 *
	 * @return void
	 */
	private function quick_deactivate_snippet( int $snippet_id, string $table_name ) {
		global $wpdb;

		$active_shared_ids = get_option( 'active_shared_network_snippets', [] );
		$active_shared_ids = is_array( $active_shared_ids )
			? array_map( 'intval', $active_shared_ids )
			: [];

		if ( $table_name === $this->db->ms_table && in_array( $snippet_id, $active_shared_ids, true ) ) {
			unset( $active_shared_ids[ array_search( $snippet_id, $active_shared_ids, true ) ] );
			$active_shared_ids = array_values( $active_shared_ids );
			update_option( 'active_shared_network_snippets', $active_shared_ids );
			clean_active_snippets_cache( $table_name );
		} else {
			$wpdb->update(
				$table_name,
				[ 'active' => '0' ],
				[ 'id' => $snippet_id ],
				[ '%d' ],
				[ '%d' ]
			);
			clean_snippets_cache( $table_name );
		}
	}

	/**
	 * Evaluate a snippet.
	 *
	 * @param array  $snippet      Snippet data.
	 * @param ?array $edit_snippet Snippet being edited, if any.
	 *
	 * @return void
	 */
	private function evaluate_snippet( array $snippet, ?array $edit_snippet = null ) {
		$snippet_id = $snippet['id'];
		$code = $snippet['code'];
		$table_name = $snippet['table'];

		// If the snippet is a single-use snippet, deactivate it before execution to ensure that the process always happens.
		if ( 'single-use' === $snippet['scope'] ) {
			$this->quick_deactivate_snippet( $snippet_id, $table_name );
		}

		if ( ! is_null( $edit_snippet ) && $edit_snippet['id'] === $snippet_id && $edit_snippet['table'] === $table_name ) {
			return;
		}

		if ( apply_filters( 'code_snippets/allow_execute_snippet', true, $snippet_id, $table_name ) ) {
			execute_snippet( $code, $snippet_id );
		}
	}

	/**
	 * Evaluate applicable active snippets as early as possible.
	 *
	 * @return bool True if snippets were evaluated, false if safe mode is active.
	 */
	public function evaluate_early(): bool {
		if ( $this->is_safe_mode_active() ) {
			return false;
		}

		$scopes = [ 'global', 'single-use', is_admin() ? 'admin' : 'front-end', 'condition' ];
		$active_snippets = $this->db->fetch_active_snippets( $scopes );
		$edit_snippet = $this->get_currently_editing_snippet();

		foreach ( $active_snippets as $snippet ) {
			if ( 'condition' === $snippet['scope'] ) {
				$this->conditions[] = $snippet;
			} elseif ( 0 !== $snippet['condition_id'] ) {
				$this->snippets_with_condition[] = $snippet;
			} else {
				$this->evaluate_snippet( $snippet, $edit_snippet );
			}
		}

		return true;
	}

	/**
	 * Evaluate conditional snippets on the 'wp' action.
	 *
	 * @return void
	 */
	public function evaluate_conditional_snippets() {
		if ( $this->is_safe_mode_active() ) {
			return;
		}

		$condition_results = [];

		foreach ( $this->conditions as $condition ) {
			$condition_results[ $condition['id'] ] = evaluate_condition( $condition['code'] );
		}

		foreach ( $this->snippets_with_condition as $snippet ) {
			$condition_id = $snippet['condition_id'];

			if ( isset( $condition_results[ $condition_id ] ) ) {
				if ( $condition_results[ $condition_id ] ) {
					$this->evaluate_snippet( $snippet );
				}
			}
		}
	}
}
