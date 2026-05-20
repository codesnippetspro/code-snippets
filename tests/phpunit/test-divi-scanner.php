<?php

namespace Code_Snippets\Tests;

use Code_Snippets\UnifiedSnippets\Scanners\Divi_Theme_Options_Scanner;

/**
 * Tests for the Tier 3 Divi Theme Options scanner.
 *
 * The scanner has no test-only seams: all fixtures are written through real WordPress APIs
 * (`update_option()` for wp_options, and the `pre_option_template` / `pre_option_stylesheet`
 * filters for `get_template()`). Each test that seeds data is responsible for cleaning up,
 * and the filter callbacks are removed in tearDown.
 *
 * @group unified-snippets
 */
class Divi_Scanner_Test extends TestCase {

	/**
	 * Closures registered against `pre_option_*` filters during a test, captured so they can
	 * be removed in tearDown.
	 *
	 * @var array<int, array{hook: string, callback: callable}>
	 */
	private array $registered_filters = [];

	/**
	 * Tear down per-test fixtures: remove any `pre_option_*` filters this test registered, and
	 * delete the wp_options rows the per-storage tests seed.
	 */
	public function tear_down() {
		foreach ( $this->registered_filters as $entry ) {
			remove_filter( $entry['hook'], $entry['callback'] );
		}

		$this->registered_filters = [];

		delete_option( 'et_divi' );
		delete_option( 'divi_custom_css' );
		delete_option( 'divi_integration_head' );
		delete_option( 'divi_integration_body' );
		delete_option( 'divi_integration_single_top' );
		delete_option( 'divi_integration_single_bottom' );
		delete_option( 'divi_integrate_header_enable' );
		delete_option( 'divi_integrate_body_enable' );
		delete_option( 'divi_integrate_singletop_enable' );
		delete_option( 'divi_integrate_singlebottom_enable' );

		parent::tear_down();
	}

	/**
	 * Force `get_template()` (and its companion `get_stylesheet()`) to report a chosen theme
	 * slug for the duration of the test, without activating that theme on disk. Uses WordPress's
	 * own `pre_option_*` short-circuit filters so we don't poke at scanner internals.
	 *
	 * @param string $template Slug to return from get_template(), e.g. 'Divi' or 'twentytwentyfour'.
	 *
	 * @return void
	 */
	private function pretend_active_template( string $template ): void {
		$callback = static function () use ( $template ) {
			return $template;
		};

		foreach ( [ 'pre_option_template', 'pre_option_stylesheet' ] as $hook ) {
			add_filter( $hook, $callback );

			$this->registered_filters[] = [
				'hook'     => $hook,
				'callback' => $callback,
			];
		}
	}

	/**
	 * Index a scan result by snippet name for easier per-field assertions.
	 *
	 * @param array<int, \Code_Snippets\UnifiedSnippets\Model\Discovered_Snippet> $results Scanner output.
	 *
	 * @return array<string, \Code_Snippets\UnifiedSnippets\Model\Discovered_Snippet>
	 */
	private function index_by_name( array $results ): array {
		$indexed = [];

		foreach ( $results as $snippet ) {
			$indexed[ $snippet->name ] = $snippet;
		}

		return $indexed;
	}

	/**
	 * Write a fully populated `et_divi` bundle (one-row storage mode) covering every field
	 * the scanner knows about, with all enable toggles set to `'on'`.
	 *
	 * @return void
	 */
	private function seed_full_divi_bundle(): void {
		update_option(
			'et_divi',
			[
				'divi_custom_css'                => '.divi-test { color: red; }',
				'divi_integration_head'          => '<meta name="divi-head" content="1">',
				'divi_integration_body'          => '<script>window.diviBody = true;</script>',
				'divi_integration_single_top'    => '<div class="divi-single-top">Top</div>',
				'divi_integration_single_bottom' => '<div class="divi-single-bottom">Bottom</div>',
				'divi_integrate_header_enable'   => 'on',
				'divi_integrate_body_enable'     => 'on',
				'divi_integrate_singletop_enable' => 'on',
				'divi_integrate_singlebottom_enable' => 'on',
			]
		);
	}

	/**
	 * A fully populated Divi install produces one Discovered_Snippet per field with the
	 * expected type, source metadata, and active flags. Uses one-row storage mode (the modern
	 * Divi default).
	 */
	public function test_scan_returns_one_snippet_per_populated_field() {
		$this->pretend_active_template( 'Divi' );
		$this->seed_full_divi_bundle();

		$scanner = new Divi_Theme_Options_Scanner();

		$this->assertTrue( $scanner->is_available() );

		$results = $scanner->scan();
		$this->assertCount( 5, $results );

		$by_name = $this->index_by_name( $results );

		$this->assertArrayHasKey( 'Divi Custom CSS', $by_name );
		$this->assertSame( 'css', $by_name['Divi Custom CSS']->type );
		$this->assertSame( '.divi-test { color: red; }', $by_name['Divi Custom CSS']->code );
		$this->assertSame( 'divi://theme-options/custom_css', $by_name['Divi Custom CSS']->source_path );
		$this->assertTrue( $by_name['Divi Custom CSS']->is_active );

		$this->assertArrayHasKey( 'Divi Head Code', $by_name );
		$this->assertSame( 'html', $by_name['Divi Head Code']->type );
		$this->assertSame( 'divi://theme-options/integration_head', $by_name['Divi Head Code']->source_path );
		$this->assertTrue( $by_name['Divi Head Code']->is_active );

		$this->assertArrayHasKey( 'Divi Body Code', $by_name );
		$this->assertSame( 'html', $by_name['Divi Body Code']->type );
		$this->assertStringContainsString( 'wp_footer', $by_name['Divi Body Code']->import_notes );
		$this->assertTrue( $by_name['Divi Body Code']->is_active );

		$this->assertArrayHasKey( 'Divi Single Top Code', $by_name );
		$this->assertStringContainsString( 'et_before_post', $by_name['Divi Single Top Code']->import_notes );
		$this->assertTrue( $by_name['Divi Single Top Code']->is_active );

		$this->assertArrayHasKey( 'Divi Single Bottom Code', $by_name );
		$this->assertStringContainsString( 'et_after_post', $by_name['Divi Single Bottom Code']->import_notes );
		$this->assertTrue( $by_name['Divi Single Bottom Code']->is_active );

		foreach ( $results as $snippet ) {
			$this->assertSame( 'theme', $snippet->source_type );
			$this->assertSame( 'Divi', $snippet->source_name );
			$this->assertSame( 'divi-theme-options', $snippet->scanner_id );
			$this->assertSame( 'medium', $snippet->risk_level );
			$this->assertTrue( $snippet->is_importable );
			$this->assertSame( 0, $snippet->line_start );
			$this->assertSame( 0, $snippet->line_end );
		}
	}

	/**
	 * Empty and whitespace-only fields are skipped, so a sparsely populated install only
	 * surfaces the fields the user has actually filled in.
	 */
	public function test_scan_skips_empty_fields() {
		$this->pretend_active_template( 'Divi' );

		update_option(
			'et_divi',
			[
				'divi_custom_css'       => '.only-css {}',
				'divi_integration_head' => '   ',
				'divi_integration_body' => '',
			]
		);

		$results = ( new Divi_Theme_Options_Scanner() )->scan();

		$this->assertCount( 1, $results );
		$this->assertSame( 'Divi Custom CSS', $results[0]->name );
		$this->assertSame( 'css', $results[0]->type );
	}

	/**
	 * Disabling Divi's `_integrate_header_enable` toggle (stored as `'off'`) is reflected as
	 * `is_active = false` on the discovered snippet so the Unified view does not falsely
	 * report it as running.
	 */
	public function test_disabled_toggle_marks_snippet_inactive() {
		$this->pretend_active_template( 'Divi' );

		update_option(
			'et_divi',
			[
				'divi_integration_head'        => '<meta name="divi-head" content="1">',
				'divi_integrate_header_enable' => 'off',
			]
		);

		$results = ( new Divi_Theme_Options_Scanner() )->scan();

		$this->assertCount( 1, $results );
		$this->assertSame( 'Divi Head Code', $results[0]->name );
		$this->assertFalse( $results[0]->is_active );
	}

	/**
	 * Divi's runtime check uses strict equality against `'on'`, so a populated Integration field
	 * with no stored toggle value (which `et_get_option()` returns as boolean false) is NOT
	 * actually emitted by Divi. The scanner must mirror that.
	 */
	public function test_missing_toggle_reports_inactive() {
		$this->pretend_active_template( 'Divi' );

		update_option(
			'et_divi',
			[
				'divi_integration_body' => '<script>window.diviBody = true;</script>',
			]
		);

		$results = ( new Divi_Theme_Options_Scanner() )->scan();

		$this->assertCount( 1, $results );
		$this->assertSame( 'Divi Body Code', $results[0]->name );
		$this->assertFalse( $results[0]->is_active );
	}

	/**
	 * Divi's save handler writes the literal string `'false'` when a checkbox is unchecked at
	 * save time. That is not strictly equal to `'on'`, so the snippet must report inactive.
	 */
	public function test_literal_false_toggle_reports_inactive() {
		$this->pretend_active_template( 'Divi' );

		update_option(
			'et_divi',
			[
				'divi_integration_head'        => '<meta name="divi-head" content="1">',
				'divi_integrate_header_enable' => 'false',
			]
		);

		$results = ( new Divi_Theme_Options_Scanner() )->scan();

		$this->assertCount( 1, $results );
		$this->assertSame( 'Divi Head Code', $results[0]->name );
		$this->assertFalse( $results[0]->is_active );
	}

	/**
	 * `source_path` is deterministic (no volatile line numbers) so the snippet hash remains
	 * stable across repeated scans. This is what change detection in Phase 5 relies on.
	 */
	public function test_hash_is_stable_across_scans() {
		$this->pretend_active_template( 'Divi' );
		$this->seed_full_divi_bundle();

		$hashes_a = wp_list_pluck( ( new Divi_Theme_Options_Scanner() )->scan(), 'hash' );
		$hashes_b = wp_list_pluck( ( new Divi_Theme_Options_Scanner() )->scan(), 'hash' );

		sort( $hashes_a );
		sort( $hashes_b );

		$this->assertSame( $hashes_a, $hashes_b );
		$this->assertCount( 5, array_unique( $hashes_a ) );
	}

	/**
	 * Editing the code of one field changes its `checksum` (so change detection fires) but not
	 * its `hash` (so the snippet is recognised as the same source location).
	 */
	public function test_checksum_changes_with_code_but_hash_stays() {
		$this->pretend_active_template( 'Divi' );
		$this->seed_full_divi_bundle();

		$head_before = $this->index_by_name( ( new Divi_Theme_Options_Scanner() )->scan() )['Divi Head Code'];

		$bundle = get_option( 'et_divi' );
		$bundle['divi_integration_head'] = '<meta name="divi-head" content="2">';
		update_option( 'et_divi', $bundle );

		$head_after = $this->index_by_name( ( new Divi_Theme_Options_Scanner() )->scan() )['Divi Head Code'];

		$this->assertSame( $head_before->hash, $head_after->hash );
		$this->assertNotSame( $head_before->checksum, $head_after->checksum );
	}

	/**
	 * Without Divi (or Extra) as the active template, the scanner reports itself unavailable
	 * even if the wp_options rows still exist. Divi's hooks are not running, so the code is
	 * dormant and should not be surfaced.
	 */
	public function test_is_available_false_without_divi_theme() {
		$this->pretend_active_template( 'twentytwentyfour' );

		$this->assertFalse( ( new Divi_Theme_Options_Scanner() )->is_available() );
	}

	/**
	 * The sibling theme Extra shares Divi's option storage layout under a different shortname,
	 * so the scanner reports itself available when Extra is the active template.
	 */
	public function test_is_available_true_for_extra_theme() {
		$this->pretend_active_template( 'Extra' );

		$this->assertTrue( ( new Divi_Theme_Options_Scanner() )->is_available() );
	}

	/**
	 * Static scanner identity surfaces correctly to the registry / REST layer.
	 */
	public function test_scanner_identity() {
		$scanner = new Divi_Theme_Options_Scanner();

		$this->assertSame( 'divi-theme-options', $scanner->get_id() );
		$this->assertSame( 'Divi Theme Options', $scanner->get_label() );
		$this->assertSame( 'medium', $scanner->get_risk_level() );
		$this->assertTrue( $scanner->supports_import() );
		$this->assertFalse( $scanner->supports_editing() );
	}

	/**
	 * One-row storage: Divi packs every option into `et_divi`, keyed by the full field id
	 * (e.g. `divi_custom_css`). The scanner must look up `<shortname>_<key>` inside that array
	 * rather than the bare key.
	 */
	public function test_reads_real_one_row_storage() {
		$this->pretend_active_template( 'Divi' );

		update_option(
			'et_divi',
			[
				'divi_custom_css'       => '.real-one-row { color: green; }',
				'divi_integration_head' => '<meta name="real-one-row" content="1">',
			]
		);

		$results = $this->index_by_name( ( new Divi_Theme_Options_Scanner() )->scan() );

		$this->assertArrayHasKey( 'Divi Custom CSS', $results );
		$this->assertSame( '.real-one-row { color: green; }', $results['Divi Custom CSS']->code );
		$this->assertArrayHasKey( 'Divi Head Code', $results );
		$this->assertSame( '<meta name="real-one-row" content="1">', $results['Divi Head Code']->code );
	}

	/**
	 * Per-row storage: each field is stored as its own option named after the full field id
	 * (e.g. `divi_integration_body`), with no `et_` prefix. The scanner falls back to this read
	 * path when the `et_<shortname>` bundle is missing the key.
	 */
	public function test_reads_real_per_row_storage() {
		$this->pretend_active_template( 'Divi' );

		update_option( 'divi_integration_body', '<script>console.log("real-per-row");</script>' );

		$results = $this->index_by_name( ( new Divi_Theme_Options_Scanner() )->scan() );

		$this->assertArrayHasKey( 'Divi Body Code', $results );
		$this->assertSame(
			'<script>console.log("real-per-row");</script>',
			$results['Divi Body Code']->code
		);
	}
}
