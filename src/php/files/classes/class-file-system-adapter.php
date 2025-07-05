<?php
namespace Code_Snippets;

class WordPress_Filesystem_Adapter implements File_System_Interface {
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

    public function delete( string $path ): bool {
        return $this->fs->delete( $path );
    }

    public function is_dir( string $path ): bool {
        return $this->fs->is_dir( $path );
    }

    public function mkdir( string $path, $chmod ) {
        return $this->fs->mkdir( $path, $chmod );
    }
}
