<?php

namespace Code_Snippets\Migration\Export;

use Code_Snippets\Model\Snippet;
use WP_Error;
use WP_Filesystem_Direct;
use ZipArchive;
use function sanitize_title;
use function wp_delete_file;
use function wp_tempnam;

/**
 * Builds downloadable snippet code files.
 *
 * @package Code_Snippets
 */
class Download_Code {

	/**
	 * Bulk download archive filename.
	 */
	private const ARCHIVE_FILENAME = 'snippets.code-snippets.zip';

	/**
	 * Content types keyed by snippet type.
	 *
	 * @var array<string, string>
	 */
	private const CONTENT_TYPES = [
		'php'  => 'text/php',
		'html' => 'text/php',
		'css'  => 'text/css',
		'js'   => 'text/javascript',
		'cond' => 'application/json',
	];

	/**
	 * File extensions keyed by snippet type.
	 *
	 * @var array<string, string>
	 */
	private const FILE_EXTENSIONS = [
		'php'  => 'php',
		'html' => 'php',
		'css'  => 'css',
		'js'   => 'js',
		'cond' => 'json',
	];

	/**
	 * Build a single downloadable code file.
	 *
	 * @param Snippet $snippet Snippet to export.
	 *
	 * @return array{filename:string, content_type:string, content:string}
	 */
	public static function build_snippet_download( Snippet $snippet ): array {
		$export = new Export_Code( [ $snippet->id ], $snippet->network );

		return [
			'filename'     => self::build_filename( $snippet ),
			'content_type' => self::CONTENT_TYPES[ $snippet->type ] ?? 'application/octet-stream',
			'content'      => $export->generate_export(),
		];
	}

	/**
	 * Build a ZIP archive containing multiple snippet downloads.
	 *
	 * @param Snippet[] $snippets Snippets to export.
	 *
	 * @return array{filename:string, content_type:string, content:string}|WP_Error
	 */
	public static function build_archive_download( array $snippets ) {
		if ( ! class_exists( ZipArchive::class ) ) {
			return new WP_Error(
				'zip_archive_unavailable',
				__( 'Multiple snippet downloads require the ZipArchive PHP extension.', 'code-snippets' ),
				[ 'status' => 501 ]
			);
		}

		$temp_file = wp_tempnam( self::ARCHIVE_FILENAME );

		if ( ! is_string( $temp_file ) ) {
			return new WP_Error(
				'zip_archive_temp_file_failed',
				__( 'The temporary download archive could not be created.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
		}

		$zip = new ZipArchive();
		$open_result = $zip->open( $temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE );

		if ( true !== $open_result ) {
			wp_delete_file( $temp_file );

			return new WP_Error(
				'zip_archive_open_failed',
				__( 'The snippet download archive could not be created.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
		}

		$used_filenames = [];

		foreach ( $snippets as $snippet ) {
			$download = self::build_snippet_download( $snippet );
			$filename = self::get_unique_filename( $download['filename'], $snippet, $used_filenames );

			$zip->addFromString( $filename, $download['content'] );
			$used_filenames[ $filename ] = true;
		}

		$zip->close();

		$filesystem = new WP_Filesystem_Direct( null );
		$content = $filesystem->get_contents( $temp_file );
		wp_delete_file( $temp_file );

		if ( ! is_string( $content ) ) {
			return new WP_Error(
				'zip_archive_read_failed',
				__( 'The snippet download archive could not be read.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
		}

		return [
			'filename'     => self::ARCHIVE_FILENAME,
			'content_type' => 'application/zip',
			'content'      => $content,
		];
	}

	/**
	 * Build a deterministic filename for a snippet download.
	 *
	 * @param Snippet $snippet Snippet to export.
	 *
	 * @return string
	 */
	private static function build_filename( Snippet $snippet ): string {
		$title = sanitize_title( $snippet->name );

		if ( '' === $title ) {
			$title = "snippet-$snippet->id";
		}

		$extension = self::FILE_EXTENSIONS[ $snippet->type ] ?? 'txt';

		return "$title.code-snippets.$extension";
	}

	/**
	 * Avoid duplicate filenames within the same archive.
	 *
	 * @param string             $filename       Proposed filename.
	 * @param Snippet            $snippet        Snippet being exported.
	 * @param array<string,bool> $used_filenames Archive filenames already in use.
	 *
	 * @return string
	 */
	private static function get_unique_filename( string $filename, Snippet $snippet, array $used_filenames ): string {
		if ( ! isset( $used_filenames[ $filename ] ) ) {
			return $filename;
		}

		$extension = self::FILE_EXTENSIONS[ $snippet->type ] ?? 'txt';
		$title = sanitize_title( $snippet->name );

		if ( '' === $title ) {
			$title = 'snippet';
		}

		$counter = 2;
		$unique_filename = sprintf( '%s-%d.code-snippets.%s', $title, $snippet->id, $extension );

		while ( isset( $used_filenames[ $unique_filename ] ) ) {
			$unique_filename = sprintf( '%s-%d-%d.code-snippets.%s', $title, $snippet->id, $counter, $extension );
			++$counter;
		}

		return $unique_filename;
	}
}
