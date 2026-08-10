<?php

namespace Code_Snippets\Flat_Files\Interfaces;

/**
 * Interface for storing and loading from flat files.
 */
interface Filesystem_Adapter {
	/**
	 * Writes a string to a file.
	 *
	 * @param string    $path     Remote path to the file where to write the data.
	 * @param string    $contents The data to write.
	 * @param int|false $chmod    Optional. The file permissions as octal number, usually 0644. Default false.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function put_contents( string $path, string $contents, $chmod ): bool;

	/**
	 * Checks if a file or directory exists.
	 *
	 * @param string $path Path to file or directory.
	 *
	 * @return bool Whether $path exists or not.
	 */
	public function exists( string $path ): bool;

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
	public function delete( string $file, bool $recursive = false, $type = false ): bool;

	/**
	 * Checks if resource is a directory.
	 * *
	 *
	 * @param string $path Directory path.
	 *
	 * @return bool Whether $path is a directory.
	 */
	public function is_dir( string $path ): bool;

	/**
	 * Creates a directory.
	 *
	 * @param string    $path  Path for new directory.
	 * @param int|false $chmod Optional. The permissions as octal number (or false to skip chmod).
	 *                         Default false.
	 *
	 * @return bool True on success, false on failure.
	 * /
	 */
	public function mkdir( string $path, $chmod ): bool;

	/**
	 * Deletes a directory.
	 *
	 * @param string $path      Path to directory.
	 * @param bool   $recursive Optional. Whether to recursively remove files/directories.
	 *                          Default false.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function rmdir( string $path, bool $recursive = false ): bool;

	/**
	 * Changes filesystem permissions.
	 *
	 * @param string    $path  Path to the file.
	 * @param int|false $chmod Optional. The permissions as octal number, usually 0644 for files.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function chmod( string $path, $chmod ): bool;

	/**
	 * Checks if a file or directory is writable.
	 *
	 * @param string $path Path to file or directory.
	 *
	 * @return bool Whether $path is writable.
	 */
	public function is_writable( string $path ): bool;
}
