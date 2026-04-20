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
	 * @param string[] $lines The file lines (without trailing newlines).
	 *
	 * @return array<int, array{marker: string, unmatched: bool, line_start: int, line_end: int, code: string}>
	 */
	private function split_into_sections( array $lines ): array {
		$sections          = [];
		$n                 = count( $lines );
		$i                 = 0;
		$custom_start      = null;
		$custom_has_body   = false;
		$custom_buffer     = [];

		$flush_custom = static function () use ( &$sections, &$custom_start, &$custom_buffer, &$custom_has_body ) {
			if ( null !== $custom_start && $custom_has_body ) {
				$sections[] = [
					'marker'     => '',
					'unmatched'  => false,
					'line_start' => $custom_start,
					'line_end'   => $custom_start + count( $custom_buffer ) - 1,
					'code'       => implode( "\n", $custom_buffer ),
				];
			}
			$custom_start    = null;
			$custom_buffer   = [];
			$custom_has_body = false;
		};

		while ( $i < $n ) {
			$line = $lines[ $i ];

			if ( preg_match( '/^# BEGIN (.+)$/', $line, $matches ) ) {
				$flush_custom();

				$marker    = trim( $matches[1] );
				$start_idx = $i;
				$end_idx   = $i;
				$matched   = false;

				for ( $j = $i + 1; $j < $n; $j++ ) {
					if ( preg_match( '/^# END (.+)$/', $lines[ $j ], $em ) && trim( $em[1] ) === $marker ) {
						$end_idx = $j;
						$matched = true;
						break;
					}

					// If another BEGIN appears before our END, stop the search at the line before it
					// so the orphan section ends cleanly instead of swallowing the next block.
					if ( preg_match( '/^# BEGIN /', $lines[ $j ] ) ) {
						$end_idx = $j - 1;
						break;
					}

					$end_idx = $j;
				}

				$sections[] = [
					'marker'     => $marker,
					'unmatched'  => ! $matched,
					'line_start' => $start_idx + 1,
					'line_end'   => $end_idx + 1,
					'code'       => implode( "\n", array_slice( $lines, $start_idx, ( $end_idx - $start_idx ) + 1 ) ),
				];

				$i = $end_idx + 1;
				continue;
			}

			if ( null === $custom_start ) {
				$custom_start = $i + 1;
			}

			$custom_buffer[] = $line;

			if ( '' !== trim( $line ) ) {
				$custom_has_body = true;
			}

			++$i;
		}

		$flush_custom();

		return $sections;
	}

	/**
	 * Build a Discovered_Snippet for a single parsed section.
	 *
	 * @param array{marker: string, unmatched: bool, line_start: int, line_end: int, code: string} $section Section data.
	 *
	 * @return Discovered_Snippet
	 */
	private function build_section_snippet( array $section ): Discovered_Snippet {
		$marker          = $section['marker'];
		$classification  = $this->classify_section( $marker, $section['code'], ! empty( $section['unmatched'] ) );
		$default_name    = '' === $marker ? __( 'Custom', 'code-snippets' ) : $marker;

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
	 * Classify a section by marker and directive content.
	 *
	 * @param string $marker    Section marker name (empty for custom/unattributed).
	 * @param string $body      Section body text.
	 * @param bool   $unmatched Whether the BEGIN marker was not closed by a matching END.
	 *
	 * @return array{category: string, risk: string, importable: bool, note: string}
	 */
	private function classify_section( string $marker, string $body, bool $unmatched = false ): array {
		if ( $unmatched ) {
			return [
				'category'   => 'server-only',
				'risk'       => 'high',
				'importable' => false,
				'note'       => sprintf(
					/* translators: %s: section marker name */
					__( 'Unclosed BEGIN marker for "%s"; review .htaccess manually.', 'code-snippets' ),
					$marker
				),
			];
		}

		if ( 'WordPress' === $marker ) {
			return [
				'category'   => 'core',
				'risk'       => 'high',
				'importable' => false,
				'note'       => __( 'WordPress core rewrite block. Never edit manually.', 'code-snippets' ),
			];
		}

		foreach ( self::SERVER_ONLY_HIGH as $needle ) {
			if ( false !== stripos( $body, $needle ) ) {
				return [
					'category'   => 'server-only',
					'risk'       => 'high',
					'importable' => false,
					'note'       => __( 'Server-level directive with no PHP equivalent.', 'code-snippets' ),
				];
			}
		}

		foreach ( self::SERVER_ONLY_MEDIUM as $needle ) {
			if ( false !== stripos( $body, $needle ) ) {
				return [
					'category'   => 'server-only',
					'risk'       => 'medium',
					'importable' => false,
					'note'       => __( 'Server-level performance directive with no PHP equivalent.', 'code-snippets' ),
				];
			}
		}

		foreach ( self::CONVERTIBLE_DIRECTIVES as $needle ) {
			if ( false !== stripos( $body, $needle ) ) {
				return [
					'category'   => 'convertible',
					'risk'       => 'low',
					'importable' => true,
					'note'       => __( 'Can be converted to a PHP snippet via WordPress hooks.', 'code-snippets' ),
				];
			}
		}

		return [
			'category'   => 'convertible',
			'risk'       => 'medium',
			'importable' => false,
			'note'       => __( 'Unrecognized directive; review manually before importing.', 'code-snippets' ),
		];
	}
}
