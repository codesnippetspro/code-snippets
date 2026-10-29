<?php

namespace Code_Snippets\Migration\Export;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use WP_Error;
use ZipArchive;
use function Code_Snippets\code_snippets;
use function Code_Snippets\save_snippet;

/**
 * Tests for snippet code downloads.
 *
 * @group export
 */
class Download_Code_Test extends UnitTestCase {

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->clear_all_snippets();
	}

	/**
	 * Clear all snippets from the database.
	 *
	 * @return void
	 */
	private function clear_all_snippets(): void {
		global $wpdb;

		$table_name = code_snippets()->db->get_table_name();
		$wpdb->query( "TRUNCATE TABLE $table_name" );
	}

	/**
	 * Downloading a single snippet uses the expected file extension and content type.
	 *
	 * @return void
	 */
	public function test_build_snippet_download_uses_type_based_filename(): void {
		$snippet = save_snippet(
			new Snippet(
				[
					'name'  => 'My HTML Snippet',
					'code'  => '<p>Hello world</p>',
					'scope' => 'content',
				]
			)
		);

		$download = Download_Code::build_snippet_download( $snippet );

		$this->assertSame( 'my-html-snippet.code-snippets.html', $download['filename'] );
		$this->assertSame( 'text/html', $download['content_type'] );
		$this->assertSame( "<p>Hello world</p>\n", $download['content'] );
	}

	/**
	 * Downloading a single PHP snippet adds a PHP opening tag.
	 *
	 * @return void
	 */
	public function test_build_snippet_download_wraps_php_snippets(): void {
		$snippet = save_snippet(
			new Snippet(
				[
					'name'  => 'My PHP Snippet',
					'code'  => 'echo "Hello world";',
					'scope' => 'global',
				]
			)
		);

		$download = Download_Code::build_snippet_download( $snippet );

		$this->assertSame( 'my-php-snippet.code-snippets.php', $download['filename'] );
		$this->assertSame( 'text/php', $download['content_type'] );
		$this->assertStringStartsWith( "<?php\n", $download['content'] );
		$this->assertStringContainsString( 'echo "Hello world";', $download['content'] );
	}

	/**
	 * Downloading multiple snippets builds a ZIP archive when ZipArchive is available.
	 *
	 * @return void
	 */
	public function test_build_archive_download_returns_zip_file(): void {
		$php_snippet = save_snippet(
			new Snippet(
				[
					'name'  => 'Bulk PHP Snippet',
					'code'  => 'echo "One";',
					'scope' => 'global',
				]
			)
		);

		$css_snippet = save_snippet(
			new Snippet(
				[
					'name'  => 'Bulk CSS Snippet',
					'code'  => 'body { color: red; }',
					'scope' => 'site-css',
				]
			)
		);

		$download = Download_Code::build_archive_download( [ $php_snippet, $css_snippet ] );

		if ( ! class_exists( ZipArchive::class ) ) {
			$this->assertInstanceOf( WP_Error::class, $download );
			$this->assertSame( 'zip_archive_unavailable', $download->get_error_code() );

			return;
		}

		$this->assertIsArray( $download );
		$this->assertMatchesRegularExpression( '/^code-snippets-\d+\.zip$/', $download['filename'] );
		$this->assertSame( 'application/zip', $download['content_type'] );

		$upload = wp_upload_bits( $download['filename'], null, $download['content'] );
		$temp_file = $upload['file'];

		$zip = new ZipArchive();
		$this->assertTrue( $zip->open( $temp_file ) );
		$this->assertStringContainsString( 'echo "One";', $zip->getFromName( 'bulk-php-snippet.code-snippets.php' ) );
		$this->assertStringContainsString( 'body { color: red; }', $zip->getFromName( 'bulk-css-snippet.code-snippets.css' ) );
		$zip->close();

		wp_delete_file( $temp_file );
	}
}
