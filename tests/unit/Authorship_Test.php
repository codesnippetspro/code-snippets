<?php

namespace Code_Snippets;

use Code_Snippets\Model\Snippet;
use WP_REST_Request;
use WP_UnitTest_Factory;

/**
 * Tests for snippet authorship tracking (created_by / updated_by) and the
 * resolved author data surfaced to the admin UI and REST responses.
 *
 * @group authorship
 */
class Authorship_Test extends UnitTestCase {

	/**
	 * Original author user ID.
	 *
	 * @var int
	 */
	protected static int $author_id;

	/**
	 * Second editor user ID.
	 *
	 * @var int
	 */
	protected static int $editor_id;

	/**
	 * Create two administrator users before the class runs.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$author_id = $factory->user->create(
			[
				'role'         => 'administrator',
				'display_name' => 'Ada Author',
			]
		);
		self::$editor_id = $factory->user->create(
			[
				'role'         => 'administrator',
				'display_name' => 'Ed Editor',
			]
		);
	}

	/**
	 * Start each test with an empty snippets table.
	 */
	public function set_up() {
		parent::set_up();

		global $wpdb;
		$table_name = code_snippets()->db->get_table_name();
		$wpdb->query( "TRUNCATE TABLE $table_name" );
	}

	/**
	 * Create a snippet as the given user and return the stored copy.
	 *
	 * @param int    $user_id User to act as.
	 * @param string $name    Snippet name.
	 *
	 * @return Snippet
	 */
	private function save_as( int $user_id, string $name ): Snippet {
		wp_set_current_user( $user_id );

		$snippet = save_snippet(
			new Snippet(
				[
					'name' => $name,
					'code' => "echo 'hi';",
				]
			)
		);

		return get_snippet( $snippet->id );
	}

	/**
	 * Stamps both authorship columns with the current user on insert.
	 */
	public function test_save_stamps_author_on_insert() {
		$stored = $this->save_as( self::$author_id, 'Authored' );

		$this->assertSame( self::$author_id, $stored->created_by );
		$this->assertSame( self::$author_id, $stored->updated_by );
	}

	/**
	 * Advances updated_by on a later save by another user while created_by stays fixed.
	 */
	public function test_updated_by_advances_while_created_by_is_fixed() {
		$snippet = $this->save_as( self::$author_id, 'Shared' );

		wp_set_current_user( self::$editor_id );
		$snippet->name = 'Shared (edited)';
		save_snippet( $snippet );

		$stored = get_snippet( $snippet->id );
		$this->assertSame( self::$author_id, $stored->created_by, 'created_by is fixed at insert' );
		$this->assertSame( self::$editor_id, $stored->updated_by, 'updated_by follows the latest editor' );
	}

	/**
	 * Resolves a user ID to a compact display object.
	 */
	public function test_resolver_returns_display_object() {
		$author = get_snippet_author( self::$author_id );

		$this->assertIsArray( $author );
		$this->assertSame( self::$author_id, $author['id'] );
		$this->assertSame( 'Ada Author', $author['display_name'] );
		$this->assertArrayHasKey( 'avatar_url', $author );
	}

	/**
	 * Returns null for an empty or unknown user ID.
	 */
	public function test_resolver_returns_null_for_unknown() {
		$this->assertNull( get_snippet_author( 0 ) );
		$this->assertNull( get_snippet_author( 987654 ) );
	}

	/**
	 * Embeds a nested author object in the snippets REST response, not a raw ID.
	 */
	public function test_rest_response_embeds_nested_author() {
		$stored = $this->save_as( self::$author_id, 'Rest Authored' );

		$request = new WP_REST_Request( 'GET', '/code-snippets/v1/snippets/' . $stored->id );
		$data = rest_get_server()->response_to_data( rest_do_request( $request ), false );

		$this->assertIsArray( $data['created_by'] );
		$this->assertSame( self::$author_id, $data['created_by']['id'] );
		$this->assertSame( 'Ada Author', $data['created_by']['display_name'] );
		$this->assertArrayHasKey( 'avatar_url', $data['created_by'] );
	}
}
