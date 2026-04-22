<?php

namespace Code_Snippets\Tests;

use Code_Snippets\REST_API\Import\Plugins\Insert_PHP_Code_Snippet_Plugin_Importer;

/**
 * Fake importer for Insert PHP Code Snippet used in DB-scanner adapter tests.
 */
class Fake_Ipcs_Importer extends Insert_PHP_Code_Snippet_Plugin_Importer {

	/**
	 * Canned data returned by {@see self::get_data()}.
	 *
	 * @var array<int, object>
	 */
	public array $rows = [];

	/**
	 * {@inheritDoc}
	 */
	public static function is_active(): bool {
		return true;
	}

	/**
	 * Return the canned rows regardless of the requested IDs.
	 *
	 * @param array<int, int> $ids_to_import Unused.
	 *
	 * @return array<int, object>
	 */
	public function get_data( array $ids_to_import = [] ): array {
		return $this->rows;
	}
}
