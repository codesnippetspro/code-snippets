<?php

namespace Code_Snippets\Core;

use Code_Snippets\UnitTestCase;
use function Code_Snippets\_get_tests_dir;

/**
 * Tests for deleting the plugin the way WordPress does, with the plugin not loaded.
 */
class Uninstall_File_Test extends UnitTestCase {

	/**
	 * Content directory used by the WordPress site the runner sets up.
	 *
	 * @var string
	 */
	private string $content_dir;

	/**
	 * Prepare a content directory of the runner's own.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->content_dir = get_temp_dir() . 'code-snippets-uninstall-' . wp_generate_password( 8, false );
		wp_mkdir_p( $this->content_dir . '/themes' );
	}

	/**
	 * Remove the runner's tables and content directory.
	 *
	 * @return void
	 */
	public function tear_down() {
		$this->run_step( 'cleanup' );
		$this->remove_directory( $this->content_dir );
		parent::tear_down();
	}

	/**
	 * Run one step of the runner script in a PHP process of its own.
	 *
	 * @param string $step  Step to run.
	 * @param string $extra Extra argument for the step.
	 *
	 * @return array{0: int, 1: string} Exit code and output.
	 */
	private function run_step( string $step, string $extra = '' ): array {
		$command = implode(
			' ',
			array_map(
				'escapeshellarg',
				[ PHP_BINARY, dirname( __DIR__, 2 ) . '/uninstall-runner.php', $step, _get_tests_dir(), $this->content_dir, $extra ]
			)
		);

		$output = [];
		$exit_code = 0;
		exec( "$command 2>&1", $output, $exit_code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec

		return [ $exit_code, implode( "\n", $output ) ];
	}

	/**
	 * Delete a directory and everything in it.
	 *
	 * @param string $dir Directory to delete.
	 *
	 * @return void
	 */
	private function remove_directory( string $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$items = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $items as $item ) {
			$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions
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
	 * @param string $object_cache Extra argument for the uninstall step that sets up the object cache.
	 *
	 * @return void
	 */
	public function test_deleting_the_plugin_removes_its_data_when_complete_uninstall_is_on( string $object_cache ): void {
		foreach ( [ 'install', 'activate' ] as $step ) {
			[ $exit_code, $output ] = $this->run_step( $step );
			$this->assertSame( 0, $exit_code, "The $step step failed: $output" );
		}

		[ $exit_code, $output ] = $this->run_step( 'uninstall', $object_cache );
		$this->assertSame( 0, $exit_code, "Uninstalling failed: $output" );

		$this->assertSame(
			[
				'table'    => null,
				'settings' => false,
				'version'  => false,
				'files'    => false,
			],
			json_decode( $output, true )
		);
	}
}
