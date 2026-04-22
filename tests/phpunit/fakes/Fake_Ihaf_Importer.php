<?php

namespace Code_Snippets\Tests;

use Code_Snippets\REST_API\Import\Plugins\Insert_Headers_And_Footers_Plugin_Importer;

/**
 * Fake importer for WPCode (Insert Headers and Footers) used in DB-scanner adapter tests.
 *
 * Overrides the two entry points the scanner uses so no plugin/database is required.
 */
class Fake_Ihaf_Importer extends Insert_Headers_And_Footers_Plugin_Importer {

	/**
	 * Canned data returned by {@see self::get_data()}.
	 *
	 * @var array<int, array<string, mixed>>
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
	 * @return array<int, array<string, mixed>>
	 */
	public function get_data( array $ids_to_import = [] ): array {
		return $this->rows;
	}
}
