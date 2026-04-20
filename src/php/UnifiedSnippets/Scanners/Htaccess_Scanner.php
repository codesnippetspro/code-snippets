<?php

namespace Code_Snippets\UnifiedSnippets\Scanners;

use Code_Snippets\UnifiedSnippets\Filesystem_Reader;
use Code_Snippets\UnifiedSnippets\Model\Discovered_Snippet;
use Code_Snippets\UnifiedSnippets\Scanner_Base;

/**
 * Scans the site's .htaccess file, splitting it into labeled sections and classifying
 * each section by its convertibility to a PHP snippet.
 *
 * Categories stored in `import_notes` as a `[category] reason` prefix so Phase 2 UI
 * can render color-coded badges without further schema changes:
 *   - `[core]`        WordPress core rewrite block. Never modify.
 *   - `[server-only]` Apache-level directives with no PHP equivalent.
 *   - `[convertible]` Directives that can be re-expressed as WordPress hooks.
 *
 * @package Code_Snippets
 */
class Htaccess_Scanner extends Scanner_Base {

	private const BEGIN_PATTERN        = '/^# BEGIN (.+)$/';
	private const END_PATTERN          = '/^# END (.+)$/';
	private const BEGIN_CUTOFF_PATTERN = '/^# BEGIN /';

	private const SERVER_ONLY_HIGH = [
		'php_value',
		'php_flag',
		'AuthType',
		'AuthUserFile',
		'<Files',
		'<FilesMatch',
		'<Directory',
	];

	private const SERVER_ONLY_MEDIUM = [
		'mod_deflate',
		'AddOutputFilterByType',
		'SetOutputFilter',
		'ExpiresByType',
	];

	private const CONVERTIBLE_DIRECTIVES = [
		'RewriteRule',
		'RewriteCond',
		'Redirect',
		'RedirectMatch',
		'Header set',
		'Header append',
		'Deny from',
		'Allow from',
		'Require',
	];

	/**
	 * Absolute path to the .htaccess file to scan.
	 *
	 * @var string
	 */
	private string $path;

	/**
	 * Class constructor.
	 *
	 * @param string|null $path Optional path override. Defaults to ABSPATH.'.htaccess'.
	 */
	public function __construct( ?string $path = null ) {
		$this->path = $path ?? ( defined( 'ABSPATH' ) ? ABSPATH . '.htaccess' : '.htaccess' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return 'htaccess';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( '.htaccess', 'code-snippets' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_available(): bool {
		return Filesystem_Reader::is_readable( $this->path );
	}

	/**
	 * {@inheritDoc}
	 */
	public function supports_import(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function scan(): array {
		$contents = Filesystem_Reader::get_contents( $this->path );

		if ( null === $contents || '' === $contents ) {
			return [];
		}

		$lines    = preg_split( "/\r\n|\n|\r/", $contents );
		$sections = $this->split_into_sections( $lines );

		$snippets = [];

		foreach ( $sections as $section ) {
			$snippets[] = $this->build_section_snippet( $section );
		}

		return $snippets;
	}

	/**
	 * Split the file lines into labeled BEGIN/END sections plus contiguous custom groups.
	 *
	 * Walks the file once. Each iteration is either:
	 *   - a BEGIN marker → consume through matching END (or orphan cutoff) and emit a labeled section
	 *   - anything else  → buffer as part of a pending custom section
	 *
	 * A custom section is only emitted if it contains at least one non-empty line.
	 *
	 * @param string[] $lines The file lines (without trailing newlines).
	 *
	 * @return array<int, array{marker: string, unmatched: bool, line_start: int, line_end: int, code: string}>
	 */
	private function split_into_sections( array $lines ): array {
		$sections = [];
		$custom   = $this->new_custom_buffer();
		$total    = count( $lines );

		for ( $i = 0; $i < $total; ) {
			if ( preg_match( self::BEGIN_PATTERN, $lines[ $i ], $matches ) ) {
				$this->flush_custom( $sections, $custom );

				$labeled  = $this->consume_labeled_section( $lines, $i, trim( $matches[1] ) );
				$sections[] = $labeled['section'];
				$i          = $labeled['next_index'];
				continue;
			}

			$this->append_to_custom( $custom, $lines[ $i ], $i );
			++$i;
		}

		$this->flush_custom( $sections, $custom );

		return $sections;
	}

	/**
	 * Consume a labeled BEGIN/END section starting at $start_idx.
	 *
	 * @param string[] $lines     All file lines.
	 * @param int      $start_idx Index of the BEGIN line.
	 * @param string   $marker    Marker name from the BEGIN line.
	 *
	 * @return array{
	 *     section: array{marker: string, unmatched: bool, line_start: int, line_end: int, code: string},
	 *     next_index: int
	 * }
	 */
	private function consume_labeled_section( array $lines, int $start_idx, string $marker ): array {
		[ $end_idx, $matched ] = $this->find_section_end( $lines, $start_idx, $marker );

		$section = [
			'marker'     => $marker,
			'unmatched'  => ! $matched,
			'line_start' => $start_idx + 1,
			'line_end'   => $end_idx + 1,
			'code'       => implode( "\n", array_slice( $lines, $start_idx, ( $end_idx - $start_idx ) + 1 ) ),
		];

		return [
			'section'    => $section,
			'next_index' => $end_idx + 1,
		];
	}

	/**
	 * Locate the closing line for a BEGIN marker.
	 *
	 * A section ends at its matching `# END $marker`. If a different `# BEGIN` appears
	 * first, the section is treated as unmatched and ends at the line before that BEGIN
	 * so the orphan does not swallow the next block. If neither is found, it runs to EOF.
	 *
	 * @param string[] $lines     All file lines.
	 * @param int      $start_idx Index of the BEGIN line.
	 * @param string   $marker    Marker name to match.
	 *
	 * @return array{0: int, 1: bool} [end index, matched?]
	 */
	private function find_section_end( array $lines, int $start_idx, string $marker ): array {
		$total   = count( $lines );
		$end_idx = $start_idx;

		for ( $j = $start_idx + 1; $j < $total; $j++ ) {
			if ( preg_match( self::END_PATTERN, $lines[ $j ], $em ) && trim( $em[1] ) === $marker ) {
				return [ $j, true ];
			}

			if ( preg_match( self::BEGIN_CUTOFF_PATTERN, $lines[ $j ] ) ) {
				return [ $j - 1, false ];
			}

			$end_idx = $j;
		}

		return [ $end_idx, false ];
	}

	/**
	 * Initial state for a pending custom (unlabeled) section.
	 *
	 * @return array{start: int|null, has_body: bool, buffer: string[]}
	 */
	private function new_custom_buffer(): array {
		return [
			'start'    => null,
			'has_body' => false,
			'buffer'   => [],
		];
	}

	/**
	 * Append a line to the in-progress custom buffer.
	 *
	 * @param array{start: int|null, has_body: bool, buffer: string[]} $custom Buffer state (by reference).
	 * @param string                                                   $line   Raw line content.
	 * @param int                                                      $idx    0-indexed line number.
	 */
	private function append_to_custom( array &$custom, string $line, int $idx ): void {
		if ( null === $custom['start'] ) {
			$custom['start'] = $idx + 1;
		}

		$custom['buffer'][] = $line;

		if ( '' !== trim( $line ) ) {
			$custom['has_body'] = true;
		}
	}

	/**
	 * Emit any pending custom section and reset the buffer.
	 *
	 * Custom sections with no non-empty lines are discarded.
	 *
	 * @param array<int, array{marker: string, unmatched: bool, line_start: int, line_end: int, code: string}> $sections Accumulated sections (by reference).
	 * @param array{start: int|null, has_body: bool, buffer: string[]}                                         $custom   Buffer state (by reference).
	 */
	private function flush_custom( array &$sections, array &$custom ): void {
		if ( null !== $custom['start'] && $custom['has_body'] ) {
			$sections[] = [
				'marker'     => '',
				'unmatched'  => false,
				'line_start' => $custom['start'],
				'line_end'   => $custom['start'] + count( $custom['buffer'] ) - 1,
				'code'       => implode( "\n", $custom['buffer'] ),
			];
		}

		$custom = $this->new_custom_buffer();
	}

	/**
	 * Build a Discovered_Snippet for a single parsed section.
	 *
	 * @param array{marker: string, unmatched: bool, line_start: int, line_end: int, code: string} $section Section data.
	 *
	 * @return Discovered_Snippet
	 */
	private function build_section_snippet( array $section ): Discovered_Snippet {
		$marker         = $section['marker'];
		$classification = $this->classify_section( $marker, $section['code'], $section['unmatched'] );
		$default_name   = '' === $marker ? __( 'Custom', 'code-snippets' ) : $marker;

		return $this->build_snippet(
			[
				'name'          => $default_name,
				'code'          => $section['code'],
				'type'          => 'config',
				'source_type'   => 'server',
				'source_name'   => $default_name,
				'source_path'   => $this->path,
				'line_start'    => $section['line_start'],
				'line_end'      => $section['line_end'],
				'is_active'     => true,
				'is_importable' => $classification['importable'],
				'risk_level'    => $classification['risk'],
				'import_notes'  => '[' . $classification['category'] . '] ' . $classification['note'],
			]
		);
	}

	/**
	 * Classify a section. First match wins, in this order:
	 *   1. unmatched BEGIN marker
	 *   2. WordPress core rewrite block
	 *   3. server-only directives (high risk, then medium risk)
	 *   4. convertible directives
	 *   5. fallthrough: unrecognized custom directive
	 *
	 * @param string $marker    Section marker name (empty for custom/unattributed).
	 * @param string $body      Section body text.
	 * @param bool   $unmatched Whether the BEGIN marker was not closed by a matching END.
	 *
	 * @return array{category: string, risk: string, importable: bool, note: string}
	 */
	private function classify_section( string $marker, string $body, bool $unmatched ): array {
		if ( $unmatched ) {
			return $this->classification(
				'server-only',
				'high',
				false,
				sprintf(
					/* translators: %s: section marker name */
					__( 'Unclosed BEGIN marker for "%s"; review .htaccess manually.', 'code-snippets' ),
					$marker
				)
			);
		}

		if ( 'WordPress' === $marker ) {
			return $this->classification(
				'core',
				'high',
				false,
				__( 'WordPress core rewrite block. Never edit manually.', 'code-snippets' )
			);
		}

		if ( $this->body_contains_any( $body, self::SERVER_ONLY_HIGH ) ) {
			return $this->classification(
				'server-only',
				'high',
				false,
				__( 'Server-level directive with no PHP equivalent.', 'code-snippets' )
			);
		}

		if ( $this->body_contains_any( $body, self::SERVER_ONLY_MEDIUM ) ) {
			return $this->classification(
				'server-only',
				'medium',
				false,
				__( 'Server-level performance directive with no PHP equivalent.', 'code-snippets' )
			);
		}

		if ( $this->body_contains_any( $body, self::CONVERTIBLE_DIRECTIVES ) ) {
			return $this->classification(
				'convertible',
				'low',
				true,
				__( 'Can be converted to a PHP snippet via WordPress hooks.', 'code-snippets' )
			);
		}

		return $this->classification(
			'convertible',
			'medium',
			false,
			__( 'Unrecognized directive; review manually before importing.', 'code-snippets' )
		);
	}

	/**
	 * Whether any of the given needles appear (case-insensitively) in the section body.
	 *
	 * @param string   $body    Section body text.
	 * @param string[] $needles Needles to search for.
	 *
	 * @return bool
	 */
	private function body_contains_any( string $body, array $needles ): bool {
		foreach ( $needles as $needle ) {
			if ( false !== stripos( $body, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build a classification result.
	 *
	 * @param string $category   Category key.
	 * @param string $risk       Risk level.
	 * @param bool   $importable Whether the section can be imported.
	 * @param string $note       Human-readable note.
	 *
	 * @return array{category: string, risk: string, importable: bool, note: string}
	 */
	private function classification( string $category, string $risk, bool $importable, string $note ): array {
		return [
			'category'   => $category,
			'risk'       => $risk,
			'importable' => $importable,
			'note'       => $note,
		];
	}
}
