<?php

namespace Code_Snippets\Tests;

use Code_Snippets\UnitTestCase;
use function Code_Snippets\normalize_snippet_code;

/**
 * Tests for stripping wrapper markup from pasted snippet code.
 *
 * @group snippets
 */
class Normalize_Snippet_Code_Test extends UnitTestCase {

	/**
	 * Wrapper markup is removed for each snippet type.
	 *
	 * @dataProvider wrapped_code_provider
	 *
	 * @param string $type     Snippet type.
	 * @param string $code     Code as pasted.
	 * @param string $expected Code as it should be stored.
	 *
	 * @return void
	 */
	public function test_wrapper_markup_is_removed( string $type, string $code, string $expected ): void {
		$this->assertSame( $expected, normalize_snippet_code( $code, $type ) );
	}

	/**
	 * Code shapes that arrive with wrapper markup.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public function wrapped_code_provider(): array {
		return [
			'php opening tag'            => [ 'php', "<?php\nreturn 1;", 'return 1;' ],
			'php open and close'         => [ 'php', "<?php\nreturn 1;\n?>", "return 1;\n" ],
			'php short tag'              => [ 'php', "<?\nreturn 1;", 'return 1;' ],
			'php tag with leading space' => [ 'php', "\n\n<?php\nreturn 1;", 'return 1;' ],
			'php tag on the same line'   => [ 'php', '<?php return 1;', ' return 1;' ],
			'css style tag'              => [ 'css', "<style>\n.a { color: red; }\n</style>", ".a { color: red; }\n" ],
			'css style with attributes'  => [ 'css', "<style type=\"text/css\">\n.a {}\n</style>", ".a {}\n" ],
			'js script tag'              => [ 'js', "<script>\nconsole.log( 1 );\n</script>", "console.log( 1 );\n" ],
			'js script with attributes'  => [ 'js', "<script type=\"module\">\nlet a = 1;\n</script>", "let a = 1;\n" ],
			'markdown fence around php'  => [ 'php', "```php\n<?php\nreturn 1;\n```", 'return 1;' ],
			'markdown fence around css'  => [ 'css', "```css\n.a {}\n```", '.a {}' ],
			'bare fence'                 => [ 'js', "```\nlet a = 1;\n```", 'let a = 1;' ],
		];
	}

	/**
	 * Code without wrapper markup is returned untouched.
	 *
	 * @dataProvider untouched_code_provider
	 *
	 * @param string $type Snippet type.
	 * @param string $code Code that should survive unchanged.
	 *
	 * @return void
	 */
	public function test_code_without_wrappers_is_untouched( string $type, string $code ): void {
		$this->assertSame( $code, normalize_snippet_code( $code, $type ) );
	}

	/**
	 * Code shapes that must not be altered.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function untouched_code_provider(): array {
		return [
			'plain php'                => [ 'php', "add_filter( 'the_content', 'cb' );" ],
			'plain css'                => [ 'css', '.a { color: red; }' ],
			'plain js'                 => [ 'js', 'console.log( 1 );' ],
			'php closing tag mid-code' => [ 'php', "if ( true ) { ?>\n<p>markup</p>\n<?php }" ],
			'style tag inside php'     => [ 'php', "echo '<style>.a{}</style>';" ],
			'script tag inside php'    => [ 'php', "echo '<script>x()</script>';" ],
			'style tag mid-css'        => [ 'css', ".a { content: '<style>'; }" ],
			'backticks inside code'    => [ 'js', 'const sql = `SELECT 1`;' ],
			'html type is left alone'  => [ 'html', "<style>\n.a {}\n</style>" ],
		];
	}

	/**
	 * A word starting with php is not mistaken for an opening tag.
	 *
	 * The previous expression matched `php` optionally and unanchored, so
	 * `<?phpinfo()` lost its first three letters and became `info()`.
	 *
	 * @return void
	 */
	public function test_phpinfo_is_not_truncated(): void {
		$this->assertSame( 'phpinfo();', normalize_snippet_code( '<?phpinfo();', 'php' ) );
	}

	/**
	 * Only the outermost wrapper is removed.
	 *
	 * @return void
	 */
	public function test_only_the_outer_wrapper_is_removed(): void {
		$this->assertSame(
			"echo '<?php';",
			normalize_snippet_code( "<?php\necho '<?php';", 'php' )
		);
	}

	/**
	 * An empty snippet stays empty.
	 *
	 * @return void
	 */
	public function test_empty_code_is_handled(): void {
		$this->assertSame( '', normalize_snippet_code( '', 'php' ) );
		$this->assertSame( '', normalize_snippet_code( '<?php', 'php' ) );
	}
}
