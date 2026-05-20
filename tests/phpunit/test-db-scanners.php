<?php

namespace Code_Snippets\Tests;

use Code_Snippets\UnifiedSnippets\Scanners\Header_Footer_Code_Manager_Scanner;
use Code_Snippets\UnifiedSnippets\Scanners\Insert_Headers_And_Footers_Scanner;
use Code_Snippets\UnifiedSnippets\Scanners\Insert_PHP_Code_Snippet_Scanner;

require_once __DIR__ . '/fakes/Fake_Ihaf_Importer.php';
require_once __DIR__ . '/fakes/Fake_Hfcm_Importer.php';
require_once __DIR__ . '/fakes/Fake_Ipcs_Importer.php';

/**
 * Tests for the Tier 2 DB-backed Unified Snippets scanners that adapt existing competitor
 * importers (WPCode, HFCM, Insert PHP Code Snippet) through {@see DB_Scanner_Adapter}.
 *
 * Each test injects a {@see Fake_*_Importer} so the source plugin does not need to be installed.
 *
 * @group unified-snippets
 */
class DB_Scanners_Test extends TestCase {

	/**
	 * WPCode rows whose `code_type` is unsupported, or whose `code` is blank, are filtered
	 * out so only importable snippets reach the scan results.
	 */
	public function test_wpcode_filters_unsupported_and_empty() {
		$importer       = new Fake_Ihaf_Importer();
		$importer->rows = [
			[
				'code' => "console.log('hi');",
				'code_type' => 'js',
				'table_data' => [
					'id' => 1,
					'title' => 'JS',
				],
			],
			[
				'code' => '// php',
				'code_type' => 'php',
				'table_data' => [
					'id' => 2,
					'title' => 'PHP',
				],
			],
			[
				'code' => '<x>',
				'code_type' => 'text',
				'table_data' => [
					'id' => 3,
					'title' => 'Unsupported',
				],
			],
			[
				'code' => '   ',
				'code_type' => 'php',
				'table_data' => [
					'id' => 4,
					'title' => 'Blank',
				],
			],
		];

		$results = ( new Insert_Headers_And_Footers_Scanner( $importer ) )->scan();

		$this->assertCount( 2, $results );
		$this->assertSame( 'js', $results[0]->type );
		$this->assertSame( 'php', $results[1]->type );
	}

	/**
	 * HFCM's `status` column maps to `is_active`, and rows with an empty `snippet` are skipped
	 * so they don't pollute the scan results.
	 */
	public function test_hfcm_active_flag_and_empty_skip() {
		$importer       = new Fake_Hfcm_Importer();
		$importer->rows = [
			[
				'script_id' => 1,
				'name' => 'On',
				'snippet' => '<s>1</s>',
				'snippet_type' => 'html',
				'status' => 'active',
			],
			[
				'script_id' => 2,
				'name' => 'Off',
				'snippet' => '<s>2</s>',
				'snippet_type' => 'html',
				'status' => 'inactive',
			],
			[
				'script_id' => 3,
				'name' => 'Blank',
				'snippet' => '',
				'snippet_type' => 'html',
				'status' => 'active',
			],
		];

		$results = ( new Header_Footer_Code_Manager_Scanner( $importer ) )->scan();

		$this->assertCount( 2, $results );
		$this->assertTrue( $results[0]->is_active );
		$this->assertFalse( $results[1]->is_active );
	}

	/**
	 * Insert PHP Code Snippet rows map `status` (int) to `is_active`, fall back to a generated
	 * "Insert PHP #N" name when `title` is empty, and inherit the scanner's elevated 'high' risk
	 * level because the imported code executes PHP.
	 */
	public function test_ipcs_active_flag_name_fallback_and_risk() {
		$importer       = new Fake_Ipcs_Importer();
		$importer->rows = [
			(object) [
				'id' => 5,
				'title' => 'Custom Hook',
				'content' => '<?php echo 1;',
				'status' => 1,
			],
			(object) [
				'id' => 6,
				'title' => '',
				'content' => '<?php echo 2;',
				'status' => 0,
			],
		];

		$scanner = new Insert_PHP_Code_Snippet_Scanner( $importer );
		$results = $scanner->scan();

		$this->assertSame( 'high', $scanner->get_risk_level() );
		$this->assertTrue( $results[0]->is_active );
		$this->assertFalse( $results[1]->is_active );
		$this->assertSame( 'Insert PHP #6', $results[1]->name );
	}

	/**
	 * When the source plugin is not active, {@see DB_Scanner_Adapter::is_available()} returns
	 * false and `scan()` short-circuits to an empty array without hitting the database.
	 */
	public function test_adapter_returns_empty_when_unavailable() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$hfcm = new Header_Footer_Code_Manager_Scanner();

		$this->assertFalse( $hfcm->is_available() );
		$this->assertSame( [], $hfcm->scan() );
	}
}
