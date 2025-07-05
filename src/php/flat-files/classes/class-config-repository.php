<?php
namespace Code_Snippets;

class Snippet_Config_Repository implements Snippet_Config_Repository_Interface {

	private File_System_Interface $fs;

	public function __construct( File_System_Interface $fs ) {
		$this->fs = $fs;
	}

	public function load( string $base_dir ): array {
		$config_file_path = trailingslashit( $base_dir ) . 'index.php';

		return is_file( $config_file_path )
			? require $config_file_path
			: [];
	}

	public function save( string $base_dir, array $active_snippets ): void {
		$config_file_path = trailingslashit( $base_dir ) . 'index.php';

		$file_content = "<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }\n\nreturn " .
			var_export( $active_snippets, true ) .
			";\n";

		$this->fs->put_contents( $config_file_path, $file_content, FS_CHMOD_FILE );
	}

	public function update( string $base_dir, Snippet $snippet, ?bool $remove = false ): void {
		$active_snippets = $this->load( $base_dir );

		if ( ! $remove ) {
			$active_snippets[ $snippet->id ] = $snippet->get_fields();
		} else {
			unset( $active_snippets[ $snippet->id ] );
		}

		$this->save( $base_dir, $active_snippets );
	}
}
