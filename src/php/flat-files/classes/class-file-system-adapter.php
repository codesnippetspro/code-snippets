<?php
namespace Code_Snippets;

class WordPress_File_System_Adapter implements File_System_Interface {
	private $fs;

	public function __construct() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		global $wp_filesystem;
		$this->fs = $wp_filesystem;
	}

	public function put_contents( string $path, string $contents, $chmod ) {
		return $this->fs->put_contents( $path, $contents, $chmod );
	}

	public function exists( string $path ): bool {
		return $this->fs->exists( $path );
	}

	public function delete( $file, $recursive = false, $type = false ): bool {
		return $this->fs->delete( $file, $recursive, $type );
	}

	public function is_dir( string $path ): bool {
		return $this->fs->is_dir( $path );
	}

	public function mkdir( string $path, $chmod ) {
		return $this->fs->mkdir( $path, $chmod );
	}

	public function rmdir( string $path, bool $recursive = false ): bool {
		return $this->fs->rmdir( $path, $recursive );
	}

	public function chmod( string $path, $chmod ): bool {
		return $this->fs->chmod( $path, $chmod );
	}

	public function is_writable( string $path ): bool {
		return $this->fs->is_writable( $path );
	}
}
