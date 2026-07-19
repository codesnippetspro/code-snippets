<?php

namespace Code_Snippets\Admin;

use Code_Snippets\UnitTestCase;
use ReflectionMethod;
use WP_UnitTest_Factory;
use function Code_Snippets\code_snippets;
use const Code_Snippets\PLUGIN_FILE;

/**
 * Tests for foreign admin notice filtering on Code Snippets screens.
 *
 * Callback ownership is resolved from a callback's defining file: closures declared in this test file
 * live outside the plugin directory and therefore stand in for foreign notices, while methods of real
 * plugin classes stand in for Code Snippets-owned notices.
 *
 * @group admin-notices
 */
class Notice_Filter_Test extends UnitTestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static int $admin_user_id;

	/**
	 * Notice filter under test.
	 *
	 * @var Notice_Filter
	 */
	private Notice_Filter $notice_filter;

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
		set_current_screen( 'dashboard' );

		code_snippets()->admin = new Bootstrap_Admin();
		code_snippets()->admin->load_classes();

		set_current_screen( code_snippets()->admin->menus['manage']->get_hookname() );

		$this->notice_filter = new Notice_Filter();
	}

	/**
	 * Filtering removes foreign notice callbacks while keeping Code Snippets-owned callbacks.
	 *
	 * @return void
	 */
	public function test_filtering_removes_foreign_notices_and_keeps_code_snippets_notices(): void {
		$foreign = static function () {};
		$owned = [ code_snippets()->admin, 'print_notices' ];

		add_action( 'admin_notices', $foreign );
		add_action( 'admin_notices', $owned );

		$this->notice_filter->filter_foreign_notices();

		$this->assertFalse( has_action( 'admin_notices', $foreign ) );
		$this->assertNotFalse( has_action( 'admin_notices', $owned ) );
	}

	/**
	 * Callback ownership requires a complete plugin directory boundary.
	 *
	 * @return void
	 */
	public function test_callback_ownership_requires_plugin_directory_boundary(): void {
		$method = new ReflectionMethod( $this->notice_filter, 'is_code_snippets_file' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $this->notice_filter, PLUGIN_FILE ) );
		$this->assertFalse(
			$method->invoke( $this->notice_filter, dirname( PLUGIN_FILE ) . '-extra/callback.php' )
		);
	}

	/**
	 * A Code Snippets screen registers the filtering hooks by default.
	 *
	 * @return void
	 */
	public function test_registers_filtering_on_code_snippets_screen(): void {
		$this->notice_filter->register_filtering( get_current_screen() );

		$this->assertNotFalse( has_action( 'admin_head', [ $this->notice_filter, 'filter_foreign_notices' ] ) );
		$this->assertNotFalse( has_action( 'admin_head', [ $this->notice_filter, 'print_fallback_styles' ] ) );
	}

	/**
	 * The separate Add New editor screen also registers notice filtering.
	 *
	 * @return void
	 */
	public function test_registers_filtering_on_add_new_screen(): void {
		$edit_menu = code_snippets()->admin->menus['edit'];
		$hooknames = $edit_menu->get_hooknames();
		set_current_screen( $hooknames[1] );

		$this->notice_filter->register_filtering( get_current_screen() );

		$this->assertNotFalse( has_action( 'admin_head', [ $this->notice_filter, 'filter_foreign_notices' ] ) );
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

		$this->assertFalse( has_action( 'admin_head', [ $this->notice_filter, 'filter_foreign_notices' ] ) );
	}

	/**
	 * Non-Code Snippets screens do not register filtering hooks.
	 *
	 * @return void
	 */
	public function test_does_not_register_on_foreign_screen(): void {
		set_current_screen( 'dashboard' );

		$this->notice_filter->register_filtering( get_current_screen() );

		$this->assertFalse( has_action( 'admin_head', [ $this->notice_filter, 'filter_foreign_notices' ] ) );
	}

	/**
	 * Filtering removes foreign invokable-object callbacks.
	 *
	 * @return void
	 */
	public function test_filtering_removes_foreign_invokable_object(): void {
		$foreign_invokable = new class() {
			/**
			 * Emit a foreign notice.
			 */
			public function __invoke() {
				echo 'foreign notice';
			}
		};

		add_action( 'admin_notices', $foreign_invokable );

		$this->notice_filter->filter_foreign_notices();

		$this->assertFalse( has_action( 'admin_notices', $foreign_invokable ) );
	}

	/**
	 * The compact-mode Tools submenu hookname activates notice filtering.
	 *
	 * @return void
	 */
	public function test_registers_filtering_on_compact_menu_screen(): void {
		add_filter( 'code_snippets_compact_menu', '__return_true' );

		$manage_menu = code_snippets()->admin->menus['manage'];
		$hooknames = $manage_menu->get_hooknames();
		$compact_hookname = get_plugin_page_hookname( code_snippets()->get_menu_slug(), 'tools.php' );

		$this->assertContains( $compact_hookname, $hooknames );

		set_current_screen( $compact_hookname );
		$this->notice_filter->register_filtering( get_current_screen() );

		$this->assertNotFalse( has_action( 'admin_head', [ $this->notice_filter, 'filter_foreign_notices' ] ) );

		remove_filter( 'code_snippets_compact_menu', '__return_true' );
	}
}
