<?php

namespace Code_Snippets\Flat_Files\Handlers;

use Code_Snippets\Flat_Files\Interfaces\Snippet_Type_Handler;

/**
 * Snippet type handler for functions snippets.
 */
class Functions_Snippet_Handler implements Snippet_Type_Handler {

	/**
	 * The guard that prevents a flat file from being requested directly.
	 */
	private const DIRECT_ACCESS_GUARD = "if ( ! defined( 'ABSPATH' ) ) { return; }";

	/**
	 * Set 'php' as the file extension for functions snippets, so they can be directly loaded.
	 *
	 * @return string
	 */
	public function get_file_extension(): string {
		return 'php';
	}

	/**
	 * Store content snippets in a 'php' directory.
	 *
	 * @return string
	 */
	public function get_dir_name(): string {
		return 'php';
	}

	/**
	 * Wrap functions snippets by adding a header that disallows direct access.
	 *
	 * The guard cannot simply be prepended. PHP requires `declare` and
	 * `namespace` to come before any other statement, so a snippet opening with
	 * either used to fatal on load with "Namespace declaration statement has to
	 * be the very first statement" — taking down every page of the site, with
	 * nothing written to the error log. The guard is inserted after that
	 * prologue instead, and inside the braces when a namespace uses block
	 * syntax, since no code may sit outside `namespace {}` blocks.
	 *
	 * @param string $code Snippet PHP code.
	 *
	 * @return string Content snippet code with header prepended.
	 */
	public function wrap_code( string $code ): string {
		$offset = $this->find_prologue_end( $code );

		if ( 0 === $offset ) {
			return "<?php\n\n" . self::DIRECT_ACCESS_GUARD . "\n\n" . $code;
		}

		return "<?php\n\n" . rtrim( substr( $code, 0, $offset ) ) .
			"\n\n" . self::DIRECT_ACCESS_GUARD . "\n\n" .
			ltrim( substr( $code, $offset ), "\n" );
	}

	/**
	 * Find the offset in the snippet code at which the guard may be inserted.
	 *
	 * Everything up to that offset is the statement prologue that PHP insists
	 * on seeing first: any number of `declare` statements, optionally followed
	 * by a namespace declaration. A braced namespace reports the offset just
	 * inside the opening brace rather than after the statement.
	 *
	 * @param string $code Snippet PHP code, stored without an opening tag.
	 *
	 * @return int Offset into `$code`, or zero to insert at the top.
	 */
	private function find_prologue_end( string $code ): int {
		$open_tag = "<?php\n";
		$tokens = token_get_all( $open_tag . $code );
		$skipped = [ T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ];
		$offset = 0;
		$end = 0;
		$in_statement = false;

		foreach ( $tokens as $index => $token ) {
			$offset += strlen( is_array( $token ) ? $token[1] : $token );

			if ( $in_statement ) {
				if ( '{' === $token ) {
					// Block syntax: the guard belongs inside the braces.
					$end = $offset;
					break;
				}

				if ( ';' === $token ) {
					$end = $offset;
					$in_statement = false;
				}

				continue;
			}

			$id = is_array( $token ) ? $token[0] : null;

			if ( in_array( $id, $skipped, true ) ) {
				continue;
			}

			if ( T_DECLARE === $id || $this->is_namespace_declaration( $tokens, $index ) ) {
				$in_statement = true;
				continue;
			}

			break;
		}

		return max( 0, $end - strlen( $open_tag ) );
	}

	/**
	 * Check whether a token opens a namespace declaration.
	 *
	 * `namespace\my_function()` uses the same keyword as an operator, and must
	 * not be mistaken for a declaration.
	 *
	 * @param array<int, array{0: int, 1: string}|string> $tokens Token list.
	 * @param int                                         $index  Token to test.
	 *
	 * @return bool
	 */
	private function is_namespace_declaration( array $tokens, int $index ): bool {
		if ( ! is_array( $tokens[ $index ] ) || T_NAMESPACE !== $tokens[ $index ][0] ) {
			return false;
		}

		$count = count( $tokens );

		for ( $next = $index + 1; $next < $count; $next++ ) {
			if ( is_array( $tokens[ $next ] ) && T_WHITESPACE === $tokens[ $next ][0] ) {
				continue;
			}

			return ! is_array( $tokens[ $next ] ) || T_NS_SEPARATOR !== $tokens[ $next ][0];
		}

		return false;
	}
}
