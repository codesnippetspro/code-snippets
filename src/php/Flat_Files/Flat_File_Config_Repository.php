<?php

namespace Code_Snippets\Flat_Files;

use Code_Snippets\Flat_Files\Interfaces\Filesystem_Adapter;
use Code_Snippets\Flat_Files\Interfaces\Snippet_Config_Repository;
use Code_Snippets\Model\Snippet;

/**
 * Snippet configuration repository implementation.
 */
class Flat_File_Config_Repository implements Snippet_Config_Repository {

	/**
	 * Name of file to store configuration.
	 */
	private const CONFIG_FILE_NAME = 'index.php';

	/**
	 * Adapter to use for connecting to the filesystem.
	 *
	 * @var Filesystem_Adapter
	 */
	private Filesystem_Adapter $fs;

	/**
	 * Constructor.
	 *
	 * @param Filesystem_Adapter $fs Adapter to use for connecting to the filesystem.
	 */
	public function __construct( Filesystem_Adapter $fs ) {
		$this->fs = $fs;
	}

	/**
	 * Load configuration from a directory.
	 *
	 * @param string $base_dir Full filesystem path to directory.
	 *
	 * @return array Loaded configuration.
	 */
	public function load( string $base_dir ): array {
		$config_file_path = trailingslashit( $base_dir ) . static::CONFIG_FILE_NAME;

		if ( is_file( $config_file_path ) ) {
			if ( function_exists( 'opcache_invalidate' ) ) {
				opcache_invalidate( $config_file_path, true );
			}
			return require $config_file_path;
		}
		return [];
	}

	/**
	 * Store configuration.
	 *
	 * @param string $base_dir        Full filesystem path to configuration directory.
	 * @param array  $active_snippets List of active snippets.
	 *
	 * @return void
	 *
	 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_var_export
	 */
	public function save( string $base_dir, array $active_snippets ): void {
		$config_file_path = trailingslashit( $base_dir ) . static::CONFIG_FILE_NAME;

		ksort( $active_snippets );

		$file_content = sprintf(
			"<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }\n\nreturn %s;\n",
			var_export( $active_snippets, true )
		);

		$this->fs->put_contents( $config_file_path, $file_content, FS_CHMOD_FILE );

		if ( is_file( $config_file_path ) ) {
			if ( function_exists( 'opcache_invalidate' ) ) {
				opcache_invalidate( $config_file_path, true );
			}
		}
	}

	/**
	 * Update stored configuration for a snippet.
	 *
	 * @param string    $base_dir Full filesystem path to configuration directory.
	 * @param Snippet   $snippet  Snippet to update.
	 * @param bool|null $remove   Whether to remove the snippet from the configuration.
	 *
	 * @return void
	 */
	public function update( string $base_dir, Snippet $snippet, ?bool $remove = false ): void {
		$active_snippets = $this->load( $base_dir );

		if ( $remove ) {
			unset( $active_snippets[ $snippet->id ] );
		} else {
			$active_snippets[ $snippet->id ] = $snippet->get_fields();
		}

		$this->save( $base_dir, $active_snippets );
	}
}
