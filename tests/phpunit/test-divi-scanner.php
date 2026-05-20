<?php

namespace Code_Snippets\Tests;

use Code_Snippets\UnifiedSnippets\Scanners\Divi_Theme_Options_Scanner;

/**
 * Tests for the Tier 3 Divi Theme Options scanner.
 *
 * The scanner is constructed with option-value overrides to avoid touching wp_options or
 * needing Divi installed in the test environment. Override mode also forces is_available()
 * to true so end-to-end behaviour can be exercised without activating the theme.
 *
 * @group unified-snippets
 */
class Divi_Scanner_Test extends TestCase {

	/**
	 * Populated fixture covering all five Divi Theme Options fields.
	 *
	 * @var array<string, string>
	 */
	private const FULL_FIXTURE = [
		'custom_css'                  => '.divi-test { color: red; }',
		'integration_head'            => '<meta name="divi-head" content="1">',
		'integration_body'            => '<script>window.diviBody = true;</script>',
		'integration_single_top'      => '<div class="divi-single-top">Top</div>',
		'integration_single_bottom'   => '<div class="divi-single-bottom">Bottom</div>',
		'integrate_header_enable'     => 'on',
		'integrate_body_enable'       => 'on',
		'integrate_singletop_enable'  => 'on',
		'integrate_singlebottom_enable' => 'on',
	];

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
	 * A populated fixture produces one Discovered_Snippet per Divi field with the expected
	 * type, source metadata, and active flags.
	 */
	public function test_scan_returns_one_snippet_per_populated_field() {
		$scanner = new Divi_Theme_Options_Scanner( self::FULL_FIXTURE );

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
		$scanner = new Divi_Theme_Options_Scanner(
			[
				'custom_css'       => '.only-css {}',
				'integration_head' => '   ',
				'integration_body' => '',
			]
		);

		$results = $scanner->scan();

		$this->assertCount( 1, $results );
		$this->assertSame( 'Divi Custom CSS', $results[0]->name );
		$this->assertSame( 'css', $results[0]->type );
	}

	/**
	 * Disabling Divi's `_integrate_header_enable` toggle is reflected as `is_active = false`
	 * on the discovered snippet so the Unified view does not falsely report it as running.
	 */
	public function test_disabled_toggle_marks_snippet_inactive() {
		$scanner = new Divi_Theme_Options_Scanner(
			[
				'integration_head'        => '<meta name="divi-head" content="1">',
				'integrate_header_enable' => 'off',
			]
		);

		$results = $scanner->scan();

		$this->assertCount( 1, $results );
		$this->assertSame( 'Divi Head Code', $results[0]->name );
		$this->assertFalse( $results[0]->is_active );
	}

	/**
	 * Divi treats unset toggles as 'on' (matching the checkbox `std` values in options_divi.php),
	 * so a populated Integration field with no stored toggle value must still report active.
	 */
	public function test_missing_toggle_defaults_to_active() {
		$scanner = new Divi_Theme_Options_Scanner(
			[
				'integration_body' => '<script>window.diviBody = true;</script>',
			]
		);

		$results = $scanner->scan();

		$this->assertCount( 1, $results );
		$this->assertSame( 'Divi Body Code', $results[0]->name );
		$this->assertTrue( $results[0]->is_active );
	}

	/**
	 * `source_path` is deterministic (no volatile line numbers) so the snippet hash remains
	 * stable across repeated scans. This is what change detection in Phase 5 relies on.
	 */
	public function test_hash_is_stable_across_scans() {
		$scanner_a = new Divi_Theme_Options_Scanner( self::FULL_FIXTURE );
		$scanner_b = new Divi_Theme_Options_Scanner( self::FULL_FIXTURE );

		$hashes_a = wp_list_pluck( $scanner_a->scan(), 'hash' );
		$hashes_b = wp_list_pluck( $scanner_b->scan(), 'hash' );

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
		$base = new Divi_Theme_Options_Scanner( self::FULL_FIXTURE );

		$modified_fixture                 = self::FULL_FIXTURE;
		$modified_fixture['integration_head'] = '<meta name="divi-head" content="2">';
		$modified = new Divi_Theme_Options_Scanner( $modified_fixture );

		$head_before = $this->index_by_name( $base->scan() )['Divi Head Code'];
		$head_after  = $this->index_by_name( $modified->scan() )['Divi Head Code'];

		$this->assertSame( $head_before->hash, $head_after->hash );
		$this->assertNotSame( $head_before->checksum, $head_after->checksum );
	}

	/**
	 * Without override fixtures, availability is driven by the active template. The test
	 * environment is not running Divi, so the scanner must report itself unavailable.
	 */
	public function test_is_available_false_without_divi_theme() {
		$scanner = new Divi_Theme_Options_Scanner();

		$this->assertFalse( $scanner->is_available() );
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
}
