<?php

namespace Code_Snippets\Tests;

use Code_Snippets\UnifiedSnippets\Scanners\Additional_CSS_Scanner;
use Code_Snippets\UnifiedSnippets\Scanners\Functions_Php_Scanner;
use Code_Snippets\UnifiedSnippets\Scanners\Htaccess_Scanner;
use Code_Snippets\UnifiedSnippets\Scanners\Mu_Plugins_Scanner;
use Code_Snippets\UnifiedSnippets\Scanners\Wp_Config_Scanner;

/**
 * Tests for the Tier 1 generic Unified Snippets scanners.
 *
 * @group unified-snippets
 */
class Unified_Scanners_Test extends TestCase {

	/**
	 * Temporary directory created per test for fixtures.
	 *
	 * @var string
	 */
	private string $tmp_dir = '';

	/**
	 * Create a fresh fixture directory before each test.
	 */
	public function set_up() {
		parent::set_up();

		$this->tmp_dir = sys_get_temp_dir() . '/cs-scanner-' . wp_generate_uuid4();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		mkdir( $this->tmp_dir, 0777, true );
	}

	/**
	 * Remove the fixture directory after each test.
	 */
	public function tear_down() {
		$this->rrmdir( $this->tmp_dir );
		parent::tear_down();
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $dir Directory path.
	 */
	private function rrmdir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		foreach ( (array) glob( $dir . '/*' ) as $path ) {
			if ( is_dir( $path ) ) {
				$this->rrmdir( $path );
			} else {
				wp_delete_file( $path );
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		rmdir( $dir );
	}

	/**
	 * Write a fixture file through the filesystem directly.
	 *
	 * @param string $path     Absolute file path.
	 * @param string $contents File contents.
	 */
	private function write_fixture( string $path, string $contents ): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $path, $contents );
	}

	/**
	 * The functions.php scanner extracts top-level functions and classes.
	 */
	public function test_functions_php_scanner_extracts_top_level_symbols() {
		$code = <<<'PHP'
<?php

function my_custom_function() {
    return 42;
}

class My_Plugin_Helper {
    public function run() {
        return true;
    }
}
PHP;

		$path = $this->tmp_dir . '/functions.php';
		$this->write_fixture( $path, $code );

		$scanner = new Functions_Php_Scanner(
			[
				'theme' => [
					'path' => $path,
					'name' => 'Fixture Theme',
				],
			]
		);

		$this->assertTrue( $scanner->is_available() );

		$results = $scanner->scan();

		$this->assertCount( 2, $results );

		$by_name = [];
		foreach ( $results as $snippet ) {
			$by_name[ $snippet->name ] = $snippet;
		}

		$this->assertArrayHasKey( 'my_custom_function', $by_name );
		$this->assertArrayHasKey( 'My_Plugin_Helper', $by_name );

		// Fixture layout:
		// L1:  <?php
		// L2:  blank
		// L3:  function my_custom_function() {
		// L4:      return 42;
		// L5:  }
		// L6:  blank
		// L7:  class My_Plugin_Helper {
		// L8:      public function run() {
		// L9:          return true;
		// L10:     }
		// L11: }.
		$this->assertSame( 3, $by_name['my_custom_function']->line_start );
		$this->assertSame( 5, $by_name['my_custom_function']->line_end );
		$this->assertSame( 7, $by_name['My_Plugin_Helper']->line_start );
		$this->assertSame( 11, $by_name['My_Plugin_Helper']->line_end );

		foreach ( $results as $snippet ) {
			$this->assertSame( 'php', $snippet->type );
			$this->assertSame( 'theme', $snippet->source_type );
			$this->assertSame( 'Fixture Theme', $snippet->source_name );
			$this->assertTrue( $snippet->is_active );
		}
	}

	/**
	 * The Additional CSS scanner reads the active theme's custom CSS post.
	 */
	public function test_additional_css_scanner_returns_customizer_css() {
		wp_update_custom_css_post( 'body { color: red; }' );

		$scanner = new Additional_CSS_Scanner();

		$this->assertTrue( $scanner->is_available() );

		$results = $scanner->scan();

		$this->assertCount( 1, $results );
		$snippet = $results[0];

		$this->assertSame( 'css', $snippet->type );
		$this->assertSame( 'customizer', $snippet->source_type );
		$this->assertSame( 'body { color: red; }', $snippet->code );
		$this->assertStringStartsWith( 'customizer://custom_css/', $snippet->source_path );

		wp_update_custom_css_post( '' );
	}

	/**
	 * The Additional CSS scanner returns nothing when there is no custom CSS.
	 */
	public function test_additional_css_scanner_returns_empty_when_no_css() {
		wp_update_custom_css_post( '' );

		$scanner = new Additional_CSS_Scanner();
		$results = $scanner->scan();

		$this->assertSame( [], $results );
	}

	/**
	 * The .htaccess scanner splits sections and classifies them correctly.
	 */
	public function test_htaccess_scanner_classifies_sections() {
		$htaccess = <<<'TXT'
# BEGIN WordPress
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
# END WordPress

# BEGIN Redirects
Redirect 301 /old /new
# END Redirects

<FilesMatch "\.env$">
    Require all denied
</FilesMatch>
TXT;

		$path = $this->tmp_dir . '/.htaccess';
		$this->write_fixture( $path, $htaccess );

		$scanner = new Htaccess_Scanner( $path );

		$this->assertTrue( $scanner->is_available() );

		$results = $scanner->scan();

		$this->assertCount( 3, $results );

		$by_name = [];
		foreach ( $results as $snippet ) {
			$by_name[ $snippet->name ] = $snippet;
		}

		$this->assertArrayHasKey( 'WordPress', $by_name );
		$this->assertSame( 'high', $by_name['WordPress']->risk_level );
		$this->assertFalse( $by_name['WordPress']->is_importable );
		$this->assertStringStartsWith( '[core]', $by_name['WordPress']->import_notes );

		$this->assertArrayHasKey( 'Redirects', $by_name );
		$this->assertTrue( $by_name['Redirects']->is_importable );
		$this->assertStringStartsWith( '[convertible]', $by_name['Redirects']->import_notes );

		$this->assertArrayHasKey( 'Custom', $by_name );
		$this->assertFalse( $by_name['Custom']->is_importable );
		$this->assertStringStartsWith( '[server-only]', $by_name['Custom']->import_notes );
	}

	/**
	 * The wp-config scanner reports only user-added lines grouped by contiguous blocks.
	 */
	public function test_wp_config_scanner_detects_user_additions() {
		$sample = <<<'PHP'
<?php
define( 'DB_NAME', 'database_name_here' );
define( 'DB_USER', 'username_here' );
$table_prefix = 'wp_';
require_once ABSPATH . 'wp-settings.php';
PHP;

		$config = <<<'PHP'
<?php
define( 'DB_NAME', 'production_db' );
define( 'DB_USER', 'production_user' );
define( 'WP_DEBUG', true );
define( 'DISALLOW_FILE_EDIT', true );
$table_prefix = 'wp_';
require_once ABSPATH . 'wp-settings.php';
PHP;

		$sample_path = $this->tmp_dir . '/wp-config-sample.php';
		$config_path = $this->tmp_dir . '/wp-config.php';
		$this->write_fixture( $sample_path, $sample );
		$this->write_fixture( $config_path, $config );

		$scanner = new Wp_Config_Scanner( $config_path, $sample_path );

		$this->assertTrue( $scanner->is_available() );

		$results = $scanner->scan();

		$this->assertCount( 1, $results );
		$snippet = $results[0];

		$this->assertSame( 'config', $snippet->type );
		$this->assertSame( 'core', $snippet->source_type );
		$this->assertFalse( $snippet->is_importable );
		$this->assertSame( 'high', $snippet->risk_level );
		$this->assertStringContainsString( "define( 'WP_DEBUG', true );", $snippet->code );
		$this->assertStringContainsString( "define( 'DISALLOW_FILE_EDIT', true );", $snippet->code );
		$this->assertSame( 4, $snippet->line_start );
		$this->assertSame( 5, $snippet->line_end );
	}

	/**
	 * The mu-plugins scanner emits one snippet per file in the configured directory.
	 */
	public function test_mu_plugins_scanner_reads_files() {
		$file = $this->tmp_dir . '/site-helper.php';
		$this->write_fixture(
			$file,
			"<?php\n/**\n * Plugin Name: Site Helper\n */\n\nreturn true;\n"
		);

		$scanner = new Mu_Plugins_Scanner( $this->tmp_dir );

		$this->assertTrue( $scanner->is_available() );

		$results = $scanner->scan();

		$this->assertCount( 1, $results );
		$snippet = $results[0];

		$this->assertSame( 'php', $snippet->type );
		$this->assertSame( 'mu-plugin', $snippet->source_type );
		$this->assertSame( $file, $snippet->source_path );
		$this->assertSame( 'Site Helper', $snippet->name );
		$this->assertStringContainsString( 'return true;', $snippet->code );
		$this->assertSame( 1, $snippet->line_start );
		$this->assertGreaterThan( 1, $snippet->line_end );
	}

	/**
	 * The .htaccess classifier uses first-match-wins: a section containing both a
	 * high-risk server-only directive and a convertible one is marked server-only.
	 */
	public function test_htaccess_scanner_first_match_wins() {
		$htaccess = <<<'TXT'
# BEGIN Mixed
Redirect 301 /old /new
php_value upload_max_filesize 64M
# END Mixed
TXT;

		$path = $this->tmp_dir . '/.htaccess';
		$this->write_fixture( $path, $htaccess );

		$results = ( new Htaccess_Scanner( $path ) )->scan();

		$this->assertCount( 1, $results );
		$this->assertSame( 'Mixed', $results[0]->name );
		$this->assertSame( 'high', $results[0]->risk_level );
		$this->assertFalse( $results[0]->is_importable );
		$this->assertStringStartsWith( '[server-only]', $results[0]->import_notes );
	}

	/**
	 * An unmatched BEGIN marker is reported as a high-risk server-only section
	 * instead of silently swallowing the rest of the file.
	 */
	public function test_htaccess_scanner_reports_unmatched_begin() {
		$htaccess = <<<'TXT'
# BEGIN Orphan
RewriteEngine On
RewriteRule ^foo$ /bar [L]
TXT;

		$path = $this->tmp_dir . '/.htaccess';
		$this->write_fixture( $path, $htaccess );

		$results = ( new Htaccess_Scanner( $path ) )->scan();

		$this->assertCount( 1, $results );
		$this->assertSame( 'Orphan', $results[0]->name );
		$this->assertSame( 'high', $results[0]->risk_level );
		$this->assertFalse( $results[0]->is_importable );
		$this->assertStringContainsString( 'Unclosed BEGIN marker', $results[0]->import_notes );
	}
}
