<?php

namespace Code_Snippets\Admin\Menus;

use function Code_Snippets\get_snippets;

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
		$counts = [ 'all' => 0 ];

		foreach ( get_snippets() as $snippet ) {
			if ( $snippet->trashed ) {
				continue;
			}

			$counts[ $snippet->type ] = ( $counts[ $snippet->type ] ?? 0 ) + 1;
			++$counts['all'];
		}

		return $counts;
	}
}
