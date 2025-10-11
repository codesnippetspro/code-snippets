<?php

namespace Code_Snippets;

interface Snippet_Type_Handler_Interface {
	public function get_file_extension(): string;
	public function get_dir_name(): string;
	public function wrap_code( string $code ): string;
}
