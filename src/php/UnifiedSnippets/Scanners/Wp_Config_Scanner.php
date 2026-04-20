<?php

namespace Code_Snippets\UnifiedSnippets\Scanners;

use Code_Snippets\UnifiedSnippets\Filesystem_Reader;
use Code_Snippets\UnifiedSnippets\Scanner_Base;

/**
 * Scans wp-config.php for user-added directives by diffing against wp-config-sample.php.
 *
 * User-added lines are reported as read-only Discovered_Snippet blocks because wp-config
 * constants run before WordPress loads and cannot be replaced by a managed snippet.
 *
 * @package Code_Snippets
 */
class Wp_Config_Scanner extends Scanner_Base {

	/**
	 * Regex patterns that identify lines whose values always differ from the sample
	 * (DB credentials, salts/keys, bootstrap require) and therefore should not be
	 * treated as user additions.
	 */
	private const NOISE_PATTERNS = [
		"/^define\(\s*'DB_(NAME|USER|PASSWORD|HOST|CHARSET|COLLATE)'/i",
		"/^define\(\s*'(AUTH|SECURE_AUTH|LOGGED_IN|NONCE)_KEY'/i",
		"/^define\(\s*'(AUTH|SECURE_AUTH|LOGGED_IN|NONCE)_SALT'/i",
		'/^\$table_prefix\s*=/i',
		"/^require_once\s+ABSPATH\s*\.\s*['\"]wp-settings\.php['\"]/i",
	];

	/**
	 * Absolute path to wp-config.php.
	 *
	 * @var string
	 */
	private string $config_path;

	/**
	 * Absolute path to wp-config-sample.php.
	 *
	 * @var string
	 */
	private string $sample_path;

	/**
	 * Class constructor.
	 *
	 * @param string|null $config_path Optional override for wp-config.php.
	 * @param string|null $sample_path Optional override for wp-config-sample.php.
	 */
	public function __construct( ?string $config_path = null, ?string $sample_path = null ) {
		$abspath           = defined( 'ABSPATH' ) ? ABSPATH : '';
		$this->config_path = $config_path ?? $abspath . 'wp-config.php';
		$this->sample_path = $sample_path ?? $abspath . 'wp-config-sample.php';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return 'wp-config';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( 'wp-config.php', 'code-snippets' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_available(): bool {
		return Filesystem_Reader::is_readable( $this->config_path )
			&& Filesystem_Reader::is_readable( $this->sample_path );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_risk_level(): string {
		return 'high';
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
		$config = Filesystem_Reader::get_contents( $this->config_path );
		$sample = Filesystem_Reader::get_contents( $this->sample_path );

		if ( null === $config || null === $sample ) {
			return [];
		}

		$sample_lines = $this->build_sample_set( $sample );
		$config_lines = preg_split( "/\r\n|\n|\r/", $config );

		$blocks = [];
		$buffer = [];
		$start  = 0;

		$flush = static function () use ( &$blocks, &$buffer, &$start ) {
			if ( ! empty( $buffer ) ) {
				$blocks[] = [
					'line_start' => $start,
					'line_end'   => $start + count( $buffer ) - 1,
					'code'       => implode( "\n", $buffer ),
				];
				$buffer = [];
				$start  = 0;
			}
		};

		foreach ( $config_lines as $idx => $line ) {
			if ( $this->is_user_addition( $line, $sample_lines ) ) {
				if ( empty( $buffer ) ) {
					$start = $idx + 1;
				}
				$buffer[] = $line;
			} else {
				$flush();
			}
		}

		$flush();

		$snippets = [];
		$note     = __( 'wp-config constants run before WordPress loads and cannot be replaced by a snippet.', 'code-snippets' );

		foreach ( $blocks as $index => $block ) {
			$snippets[] = $this->build_snippet(
				[
					'name'          => sprintf(
						/* translators: %d: block index */
						__( 'wp-config.php addition #%d', 'code-snippets' ),
						$index + 1
					),
					'code'          => $block['code'],
					'type'          => 'config',
					'source_type'   => 'core',
					'source_name'   => 'wp-config.php',
					'source_path'   => $this->config_path,
					'line_start'    => $block['line_start'],
					'line_end'      => $block['line_end'],
					'is_active'     => true,
					'is_importable' => false,
					'risk_level'    => 'high',
					'import_notes'  => $note,
				]
			);
		}

		return $snippets;
	}

	/**
	 * Build a set of trimmed sample lines for quick membership checks.
	 *
	 * @param string $sample Contents of wp-config-sample.php.
	 *
	 * @return array<string, true> Lookup keyed by trimmed line.
	 */
	private function build_sample_set( string $sample ): array {
		$set = [];

		foreach ( preg_split( "/\r\n|\n|\r/", $sample ) as $line ) {
			$trimmed = trim( $line );
			if ( '' !== $trimmed ) {
				$set[ $trimmed ] = true;
			}
		}

		return $set;
	}

	/**
	 * Determine whether a wp-config line represents a user addition worth reporting.
	 *
	 * @param string              $line         Raw line from wp-config.php.
	 * @param array<string, true> $sample_lines Lookup of sample lines.
	 *
	 * @return bool
	 */
	private function is_user_addition( string $line, array $sample_lines ): bool {
		$trimmed = trim( $line );

		if ( '' === $trimmed ) {
			return false;
		}

		if ( '<?php' === $trimmed || '?>' === $trimmed ) {
			return false;
		}

		if ( 0 === strpos( $trimmed, '//' ) || 0 === strpos( $trimmed, '#' ) || 0 === strpos( $trimmed, '*' ) || 0 === strpos( $trimmed, '/*' ) ) {
			return false;
		}

		if ( isset( $sample_lines[ $trimmed ] ) ) {
			return false;
		}

		foreach ( self::NOISE_PATTERNS as $pattern ) {
			if ( preg_match( $pattern, $trimmed ) ) {
				return false;
			}
		}

		return true;
	}
}
