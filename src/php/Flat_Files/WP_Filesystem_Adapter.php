<?php

namespace Code_Snippets\Flat_Files;

use Code_Snippets\Flat_Files\Interfaces\Filesystem_Adapter;
use WP_Filesystem_Base;

/**
 * Adaptor that implements File_System_Interface using the WP_Filesystem API.
 */
class WP_Filesystem_Adapter implements Filesystem_Adapter {

	/**
	 * Instance of WP_Filesystem
	 *
	 * @var WP_Filesystem_Base
	 */
	private $fs;

	/**
	 * Constructor.
	 *
	 * Initialises WP_Filesystem and stores the instance in this class.
	 */
	public function __construct() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();
		global $wp_filesystem;
		$this->fs = $wp_filesystem;
	}

	/**
	 * Writes a string to a file.
	 *
	 * @param string    $path     Remote path to the file where to write the data.
	 * @param string    $contents The data to write.
	 * @param int|false $chmod    Optional. The file permissions as octal number, usually 0644. Default false.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function put_contents( string $path, string $contents, $chmod ): bool {
		return $this->fs->put_contents( $path, $contents, $chmod );
	}

	/**
	 * Checks if a file or directory exists.
	 *
	 * @param string $path Path to file or directory.
	 *
	 * @return bool Whether $path exists or not.
	 */
	public function exists( string $path ): bool {
		return $this->fs->exists( $path );
	}

	/**
	 * Deletes a file or directory.
	 *
	 * @param string       $file      Path to the file or directory.
	 * @param bool         $recursive Optional. If set to true, deletes files and folders recursively.
	 *                                Default false.
	 * @param string|false $type      Type of resource. 'f' for file, 'd' for directory.
	 *                                Default false.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete( string $file, bool $recursive = false, $type = false ): bool {
		return $this->fs->delete( $file, $recursive, $type );
	}

	/**
	 * Checks if resource is a directory.
	 * *
	 *
	 * @param string $path Directory path.
	 *
	 * @return bool Whether $path is a directory.
	 */
	public function is_dir( string $path ): bool {
		return $this->fs->is_dir( $path );
	}

	/**
	 * Creates a directory.
	 *
	 * @param string    $path         Path for new directory.
	 * @param int|false $chmod        Optional. The permissions as octal number (or false to skip chmod).
	 *                                Default false.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function mkdir( string $path, $chmod ): bool {
		return $this->fs->mkdir( $path, $chmod );
	}

	/**
	 * Deletes a directory.
	 *
	 * @param string $path      Path to directory.
	 * @param bool   $recursive Optional. Whether to recursively remove files/directories.
	 *                          Default false.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function rmdir( string $path, bool $recursive = false ): bool {
		return $this->fs->rmdir( $path, $recursive );
	}

	/**
	 * Changes filesystem permissions.
	 *
	 * @param string    $path  Path to the file.
	 * @param int|false $chmod Optional. The permissions as octal number, usually 0644 for files.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function chmod( string $path, $chmod ): bool {
		return $this->fs->chmod( $path, $chmod );
	}

	/**
	 * Checks if a file or directory is writable.
	 *
	 * @param string $path Path to file or directory.
	 *
	 * @return bool Whether $path is writable.
	 */
	public function is_writable( string $path ): bool {
		return $this->fs->is_writable( $path );
	}
}
