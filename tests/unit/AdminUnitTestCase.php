<?php

namespace Code_Snippets;

use WP_UnitTest_Factory;

/**
 * Base test case for all Code Snippets tests.
 */
class AdminUnitTestCase extends UnitTestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static int $admin_user_id;

	/**
	 * Retrieve the identifier of the administrator user created for these tests.
	 *
	 * @return int
	 */
	protected static function get_user_id(): int {
		return self::$admin_user_id;
	}

	/**
	 * Set up fixtures before any tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_user_id = $factory->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$admin_user_id );
	}
}
