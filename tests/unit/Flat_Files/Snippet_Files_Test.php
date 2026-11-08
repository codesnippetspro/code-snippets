<?php

namespace Code_Snippets\Flat_Files;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use function Code_Snippets\clean_active_snippets_cache;
use function Code_Snippets\code_snippets;
use function Code_Snippets\deactivate_snippet;
use function Code_Snippets\delete_snippet;
use function Code_Snippets\save_snippet;

/**
 * Tests for writing and reading snippet flat files.
 *
 * @group flat-files
 */
class Snippet_Files_Test extends UnitTestCase {

	/**
	 * Whether the flat-files directory existed before the test.
	 *
	 * @var bool
	 */
	private bool $had_base_dir;

	/**
	 * Whether file-based execution was enabled before the test.
	 *
	 * @var bool
	 */
	private bool $was_active;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->had_base_dir = is_dir( Snippet_Files::get_base_dir() );
		$this->was_active = Snippet_Files::is_active();

		// The plugin's own listeners would keep the index in step, hiding a stale entry.
		remove_all_actions( 'code_snippets/deactivate_snippet' );
		remove_all_actions( 'code_snippets/delete_snippet' );
		remove_all_actions( 'code_snippets/trash_snippet' );
	}

	/**
	 * Restore the flat-files directory after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		if ( ! $this->was_active ) {
			$this->build_snippet_files()->create_all_flat_files( $this->settings( false ) );
		}

		if ( ! $this->had_base_dir ) {
			( new WP_Filesystem_Adapter() )->rmdir( Snippet_Files::get_base_dir(), true );
		}

		parent::tear_down();
	}

	/**
	 * Build a Snippet_Files instance writing to the real filesystem.
	 *
	 * @return Snippet_Files
	 */
	private function build_snippet_files(): Snippet_Files {
		$fs = new WP_Filesystem_Adapter();

		return new Snippet_Files( code_snippets()->snippet_handler_registry, $fs, new Flat_File_Config_Repository( $fs ) );
	}

	/**
	 * Directory holding the index of PHP snippets for the site table.
	 *
	 * @return string
	 */
	private function php_dir(): string {
		return Snippet_Files::get_base_dir( Snippet_Files::get_hashed_table_name( code_snippets()->db->table ), 'php' );
	}

	/**
	 * IDs listed in the index of PHP snippets for the site table.
	 *
	 * @return int[]
	 */
	private function indexed_ids(): array {
		return array_keys( ( new Flat_File_Config_Repository( new WP_Filesystem_Adapter() ) )->load( $this->php_dir() ) );
	}

	/**
	 * Settings data with file-based execution switched on or off.
	 *
	 * @param bool $enabled Whether file-based execution is enabled.
	 *
	 * @return array<string, array<string, bool>>
	 */
	private function settings( bool $enabled ): array {
		return [ 'general' => [ 'enable_flat_files' => $enabled ] ];
	}

	/**
	 * Save an active snippet.
	 *
	 * @param string $name Snippet name.
	 *
	 * @return Snippet
	 */
	private function active_snippet( string $name ): Snippet {
		return save_snippet(
			new Snippet(
				[
					'name'   => $name,
					'code'   => '/* test */',
					'scope'  => 'global',
					'active' => true,
				]
			)
		);
	}

	/**
	 * Snippets switched off or deleted while file-based execution was off are left out when it is switched on again.
	 *
	 * @return void
	 */
	public function test_enabling_rebuilds_the_index_from_active_snippets(): void {
		$kept = $this->active_snippet( 'Kept' );
		$deactivated = $this->active_snippet( 'Deactivated while off' );
		$deleted = $this->active_snippet( 'Deleted while off' );

		$snippet_files = $this->build_snippet_files();
		$snippet_files->create_all_flat_files( $this->settings( true ) );

		$this->assertContains( $deactivated->id, $this->indexed_ids() );
		$this->assertContains( $deleted->id, $this->indexed_ids() );

		$snippet_files->create_all_flat_files( $this->settings( false ) );
		deactivate_snippet( $deactivated->id );
		delete_snippet( $deleted->id );
		$snippet_files->create_all_flat_files( $this->settings( true ) );

		$this->assertContains( $kept->id, $this->indexed_ids() );
		$this->assertNotContains( $deactivated->id, $this->indexed_ids() );
		$this->assertNotContains( $deleted->id, $this->indexed_ids() );
	}

	/**
	 * An index entry written before snippets had conditions loads with no condition.
	 *
	 * @return void
	 */
	public function test_entry_without_condition_id_loads_without_a_condition(): void {
		wp_mkdir_p( $this->php_dir() );

		( new Flat_File_Config_Repository( new WP_Filesystem_Adapter() ) )->save(
			$this->php_dir(),
			[
				999 => [
					'id'       => 999,
					'code'     => '/* old */',
					'scope'    => 'global',
					'active'   => true,
					'priority' => 10,
				],
			]
		);

		clean_active_snippets_cache( code_snippets()->db->table, [ 'global' ] );

		$snippets = array_column( Snippet_Files::get_active_snippets_from_flat_files( [ 'global' ] ), null, 'id' );

		$this->assertSame( 0, $snippets[999]['condition_id'] );
	}
}
