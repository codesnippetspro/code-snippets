<?php

namespace Code_Snippets\UnifiedSnippets;

use WP_Filesystem_Base;

/**
 * Thin read-only wrapper around the WP_Filesystem API used by the Tier 1 scanners.
 *
 * The WP_Filesystem instance is initialised lazily and shared across all scanners
 * in a request. When the API is unavailable (for example during early bootstrap
 * or in isolated unit tests), the reader falls back to native PHP file reads so
 * scanners can still function.
 *
 * @package Code_Snippets
 */
class Filesystem_Reader {

	/**
	 * Shared WP_Filesystem instance, or null if unavailable.
	 *
	 * @var WP_Filesystem_Base|null
	 */
	private static ?WP_Filesystem_Base $fs = null;

	/**
	 * Whether initialisation has been attempted this request.
	 *
	 * @var bool
	 */
	private static bool $initialised = false;

	/**
	 * Read the contents of a file.
	 *
	 * @param string $path Absolute path to the file.
	 *
	 * @return string|null File contents, or null if the file could not be read.
	 */
	public static function get_contents( string $path ): ?string {
		$fs = self::get_fs();

		if ( $fs && $fs->exists( $path ) ) {
			$contents = $fs->get_contents( $path );
			return false === $contents ? null : $contents;
		}

		if ( ! is_readable( $path ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$contents = file_get_contents( $path );
		return false === $contents ? null : $contents;
	}

	/**
	 * Determine whether a path is readable through the filesystem API.
	 *
	 * @param string $path Absolute path.
	 *
	 * @return bool
	 */
	public static function is_readable( string $path ): bool {
		$fs = self::get_fs();

		if ( $fs ) {
			return $fs->is_readable( $path );
		}

		return is_readable( $path );
	}

	/**
	 * Lazily initialise and retrieve the shared WP_Filesystem instance.
	 *
	 * @return WP_Filesystem_Base|null
	 */
	private static function get_fs(): ?WP_Filesystem_Base {
		if ( self::$initialised ) {
			return self::$fs;
		}

		self::$initialised = true;

		if ( ! defined( 'ABSPATH' ) ) {
			return null;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			$includes = ABSPATH . 'wp-admin/includes/file.php';
			if ( ! is_readable( $includes ) ) {
				return null;
			}
			require_once $includes;
		}

		if ( ! WP_Filesystem() ) {
			return null;
		}

		global $wp_filesystem;

		if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
			self::$fs = $wp_filesystem;
		}

		return self::$fs;
	}
}
