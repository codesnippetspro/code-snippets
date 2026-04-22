<?php

namespace Code_Snippets\Tests;

use Code_Snippets\REST_API\Import\Plugins\Header_Footer_Code_Manager_Plugin_Importer;

/**
 * Fake importer for Header Footer Code Manager used in DB-scanner adapter tests.
 */
class Fake_Hfcm_Importer extends Header_Footer_Code_Manager_Plugin_Importer {

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
