<?php

namespace Code_Snippets;

class Php_Snippet_Handler implements Snippet_Type_Handler_Interface {
	public function get_file_extension(): string {
		return 'php';
	}

	public function get_dir_name(): string {
		return 'php';
	}

	public function wrap_code( string $code ): string {
		$output = "<?php\n\n" . apply_filters( 'code_snippets_php_snippet_file_code', $code );
		return $output;
	}
}
