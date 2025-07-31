<?php

namespace Evaluation;

use Code_Snippets\DB;
use Code_Snippets\REST_API\Snippets_REST_Controller;
use function Code_Snippets\clean_active_snippets_cache;
use function Code_Snippets\clean_snippets_cache;
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
	 * List of active snippets.
	 *
	 * @var array{
	 *     id: int,
	 *     code: string,
	 *     scope: string,
	 *     table: string
	 * }
	 */
	private array $active_snippets = [];

	/**
	 * Class constructor.
	 *
	 * @param DB $db Database class instance.
	 */
	public function __construct( DB $db ) {
		$this->db = $db;
		add_action( 'plugins_loaded', [ $this, 'evaluate_early' ], 1 );
	}

	/**
	 * Fetch active snippets from the database.
	 *
	 * This method retrieves all active snippets from the database, excluding those that are currently being edited.
	 * It also checks the request context to determine if the snippets should be executed.
	 */
	private function fetch_active_snippets() {
		$scopes = [ 'global', 'single-use', is_admin() ? 'admin' : 'front-end' ];
		$data = $this->db->fetch_active_snippets( $scopes );

		// Detect if a snippet is currently being edited, and if so, spare it from execution.
		$edit_id = 0;
		$edit_table = $this->db->table;

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
			}
		}

		foreach ( $data as $table_name => $active_snippets ) {
			foreach ( $active_snippets as $snippet ) {
				$snippet_id = intval( $snippet['id'] );

				if ( $edit_id !== $snippet_id || $table_name !== $edit_table ) {
					$this->active_snippets[] = [
						'id'       => $snippet_id,
						'code'     => $snippet['code'],
						'scope'    => $snippet['scope'],
						'table'    => $table_name,
						'priority' => intval( $snippet['priority'] ),
					];
				}
			}
		}

		if ( count( $data ) > 1 ) {
			usort(
				$this->active_snippets,
				function ( $snippet_a, $snippet_b ) {
					return $snippet_a['priority'] <=> $snippet_b['priority'];
				}
			);
		}
	}

	/**
	 * Check if the plugin is running in safe mode.
	 *
	 * @return bool
	 */
	public function is_safe_mode_active(): bool {
		return ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) ||
		       ! apply_filters( 'code_snippets/execute_snippets', true );
	}

	/**
	 * Evaluate applicable active snippets as early as possible.
	 *
	 * @return bool True if snippets were evaluated, false if safe mode is active.
	 */
	public function evaluate_early(): bool {
		global $wpdb;

		// Bail early if safe mode is active.
		if ( $this->is_safe_mode_active() ) {
			return false;
		}

		$this->fetch_active_snippets();

		foreach ( $this->active_snippets as $snippet ) {
			$snippet_id = $snippet['id'];
			$code = $snippet['code'];
			$table_name = $snippet['table'];

			// If the snippet is a single-use snippet, deactivate it before execution to ensure that the process always happens.
			if ( 'single-use' === $snippet['scope'] ) {
				$active_shared_ids = get_option( 'active_shared_network_snippets', array() );

				if ( $table_name === $this->db->ms_table && is_array( $active_shared_ids ) && in_array( $snippet_id, $active_shared_ids, true ) ) {
					unset( $active_shared_ids[ array_search( $snippet_id, $active_shared_ids, true ) ] );
					$active_shared_ids = array_values( $active_shared_ids );
					update_option( 'active_shared_network_snippets', $active_shared_ids );
					clean_active_snippets_cache( $table_name );
				} else {
					$wpdb->update(
						$table_name,
						array( 'active' => '0' ),
						array( 'id' => $snippet_id ),
						array( '%d' ),
						array( '%d' )
					);
					clean_snippets_cache( $table_name );
				}
			}

			if ( apply_filters( 'code_snippets/allow_execute_snippet', true, $snippet_id, $table_name ) ) {
				execute_snippet( $code, $snippet_id );
			}
		}

		return true;
	}
}
