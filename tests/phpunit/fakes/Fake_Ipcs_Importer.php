<?php

namespace Code_Snippets\Tests;

use Code_Snippets\REST_API\Import\Plugins\Insert_PHP_Code_Snippet_Plugin_Importer;

/**
 * Test double for {@see Insert_PHP_Code_Snippet_Plugin_Importer}.
 *
 * Always reports the source plugin as active and returns whatever rows the test assigns to
 * the public {@see self::$rows} property, so Insert PHP Code Snippet scanner tests do not
 * need a real install.
 */
class Fake_Ipcs_Importer extends Insert_PHP_Code_Snippet_Plugin_Importer {

	/**
	 * Rows the fake importer should return from {@see self::get_data()}.
	 *
	 * @var array<int, array<string, mixed>|object>
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
	 * @return array<int, array<string, mixed>|object>
	 */
	public function get_data( array $ids_to_import = [] ): array {
		return $this->rows;
	}
}
