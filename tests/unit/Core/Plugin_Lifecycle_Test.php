<?php

namespace Code_Snippets\Core;

use Code_Snippets\UnitTestCase;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;
use function Code_Snippets\_get_tests_dir;

/**
 * Tests for deleting and updating the plugin the way WordPress does, in PHP processes of their own.
 */
class Plugin_Lifecycle_Test extends UnitTestCase {

	/**
	 * Plugin zip built from the repository, shared by the tests in this class.
	 *
	 * @var string|null
	 */
	private static ?string $package = null;

	/**
	 * Directory for the WordPress site the runner sets up.
	 *
	 * @var string
	 */
	private string $site_dir;

	/**
	 * Remove the shared plugin zip.
	 *
	 * @return void
	 */
	public static function tear_down_after_class() {
		if ( self::$package && file_exists( self::$package ) ) {
			unlink( self::$package ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		self::$package = null;
		parent::tear_down_after_class();
	}

	/**
	 * Prepare a content and plugins directory of the runner's own.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->site_dir = get_temp_dir() . 'code-snippets-lifecycle-' . wp_generate_password( 8, false );
		wp_mkdir_p( $this->site_dir . '/content/themes' );
		wp_mkdir_p( $this->site_dir . '/plugins' );
	}

	/**
	 * Remove the runner's tables and directories.
	 *
	 * @return void
	 */
	public function tear_down() {
		$this->run_step( 'cleanup' );
		self::remove_directory( $this->site_dir );
		parent::tear_down();
	}

	/**
	 * Run one step of the runner script in a PHP process of its own, and fail the test if it does not succeed.
	 *
	 * @param string   $step    Step to run.
	 * @param string[] $options Options for the step, as name=value.
	 *
	 * @return array<string, mixed> State of the plugin's data after the step.
	 */
	private function run_step( string $step, array $options = [] ): array {
		$arguments = array_merge(
			[ PHP_BINARY, dirname( __DIR__, 2 ) . '/plugin-lifecycle-runner.php', $step, _get_tests_dir(), $this->site_dir . '/content' ],
			$options
		);

		$output = [];
		$exit_code = 0;
		exec( implode( ' ', array_map( 'escapeshellarg', $arguments ) ) . ' 2>&1', $output, $exit_code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec

		$this->assertSame( 0, $exit_code, "The $step step failed: " . implode( "\n", $output ) );

		return 'cleanup' === $step ? [] : (array) json_decode( (string) end( $output ), true );
	}

	/**
	 * Build a zip of the plugin from the repository's src directory, as WordPress expects a plugin package.
	 *
	 * @return string Path to the zip.
	 */
	private static function get_package(): string {
		if ( self::$package ) {
			return self::$package;
		}

		$source = dirname( __DIR__, 3 ) . '/src';
		$package = get_temp_dir() . 'code-snippets-package-' . wp_generate_password( 8, false ) . '.zip';

		$zip = new ZipArchive();
		$zip->open( $package, ZipArchive::CREATE );

		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source, FilesystemIterator::SKIP_DOTS ) );

		// Coding standards tools are never loaded by the plugin, and make up most of the files in a development install.
		$skip = '#^vendor/(squizlabs|phpcsstandards|phpcompatibility|wp-coding-standards|dealerdirect)/#';

		foreach ( $files as $file ) {
			if ( preg_match( $skip, substr( $file->getPathname(), strlen( $source ) + 1 ) ) ) {
				continue;
			}

			$zip->addFile( $file->getPathname(), 'code-snippets/' . substr( $file->getPathname(), strlen( $source ) + 1 ) );
		}

		$zip->close();
		self::$package = $package;

		return $package;
	}

	/**
	 * Delete a directory and everything in it.
	 *
	 * @param string $dir Directory to delete.
	 *
	 * @return void
	 */
	private static function remove_directory( string $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$items = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $items as $item ) {
			// A linked directory is removed as a link, leaving what it points to alone.
			$item->isDir() && ! $item->isLink() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	/**
	 * Object caches the uninstaller meets.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function object_cache_provider(): array {
		return [
			'cache that flushes whole groups' => [ '' ],
			'cache that cannot flush groups'  => [ 'no-group-flush' ],
		];
	}

	/**
	 * Deleting the inactive plugin with complete uninstall on removes its data and finishes without an error.
	 *
	 * @dataProvider object_cache_provider
	 *
	 * @param string $object_cache Object cache to uninstall with.
	 *
	 * @return void
	 */
	public function test_deleting_the_plugin_removes_its_data_when_complete_uninstall_is_on( string $object_cache ): void {
		$this->run_step( 'install-wp' );
		$this->run_step( 'activate' );
		$this->run_step( 'request' );
		$this->run_step( 'add-data' );
		$this->run_step( 'deactivate' );

		$state = $this->run_step( 'uninstall', [ "cache=$object_cache" ] );

		$this->assertFalse( $state['table'], 'The snippets table was not removed.' );
		$this->assertFalse( $state['version'] );
		$this->assertFalse( $state['settings'] );
		$this->assertFalse( $state['dir_names'] );
		$this->assertFalse( $state['flat_dir'] );
	}

	/**
	 * Deleting the plugin while another edition stays installed keeps the data that edition uses.
	 *
	 * @return void
	 */
	public function test_deleting_the_plugin_keeps_the_data_while_another_edition_is_installed(): void {
		$this->run_step( 'install-wp' );
		$this->run_step( 'activate' );
		$this->run_step( 'request' );
		$this->run_step( 'add-data' );
		$this->run_step( 'deactivate' );
		$this->run_step( 'add-edition' );

		$state = $this->run_step( 'uninstall' );

		$this->assertTrue( $state['table'], 'The snippets table was removed.' );
		$this->assertContains( 'Snippet added by the test', $state['snippets'] );
		$this->assertTrue( $state['settings']['general']['complete_uninstall'] );
		$this->assertTrue( $state['flat_dir'] );
	}

	/**
	 * Orders in which both editions can be deleted together.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function deletion_order_provider(): array {
		return [
			'this plugin first'  => [ '' ],
			'other edition first' => [ 'other-first' ],
		];
	}

	/**
	 * Deleting both editions together finishes without an error and removes the data.
	 *
	 * @dataProvider deletion_order_provider
	 *
	 * @param string $order Which edition is deleted first.
	 *
	 * @return void
	 */
	public function test_deleting_both_editions_together_removes_the_data( string $order ): void {
		$plugins = 'plugins=' . $this->site_dir . '/plugins';

		$this->run_step( 'install-wp' );
		$this->run_step( 'install-plugin', [ $plugins, 'package=' . self::get_package() ] );
		$this->run_step( 'activate', [ $plugins ] );
		$this->run_step( 'request', [ $plugins ] );
		$this->run_step( 'add-data', [ $plugins ] );
		$this->run_step( 'deactivate', [ $plugins ] );
		$this->run_step( 'add-edition', [ $plugins, 'copy=1' ] );

		$state = $this->run_step( 'uninstall-all', [ $plugins, "order=$order" ] );

		$this->assertNull( $state['files'], 'The plugin files were not deleted.' );
		$this->assertFalse( $state['table'], 'The snippets table was not removed.' );
		$this->assertFalse( $state['settings'] );
		$this->assertFalse( $state['flat_dir'] );
	}

	/**
	 * Ways WordPress replaces the files of an installed plugin.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function update_method_provider(): array {
		return [
			'update from WordPress.org'       => [ 'update' ],
			'upload replacing the current one' => [ 'overwrite' ],
		];
	}

	/**
	 * Updating the plugin keeps the snippets and settings of a site that ran an earlier version, even with complete
	 * uninstall on, and does not add the sample snippets again.
	 *
	 * @dataProvider update_method_provider
	 *
	 * @param string $method Step that replaces the plugin's files.
	 *
	 * @return void
	 */
	public function test_updating_the_plugin_keeps_existing_snippets( string $method ): void {
		$plugins = 'plugins=' . $this->site_dir . '/plugins';
		$package = 'package=' . self::get_package();

		$this->run_step( 'install-wp' );
		$this->run_step( 'install-plugin', [ $plugins, $package ] );
		$this->run_step( 'activate', [ $plugins ] );
		$this->run_step( 'request', [ $plugins ] );
		$this->run_step( 'add-data', [ $plugins ] );
		$before = $this->run_step( 'from-version', [ $plugins, 'version=4.1.0' ] );

		$this->assertCount( 5, $before['snippets'], 'The site should start with the sample snippets and one more.' );

		$this->run_step( $method, [ $plugins, $package ] );
		$this->run_step( 'activate', [ $plugins ] );
		$after = $this->run_step( 'request', [ $plugins ] );

		$this->assertTrue( $after['active'] );
		$this->assertSame( $before['snippets'], $after['snippets'] );
		$this->assertSame( $after['files'], $after['version'], 'The data was not upgraded to the installed version.' );
		$this->assertTrue( $after['settings']['general']['complete_uninstall'] );
		$this->assertTrue( $after['flat_dir'] );
	}
}
