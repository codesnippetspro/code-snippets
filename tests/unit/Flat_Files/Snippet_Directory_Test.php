<?php

namespace Code_Snippets\Flat_Files;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use WP_Filesystem_Direct;
use function Code_Snippets\activate_snippet;
use function Code_Snippets\code_snippets;
use function Code_Snippets\deactivate_snippet;
use function Code_Snippets\save_snippet;

/**
 * Tests for the directories that hold each table's snippet files.
 *
 * @group flat-files
 */
class Snippet_Directory_Test extends UnitTestCase {

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
	 * Snippet files instance under test.
	 *
	 * @var Snippet_Files
	 */
	private Snippet_Files $snippet_files;

	/**
	 * Switch file-based execution on before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->had_base_dir = is_dir( Snippet_Files::get_base_dir() );
		$this->was_active = Snippet_Files::is_active();

		$fs = new WP_Filesystem_Adapter();
		$this->snippet_files = new Snippet_Files( code_snippets()->snippet_handler_registry, $fs, new Flat_File_Config_Repository( $fs ) );
		$this->snippet_files->create_all_flat_files( $this->settings( true ) );
		$this->snippet_files->register_hooks();
		$this->snippet_files->maybe_recreate_flat_files();
	}

	/**
	 * Restore the flat-files directory after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		if ( ! $this->was_active ) {
			$this->snippet_files->create_all_flat_files( $this->settings( false ) );
		}

		if ( ! $this->had_base_dir ) {
			( new WP_Filesystem_Adapter() )->rmdir( Snippet_Files::get_base_dir(), true );
		}

		parent::tear_down();
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
	 * IDs of the snippets that would run on the front end from the snippet files.
	 *
	 * @return int[]
	 */
	private function running_ids(): array {
		wp_cache_flush();

		return array_column( Snippet_Files::get_active_snippets_from_flat_files( [ 'global', 'single-use', 'front-end' ] ), 'id' );
	}

	/**
	 * Change the salts used by wp_hash().
	 *
	 * @return void
	 */
	private function change_salts(): void {
		add_filter(
			'salt',
			function ( $salt ) {
				return $salt . '-changed';
			}
		);
	}

	/**
	 * Directory holding the site table's snippet files.
	 *
	 * @return string
	 */
	private function site_dir(): string {
		return Snippet_Files::get_base_dir( Snippet_Files::get_hashed_table_name( code_snippets()->db->table ) );
	}

	/**
	 * Snippets keep running from their files after the salts are changed.
	 *
	 * @return void
	 */
	public function test_snippets_keep_running_after_the_salts_change(): void {
		$snippet = $this->active_snippet( 'Before the salts change' );

		$this->change_salts();

		$this->assertContains( $snippet->id, $this->running_ids() );
	}

	/**
	 * A site's existing directory is kept when its name is first stored.
	 *
	 * @return void
	 */
	public function test_existing_directory_is_kept(): void {
		$table = code_snippets()->db->table;
		delete_option( Snippet_Files::DIR_NAMES_OPTION );

		$this->snippet_files->maybe_recreate_flat_files();

		$this->assertSame( [ $table => wp_hash( $table ) ], get_option( Snippet_Files::DIR_NAMES_OPTION ) );
	}

	/**
	 * Files written before the salts changed are recreated when the directory name is first stored.
	 *
	 * @return void
	 */
	public function test_files_from_before_a_salt_change_are_recreated(): void {
		$untouched = $this->active_snippet( 'Untouched since the salts changed' );
		$toggled = $this->active_snippet( 'Turned off and on since the salts changed' );

		delete_option( Snippet_Files::DIR_NAMES_OPTION );
		$this->change_salts();
		deactivate_snippet( $toggled->id );
		activate_snippet( $toggled->id );

		$this->assertSame( [ $toggled->id ], $this->running_ids() );

		$this->snippet_files->maybe_recreate_flat_files();

		$this->assertEqualsCanonicalizing( [ $untouched->id, $toggled->id ], $this->running_ids() );
	}

	/**
	 * A missing directory is recreated from the active snippets.
	 *
	 * @return void
	 */
	public function test_missing_directory_is_recreated(): void {
		$snippet = $this->active_snippet( 'Directory removed' );

		( new WP_Filesystem_Adapter() )->rmdir( $this->site_dir(), true );
		$this->snippet_files->maybe_recreate_flat_files();

		$this->assertContains( $snippet->id, $this->running_ids() );
	}

	/**
	 * Files are left alone when the directory name is stored and the directory exists.
	 *
	 * @return void
	 */
	public function test_existing_files_are_left_alone(): void {
		$this->active_snippet( 'Already written' );
		$index = $this->site_dir() . '/php/index.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		( new WP_Filesystem_Direct( null ) )->touch( $index, time() - HOUR_IN_SECONDS );
		clearstatcache();
		$modified = filemtime( $index );

		$this->snippet_files->maybe_recreate_flat_files();
		clearstatcache();

		$this->assertSame( $modified, filemtime( $index ) );
	}
}
