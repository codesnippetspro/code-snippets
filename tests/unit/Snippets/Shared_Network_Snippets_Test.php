<?php

namespace Code_Snippets;

use Code_Snippets\Model\Snippet;

/**
 * Tests for sharing network snippets with subsites.
 *
 * Whether a network snippet is shared is not a column on the snippet. It is
 * held as a list of IDs in a network option, and each subsite keeps its own
 * list of the shared snippets it has switched on.
 *
 * @group snippet-ops
 * @group multisite
 */
class Shared_Network_Snippets_Test extends UnitTestCase {

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Sharing with subsites only exists on multisite installs.' );
		}

		delete_site_option( 'shared_network_snippets' );
	}

	/**
	 * Build an unsaved network snippet.
	 *
	 * @param bool $shared Whether it is shared with subsites.
	 *
	 * @return Snippet
	 */
	private function network_snippet( bool $shared ): Snippet {
		return new Snippet(
			[
				'name'           => 'Shared network fixture',
				'code'           => 'body { color: red; }',
				'scope'          => 'site-css',
				'network'        => true,
				'shared_network' => $shared,
			]
		);
	}

	/**
	 * Save an existing network snippet with a different share setting, as the editor does.
	 *
	 * @param int  $id     Snippet identifier.
	 * @param bool $shared Requested share setting.
	 *
	 * @return Snippet|null
	 */
	private function save_with_sharing( int $id, bool $shared ): ?Snippet {
		$edit = clone get_snippet( $id, true );
		$edit->shared_network = $shared;

		return save_snippet( $edit );
	}

	/**
	 * IDs currently shared with subsites.
	 *
	 * @return array
	 */
	private function shared_ids(): array {
		return (array) get_site_option( 'shared_network_snippets', [] );
	}

	/**
	 * A new snippet saved as shared is shared.
	 *
	 * @return void
	 */
	public function test_new_snippet_saved_as_shared_is_shared(): void {
		$saved = save_snippet( $this->network_snippet( true ) );

		$this->assertSame( [ $saved->id ], $this->shared_ids() );
		$this->assertTrue( $saved->shared_network, 'the saved snippet should report the setting it was saved with' );
	}

	/**
	 * A new snippet saved without sharing is not shared.
	 *
	 * @return void
	 */
	public function test_new_snippet_saved_without_sharing_is_not_shared(): void {
		$saved = save_snippet( $this->network_snippet( false ) );

		$this->assertSame( [], $this->shared_ids() );
		$this->assertFalse( $saved->shared_network );
	}

	/**
	 * Sharing can be switched on for a snippet that already exists.
	 *
	 * @return void
	 */
	public function test_sharing_can_be_switched_on_for_an_existing_snippet(): void {
		$id = save_snippet( $this->network_snippet( false ) )->id;

		$saved = $this->save_with_sharing( $id, true );

		$this->assertSame( [ $id ], $this->shared_ids() );
		$this->assertTrue( $saved->shared_network );
		$this->assertTrue( get_snippet( $id, true )->shared_network, 'the setting should still be on when the snippet is read again' );
	}

	/**
	 * Sharing can be switched off again.
	 *
	 * @return void
	 */
	public function test_sharing_can_be_switched_off(): void {
		$id = save_snippet( $this->network_snippet( true ) )->id;

		$saved = $this->save_with_sharing( $id, false );

		$this->assertSame( [], $this->shared_ids() );
		$this->assertFalse( $saved->shared_network );
	}

	/**
	 * A save that does not mention sharing leaves it as it was.
	 *
	 * @return void
	 */
	public function test_saving_without_a_share_setting_keeps_the_current_one(): void {
		$id = save_snippet( $this->network_snippet( true ) )->id;

		$edit = new Snippet(
			[
				'id'      => $id,
				'name'    => 'Shared network fixture',
				'code'    => 'body { color: blue; }',
				'scope'   => 'site-css',
				'network' => true,
			]
		);
		save_snippet( $edit );

		$this->assertSame( [ $id ], $this->shared_ids() );
	}

	/**
	 * Hooks on a save are handed the share setting that was saved.
	 *
	 * @return void
	 */
	public function test_save_hooks_receive_the_requested_share_setting(): void {
		$seen = [];
		$record = function ( Snippet $snippet ) use ( &$seen ) {
			$seen[] = $snippet->shared_network;
		};

		add_action( 'code_snippets/create_snippet', $record );
		add_action( 'code_snippets/update_snippet', $record );

		$id = save_snippet( $this->network_snippet( true ) )->id;
		$this->save_with_sharing( $id, false );

		remove_action( 'code_snippets/create_snippet', $record );
		remove_action( 'code_snippets/update_snippet', $record );

		$this->assertSame( [ true, false ], $seen );
	}

	/**
	 * Saving a shared snippet repeatedly lists its ID once.
	 *
	 * @return void
	 */
	public function test_repeated_saves_list_a_shared_snippet_once(): void {
		$id = save_snippet( $this->network_snippet( true ) )->id;

		$this->save_with_sharing( $id, true );
		$this->save_with_sharing( $id, true );

		$this->assertSame( [ $id ], $this->shared_ids() );
	}

	/**
	 * A list that already holds repeats is tidied by the next save.
	 *
	 * @return void
	 */
	public function test_existing_repeats_are_removed_on_the_next_save(): void {
		$id = save_snippet( $this->network_snippet( true ) )->id;
		update_site_option( 'shared_network_snippets', [ $id, $id, $id ] );

		$this->save_with_sharing( $id, true );

		$this->assertSame( [ $id ], $this->shared_ids() );
	}

	/**
	 * Switching sharing off switches the snippet off on every subsite using it.
	 *
	 * @return void
	 */
	public function test_switching_sharing_off_deactivates_the_snippet_on_every_subsite(): void {
		$id = save_snippet( $this->network_snippet( true ) )->id;
		$other_id = $id + 1000;
		$subsites = [ $this->factory()->blog->create(), $this->factory()->blog->create() ];

		foreach ( $subsites as $subsite ) {
			update_blog_option( $subsite, 'active_shared_network_snippets', [ $id, $other_id ] );
		}

		$this->save_with_sharing( $id, false );

		foreach ( $subsites as $subsite ) {
			$this->assertSame(
				[ $other_id ],
				array_values( (array) get_blog_option( $subsite, 'active_shared_network_snippets' ) ),
				'the snippet should no longer be active on a subsite, and others it had on should be untouched'
			);
		}
	}

	/**
	 * Switching sharing off leaves the request on the site it started on.
	 *
	 * @return void
	 */
	public function test_switching_sharing_off_returns_to_the_original_site(): void {
		$id = save_snippet( $this->network_snippet( true ) )->id;
		$this->factory()->blog->create();
		$this->factory()->blog->create();
		$original = get_current_blog_id();

		$this->save_with_sharing( $id, false );

		$this->assertSame( $original, get_current_blog_id(), 'the save should finish on the site it started on' );
		$this->assertFalse( ms_is_switched(), 'no switch to another site should be left open' );
	}
}
