<?php

namespace Code_Snippets\Flat_Files;

use Code_Snippets\Flat_Files\Interfaces\Filesystem_Adapter;
use Code_Snippets\Flat_Files\Interfaces\Snippet_Config_Repository;
use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use function Code_Snippets\save_snippet;
use function Code_Snippets\update_snippet_fields;

/**
 * Tests for flat-file execution hooks.
 *
 * @group flat-files
 */
class Flat_Files_Hooks_Test extends UnitTestCase {

	/**
	 * Build a Snippet_Files instance with a stub filesystem adapter.
	 *
	 * @param Filesystem_Adapter $fs Filesystem adapter.
	 *
	 * @return Snippet_Files
	 */
	private function build_snippet_files( Filesystem_Adapter $fs ): Snippet_Files {
		$config_repo = new class() implements Snippet_Config_Repository {

			/**
			 * Load a list of active snippets.
			 *
			 * @param string $base_dir Base directory.
			 *
			 * @return array<string, mixed>[]
			 */
			public function load( string $base_dir ): array {
				return [];
			}

			/**
			 * Save the active snippets list.
			 *
			 * @param string $base_dir        Base directory.
			 * @param array  $active_snippets Active snippets.
			 *
			 * @return void
			 */
			public function save( string $base_dir, array $active_snippets ): void {
			}

			/**
			 * Update a snippet entry in the active config.
			 *
			 * @param string    $base_dir Base directory.
			 * @param Snippet   $snippet  Snippet.
			 * @param bool|null $remove   Whether to remove the snippet.
			 *
			 * @return void
			 */
			public function update( string $base_dir, Snippet $snippet, ?bool $remove = false ): void {
			}
		};

		return new Snippet_Files( new Handler_Registry( [] ), $fs, $config_repo );
	}

	/**
	 * Ensure update_snippet_fields triggers the update action with a Snippet object.
	 *
	 * @return void
	 */
	public function test_update_snippet_fields_triggers_update_action_with_snippet_object() {
		$snippet = new Snippet(
			[
				'name'   => 'E2E Flat Files Hook Test',
				'desc'   => '',
				'code'   => '/* test */',
				'scope'  => 'global',
				'active' => false,
			]
		);

		$saved = save_snippet( $snippet );
		$this->assertNotNull( $saved );
		$this->assertGreaterThan( 0, $saved->id );

		$observed = null;
		$callback = static function ( $snippet_arg ) use ( &$observed ) {
			$observed = $snippet_arg;
		};

		add_action( 'code_snippets/update_snippet', $callback, 0 );

		update_snippet_fields( $saved->id, [ 'priority' => 9 ] );

		remove_action( 'code_snippets/update_snippet', $callback, 0 );

		$this->assertInstanceOf( Snippet::class, $observed );
		$this->assertSame( $saved->id, $observed->id );
	}

	/**
	 * Ensure add_option sync only runs for the active_shared_network_snippets option.
	 *
	 * @return void
	 */
	public function test_add_option_sync_only_runs_for_active_shared_network_snippets(): void {
		$writes = [];
		$fs = new class( $writes ) implements Filesystem_Adapter {

			/**
			 * Paths written via put_contents().
			 *
			 * @var array<int, string>
			 */
			private array $writes;

			/**
			 * Constructor.
			 *
			 * @param array<int, string> $writes Write sink.
			 */
			public function __construct( array &$writes ) {
				$this->writes = &$writes;
			}

			/**
			 * Write file contents.
			 *
			 * @param string $path     Path.
			 * @param string $contents Contents.
			 * @param mixed  $chmod    Chmod mode.
			 *
			 * @return bool
			 */
			public function put_contents( string $path, string $contents, $chmod ): bool {
				$this->writes[] = $path;
				return true;
			}

			/**
			 * Whether a path exists.
			 *
			 * @param string $path Path.
			 *
			 * @return bool
			 */
			public function exists( string $path ): bool {
				return false;
			}

			/**
			 * Delete a path.
			 *
			 * @param string $file      File path.
			 * @param bool   $recursive Recursive delete.
			 * @param mixed  $type      Delete type.
			 *
			 * @return bool
			 */
			public function delete( string $file, bool $recursive = false, $type = false ): bool {
				return true;
			}

			/**
			 * Whether a path is a directory.
			 *
			 * @param string $path Path.
			 *
			 * @return bool
			 */
			public function is_dir( string $path ): bool {
				return true;
			}

			/**
			 * Create a directory.
			 *
			 * @param string $path  Path.
			 * @param mixed  $chmod Chmod mode.
			 *
			 * @return bool
			 */
			public function mkdir( string $path, $chmod ): bool {
				return true;
			}

			/**
			 * Remove a directory.
			 *
			 * @param string $path      Path.
			 * @param bool   $recursive Recursive remove.
			 *
			 * @return bool
			 */
			public function rmdir( string $path, bool $recursive = false ): bool {
				return true;
			}

			/**
			 * Change permissions.
			 *
			 * @param string $path  Path.
			 * @param mixed  $chmod Chmod mode.
			 *
			 * @return bool
			 */
			public function chmod( string $path, $chmod ): bool {
				return true;
			}

			/**
			 * Whether a path is writable.
			 *
			 * @param string $path Path.
			 *
			 * @return bool
			 */
			public function is_writable( string $path ): bool {
				return true;
			}
		};

		$snippet_files = $this->build_snippet_files( $fs );
		$snippet_files->sync_active_shared_network_snippets_add( 'litespeed.some_option', [ 1 ] );

		$this->assertSame( [], $writes );

		$snippet_files->sync_active_shared_network_snippets_add( 'active_shared_network_snippets', [ 1 ] );

		$this->assertNotEmpty( $writes );
	}

	/**
	 * Ensure get_hashed_table_name uses WordPress hashing when available.
	 *
	 * @return void
	 */
	public function test_get_hashed_table_name_uses_wordpress_hash_when_available(): void {
		$table_name = 'wp_code_snippets';

		$this->assertSame( wp_hash( $table_name ), Snippet_Files::get_hashed_table_name( $table_name ) );
	}
}
