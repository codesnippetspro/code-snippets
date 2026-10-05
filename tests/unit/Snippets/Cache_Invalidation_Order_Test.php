<?php

namespace Code_Snippets;

use Code_Snippets\Model\Snippet;

/**
 * Tests that snippet writes invalidate the cached snippet list before the new
 * state is read back or handed to hooks.
 *
 * Every write in snippet-ops.php used to clear the cache as its last step, so
 * anything reading in between — the value these functions return, and the
 * listeners on their hooks — was served the snippet as it stood before the
 * write. With file-based execution that reached disk, and a snippet went on
 * running after it was deactivated.
 *
 * @group snippet-ops
 * @group cache
 */
class Cache_Invalidation_Order_Test extends UnitTestCase {

	/**
	 * Create a snippet directly, without going through the cache.
	 *
	 * @param array $fields Field values to override.
	 *
	 * @return int Identifier of the new snippet.
	 */
	private function create_snippet( array $fields = [] ): int {
		$snippet = new Snippet(
			array_merge(
				[
					'name'   => 'Cache ordering fixture',
					'code'   => '// ORIGINAL',
					'scope'  => 'global',
					'active' => false,
				],
				$fields
			)
		);

		return save_snippet( $snippet )->id;
	}

	/**
	 * Populate the cached snippet list, as any earlier read in the request would.
	 *
	 * @return void
	 */
	private function warm_cache(): void {
		get_snippets();
	}

	/**
	 * Read a field straight from the database, bypassing every cache.
	 *
	 * @param int    $id    Snippet identifier.
	 * @param string $field Column to read.
	 *
	 * @return string
	 */
	private function read_from_database( int $id, string $field ): string {
		global $wpdb;
		$table = code_snippets()->db->table;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- reading one column of a known table to bypass the cache under test.
		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT `$field` FROM $table WHERE id = %d", $id ) );
	}

	/**
	 * An edited snippet is returned as it was saved, not as it was beforehand.
	 *
	 * The snippet is cloned out of the cache first. A persistent object cache
	 * serialises, so a caller holds its own instance; the default cache hands
	 * back the very object it stores, and mutating that would quietly update
	 * the cached copy too and hide the fault this covers.
	 *
	 * @return void
	 */
	public function test_saving_returns_the_saved_snippet(): void {
		$id = $this->create_snippet();
		$this->warm_cache();

		$edit = clone get_snippet( $id );
		$edit->code = '// EDITED';
		$returned = save_snippet( $edit );

		$this->assertSame( '// EDITED', $this->read_from_database( $id, 'code' ), 'the edit should reach the database' );
		$this->assertSame( '// EDITED', $returned->code, 'saving should return the snippet as saved, not as it was before' );
	}

	/**
	 * An edited snippet reaches its hook as it was saved.
	 *
	 * @return void
	 */
	public function test_saving_hands_the_saved_snippet_to_its_hook(): void {
		$id = $this->create_snippet();
		$this->warm_cache();

		$received = null;
		add_action(
			'code_snippets/update_snippet',
			function ( Snippet $updated ) use ( &$received ) {
				$received = $updated->code;
			}
		);

		$edit = clone get_snippet( $id );
		$edit->code = '// EDITED';
		save_snippet( $edit );

		$this->assertSame( '// EDITED', $received, 'the hook should receive the snippet as saved' );
	}

	/**
	 * Activating reports the snippet as active.
	 *
	 * @return void
	 */
	public function test_activating_returns_an_active_snippet(): void {
		$id = $this->create_snippet();
		$this->warm_cache();

		$activated = activate_snippet( $id );

		$this->assertInstanceOf( Snippet::class, $activated, 'activation should succeed' );
		$this->assertSame( '1', $this->read_from_database( $id, 'active' ), 'activation should reach the database' );
		$this->assertTrue( $activated->active, 'activation should return the snippet as active' );
	}

	/**
	 * A snippet reaches the activation hook already marked active.
	 *
	 * @return void
	 */
	public function test_activating_hands_an_active_snippet_to_its_hook(): void {
		$id = $this->create_snippet();
		$this->warm_cache();

		$received = null;
		add_action(
			'code_snippets/activate_snippet',
			function ( Snippet $snippet ) use ( &$received ) {
				$received = $snippet->active;
			}
		);

		activate_snippet( $id );

		$this->assertTrue( $received, 'the hook should receive the snippet as active' );
	}

	/**
	 * Deactivating reports the snippet as inactive.
	 *
	 * @return void
	 */
	public function test_deactivating_returns_an_inactive_snippet(): void {
		$id = $this->create_snippet( [ 'active' => true ] );
		$this->warm_cache();

		$deactivated = deactivate_snippet( $id );

		$this->assertInstanceOf( Snippet::class, $deactivated, 'deactivation should succeed' );
		$this->assertSame( '0', $this->read_from_database( $id, 'active' ), 'deactivation should reach the database' );
		$this->assertFalse( $deactivated->active, 'deactivation should return the snippet as inactive' );
	}

	/**
	 * A listener on the deactivation hook no longer sees an active snippet.
	 *
	 * The hook passes an identifier rather than the snippet, so a listener has
	 * to read it back. File-based execution does exactly this, and wrote the
	 * snippet to disk still marked active.
	 *
	 * @return void
	 */
	public function test_deactivation_hook_reads_back_an_inactive_snippet(): void {
		$id = $this->create_snippet( [ 'active' => true ] );
		$this->warm_cache();

		$received = null;
		add_action(
			'code_snippets/deactivate_snippet',
			function ( int $snippet_id, bool $network ) use ( &$received ) {
				$received = get_snippet( $snippet_id, $network )->active;
			},
			10,
			2
		);

		deactivate_snippet( $id );

		$this->assertFalse( $received, 'a listener reading the snippet back should see it inactive' );
	}

	/**
	 * A listener on the deletion hook no longer finds the deleted snippet.
	 *
	 * @return void
	 */
	public function test_deletion_hook_no_longer_finds_the_snippet(): void {
		$id = $this->create_snippet();
		$this->warm_cache();

		$still_listed = null;
		add_action(
			'code_snippets/delete_snippet',
			function () use ( $id, &$still_listed ) {
				$still_listed = in_array( $id, wp_list_pluck( get_snippets(), 'id' ), true );
			}
		);

		delete_snippet( $id );

		$this->assertFalse( $still_listed, 'the deleted snippet should be gone from the list a listener reads' );
	}
}
