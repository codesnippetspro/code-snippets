<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Admin\Whats_New_Badge;
use Code_Snippets\Client\Welcome_Client;
use Code_Snippets\UnitTestCase;
use WP_UnitTest_Factory;
use function Code_Snippets\code_snippets;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Tests for the What's New menu.
 */
class Welcome_Menu_Test extends UnitTestCase {

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
	 * Loading the page marks the current release seen before assets enqueue.
	 *
	 * @return void
	 */
	public function test_load_marks_current_release_seen(): void {
		wp_set_current_user( self::$admin_user_id );
		set_current_screen( 'snippets_page_' . code_snippets()->get_menu_slug( 'welcome' ) );
		update_user_meta( self::$admin_user_id, Whats_New_Badge::USER_META_KEY, '1.0.0' );

		$client = $this->getMockBuilder( Welcome_Client::class )
			->disableOriginalConstructor()
			->getMock();
		$menu = new Welcome_Menu( $client );
		$menu->load();

		$this->assertSame(
			PLUGIN_VERSION,
			get_user_meta( self::$admin_user_id, Whats_New_Badge::USER_META_KEY, true )
		);
	}
}
