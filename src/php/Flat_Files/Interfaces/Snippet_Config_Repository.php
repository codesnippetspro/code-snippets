<?php

namespace Code_Snippets\Flat_Files\Interfaces;

use Code_Snippets\Model\Snippet;

/**
 * Interface for storing snippet configuration within a flat file.
 */
interface Snippet_Config_Repository {

	/**
	 * Load configuration from a directory.
	 *
	 * @param string $base_dir Full filesystem path to directory.
	 *
	 * @return array Loaded configuration.
	 */
	public function load( string $base_dir ): array;

	/**
	 * Store configuration.
	 *
	 * @param string $base_dir        Full filesystem path to configuration directory.
	 * @param array  $active_snippets List of active snippets.
	 *
	 * @return void
	 */
	public function save( string $base_dir, array $active_snippets ): void;

	/**
	 * Update stored configuration for a snippet.
	 *
	 * @param string    $base_dir Full filesystem path to configuration directory.
	 * @param Snippet   $snippet  Snippet to update.
	 * @param bool|null $remove   Whether to remove the snippet from the configuration.
	 *
	 * @return void
	 */
	public function update( string $base_dir, Snippet $snippet, ?bool $remove = false ): void;
}
