<?php

namespace Code_Snippets;

class Html_Snippet_Handler implements Snippet_Type_Handler_Interface {
	public function get_file_extension(): string {
		return 'php';
	}

	public function get_dir_name(): string {
		return 'html';
	}

	public function wrap_code( string $code ): string {
		return "<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }\n\n?>\n\n" . $code;
	}
}
