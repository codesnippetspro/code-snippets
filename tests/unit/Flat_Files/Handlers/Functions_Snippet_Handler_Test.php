<?php

namespace Code_Snippets\Flat_Files\Handlers;

use Code_Snippets\UnitTestCase;

/**
 * Tests for wrapping functions snippets in a direct-access guard.
 *
 * @group flat-files
 */
class Functions_Snippet_Handler_Test extends UnitTestCase {

	/**
	 * Handler under test.
	 *
	 * @var Functions_Snippet_Handler
	 */
	private $handler;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->handler = new Functions_Snippet_Handler();
	}

	/**
	 * Assert that wrapped code is valid PHP.
	 *
	 * A namespace placed after another statement is a compile error, not a
	 * parse error, so neither token_get_all() nor include can be used to detect
	 * it from inside the test process: including the file kills the process
	 * outright. The check is delegated to a subprocess instead.
	 *
	 * @param string $wrapped Wrapped snippet code.
	 *
	 * @return void
	 */
	private function assert_valid_php( string $wrapped ): void {
		if ( ! function_exists( 'exec' ) ) {
			$this->markTestSkipped( 'exec() is unavailable, cannot lint generated code.' );
		}

		$file = tempnam( sys_get_temp_dir(), 'cs-flat-file-' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Linting a generated file requires a real file on disk.
		file_put_contents( $file, $wrapped );

		$output = [];
		$status = 0;
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- A subprocess is the only way to detect a compile error without killing the test run.
		exec( escapeshellcmd( PHP_BINARY ) . ' -l ' . escapeshellarg( $file ) . ' 2>&1', $output, $status );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Removing a temp file created by this test.
		unlink( $file );

		$this->assertSame(
			0,
			$status,
			"Generated flat file is not valid PHP:\n" . implode( "\n", $output ) . "\n\n--- generated ---\n" . $wrapped
		);
	}

	/**
	 * Snippets with no prologue keep the guard directly below the opening tag.
	 *
	 * @return void
	 */
	public function test_guard_is_added_at_the_top_of_an_ordinary_snippet(): void {
		$wrapped = $this->handler->wrap_code( "function my_snippet() {}\n" );

		$this->assertStringStartsWith( "<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }", $wrapped );
		$this->assertStringContainsString( 'function my_snippet() {}', $wrapped );
		$this->assert_valid_php( $wrapped );
	}

	/**
	 * A namespaced snippet keeps its declaration first.
	 *
	 * Prepending the guard used to push the namespace declaration down the
	 * file, which is a fatal error and left the whole site unable to load.
	 *
	 * @return void
	 */
	public function test_guard_is_added_below_a_namespace_declaration(): void {
		$wrapped = $this->handler->wrap_code( "namespace My\\Plugin;\n\nfunction my_snippet() {}\n" );

		$this->assertLessThan(
			strpos( $wrapped, "if ( ! defined( 'ABSPATH' ) )" ),
			strpos( $wrapped, 'namespace My\\Plugin;' ),
			'The namespace declaration must still come before the guard.'
		);
		$this->assert_valid_php( $wrapped );
	}

	/**
	 * A strict_types declaration must stay the very first statement.
	 *
	 * @return void
	 */
	public function test_guard_is_added_below_a_declare_statement(): void {
		$wrapped = $this->handler->wrap_code( "declare(strict_types=1);\n\nfunction my_snippet() {}\n" );

		$this->assertLessThan(
			strpos( $wrapped, "if ( ! defined( 'ABSPATH' ) )" ),
			strpos( $wrapped, 'declare(strict_types=1);' ),
			'The declare statement must still come before the guard.'
		);
		$this->assert_valid_php( $wrapped );
	}

	/**
	 * A declare statement followed by a namespace is handled as one prologue.
	 *
	 * @return void
	 */
	public function test_guard_is_added_below_both_declare_and_namespace(): void {
		$wrapped = $this->handler->wrap_code(
			"declare(strict_types=1);\n\nnamespace My\\Plugin;\n\nfunction my_snippet() {}\n"
		);

		$this->assertLessThan(
			strpos( $wrapped, "if ( ! defined( 'ABSPATH' ) )" ),
			strpos( $wrapped, 'namespace My\\Plugin;' ),
			'The namespace declaration must still come before the guard.'
		);
		$this->assert_valid_php( $wrapped );
	}

	/**
	 * Braced namespaces take the guard inside the block.
	 *
	 * No code may exist outside `namespace {}` blocks, so the guard cannot be
	 * placed above or below the declaration.
	 *
	 * @return void
	 */
	public function test_guard_is_added_inside_a_braced_namespace(): void {
		$wrapped = $this->handler->wrap_code( "namespace My\\Plugin {\n\tfunction my_snippet() {}\n}\n" );

		$this->assertLessThan(
			strpos( $wrapped, "if ( ! defined( 'ABSPATH' ) )" ),
			strpos( $wrapped, 'namespace My\\Plugin {' ),
			'The guard must sit inside the namespace block.'
		);
		$this->assert_valid_php( $wrapped );
	}

	/**
	 * Leading comments do not hide the namespace declaration.
	 *
	 * @return void
	 */
	public function test_guard_is_added_below_a_namespace_preceded_by_comments(): void {
		$wrapped = $this->handler->wrap_code(
			"/**\n * Doc comment.\n */\n\n// A line comment.\nnamespace My\\Plugin;\n\nfunction my_snippet() {}\n"
		);

		$this->assertLessThan(
			strpos( $wrapped, "if ( ! defined( 'ABSPATH' ) )" ),
			strpos( $wrapped, 'namespace My\\Plugin;' ),
			'The namespace declaration must still come before the guard.'
		);
		$this->assert_valid_php( $wrapped );
	}

	/**
	 * The namespace operator is not mistaken for a declaration.
	 *
	 * @return void
	 */
	public function test_namespace_operator_is_not_treated_as_a_declaration(): void {
		$wrapped = $this->handler->wrap_code( "namespace\\my_function();\n" );

		$this->assertStringStartsWith( "<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }", $wrapped );
		$this->assert_valid_php( $wrapped );
	}

	/**
	 * The guard is still present for every supported prologue.
	 *
	 * @return void
	 */
	public function test_every_snippet_shape_keeps_the_direct_access_guard(): void {
		$shapes = [
			'plain'      => "function my_snippet() {}\n",
			'namespace'  => "namespace My\\Plugin;\n\nfunction my_snippet() {}\n",
			'declare'    => "declare(strict_types=1);\n\nfunction my_snippet() {}\n",
			'both'       => "declare(strict_types=1);\n\nnamespace My\\Plugin;\n\nfunction my_snippet() {}\n",
			'braced'     => "namespace My\\Plugin {\n\tfunction my_snippet() {}\n}\n",
		];

		foreach ( $shapes as $label => $code ) {
			$this->assertStringContainsString(
				"if ( ! defined( 'ABSPATH' ) ) { return; }",
				$this->handler->wrap_code( $code ),
				"The $label snippet lost its direct-access guard."
			);
		}
	}
}
