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
		delete_user_option( self::$admin_user_id, 'snippets_table_truncate_row_values' );
		unset( $_POST['wp_screen_options'], $_POST['screenoptionnonce'], $_POST['snippets_table_truncate_row_values'], $_REQUEST['page'] );
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
		update_user_option( self::$admin_user_id, 'snippets_table_truncate_row_values', 0 );

		$menu = new Manage_Menu();
		$menu->enqueue_assets();

		$data = wp_scripts()->get_data( Manage_Menu::JS_HANDLE, 'data' );

		$this->assertIsString( $data );
		$this->assertStringContainsString( '"hiddenColumns":["desc","date"]', $data );
		$this->assertStringContainsString( '"truncateRowValues":"0"', $data );
	}

	/**
	 * The manage screen renders a truncation toggle in Screen Options.
	 *
	 * @return void
	 */
	public function test_render_screen_settings_adds_truncation_toggle(): void {
		$menu = new Manage_Menu();

		$output = $menu->render_screen_settings( '', get_current_screen() );

		$this->assertStringContainsString( 'snippets-table-truncate-row-values', $output );
		$this->assertStringContainsString( 'Truncate long row values', $output );
	}

	/**
	 * The truncation preference is saved from the Screen Options form.
	 *
	 * @return void
	 */
	public function test_save_truncation_preference_updates_user_option(): void {
		$_REQUEST['page'] = code_snippets()->get_menu_slug();
		$_POST['wp_screen_options'] = array(
			'option' => 'snippets_per_page',
			'value'  => '20',
		);
		$_POST['screenoptionnonce'] = wp_create_nonce( 'screen-options-nonce' );

		$menu = new Manage_Menu();
		$menu->save_truncation_preference();

		$this->assertFalse( (bool) get_user_option( 'snippets_table_truncate_row_values', self::$admin_user_id ) );

		$_POST['snippets_table_truncate_row_values'] = '1';
		$menu->save_truncation_preference();

		$this->assertTrue( (bool) get_user_option( 'snippets_table_truncate_row_values', self::$admin_user_id ) );
	}
}
