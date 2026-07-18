<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Model\Snippet;
use Code_Snippets\Utils\Code_Highlighter;
use Code_Snippets\UnitTestCase;
use ReflectionException;
use ReflectionMethod;
use WP_Error;
use WP_UnitTest_Factory;
use function Code_Snippets\code_snippets;
use function Code_Snippets\save_snippet;

/**
 * Tests for the manage menu registration.
 *
 * @group admin-menu
 */
class Manage_Menu_Test extends UnitTestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static int $admin_user_id;

	/**
	 * Set up fixtures before any tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
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
		remove_all_filters( 'manage_' . get_current_screen()->id . '_columns' );
		remove_all_filters( 'screen_settings' );
		delete_user_option( self::$admin_user_id, 'snippets_table_truncate_row_values' );
		unset(
			$_POST['wp_screen_options'],
			$_POST['code_snippets_action'],
			$_POST['code_snippets_bulk_download_nonce'],
			$_POST['snippets'],
			$_POST['screenoptionnonce'],
			$_POST['snippets_table_truncate_row_values'],
			$_REQUEST['page'],
			$_REQUEST['subpage']
		);
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
		$this->assertStringContainsString( '"bulkDownloadNonce":"', $data );
		$this->assertStringContainsString( '"supportsZipDownloads":', $data );
	}

	/**
	 * The manage screen loads only the assets required for read-only code previews.
	 *
	 * @return void
	 */
	public function test_enqueue_assets_loads_preview_editor_without_full_editor_dependencies(): void {
		foreach ( [ 'htmlhint', 'csslint', 'jshint', 'code-snippets-code-editor' ] as $handle ) {
			wp_dequeue_script( $handle );
		}

		$menu = new Manage_Menu();
		$menu->enqueue_assets();

		$this->assertTrue( wp_script_is( 'code-editor', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'code-editor', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'htmlhint', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'csslint', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'jshint', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'code-snippets-code-editor', 'enqueued' ) );
	}

	/**
	 * The manage screen renders a truncation toggle in Screen Options.
	 *
	 * @return void
	 */
	public function test_render_screen_settings_adds_truncation_toggle(): void {
		$menu = new Manage_Menu();

		$output = $menu->render_screen_settings( '' );

		$this->assertStringContainsString( 'snippets-table-truncate-row-values', $output );
		$this->assertStringContainsString( 'Truncate long snippet names and descriptions', $output );
	}

	/**
	 * The Community Cloud view does not render snippet-only Screen Options controls.
	 *
	 * @return void
	 */
	public function test_render_screen_settings_skips_truncation_toggle_on_cloud_community_view(): void {
		$_REQUEST['subpage'] = 'cloud-community';

		$menu = new Manage_Menu();
		$output = $menu->render_screen_settings( '' );

		$this->assertSame( '', $output );
	}

	/**
	 * The Community Cloud view does not register snippet table columns in Screen Options.
	 *
	 * @return void
	 */
	public function test_load_skips_screen_option_columns_on_cloud_community_view(): void {
		$_REQUEST['subpage'] = 'cloud-community';

		$menu = new Manage_Menu();
		$menu->load();

		$screen = get_current_screen();

		$this->assertFalse( has_filter( "manage_{$screen->id}_columns", array( $menu, 'get_screen_columns' ) ) );
		$this->assertFalse( has_filter( 'screen_settings', array( $menu, 'render_screen_settings' ) ) );
	}

	/**
	 * The Community Cloud view still registers the shared pagination Screen Option.
	 *
	 * @return void
	 */
	public function test_load_registers_per_page_screen_option_on_cloud_community_view(): void {
		$_REQUEST['subpage'] = 'cloud-community';

		$menu = new Manage_Menu();
		$menu->load();

		$screen = get_current_screen();

		$this->assertSame( 'snippets_per_page', $screen->get_option( 'per_page', 'option' ) );
		$this->assertSame( 100, $screen->get_option( 'per_page', 'default' ) );
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

	/**
	 * The Community Cloud screen does not overwrite the snippets-table truncation preference.
	 *
	 * @return void
	 */
	public function test_save_truncation_preference_ignores_cloud_community_view(): void {
		update_user_option( self::$admin_user_id, 'snippets_table_truncate_row_values', 1 );

		$_REQUEST['page'] = code_snippets()->get_menu_slug();
		$_REQUEST['subpage'] = 'cloud-community';
		$_POST['wp_screen_options'] = array(
			'option' => 'snippets_per_page',
			'value'  => '20',
		);
		$_POST['screenoptionnonce'] = wp_create_nonce( 'screen-options-nonce' );

		$menu = new Manage_Menu();
		$menu->save_truncation_preference();

		$this->assertTrue( (bool) get_user_option( 'snippets_table_truncate_row_values', self::$admin_user_id ) );
	}

	/**
	 * Subsite admins cannot request downloads from the network snippets table.
	 *
	 * @return void
	 *
	 * @throws ReflectionException Creates instance of ReflectionMethod class.
	 */
	public function test_network_bulk_download_requires_network_cap(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network snippet downloads only apply on multisite.' );
		}

		$snippet = save_snippet(
			new Snippet(
				array(
					'name'    => 'Network Download Fixture',
					'code'    => '<?php echo "network";',
					'scope'   => 'global',
					'network' => true,
				)
			)
		);

		$snippets_json = wp_json_encode(
			array(
				array(
					'id'      => $snippet->id,
					'network' => true,
				),
			)
		);

		$menu = new Manage_Menu();
		$method = new ReflectionMethod( $menu, 'get_requested_download_snippets' );
		$method->setAccessible( true );
		$result = $method->invoke( $menu, $snippets_json );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'code_snippets_forbidden_network_download', $result->get_error_code() );
	}

	/**
	 * The manage page no longer loads the Prism assets: CodeMirror renders the
	 * preview modal, and Prism remains registered for the front-end shortcode.
	 *
	 * @return void
	 */
	public function test_enqueue_assets_does_not_enqueue_prism(): void {
		$menu = new Manage_Menu();
		$menu->enqueue_assets();

		$this->assertTrue( wp_style_is( Manage_Menu::CSS_HANDLE ) );
		$this->assertTrue( wp_script_is( Manage_Menu::JS_HANDLE ) );
		$this->assertFalse( wp_style_is( Code_Highlighter::PRISM_HANDLE ) );
		$this->assertFalse( wp_script_is( Code_Highlighter::PRISM_HANDLE ) );
	}
}
