<?php

namespace Code_Snippets;

class Conditions_Snippet_Handler implements Snippet_Type_Handler_Interface {
	public function get_file_extension(): string {
		return 'php';
	}

	public function get_dir_name(): string {
		return 'cond';
	}

	public function wrap_code( string $code ): string {
		return "<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }\n\n" . var_export( json_decode( $code, true ), true );
	}
}
