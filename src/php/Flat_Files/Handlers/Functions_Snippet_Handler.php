<?php

namespace Code_Snippets\Flat_Files\Handlers;

use Code_Snippets\Flat_Files\Interfaces\Snippet_Type_Handler;

/**
 * Snippet type handler for functions snippets.
 */
class Functions_Snippet_Handler implements Snippet_Type_Handler {

	/**
	 * Set 'php' as the file extension for functions snippets, so they can be directly loaded.
	 *
	 * @return string
	 */
	public function get_file_extension(): string {
		return 'php';
	}

	/**
	 * Store content snippets in a 'php' directory.
	 *
	 * @return string
	 */
	public function get_dir_name(): string {
		return 'php';
	}

	/**
	 * Wrap functions snippets by adding a header that disallows direct access.
	 *
	 * @param string $code Snippet PHP code.
	 *
	 * @return string Content snippet code with header prepended.
	 */
	public function wrap_code( string $code ): string {
		return "<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }\n\n" . $code;
	}
}
