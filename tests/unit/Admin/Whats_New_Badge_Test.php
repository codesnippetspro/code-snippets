<?php

namespace Code_Snippets\Admin;

use Code_Snippets\UnitTestCase;
use WP_UnitTest_Factory;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Tests for the What's New unseen-release state.
 */
class Whats_New_Badge_Test extends UnitTestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static int $admin_user_id;

	/**
	 * Create the administrator fixture.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_user_id = $factory->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * Reset the current user's seen version.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::$admin_user_id );
		delete_user_meta( self::$admin_user_id, Whats_New_Badge::USER_META_KEY );
	}

	/**
	 * Check whether stored versions identify an unseen release.
	 *
	 * @dataProvider provide_seen_versions
	 *
	 * @param mixed $seen     Stored user-meta value.
	 * @param bool  $expected Whether the current release is unseen.
	 *
	 * @return void
	 */
	public function test_has_unseen_release( $seen, bool $expected ): void {
		if ( null !== $seen ) {
			update_user_meta( self::$admin_user_id, Whats_New_Badge::USER_META_KEY, $seen );
		}

		$this->assertSame( $expected, Whats_New_Badge::has_unseen_release() );
	}

	/**
	 * Provide stored seen versions and their expected state.
	 *
	 * @return array<string, array{mixed, bool}>
	 */
	public static function provide_seen_versions(): array {
		return [
			'missing' => [ null, true ],
			'older' => [ '1.0.0', true ],
			'current' => [ PLUGIN_VERSION, false ],
			'newer' => [ '99.0.0', false ],
			'malformed' => [ [ 'unexpected' ], true ],
		];
	}

	/**
	 * Marking the page seen stores the current release for this user.
	 *
	 * @return void
	 */
	public function test_mark_seen_release(): void {
		Whats_New_Badge::mark_seen_release();

		$this->assertSame(
			PLUGIN_VERSION,
			get_user_meta( self::$admin_user_id, Whats_New_Badge::USER_META_KEY, true )
		);
	}

	/**
	 * Activation seeds fresh installs without overwriting reactivations.
	 *
	 * @return void
	 */
	public function test_seed_fresh_install(): void {
		delete_option( 'code_snippets_version' );
		Whats_New_Badge::seed_fresh_install();
		$this->assertSame(
			PLUGIN_VERSION,
			get_user_meta( self::$admin_user_id, Whats_New_Badge::USER_META_KEY, true )
		);

		update_user_meta( self::$admin_user_id, Whats_New_Badge::USER_META_KEY, '1.0.0' );
		update_option( 'code_snippets_version', PLUGIN_VERSION );
		Whats_New_Badge::seed_fresh_install();
		$this->assertSame(
			'1.0.0',
			get_user_meta( self::$admin_user_id, Whats_New_Badge::USER_META_KEY, true )
		);
	}
}
