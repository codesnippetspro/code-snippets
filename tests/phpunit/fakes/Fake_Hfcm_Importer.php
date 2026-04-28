<?php

namespace Code_Snippets\Tests;

use Code_Snippets\REST_API\Import\Plugins\Header_Footer_Code_Manager_Plugin_Importer;

class Fake_Hfcm_Importer extends Header_Footer_Code_Manager_Plugin_Importer {

	public static function is_active(): bool {
		return true;
	}

	public function get_data( array $ids_to_import = [] ): array {
		return $this->rows;
	}
}
