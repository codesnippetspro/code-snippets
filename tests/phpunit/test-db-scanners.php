<?php

namespace Code_Snippets\Tests;

use Code_Snippets\UnifiedSnippets\Scanners\Header_Footer_Code_Manager_Scanner;
use Code_Snippets\UnifiedSnippets\Scanners\Insert_Headers_And_Footers_Scanner;
use Code_Snippets\UnifiedSnippets\Scanners\Insert_PHP_Code_Snippet_Scanner;

require_once __DIR__ . '/fakes/Fake_Ihaf_Importer.php';
require_once __DIR__ . '/fakes/Fake_Hfcm_Importer.php';
require_once __DIR__ . '/fakes/Fake_Ipcs_Importer.php';

/**
 * Tests for the DB-aware scanner adapters.
 *
 * @group unified-snippets
 */
class DB_Scanners_Test extends TestCase {

	/**
	 * WPCode adapter maps supported code types and skips unsupported ones.
	 */
	public function test_wpcode_adapter_maps_supported_types() {
		$importer       = new Fake_Ihaf_Importer();
		$importer->rows = [
			[
				'code'       => "console.log('hi');",
				'code_type'  => 'js',
				'table_data' => [
					'id'    => 7,
					'title' => 'Hello JS',
				],
			],
			[
				'code'       => '// plain php',
				'code_type'  => 'php',
				'table_data' => [
					'id'    => 8,
					'title' => 'PHP Helper',
				],
			],
			[
				'code'       => '<x>',
				'code_type'  => 'text',
				'table_data' => [
					'id'    => 9,
					'title' => 'Unsupported',
				],
			],
			[
				'code'       => '   ',
				'code_type'  => 'php',
				'table_data' => [
					'id'    => 10,
					'title' => 'Empty',
				],
			],
		];

		$scanner = new Insert_Headers_And_Footers_Scanner( $importer );

		$this->assertTrue( $scanner->is_available() );
		$this->assertSame( 'wpcode', $scanner->get_id() );

		$results = $scanner->scan();
		$this->assertCount( 2, $results );

		$first = $results[0];
		$this->assertSame( 'Hello JS', $first->name );
		$this->assertSame( 'js', $first->type );
		$this->assertSame( 'plugin', $first->source_type );
		$this->assertSame( 'db://wpcode_snippets/7', $first->source_path );
		$this->assertTrue( $first->is_active );
		$this->assertSame( 'wpcode', $first->scanner_id );

		$this->assertSame( 'php', $results[1]->type );
	}

	/**
	 * HFCM adapter reads `snippet`/`script_id`/`status` and builds a proper URI.
	 */
	public function test_hfcm_adapter_maps_rows_and_active_flag() {
		$importer       = new Fake_Hfcm_Importer();
		$importer->rows = [
			[
				'script_id'    => 42,
				'name'         => 'Analytics Header',
				'snippet'      => '<script>analytics();</script>',
				'snippet_type' => 'html',
				'status'       => 'active',
			],
			[
				'script_id'    => 43,
				'name'         => 'Disabled Pixel',
				'snippet'      => '<script>pixel();</script>',
				'snippet_type' => 'html',
				'status'       => 'inactive',
			],
			[
				'script_id'    => 44,
				'name'         => 'Empty',
				'snippet'      => '',
				'snippet_type' => 'html',
				'status'       => 'active',
			],
		];

		$scanner = new Header_Footer_Code_Manager_Scanner( $importer );
		$results = $scanner->scan();

		$this->assertCount( 2, $results );

		$active = $results[0];
		$this->assertSame( 'Analytics Header', $active->name );
		$this->assertSame( 'html', $active->type );
		$this->assertSame( 'db://hfcm_scripts/42', $active->source_path );
		$this->assertTrue( $active->is_active );
		$this->assertSame( 'hfcm', $active->scanner_id );

		$this->assertFalse( $results[1]->is_active );
	}

	/**
	 * IPCS adapter treats status=1 as active and always emits PHP snippets.
	 */
	public function test_ipcs_adapter_maps_rows_and_risk() {
		$importer       = new Fake_Ipcs_Importer();
		$importer->rows = [
			(object) [
				'id'      => 5,
				'title'   => 'Custom Hook',
				'content' => "<?php add_action( 'init', '__return_true' );",
				'status'  => 1,
			],
			(object) [
				'id'      => 6,
				'title'   => '',
				'content' => '<?php echo 1;',
				'status'  => 0,
			],
		];

		$scanner = new Insert_PHP_Code_Snippet_Scanner( $importer );

		$this->assertSame( 'high', $scanner->get_risk_level() );

		$results = $scanner->scan();
		$this->assertCount( 2, $results );

		$this->assertSame( 'Custom Hook', $results[0]->name );
		$this->assertSame( 'php', $results[0]->type );
		$this->assertSame( 'db://xyz_ips_short_code/5', $results[0]->source_path );
		$this->assertTrue( $results[0]->is_active );
		$this->assertSame( 'high', $results[0]->risk_level );

		$this->assertSame( 'Insert PHP #6', $results[1]->name );
		$this->assertFalse( $results[1]->is_active );
	}

	/**
	 * Adapters short-circuit when the source plugin is inactive.
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
