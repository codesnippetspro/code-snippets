<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Model\Snippet;
use function Code_Snippets\code_snippets;

defined( 'ABSPATH' ) || exit;

/**
 * Counts stored snippets by their editor type.
 */
class Snippet_Type_Counter {

	/**
	 * Count non-trashed snippets by type.
	 *
	 * @return array<string, int> Map of type name to snippet count, including 'all'.
	 */
	public function count(): array {
		global $wpdb;
		$table = code_snippets()->db->get_table_name();

		// The table name comes from the plugin database service and cannot be parameterized.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotSafe
		$results = $wpdb->get_results( "SELECT scope, COUNT(*) AS count FROM $table WHERE active >= 0 GROUP BY scope" );
		// phpcs:enable

		$counts = [ 'all' => 0 ];

		foreach ( $results as $row ) {
			$type = Snippet::get_type_from_scope( $row->scope );
			$counts[ $type ] = ( $counts[ $type ] ?? 0 ) + (int) $row->count;
			$counts['all'] += (int) $row->count;
		}

		return $counts;
	}
}
