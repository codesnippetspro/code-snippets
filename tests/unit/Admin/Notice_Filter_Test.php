<?php

namespace Code_Snippets\Admin;

use Code_Snippets\AdminUnitTestCase;
use function Code_Snippets\code_snippets;

/**
 * Tests for hiding foreign admin notices on Code Snippets screens.
 *
 * @group admin-notices
 */
class Notice_Filter_Test extends AdminUnitTestCase {

	/**
	 * Notice filter under test.
	 *
	 * @var Notice_Filter
	 */
	private Notice_Filter $notice_filter;

	/**
	 * Instance of admin class.
	 *
	 * @var Bootstrap_Admin
	 */
	private Bootstrap_Admin $admin;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		set_current_screen( 'dashboard' );

		$this->admin = new Bootstrap_Admin();
		$this->admin->load_classes();

		set_current_screen( $this->admin->get_menu( 'manage' )->get_hookname() );

		$this->notice_filter = new Notice_Filter( $this->admin );
	}

	/**
	 * Registering the notice filter does not remove third-party callbacks.
	 *
	 * @return void
	 */
	public function test_filtering_preserves_foreign_notice_callbacks(): void {
		$foreign = static function () {
		};
		add_action( 'admin_notices', $foreign );

		$this->notice_filter->register_filtering( get_current_screen() );

		$this->assertNotFalse( has_action( 'admin_notices', $foreign ) );
		$this->assertFalse( method_exists( $this->notice_filter, 'filter_foreign_notices' ) );
	}

	/**
	 * A Code Snippets screen registers the filtering hooks by default.
	 *
	 * @return void
	 */
	public function test_registers_filtering_on_code_snippets_screen(): void {
		$this->notice_filter->register_filtering( get_current_screen() );

		$this->assertNotFalse( has_action( 'admin_head', [ $this->notice_filter, 'print_fallback_styles' ] ) );
	}

	/**
	 * The separate Add New editor screen also registers notice filtering.
	 *
	 * @return void
	 */
	public function test_registers_filtering_on_add_new_screen(): void {
		$hooknames = $this->admin->get_menu( 'edit' )->get_hooknames();
		set_current_screen( $hooknames[1] );

		$this->notice_filter->register_filtering( get_current_screen() );

		$this->assertNotFalse( has_action( 'admin_head', [ $this->notice_filter, 'print_fallback_styles' ] ) );
	}

	/**
	 * The filter_foreign_notices filter disables filtering when set to false.
	 *
	 * @return void
	 */
	public function test_filter_disables_filtering(): void {
		add_filter( 'code_snippets/admin/filter_foreign_notices', '__return_false' );

		$this->notice_filter->register_filtering( get_current_screen() );

		$this->assertFalse( has_action( 'admin_head', [ $this->notice_filter, 'print_fallback_styles' ] ) );
	}

	/**
	 * Non-Code Snippets screens do not register filtering hooks.
	 *
	 * @return void
	 */
	public function test_does_not_register_on_foreign_screen(): void {
		set_current_screen( 'dashboard' );

		$this->notice_filter->register_filtering( get_current_screen() );

		$this->assertFalse( has_action( 'admin_head', [ $this->notice_filter, 'print_fallback_styles' ] ) );
	}

	/**
	 * The compact-mode Tools submenu hookname activates notice filtering.
	 *
	 * @return void
	 */
	public function test_registers_filtering_on_compact_menu_screen(): void {
		add_filter( 'code_snippets_compact_menu', '__return_true' );

		$hooknames = $this->admin->get_menu( 'manage' )->get_hooknames();
		$compact_hookname = get_plugin_page_hookname( code_snippets()->get_menu_slug(), 'tools.php' );

		$this->assertContains( $compact_hookname, $hooknames );

		set_current_screen( $compact_hookname );
		$this->notice_filter->register_filtering( get_current_screen() );

		$this->assertNotFalse( has_action( 'admin_head', [ $this->notice_filter, 'print_fallback_styles' ] ) );

		remove_filter( 'code_snippets_compact_menu', '__return_true' );
	}
}
