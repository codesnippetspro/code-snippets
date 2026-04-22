<?php

namespace Code_Snippets\Tests;

use Code_Snippets\REST_API\Import\Plugins\Insert_PHP_Code_Snippet_Plugin_Importer;

class Fake_Ipcs_Importer extends Insert_PHP_Code_Snippet_Plugin_Importer {

	public array $rows = [];

	public static function is_active(): bool {
		return true;
	}

	public function get_data( array $ids_to_import = [] ): array {
		return $this->rows;
	}
}
