<?php

namespace Code_Snippets\Admin\Menus\Manage;

use Code_Snippets\Admin\Menus\Admin_Menu;
use Code_Snippets\AdminUnitTestCase;
use Code_Snippets\Model\Snippet;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippets;
use function Code_Snippets\save_snippet;
use function Code_Snippets\trash_snippet;

/**
 * Tests for manage menu assets and localized data.
 */
class Manage_Menu_Assets_Test extends AdminUnitTestCase {

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		set_current_screen( 'toplevel_page_' . code_snippets()->get_menu_slug() );
		unset( $_REQUEST['subpage'] );
	}

	/**
	 * Localized data retains the expected manage data keys.
	 *
	 * @return void
	 */
	public function test_enqueue_localizes_manage_data(): void {
		$filter = static fn() => PHP_INT_MAX;
		add_filter( 'code_snippets/manage/inline_snippets_limit', $filter );

		$this->enqueue_assets();
		$localized = $this->get_localized_data();

		remove_filter( 'code_snippets/manage/inline_snippets_limit', $filter );

		$this->assertSame(
			[
				'hasNetworkCap',
				'hiddenColumns',
				'truncateRowValues',
				'snippetsPerPage',
				'cloudSearchPerPage',
				'isSafeModeActive',
				'bulkDownloadNonce',
				'runOnceNonce',
				'supportsZipDownloads',
				'editorTheme',
				'typeCounts',
				'listOrder',
				'snippetsList',
			],
			array_keys( $localized )
		);
	}

	/**
	 * Preview assets exclude the full editing dependencies.
	 *
	 * @return void
	 */
	public function test_enqueue_loads_only_preview_editor_dependencies(): void {
		foreach ( [ 'htmlhint', 'csslint', 'jshint', 'code-snippets-code-editor' ] as $handle ) {
			wp_dequeue_script( $handle );
		}

		$this->enqueue_assets();

		$this->assertTrue( wp_script_is( 'code-editor' ) );
		$this->assertTrue( wp_style_is( 'code-editor' ) );
		$this->assertFalse( wp_script_is( 'htmlhint' ) );
		$this->assertFalse( wp_script_is( 'csslint' ) );
		$this->assertFalse( wp_script_is( 'jshint' ) );
		$this->assertFalse( wp_script_is( 'code-snippets-code-editor' ) );
	}

	/**
	 * The inline snippets threshold can be disabled.
	 *
	 * @return void
	 */
	public function test_enqueue_applies_disabled_inline_snippets_limit(): void {
		add_filter( 'code_snippets/manage/inline_snippets_limit', '__return_zero' );

		$this->enqueue_assets();
		$localized = $this->get_localized_data();

		remove_filter( 'code_snippets/manage/inline_snippets_limit', '__return_zero' );

		$this->assertArrayNotHasKey( 'snippetsList', $localized );
	}

	/**
	 * Snippet lists at or below the configured limit are localized inline.
	 *
	 * @dataProvider provide_inline_limit_offsets
	 *
	 * @param int $limit_offset Number added to the current snippet count.
	 *
	 * @return void
	 */
	public function test_enqueue_localizes_snippets_within_inline_limit( int $limit_offset ): void {
		save_snippet( new Snippet( [ 'name' => 'Inline Snippet Fixture' ] ) );
		$inline_limit = count( get_snippets() ) + $limit_offset;
		$filter = static fn() => $inline_limit;
		add_filter( 'code_snippets/manage/inline_snippets_limit', $filter );

		$this->enqueue_assets();
		$localized = $this->get_localized_data();

		remove_filter( 'code_snippets/manage/inline_snippets_limit', $filter );

		$this->assertArrayHasKey( 'snippetsList', $localized );
	}

	/**
	 * Provide offsets that place the snippet count at or below the inline limit.
	 *
	 * @return array<string,array{int}>
	 */
	public static function provide_inline_limit_offsets(): array {
		return [
			'at the limit'    => [ 0 ],
			'below the limit' => [ 1 ],
		];
	}

	/**
	 * Snippet lists above the configured limit are omitted.
	 *
	 * @return void
	 */
	public function test_enqueue_omits_snippets_above_inline_limit(): void {
		save_snippet( new Snippet( [ 'name' => 'Oversized Inline Snippet Fixture' ] ) );
		$inline_limit = count( get_snippets() ) - 1;
		$filter = static fn() => $inline_limit;
		add_filter( 'code_snippets/manage/inline_snippets_limit', $filter );

		$this->enqueue_assets();
		$localized = $this->get_localized_data();

		remove_filter( 'code_snippets/manage/inline_snippets_limit', $filter );

		$this->assertArrayNotHasKey( 'snippetsList', $localized );
	}

	/**
	 * Compatible scopes are combined and trashed snippets are excluded.
	 *
	 * @return void
	 */
	public function test_count_returns_non_trashed_counts_by_snippet_type(): void {
		$this->enqueue_assets();
		$before = $this->get_localized_data();

		save_snippet( new Snippet( [ 'scope' => 'global' ] ) );
		save_snippet( new Snippet( [ 'scope' => 'admin' ] ) );
		save_snippet( new Snippet( [ 'scope' => 'content' ] ) );
		$trashed = save_snippet( new Snippet( [ 'scope' => 'site-css' ] ) );

		$this->assertInstanceOf( Snippet::class, $trashed );
		trash_snippet( $trashed->id );

		$this->enqueue_assets();
		$localized = $this->get_localized_data();

		$this->assertSame( ( $before['typeCounts']['all'] ?? 0 ) + 3, $localized['typeCounts']['all'] );
		$this->assertSame( ( $before['typeCounts']['php'] ?? 0 ) + 2, $localized['typeCounts']['php'] );
		$this->assertSame( ( $before['typeCounts']['html'] ?? 0 ) + 1, $localized['typeCounts']['html'] );
		$this->assertArrayNotHasKey( 'css', $localized['typeCounts'], 'css' );
	}

	/**
	 * Enqueue assets through the extracted service.
	 *
	 * @return void
	 */
	private function enqueue_assets(): void {
		$assets = new Manage_Menu_Assets( new Manage_Menu_Screen_Options() );
		$assets->enqueue( Admin_Menu::$script_deps, Admin_Menu::$style_deps );
	}

	/**
	 * Decode the latest manage menu localization payload.
	 *
	 * @return array<string, mixed>
	 */
	private function get_localized_data(): array {
		$data = wp_scripts()->get_data( 'code-snippets-manage-menu', 'data' );
		$prefix = 'var CODE_SNIPPETS_MANAGE = ';
		$offset = is_string( $data ) ? strrpos( $data, $prefix ) : false;

		$this->assertNotFalse( $offset );
		$json = substr( $data, $offset + strlen( $prefix ) );

		return json_decode( substr( $json, 0, strrpos( $json, ';' ) ), true );
	}

	/**
	 * The inline snippets threshold can be disabled for constrained installations.
	 *
	 * @return void
	 */
	public function test_enqueue_assets_applies_inline_snippets_limit_filter(): void {
		add_filter( 'code_snippets/manage/inline_snippets_limit', '__return_zero' );

		$this->enqueue_assets();
		$data = wp_scripts()->get_data( 'code-snippets-manage-menu', 'data' );
		$localized_offset = is_string( $data ) ? strrpos( $data, 'var CODE_SNIPPETS_MANAGE = ' ) : false;

		remove_filter( 'code_snippets/manage/inline_snippets_limit', '__return_zero' );

		$this->assertIsString( $data );
		$this->assertNotFalse( $localized_offset );
		$this->assertStringNotContainsString( '"snippetsList":', substr( $data, $localized_offset ) );
	}

	/**
	 * The manage page no longer loads the Prism assets: CodeMirror renders the
	 * preview modal, and Prism remains registered for the front-end shortcode.
	 *
	 * @return void
	 */
	public function test_enqueue_assets_does_not_enqueue_prism(): void {
		$this->enqueue_assets();

		$this->assertTrue( wp_style_is( 'code-snippets-manage' ) );
		$this->assertTrue( wp_script_is( 'code-snippets-manage-menu' ) );
		$this->assertFalse( wp_style_is( 'code-snippets-prism' ) );
		$this->assertFalse( wp_script_is( 'code-snippets-prism' ) );
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

		$this->enqueue_assets();

		$this->assertTrue( wp_script_is( 'code-editor' ) );
		$this->assertTrue( wp_style_is( 'code-editor' ) );
		$this->assertFalse( wp_script_is( 'htmlhint' ) );
		$this->assertFalse( wp_script_is( 'csslint' ) );
		$this->assertFalse( wp_script_is( 'jshint' ) );
		$this->assertFalse( wp_script_is( 'code-snippets-code-editor' ) );
	}
}
