<?php

namespace Code_Snippets;

use Code_Snippets\Vendor\MatthiasMullie\Minify;

class Css_Snippet_Handler implements Snippet_Type_Handler_Interface {
	public function get_file_extension(): string {
		return 'css';
	}

	public function get_dir_name(): string {
		return 'css';
	}

	public function wrap_code( string $code ): string {
		$setting = Settings\get_setting( 'general', 'minify_output' );

		if ( is_array( $setting ) && in_array( 'css', $setting, true ) ) {
			$minifier = new Minify\CSS( $code );
			$code = $minifier->minify();
		}

		return $code;
	}
}
