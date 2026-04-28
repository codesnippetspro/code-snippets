<?php

namespace Code_Snippets\UnifiedSnippets\Scanners;

use Code_Snippets\UnifiedSnippets\Filesystem_Reader;
use Code_Snippets\UnifiedSnippets\Model\Discovered_Snippet;
use Code_Snippets\UnifiedSnippets\Scanner_Base;
use ParseError;

/**
 * Scans the active theme (and child theme) functions.php files for top-level
 * function, class, trait, and interface definitions.
 *
 * @package Code_Snippets
 */
class Functions_Php_Scanner extends Scanner_Base {

	/**
	 * Optional override paths keyed by source_type ('theme', 'child-theme').
	 *
	 * @var array<string, array{path: string, name: string}>
	 */
	private array $path_overrides;

	private const DEFINITION_TOKENS = [ T_FUNCTION, T_CLASS, T_TRAIT, T_INTERFACE ];

	/**
	 * Class constructor.
	 *
	 * @param array<string, array{path: string, name: string}> $path_overrides Optional path overrides for testing.
	 */
	public function __construct( array $path_overrides = [] ) {
		$this->path_overrides = $path_overrides;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return 'functions-php';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( 'Theme functions.php', 'code-snippets' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_available(): bool {
		foreach ( $this->resolve_targets() as $target ) {
			if ( Filesystem_Reader::is_readable( $target['path'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_risk_level(): string {
		return 'medium';
	}

	/**
	 * {@inheritDoc}
	 */
	public function scan(): array {
		$snippets = [];

		foreach ( $this->resolve_targets() as $source_type => $target ) {
			if ( ! Filesystem_Reader::is_readable( $target['path'] ) ) {
				continue;
			}

			$snippets = array_merge(
				$snippets,
				$this->scan_file( $target['path'], $source_type, $target['name'] )
			);
		}

		return $snippets;
	}

	/**
	 * Resolve parent and child theme functions.php targets.
	 *
	 * @return array<string, array{path: string, name: string}>
	 */
	private function resolve_targets(): array {
		if ( $this->path_overrides ) {
			return $this->path_overrides;
		}

		$targets = [];

		if ( function_exists( 'get_template_directory' ) ) {
			$parent_path = wp_normalize_path( get_template_directory() . '/functions.php' );
			$parent_name = function_exists( 'wp_get_theme' ) ? wp_get_theme( get_template() )->get( 'Name' ) : 'Parent theme';

			$targets['theme'] = [
				'path' => $parent_path,
				'name' => $parent_name,
			];
		}

		if ( function_exists( 'get_stylesheet_directory' ) && get_stylesheet() !== get_template() ) {
			$child_path = wp_normalize_path( get_stylesheet_directory() . '/functions.php' );
			$child_name = wp_get_theme()->get( 'Name' );

			$targets['child-theme'] = [
				'path' => $child_path,
				'name' => $child_name,
			];
		}

		return $targets;
	}

	/**
	 * Scan a single functions.php file, extracting top-level symbols.
	 *
	 * @param string $path        Absolute file path.
	 * @param string $source_type 'theme' or 'child-theme'.
	 * @param string $source_name Human-readable theme name.
	 *
	 * @return Discovered_Snippet[]
	 */
	private function scan_file( string $path, string $source_type, string $source_name ): array {
		$code = Filesystem_Reader::get_contents( $path );

		if ( null === $code || '' === $code ) {
			return [];
		}

		$tokens = $this->tokenize( $code );

		if ( null === $tokens ) {
			return [];
		}

		$lines    = explode( "\n", $code );
		$snippets = [];

		foreach ( $this->find_top_level_symbols( $tokens ) as $symbol ) {
			$snippets[] = $this->build_snippet(
				[
					'name'        => $symbol['name'],
					'code'        => $this->slice_source( $lines, $symbol['line_start'], $symbol['line_end'] ),
					'type'        => 'php',
					'source_type' => $source_type,
					'source_name' => $source_name,
					'source_path' => $path,
					'line_start'  => $symbol['line_start'],
					'line_end'    => $symbol['line_end'],
					'is_active'   => true,
				]
			);
		}

		return $snippets;
	}

	/**
	 * Tokenize PHP source, returning null if the file cannot be parsed.
	 *
	 * TOKEN_PARSE (PHP 8.0+) makes token_get_all() throw ParseError on invalid source
	 * instead of silently returning a partial stream. On 7.4 we accept partial results.
	 *
	 * @param string $code Source code.
	 *
	 * @return array<int, array{0: int, 1: string, 2: int}|string>|null
	 */
	private function tokenize( string $code ): ?array {
		try {
			return PHP_VERSION_ID >= 80000
				? token_get_all( $code, TOKEN_PARSE )
				: token_get_all( $code );
		} catch ( ParseError $e ) {
			return null;
		}
	}

	/**
	 * Yield each top-level function/class/trait/interface definition as {name, line_start, line_end}.
	 *
	 * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens Token stream.
	 *
	 * @return \Generator<int, array{name: string, line_start: int, line_end: int}>
	 */
	private function find_top_level_symbols( array $tokens ): \Generator {
		$depth = 0;
		$count = count( $tokens );

		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];

			if ( '{' === $token ) {
				++$depth;
				continue;
			}

			if ( '}' === $token ) {
				$depth = max( 0, $depth - 1 );
				continue;
			}

			if ( 0 !== $depth || ! is_array( $token ) ) {
				continue;
			}

			if ( ! in_array( $token[0], self::DEFINITION_TOKENS, true ) ) {
				continue;
			}

			$name = $this->read_symbol_name( $tokens, $i );

			if ( '' === $name ) {
				continue;
			}

			$end_index = $this->find_symbol_end( $tokens, $i, $token[0] );

			yield [
				'name'       => $name,
				'line_start' => $token[2],
				'line_end'   => $this->token_line( $tokens, $end_index ),
			];

			$i = $end_index;
		}
	}

	/**
	 * Read the T_STRING name that follows a definition token (function foo, class Bar, etc.).
	 *
	 * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens Token stream.
	 * @param int                                                 $start  Index of the definition keyword.
	 *
	 * @return string Empty string if no name is present (e.g. an anonymous class).
	 */
	private function read_symbol_name( array $tokens, int $start ): string {
		for ( $i = $start + 1, $n = count( $tokens ); $i < $n; $i++ ) {
			$token = $tokens[ $i ];

			if ( is_array( $token ) && T_STRING === $token[0] ) {
				return $token[1];
			}

			// Reached the parameter list or body without seeing a name: it's anonymous.
			if ( '(' === $token || '{' === $token || ';' === $token ) {
				return '';
			}
		}

		return '';
	}

	/**
	 * Find the index of the token that closes a symbol's body (the matching `}` or, for
	 * interface/abstract declarations, the terminating `;`).
	 *
	 * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens  Token stream.
	 * @param int                                                 $start   Index of the definition keyword.
	 * @param int                                                 $type_id The definition token id (T_FUNCTION etc).
	 *
	 * @return int Index of the closing token. Falls back to the last token if unmatched.
	 */
	private function find_symbol_end( array $tokens, int $start, int $type_id ): int {
		$depth      = 0;
		$seen_brace = false;
		$count      = count( $tokens );

		for ( $i = $start + 1; $i < $count; $i++ ) {
			$token = $tokens[ $i ];

			if ( '{' === $token ) {
				++$depth;
				$seen_brace = true;
				continue;
			}

			if ( '}' === $token ) {
				--$depth;
				if ( $seen_brace && 0 === $depth ) {
					return $i;
				}
				continue;
			}

			// A `;` before any body closes declarations that have no body (e.g. abstract method).
			// Functions always have a body in a functions.php top-level context, so ignore `;` for them.
			if ( ';' === $token && ! $seen_brace && T_FUNCTION !== $type_id ) {
				return $i;
			}
		}

		return $count - 1;
	}

	/**
	 * Join a 1-indexed inclusive line range from an array of source lines.
	 *
	 * @param string[] $lines      Source lines.
	 * @param int      $line_start First line (1-indexed).
	 * @param int      $line_end   Last line (1-indexed, inclusive).
	 *
	 * @return string
	 */
	private function slice_source( array $lines, int $line_start, int $line_end ): string {
		return implode( "\n", array_slice( $lines, $line_start - 1, ( $line_end - $line_start ) + 1 ) );
	}

	/**
	 * Get the source line for a token index. For string tokens (which carry no line
	 * metadata), walk backwards to the nearest array token and add any newlines in
	 * its text so the result reflects the string token's actual source line.
	 *
	 * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens Full token stream.
	 * @param int                                                 $index  Token index.
	 *
	 * @return int
	 */
	private function token_line( array $tokens, int $index ): int {
		if ( is_array( $tokens[ $index ] ) ) {
			return $tokens[ $index ][2];
		}

		for ( $k = $index - 1; $k >= 0; $k-- ) {
			if ( is_array( $tokens[ $k ] ) ) {
				return $tokens[ $k ][2] + substr_count( $tokens[ $k ][1], "\n" );
			}
		}

		return 1;
	}
}
