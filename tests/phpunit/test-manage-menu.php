<?php

namespace Code_Snippets\Tests;

use Code_Snippets\Admin\Menus\Manage_Menu;
use function Code_Snippets\code_snippets;

/**
 * Tests for the manage menu registration.
 *
 * @group admin-menu
 */
class Manage_Menu_Test extends TestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static int $admin_user_id;

	/**
	 * Set up fixtures before any tests run.
	 *
	 * @param mixed $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_user_id = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$admin_user_id );
		set_current_screen( 'toplevel_page_' . code_snippets()->get_menu_slug() );
	}

	/**
	 * The manage screen registers a Columns section in Screen Options.
	 *
	 * @return void
	 */
	public function test_load_registers_screen_option_columns(): void {
		$menu = new Manage_Menu();
		$menu->load();

		$columns = get_column_headers( get_current_screen() );

		$this->assertSame( 'Columns', $columns['_title'] );
		$this->assertSame( 'Description', $columns['desc'] );
		$this->assertSame( 'Modified', $columns['date'] );
	}

	/**
	 * Hidden columns are localized for the manage table app.
	 *
	 * @return void
	 */
	public function test_enqueue_assets_localizes_hidden_columns(): void {
		$screen = get_current_screen();
		update_user_option( self::$admin_user_id, 'manage' . $screen->id . 'columnshidden', array( 'desc', 'date' ) );

		$menu = new Manage_Menu();
		$menu->enqueue_assets();

		$data = wp_scripts()->get_data( Manage_Menu::JS_HANDLE, 'data' );

		$this->assertIsString( $data );
		$this->assertStringContainsString( '"hiddenColumns":["desc","date"]', $data );
	}
}
