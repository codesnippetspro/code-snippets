<?php

namespace Code_Snippets\Integration;

use Code_Snippets\UnitTestCase;
use function Code_Snippets\code_snippets;

/**
 * Tests for the per-request "Ran on this page" snippet tracker.
 *
 * @group admin-bar
 */
class Active_Snippets_Tracker_Test extends UnitTestCase {

	/**
	 * The plugin's tracker instance (already wired to the execution hooks).
	 *
	 * @var Active_Snippets_Tracker
	 */
	private Active_Snippets_Tracker $tracker;

	/**
	 * Start each test with an empty tracker.
	 */
	public function set_up() {
		parent::set_up();
		$this->tracker = code_snippets()->active_snippets_tracker;
		$this->tracker->reset();
	}

	/**
	 * Leave the tracker empty for the next test.
	 */
	public function tear_down() {
		$this->tracker->reset();
		parent::tear_down();
	}

	/**
	 * Stores the id and kind.
	 */
	public function test_record_stores_id_and_kind() {
		$this->tracker->record( 5, 'css' );

		$this->assertSame( 1, $this->tracker->count() );
		$this->assertSame(
			[
				'id'   => 5,
				'kind' => 'css',
			],
			$this->tracker->all()[5]
		);
	}

	/**
	 * A repeated id is ignored, keeping the first kind seen.
	 */
	public function test_record_dedups_by_id_keeping_first() {
		$this->tracker->record( 5, 'php' );
		$this->tracker->record( 5, 'css' );

		$this->assertSame( 1, $this->tracker->count() );
		$this->assertSame( 'php', $this->tracker->all()[5]['kind'] );
	}

	/**
	 * Non-positive ids are not recorded.
	 */
	public function test_record_ignores_non_positive_ids() {
		$this->tracker->record( 0, 'php' );
		$this->tracker->record( -3, 'php' );

		$this->assertSame( 0, $this->tracker->count() );
	}

	/**
	 * The after_execute_snippet hook records a PHP snippet.
	 */
	public function test_execute_hook_records_php_snippet() {
		do_action( 'code_snippets/after_execute_snippet', '<?php', 7, true );

		$this->assertSame(
			[
				'id'   => 7,
				'kind' => 'php',
			],
			$this->tracker->all()[7] ?? null
		);
	}

	/**
	 * The flat-file execute hook records a PHP snippet.
	 */
	public function test_flat_file_execute_hook_records_php_snippet() {
		do_action( 'code_snippets/after_execute_snippet_from_flat_file', '/path/8.php', 8 );

		$this->assertSame( 'php', $this->tracker->all()[8]['kind'] ?? null );
	}

	/**
	 * The snippet_ran_on_page hook records with the emitted kind.
	 */
	public function test_output_hook_records_with_kind() {
		do_action( 'code_snippets/snippet_ran_on_page', 9, 'css' );

		$this->assertSame(
			[
				'id'   => 9,
				'kind' => 'css',
			],
			$this->tracker->all()[9] ?? null
		);
	}

	/**
	 * A non-integer id fired on a hook is ignored rather than fatal.
	 */
	public function test_hook_ignores_non_integer_id() {
		do_action( 'code_snippets/snippet_ran_on_page', 'not-an-id', 'css' );

		$this->assertSame( 0, $this->tracker->count() );
	}

	/**
	 * Clears all records.
	 */
	public function test_reset_clears_records() {
		$this->tracker->record( 5, 'php' );
		$this->tracker->reset();

		$this->assertSame( 0, $this->tracker->count() );
		$this->assertSame( [], $this->tracker->all() );
	}
}
