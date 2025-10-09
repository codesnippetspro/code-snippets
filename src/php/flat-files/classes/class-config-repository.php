<?php
namespace Code_Snippets;

class Snippet_Config_Repository implements Snippet_Config_Repository_Interface {

	const CONFIG_FILE_NAME = 'index.php';

	private File_System_Interface $fs;

	public function __construct( File_System_Interface $fs ) {
		$this->fs = $fs;
	}

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

	public function save( string $base_dir, array $active_snippets ): void {
		$config_file_path = trailingslashit( $base_dir ) . static::CONFIG_FILE_NAME;

		ksort( $active_snippets );

		$file_content = "<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }\n\nreturn " .
			var_export( $active_snippets, true ) .
			";\n";

		$this->fs->put_contents( $config_file_path, $file_content, FS_CHMOD_FILE );

		if ( is_file( $config_file_path ) ) {
			if ( function_exists( 'opcache_invalidate' ) ) {
				opcache_invalidate( $config_file_path, true );
			}
		}
	}

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
