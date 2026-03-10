<?php

namespace Code_Snippets\Tests;

use Code_Snippets\Flat_Files\Handler_Registry;
use Code_Snippets\Flat_Files\Interfaces\Filesystem_Adapter;
use Code_Snippets\Flat_Files\Interfaces\Snippet_Config_Repository;
use Code_Snippets\Flat_Files\Snippet_Files;
use Code_Snippets\Model\Snippet;
use function Code_Snippets\save_snippet;
use function Code_Snippets\update_snippet_fields;

/**
 * Tests for flat-file execution hooks.
 *
 * @group flat-files
 */
class Flat_Files_Hooks_Test extends TestCase {
	private function build_snippet_files( Filesystem_Adapter $fs ): Snippet_Files {
		$config_repo = new class() implements Snippet_Config_Repository {
			public function load( string $base_dir ): array {
				return [];
			}

			public function save( string $base_dir, array $active_snippets ): void {
			}

			public function update( string $base_dir, Snippet $snippet, ?bool $remove = false ): void {
			}
		};

		return new Snippet_Files( new Handler_Registry( [] ), $fs, $config_repo );
	}

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

		add_action( 'code_snippets/update_snippet', $callback, 0, 1 );

		update_snippet_fields( $saved->id, [ 'priority' => 9 ] );

		remove_action( 'code_snippets/update_snippet', $callback, 0 );

		$this->assertInstanceOf( Snippet::class, $observed );
		$this->assertSame( $saved->id, $observed->id );
	}

	public function test_add_option_sync_only_runs_for_active_shared_network_snippets(): void {
		$writes = [];
		$fs = new class( $writes ) implements Filesystem_Adapter {
			private array $writes;

			public function __construct( array &$writes ) {
				$this->writes = &$writes;
			}

			public function put_contents( string $path, string $contents, $chmod ): bool {
				$this->writes[] = $path;
				return true;
			}

			public function exists( string $path ): bool {
				return false;
			}

			public function delete( string $file, bool $recursive = false, $type = false ): bool {
				return true;
			}

			public function is_dir( string $path ): bool {
				return true;
			}

			public function mkdir( string $path, $chmod ): bool {
				return true;
			}

			public function rmdir( string $path, bool $recursive = false ): bool {
				return true;
			}

			public function chmod( string $path, $chmod ): bool {
				return true;
			}

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

	public function test_get_hashed_table_name_uses_wordpress_hash_when_available(): void {
		$table_name = 'wp_code_snippets';

		$this->assertSame( wp_hash( $table_name ), Snippet_Files::get_hashed_table_name( $table_name ) );
	}
}
