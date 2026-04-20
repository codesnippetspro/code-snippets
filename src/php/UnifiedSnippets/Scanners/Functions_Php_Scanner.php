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

		try {
			$tokens = token_get_all( $code, TOKEN_PARSE );
		} catch ( ParseError $e ) {
			return [];
		}

		$lines    = explode( "\n", $code );
		$snippets = [];
		$depth    = 0;

		for ( $i = 0, $n = count( $tokens ); $i < $n; $i++ ) {
			$token = $tokens[ $i ];

			if ( is_string( $token ) ) {
				if ( '{' === $token ) {
					++$depth;
				} elseif ( '}' === $token ) {
					$depth = max( 0, $depth - 1 );
				}
				continue;
			}

			if ( 0 !== $depth ) {
				continue;
			}

			if ( ! in_array( $token[0], [ T_FUNCTION, T_CLASS, T_TRAIT, T_INTERFACE ], true ) ) {
				continue;
			}

			$symbol = $this->extract_symbol( $tokens, $i, $lines );

			if ( null === $symbol ) {
				continue;
			}

			$snippets[] = $this->build_snippet(
				[
					'name'        => $symbol['name'],
					'code'        => $symbol['code'],
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
	 * Extract a single top-level symbol starting at the given token index.
	 *
	 * Advances $i past the symbol's closing brace (or semicolon).
	 *
	 * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens Full token stream.
	 * @param int                                                 $i      Current index (by reference).
	 * @param string[]                                            $lines  Original source split by newline.
	 *
	 * @return array{name: string, code: string, line_start: int, line_end: int}|null
	 */
	private function extract_symbol( array $tokens, int &$i, array $lines ): ?array {
		$start_token = $tokens[ $i ];
		$line_start  = $start_token[2];
		$type_id     = $start_token[0];
		$n           = count( $tokens );
		$name        = '';

		for ( $j = $i + 1; $j < $n; $j++ ) {
			$inner = $tokens[ $j ];

			if ( is_string( $inner ) ) {
				// Anonymous function definitions never reach here at top level without a T_STRING.
				if ( '(' === $inner || ';' === $inner || '{' === $inner ) {
					break;
				}
				continue;
			}

			if ( T_STRING === $inner[0] ) {
				$name = $inner[1];
				break;
			}
		}

		if ( '' === $name ) {
			return null;
		}

		$depth      = 0;
		$seen_brace = false;
		$line_end   = $line_start;

		for ( $j = $i + 1; $j < $n; $j++ ) {
			$inner = $tokens[ $j ];

			if ( is_string( $inner ) ) {
				if ( '{' === $inner ) {
					++$depth;
					$seen_brace = true;
				} elseif ( '}' === $inner ) {
					--$depth;
					if ( 0 === $depth && $seen_brace ) {
						$line_end = $this->token_line( $tokens, $j );
						$i        = $j;
						break;
					}
				} elseif ( ';' === $inner && ! $seen_brace && T_FUNCTION !== $type_id ) {
					$line_end = $this->token_line( $tokens, $j );
					$i        = $j;
					break;
				}
			}
		}

		$snippet_lines = array_slice( $lines, $line_start - 1, ( $line_end - $line_start ) + 1 );

		return [
			'name'       => $name,
			'code'       => implode( "\n", $snippet_lines ),
			'line_start' => $line_start,
			'line_end'   => $line_end,
		];
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
