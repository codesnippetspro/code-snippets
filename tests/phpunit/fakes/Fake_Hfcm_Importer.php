<?php

namespace Code_Snippets\Tests;

use Code_Snippets\REST_API\Import\Plugins\Header_Footer_Code_Manager_Plugin_Importer;

/**
 * Test double for {@see Header_Footer_Code_Manager_Plugin_Importer}.
 *
 * Always reports the source plugin as active and returns whatever rows the test assigns to
 * the public {@see self::$rows} property, so HFCM scanner tests do not need a real install.
 */
class Fake_Hfcm_Importer extends Header_Footer_Code_Manager_Plugin_Importer {

	/**
	 * Rows the fake importer should return from {@see self::get_data()}.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $rows = [];

	/**
	 * Force the source plugin to look active.
	 *
	 * @return bool Always true.
	 */
	public static function is_active(): bool {
		return true;
	}

	/**
	 * Return the rows assigned to this fake.
	 *
	 * @param array<int|string> $ids_to_import Unused; satisfies the parent signature.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_data( array $ids_to_import = [] ): array {
		return $this->rows;
	}
}
