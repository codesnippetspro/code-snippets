<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\Model\Snippet;
use Code_Snippets\AdminUnitTestCase;
use Code_Snippets\Revisions\Revision_Manager;
use WP_REST_Request;
use function Code_Snippets\code_snippets;
use function Code_Snippets\save_snippet;
use function Code_Snippets\delete_snippet;
use function Code_Snippets\Settings\update_setting;

/**
 * Tests for the snippet revision system.
 *
 * @group revisions
 */
class Revisions_Test extends AdminUnitTestCase {

	/**
	 * Revision manager instance.
	 *
	 * @var Revision_Manager
	 */
	protected $revision_manager;

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'code-snippets/v1';

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		$this->revision_manager = code_snippets()->revision_manager;

		// Ensure revisions are enabled with a reasonable limit.
		update_setting( 'general', 'max_revisions', 10 );
		update_setting( 'general', 'preserve_on_delete', false );
		update_setting( 'general', 'delete_revision_default', '' );
	}

	/**
	 * Create a test snippet and save it.
	 *
	 * @param array $overrides Optional field overrides.
	 *
	 * @return Snippet
	 */
	protected function create_test_snippet( array $overrides = [] ): Snippet {
		$defaults = [
			'name'  => 'Test Snippet',
			'desc'  => 'A test snippet for revision testing.',
			'code'  => '// Hello World',
			'scope' => 'global',
			'tags'  => [ 'test' ],
		];

		$snippet = new Snippet( array_merge( $defaults, $overrides ) );
		return save_snippet( $snippet );
	}

	/**
	 * Create a test snippet and update it once to trigger a revision.
	 *
	 * @param array $overrides Optional field overrides.
	 *
	 * @return Snippet
	 */
	protected function create_test_snippet_with_revision( array $overrides = [] ): Snippet {
		$snippet = $this->create_test_snippet( $overrides );
		$snippet->code .= "\n// revision trigger";
		return save_snippet( $snippet );
	}

	/**
	 * List the code of every stored revision, newest first.
	 *
	 * @param Snippet $snippet Snippet to read revisions for.
	 *
	 * @return string[]
	 */
	protected function revision_codes( Snippet $snippet ): array {
		return array_map(
			function ( $rev ) {
				return json_decode( $rev->post_content, true )['code'];
			},
			$this->revision_manager->get_revisions( $snippet->id, $snippet->network )
		);
	}

	/**
	 * Test that creating a snippet records the state it starts in.
	 */
	public function test_revision_created_on_create() {
		$snippet = $this->create_test_snippet( [ 'code' => '// Starting code' ] );

		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$this->assertCount( 1, $revisions, 'Creating a snippet should record its initial state.' );

		$snapshot = json_decode( $revisions[0]->post_content, true );
		$this->assertEquals( '// Starting code', $snapshot['code'] );
	}

	/**
	 * Test that a revision is created when an existing snippet is updated.
	 */
	public function test_revision_created_on_update() {
		$snippet = $this->create_test_snippet();

		// Update the snippet to trigger the update hook.
		$snippet->code = '// Updated code';
		save_snippet( $snippet );

		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$this->assertCount( 2, $revisions, 'Updating should add to the revision recorded on creation.' );
	}

	/**
	 * Test that saving an identical snippet does not create a duplicate revision.
	 */
	public function test_no_duplicate_revision_on_identical_save() {
		$snippet = $this->create_test_snippet();

		// First update creates a revision.
		$snippet->code = '// Changed';
		save_snippet( $snippet );

		// Save again without changes — should NOT create another revision.
		save_snippet( $snippet );

		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$this->assertCount( 2, $revisions, 'Saving without changes should not create a new revision.' );
	}

	/**
	 * Test that modifying code creates a new revision.
	 */
	public function test_revision_created_on_code_change() {
		$snippet = $this->create_test_snippet();

		// Modify code and save.
		$snippet->code = '// Updated code';
		save_snippet( $snippet );

		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$this->assertCount( 2, $revisions, 'Changing code should create a new revision.' );
	}

	/**
	 * Test that the revision snapshot holds the state that was saved.
	 */
	public function test_revision_snapshot_content() {
		$snippet = $this->create_test_snippet(
			[
				'name'     => 'Snapshot Test',
				'code'     => '// Original code',
				'priority' => 5,
			]
		);

		// Update to trigger a revision.
		$snippet->code = '// Snapshot code';
		save_snippet( $snippet );

		$latest = $this->revision_manager->get_latest_revision( $snippet->id, $snippet->network );
		$this->assertNotNull( $latest );

		$snapshot = json_decode( $latest->post_content, true );
		$this->assertEquals( 'Snapshot Test', $snapshot['name'] );
		$this->assertEquals( '// Snapshot code', $snapshot['code'] );
		$this->assertEquals( 5, $snapshot['priority'] );
	}

	/**
	 * Test the full history of a snippet, including a restore.
	 *
	 * Every save records the state it produced, so the list holds each version
	 * the snippet has been in — starting code included — and restoring adds the
	 * version it puts back rather than rewriting what came before.
	 */
	public function test_history_covers_every_saved_state() {
		$snippet = $this->create_test_snippet( [ 'code' => 'red' ] );

		$snippet->code = 'green';
		save_snippet( $snippet );

		$snippet->code = 'violet';
		save_snippet( $snippet );

		$this->assertEquals(
			[ 'violet', 'green', 'red' ],
			$this->revision_codes( $snippet ),
			'Each save should leave the state it produced in the history.'
		);

		// Restore the original, from violet back to red.
		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$restored = $this->revision_manager->restore_revision( $snippet->id, end( $revisions )->ID, $snippet->network );

		$this->assertNotNull( $restored );
		$this->assertEquals( 'red', $restored->code );

		$this->assertEquals(
			[ 'red', 'violet', 'green', 'red' ],
			$this->revision_codes( $snippet ),
			'Restoring should record the version it puts back, leaving the earlier history intact.'
		);
	}

	/**
	 * Test that toggling activation alone does not create a revision.
	 */
	public function test_no_revision_on_activation_toggle() {
		$snippet = $this->create_test_snippet_with_revision();
		$before = $this->revision_manager->get_revision_count( $snippet->id, $snippet->network );

		$snippet->active = ! $snippet->active;
		save_snippet( $snippet );

		$this->assertEquals(
			$before,
			$this->revision_manager->get_revision_count( $snippet->id, $snippet->network ),
			'Switching a snippet on or off should not add a revision.'
		);
	}

	/**
	 * Test sequential revision numbering.
	 */
	public function test_revision_numbering() {
		$snippet = $this->create_test_snippet();

		$snippet->code = '// v2';
		save_snippet( $snippet );

		$snippet->code = '// v3';
		save_snippet( $snippet );

		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$this->assertCount( 3, $revisions );

		// Revisions are returned newest-first.
		$numbers = array_map(
			function ( $rev ) {
				return (int) get_post_meta( $rev->ID, Revision_Manager::META_REVISION_NUMBER, true );
			},
			$revisions
		);

		$this->assertEquals( [ 3, 2, 1 ], $numbers );
	}

	/**
	 * Test FIFO enforcement: oldest revisions are deleted when limit is exceeded.
	 */
	public function test_fifo_enforcement() {
		update_setting( 'general', 'max_revisions', 3 );

		$snippet = $this->create_test_snippet();

		// Create 4 more revisions (5 total saves, but limit is 3).
		for ( $i = 2; $i <= 5; $i++ ) {
			$snippet->code = "// Version $i";
			save_snippet( $snippet );
		}

		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$this->assertCount( 3, $revisions, 'Only the most recent 3 revisions should be kept.' );

		// Five saves were recorded (creation plus four edits), so the oldest kept is v3.
		$oldest_number = (int) get_post_meta(
			end( $revisions )->ID,
			Revision_Manager::META_REVISION_NUMBER,
			true
		);
		$this->assertEquals( 3, $oldest_number );
	}

	/**
	 * Test that revisions are not created when globally disabled.
	 */
	public function test_revisions_disabled_globally() {
		update_setting( 'general', 'max_revisions', 0 );

		$snippet = $this->create_test_snippet();

		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$this->assertCount( 0, $revisions, 'No revisions should be created when globally disabled.' );
	}

	/**
	 * Test per-snippet versioning toggle.
	 */
	public function test_per_snippet_versioning_disabled() {
		$snippet = $this->create_test_snippet_with_revision();

		// Disable versioning for this snippet.
		Revision_Manager::set_snippet_versioning_disabled( $snippet->id, true, $snippet->network );

		$snippet->code = '// Should not create revision';
		save_snippet( $snippet );

		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$this->assertCount( 2, $revisions, 'No new revision should be created when per-snippet versioning is disabled.' );
	}

	/**
	 * Test re-enabling per-snippet versioning.
	 */
	public function test_per_snippet_versioning_reenable() {
		$snippet = $this->create_test_snippet_with_revision();

		// Disable and then re-enable.
		Revision_Manager::set_snippet_versioning_disabled( $snippet->id, true, $snippet->network );
		Revision_Manager::set_snippet_versioning_disabled( $snippet->id, false, $snippet->network );

		$snippet->code = '// New revision after re-enable';
		save_snippet( $snippet );

		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$this->assertCount( 3, $revisions, 'New revisions should be created after re-enabling versioning.' );
	}

	/**
	 * Test restore creates a backup of current state and applies revision data.
	 */
	public function test_restore_revision() {
		$snippet = $this->create_test_snippet( [ 'code' => '// Original code' ] );

		// First update records the original code as revision 1.
		$snippet->code = '// First update';
		save_snippet( $snippet );

		// Second update records the first update as revision 2.
		$snippet->code = '// Second update';
		save_snippet( $snippet );

		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$this->assertCount( 3, $revisions );

		// Restore the oldest revision, which holds the original code.
		$first_revision = end( $revisions );

		$restored = $this->revision_manager->restore_revision(
			$snippet->id,
			$first_revision->ID,
			$snippet->network
		);

		$this->assertNotNull( $restored, 'Restore should return a snippet.' );
		$this->assertEquals( '// Original code', $restored->code, 'Code should be restored to the original.' );

		// Restoring is a save like any other: it records the version it puts back.
		$revisions_after = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$this->assertCount( 4, $revisions_after, 'Restore should record the version it restores.' );
	}

	/**
	 * Test that repeated restores never remove stored revisions.
	 *
	 * After any restore the live snippet equals a stored revision, so a further
	 * restore must not treat that revision as a redundant copy.
	 */
	public function test_repeated_restores_keep_history() {
		$snippet = $this->create_test_snippet( [ 'code' => '// a' ] );

		$snippet->code = '// b';
		save_snippet( $snippet );

		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$this->assertEquals( [ '// b', '// a' ], $this->revision_codes( $snippet ) );

		// Restore the newest revision, putting the snippet on a stored version.
		$this->revision_manager->restore_revision( $snippet->id, $revisions[0]->ID, $snippet->network );

		// Nothing changed, so nothing new is recorded — but nothing is lost either.
		$this->assertEquals(
			[ '// b', '// a' ],
			$this->revision_codes( $snippet ),
			'Restoring the version already in place should leave the history alone.'
		);

		// Restore the older one from that state.
		$restored = $this->revision_manager->restore_revision( $snippet->id, $revisions[1]->ID, $snippet->network );

		$this->assertNotNull( $restored );
		$this->assertEquals( '// a', $restored->code );

		$this->assertEquals(
			[ '// a', '// b', '// a' ],
			$this->revision_codes( $snippet ),
			'A restore should add to the history, never remove from it.'
		);
	}

	/**
	 * Test that restoring a revision that doesn't belong to the snippet fails.
	 */
	public function test_restore_mismatched_revision() {
		$snippet_a = $this->create_test_snippet( [ 'name' => 'Snippet A' ] );
		$snippet_b = $this->create_test_snippet_with_revision( [ 'name' => 'Snippet B' ] );

		$revisions_b = $this->revision_manager->get_revisions( $snippet_b->id, $snippet_b->network );
		$revision_b = $revisions_b[0];

		// Try to restore snippet B's revision onto snippet A.
		$result = $this->revision_manager->restore_revision(
			$snippet_a->id,
			$revision_b->ID,
			$snippet_a->network
		);

		$this->assertNull( $result, 'Restoring a revision from another snippet should fail.' );
	}

	/**
	 * Test revisions are purged when snippet is permanently deleted.
	 */
	public function test_revisions_purged_on_delete() {
		update_setting( 'general', 'preserve_on_delete', false );

		$snippet = $this->create_test_snippet_with_revision();
		$snippet_id = $snippet->id;
		$network = $snippet->network;

		// Verify revision exists.
		$revisions = $this->revision_manager->get_revisions( $snippet_id, $network );
		$this->assertCount( 2, $revisions );

		delete_snippet( $snippet_id, $network );

		$revisions_after = $this->revision_manager->get_revisions( $snippet_id, $network );
		$this->assertCount( 0, $revisions_after, 'Revisions should be purged when snippet is permanently deleted.' );
	}

	/**
	 * Test revisions are preserved when preserve_on_delete is enabled.
	 */
	public function test_revisions_preserved_on_delete() {
		update_setting( 'general', 'preserve_on_delete', true );

		$snippet = $this->create_test_snippet_with_revision();
		$snippet_id = $snippet->id;
		$network = $snippet->network;

		delete_snippet( $snippet_id, $network );

		$revisions = $this->revision_manager->get_revisions( $snippet_id, $network );
		$this->assertCount( 2, $revisions, 'Revisions should be preserved when preserve_on_delete is enabled.' );
	}

	/**
	 * Test revision count method.
	 */
	public function test_revision_count() {
		$snippet = $this->create_test_snippet_with_revision();

		$this->assertEquals( 2, $this->revision_manager->get_revision_count( $snippet->id, $snippet->network ) );

		$snippet->code = '// v2';
		save_snippet( $snippet );

		$this->assertEquals( 3, $this->revision_manager->get_revision_count( $snippet->id, $snippet->network ) );
	}

	/**
	 * Test purge_revisions manually.
	 */
	public function test_purge_revisions() {
		$snippet = $this->create_test_snippet_with_revision();

		$snippet->code = '// v2';
		save_snippet( $snippet );

		$this->assertEquals( 3, $this->revision_manager->get_revision_count( $snippet->id, $snippet->network ) );

		$deleted = $this->revision_manager->purge_revisions( $snippet->id, $snippet->network );
		$this->assertEquals( 3, $deleted );
		$this->assertEquals( 0, $this->revision_manager->get_revision_count( $snippet->id, $snippet->network ) );
	}

	/**
	 * Test GET revisions REST endpoint.
	 */
	public function test_rest_get_revisions() {
		$snippet = $this->create_test_snippet_with_revision();

		$snippet->code = '// v2';
		save_snippet( $snippet );

		$request = new WP_REST_Request(
			'GET',
			"/{$this->namespace}/snippets/{$snippet->id}/revisions"
		);
		$request->set_param( 'network', false );

		$response = rest_do_request( $request );
		$data = rest_get_server()->response_to_data( $response, false );

		$this->assertIsArray( $data );
		$this->assertCount( 3, $data );
		$this->assertEquals( 3, $data[0]['revision_number'] );
		$this->assertEquals( 2, $data[1]['revision_number'] );
	}

	/**
	 * Test GET single revision REST endpoint.
	 */
	public function test_rest_get_single_revision() {
		$snippet = $this->create_test_snippet_with_revision();

		$latest = $this->revision_manager->get_latest_revision( $snippet->id, $snippet->network );

		$request = new WP_REST_Request(
			'GET',
			"/{$this->namespace}/snippets/{$snippet->id}/revisions/{$latest->ID}"
		);
		$request->set_param( 'network', false );

		$response = rest_do_request( $request );
		$data = rest_get_server()->response_to_data( $response, false );

		foreach ( [ 'id', 'snippet_id', 'revision_number', 'date', 'author', 'author_id', 'snapshot' ] as $key ) {
			$this->assertArrayHasKey( $key, $data );
		}

		$this->assertEquals( $snippet->id, $data['snippet_id'] );
		$this->assertEquals( 2, $data['revision_number'] );
		$this->assertIsArray( $data['snapshot'] );
	}

	/**
	 * Test POST restore revision REST endpoint.
	 */
	public function test_rest_restore_revision() {
		$snippet = $this->create_test_snippet( [ 'code' => '// Original' ] );

		$snippet->code = '// Modified';
		save_snippet( $snippet );

		$snippet->code = '// Modified again';
		save_snippet( $snippet );

		$revisions = $this->revision_manager->get_revisions( $snippet->id, $snippet->network );
		$first_revision = end( $revisions );

		$request = new WP_REST_Request(
			'POST',
			"/{$this->namespace}/snippets/{$snippet->id}/revisions/{$first_revision->ID}/restore"
		);
		$request->set_param( 'network', false );

		$response = rest_do_request( $request );
		$data = rest_get_server()->response_to_data( $response, false );

		$this->assertTrue( $data['success'] );
	}

	/**
	 * Test GET versioning settings REST endpoint.
	 */
	public function test_rest_get_versioning_settings() {
		$snippet = $this->create_test_snippet();

		$request = new WP_REST_Request(
			'GET',
			"/{$this->namespace}/snippets/{$snippet->id}/revisions/settings"
		);
		$request->set_param( 'network', false );

		$response = rest_do_request( $request );
		$data = rest_get_server()->response_to_data( $response, false );

		$this->assertArrayHasKey( 'disabled', $data );
		$this->assertFalse( $data['disabled'] );
		$this->assertArrayHasKey( 'revision_count', $data );
	}

	/**
	 * Test POST update versioning settings REST endpoint.
	 */
	public function test_rest_update_versioning_settings() {
		$snippet = $this->create_test_snippet();

		$request = new WP_REST_Request(
			'POST',
			"/{$this->namespace}/snippets/{$snippet->id}/revisions/settings"
		);
		$request->set_param( 'network', false );
		$request->set_param( 'disabled', true );

		$response = rest_do_request( $request );
		$data = rest_get_server()->response_to_data( $response, false );

		$this->assertTrue( $data['disabled'] );

		// Confirm it persisted.
		$this->assertTrue(
			Revision_Manager::is_snippet_versioning_disabled( $snippet->id, $snippet->network )
		);
	}

	/**
	 * Test that revision stores correct author.
	 */
	public function test_revision_stores_author() {
		$snippet = $this->create_test_snippet_with_revision();

		$latest = $this->revision_manager->get_latest_revision( $snippet->id, $snippet->network );
		$this->assertEquals( self::$admin_user_id, (int) $latest->post_author );
	}

	/**
	 * Test delete_revision_default='delete' forces purge even when preserve_on_delete is true.
	 */
	public function test_delete_revision_default_delete() {
		update_setting( 'general', 'preserve_on_delete', true );
		update_setting( 'general', 'delete_revision_default', 'delete' );

		$snippet = $this->create_test_snippet_with_revision();
		$snippet_id = $snippet->id;
		$network = $snippet->network;

		$this->assertCount( 2, $this->revision_manager->get_revisions( $snippet_id, $network ) );

		delete_snippet( $snippet_id, $network );

		$this->assertCount(
            0,
            $this->revision_manager->get_revisions( $snippet_id, $network ),
            'delete_revision_default=delete should purge revisions even with preserve_on_delete=true.'
        );
	}

	/**
	 * Test delete_revision_default='keep' preserves revisions even when preserve_on_delete is false.
	 */
	public function test_delete_revision_default_keep() {
		update_setting( 'general', 'preserve_on_delete', false );
		update_setting( 'general', 'delete_revision_default', 'keep' );

		$snippet = $this->create_test_snippet_with_revision();
		$snippet_id = $snippet->id;
		$network = $snippet->network;

		delete_snippet( $snippet_id, $network );

		$this->assertCount(
            2,
            $this->revision_manager->get_revisions( $snippet_id, $network ),
            'delete_revision_default=keep should preserve revisions even with preserve_on_delete=false.'
        );
	}

	/**
	 * Test GET delete preference REST endpoint.
	 */
	public function test_rest_get_delete_preference() {
		update_setting( 'general', 'delete_revision_default', 'keep' );

		$request = new WP_REST_Request( 'GET', "/{$this->namespace}/snippets/revisions/preferences" );

		$response = rest_do_request( $request );
		$data = rest_get_server()->response_to_data( $response, false );

		$this->assertEquals( 'keep', $data['delete_revision_default'] );
	}

	/**
	 * Test POST update delete preference REST endpoint.
	 */
	public function test_rest_update_delete_preference() {
		$request = new WP_REST_Request( 'POST', "/{$this->namespace}/snippets/revisions/preferences" );
		$request->set_param( 'delete_revision_default', 'delete' );

		$response = rest_do_request( $request );
		$data = rest_get_server()->response_to_data( $response, false );

		$this->assertEquals( 'delete', $data['delete_revision_default'] );
	}

	/**
	 * Test that delete_revision removes the CPT post.
	 */
	public function test_delete_revision_removes_cpt_post() {
		$snippet = $this->create_test_snippet_with_revision();

		$latest = $this->revision_manager->get_latest_revision( $snippet->id, $snippet->network );
		$this->assertNotNull( $latest );

		$result = $this->revision_manager->delete_revision( $latest->ID, $snippet->id, $snippet->network );
		$this->assertTrue( $result );

		// Verify the revision no longer exists, leaving the one from creation.
		$this->assertNull( $this->revision_manager->get_revision( $latest->ID, $snippet->id, $snippet->network ) );
		$this->assertEquals( 1, $this->revision_manager->get_revision_count( $snippet->id, $snippet->network ) );
	}

	/**
	 * Test that delete_revision rejects a revision belonging to a different snippet.
	 */
	public function test_delete_revision_rejects_wrong_snippet() {
		$snippet_a = $this->create_test_snippet( [ 'name' => 'Snippet A' ] );
		$snippet_b = $this->create_test_snippet_with_revision( [ 'name' => 'Snippet B' ] );

		$revision_b = $this->revision_manager->get_latest_revision( $snippet_b->id, $snippet_b->network );
		$this->assertNotNull( $revision_b );

		// Attempt to delete snippet B's revision using snippet A's ID.
		$result = $this->revision_manager->delete_revision( $revision_b->ID, $snippet_a->id, $snippet_a->network );
		$this->assertFalse( $result, 'Should not be able to delete a revision belonging to a different snippet.' );

		// Verify the revision still exists.
		$this->assertNotNull( $this->revision_manager->get_revision( $revision_b->ID, $snippet_b->id, $snippet_b->network ) );
	}

	/**
	 * Test DELETE revision REST endpoint.
	 */
	public function test_rest_delete_revision() {
		$snippet = $this->create_test_snippet_with_revision();

		$latest = $this->revision_manager->get_latest_revision( $snippet->id, $snippet->network );

		$request = new WP_REST_Request(
			'DELETE',
			"/{$this->namespace}/snippets/{$snippet->id}/revisions/{$latest->ID}"
		);
		$request->set_param( 'network', false );

		$response = rest_do_request( $request );
		$this->assertEquals( 204, $response->get_status() );

		// Verify the revision was deleted, leaving the one from creation.
		$this->assertEquals( 1, $this->revision_manager->get_revision_count( $snippet->id, $snippet->network ) );
	}

	/**
	 * Restoring a revision must not change whether the snippet is active.
	 *
	 * The restore preview shows content and configuration changes but never
	 * activation, so restoring a snapshot taken while the snippet was active
	 * would otherwise switch it on — and start running the restored code —
	 * with nothing on screen to say so.
	 */
	public function test_restore_preserves_current_activation_state() {
		$snippet = $this->create_test_snippet( [ 'active' => true ] );

		// Second save, while still active, captures a snapshot with active = true.
		$snippet->code .= "\n// first edit";
		$snippet = save_snippet( $snippet );

		$revision = $this->revision_manager->get_latest_revision( $snippet->id, $snippet->network );
		$this->assertNotNull( $revision );

		// Deactivate, then restore the snapshot taken while it was active.
		$snippet->active = false;
		$snippet = save_snippet( $snippet );

		$restored = $this->revision_manager->restore_revision( $snippet->id, $revision->ID, $snippet->network );

		$this->assertNotNull( $restored );
		$this->assertFalse( (bool) $restored->active, 'Restore must leave the snippet deactivated.' );
	}

	/**
	 * A revision may only be read through the snippet it belongs to.
	 */
	public function test_get_revision_rejects_another_snippets_revision() {
		$snippet_a = $this->create_test_snippet( [ 'name' => 'Snippet A' ] );
		$snippet_b = $this->create_test_snippet_with_revision( [ 'name' => 'Snippet B' ] );

		$revision_b = $this->revision_manager->get_latest_revision( $snippet_b->id, $snippet_b->network );
		$this->assertNotNull( $revision_b );

		$this->assertNull(
			$this->revision_manager->get_revision( $revision_b->ID, $snippet_a->id, $snippet_a->network ),
			'A revision must not be readable through a snippet it does not belong to.'
		);
	}

	/**
	 * Site and network snippets have independent ID sequences, so scope has to be
	 * part of the ownership check — not just the snippet ID.
	 */
	public function test_get_revision_rejects_mismatched_network_scope() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		$snippet = $this->create_test_snippet_with_revision();

		$revision = $this->revision_manager->get_latest_revision( $snippet->id, $snippet->network );
		$this->assertNotNull( $revision );

		$opposite_scope = ! $snippet->network;

		$this->assertNull(
			$this->revision_manager->get_revision( $revision->ID, $snippet->id, $opposite_scope ),
			'A revision must not be reachable from the opposite network scope.'
		);
	}

	/**
	 * Deletion is scoped the same way as reading.
	 */
	public function test_delete_revision_rejects_mismatched_network_scope() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		$snippet = $this->create_test_snippet_with_revision();

		$revision = $this->revision_manager->get_latest_revision( $snippet->id, $snippet->network );
		$this->assertNotNull( $revision );

		$opposite_scope = ! $snippet->network;

		$this->assertFalse(
			$this->revision_manager->delete_revision( $revision->ID, $snippet->id, $opposite_scope ),
			'A revision must not be deletable from the opposite network scope.'
		);

		$this->assertNotNull(
			$this->revision_manager->get_revision( $revision->ID, $snippet->id, $snippet->network ),
			'The revision should survive a wrongly-scoped delete.'
		);
	}
}
