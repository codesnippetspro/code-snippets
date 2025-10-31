<?php

namespace Code_Snippets\Flat_Files\Handlers;

use Code_Snippets\Flat_Files\Interfaces\Snippet_Type_Handler;

/**
 * Snippet type handler for content snippets.
 */
class Content_Snippet_Handler implements Snippet_Type_Handler {

	/**
	 * Set 'php' as the file extension for content snippets, as they can contain embedded PHP code.
	 *
	 * @return string
	 */
	public function get_file_extension(): string {
		return 'php';
	}

	/**
	 * Store content snippets in an 'html' directory.
	 *
	 * @return string
	 */
	public function get_dir_name(): string {
		return 'html';
	}

	/**
	 * Wrap content snippets by adding a header that disallows direct access.
	 *
	 * @param string $code Content snippet code.
	 *
	 * @return string Content snippet code with header prepended.
	 */
	public function wrap_code( string $code ): string {
		return "<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }\n\n?>\n\n" . $code;
	}
}
