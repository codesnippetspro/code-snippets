<?php

namespace Code_Snippets\Flat_Files\Interfaces;

/**
 * Interface for handling flat file operations for a specific snippet type.
 */
interface Snippet_Type_Handler {

	/**
	 * Retrieve the file extension to use when creating files.
	 *
	 * @return string File extension, without the period.
	 */
	public function get_file_extension(): string;

	/**
	 * Retrieve the name of the directory to store flat files in for this type.
	 *
	 * @return string
	 */
	public function get_dir_name(): string;

	/**
	 * Prepare code for writing by prepending or appending additional content.
	 *
	 * @param string $code Snippet code.
	 *
	 * @return string Writable code.
	 */
	public function wrap_code( string $code ): string;
}
