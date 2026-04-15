<?php

namespace Code_Snippets\UnifiedSnippets\Model;

use Code_Snippets\Model\Model;

/**
 * Represents a snippet discovered during a site scan.
 *
 * Discovered snippets are read-only references to code found elsewhere in the WordPress installation.
 * They only become managed snippets when explicitly imported.
 *
 * @package Code_Snippets
 *
 * @property string $hash          Deterministic hash of source + location for deduplication.
 * @property string $name          Human-readable name (auto-generated or parsed).
 * @property string $code          The discovered code content.
 * @property string $type          php, css, js, html, config, or mixed.
 * @property string $source_type   theme, child-theme, plugin, builder, core, server, customizer, or mu-plugin.
 * @property string $source_name   Human-readable source (e.g. "Astra", "Elementor", "functions.php").
 * @property string $source_path   File path, DB reference, or CPT identifier.
 * @property int    $line_start    Starting line in source file (0 for DB-stored code).
 * @property int    $line_end      Ending line in source file.
 * @property bool   $is_active     Whether the code is currently active/running.
 * @property bool   $is_importable Whether this snippet can be imported into Code Snippets.
 * @property string $import_notes  Explanation of import constraints.
 * @property string $risk_level    low, medium, or high.
 * @property string $checksum      Content hash for change detection between scans.
 * @property string $scan_date     ISO 8601 timestamp of when this was last scanned.
 * @property string $scanner_id    Which scanner found this.
 */
class Discovered_Snippet extends Model {

	private const VALID_TYPES = [ 'php', 'css', 'js', 'html', 'config', 'mixed' ];

	private const VALID_SOURCE_TYPES = [
		'theme', 'child-theme', 'plugin', 'builder',
		'core', 'server', 'customizer', 'mu-plugin',
	];

	private const VALID_RISK_LEVELS = [ 'low', 'medium', 'high' ];

	/**
	 * Default values for discovered snippet fields.
	 *
	 * @var array<string, mixed>
	 */
	protected static array $default_values = [
		'hash'          => '',
		'name'          => '',
		'code'          => '',
		'type'          => 'php',
		'source_type'   => '',
		'source_name'   => '',
		'source_path'   => '',
		'line_start'    => 0,
		'line_end'      => 0,
		'is_active'     => false,
		'is_importable' => true,
		'import_notes'  => '',
		'risk_level'    => 'low',
		'checksum'      => '',
		'scan_date'     => '',
		'scanner_id'    => '',
	];

	/**
	 * Field name aliases.
	 *
	 * @var array<string, string>
	 */
	protected static array $field_aliases = [
		'source' => 'source_name',
		'path'   => 'source_path',
		'risk'   => 'risk_level',
	];

	/**
	 * Prepare a value before it is stored.
	 *
	 * @param mixed  $value Value to prepare.
	 * @param string $field Field name.
	 *
	 * @return mixed Value in the correct format.
	 */
	protected function prepare_field( $value, string $field ) {
		switch ( $field ) {
			case 'line_start':
			case 'line_end':
				return max( 0, (int) $value );

			case 'is_active':
			case 'is_importable':
				return (bool) $value;

			default:
				return $value;
		}
	}

	/**
	 * Prepare the type field by validating against allowed values.
	 *
	 * @param string $type The type value.
	 *
	 * @return string Validated type.
	 * @noinspection PhpUnused
	 */
	protected function prepare_type( string $type ): string {
		return in_array( $type, self::VALID_TYPES, true ) ? $type : 'php';
	}

	/**
	 * Prepare the source_type field by validating against allowed values.
	 *
	 * @param string $source_type The source type value.
	 *
	 * @return string Validated source type.
	 * @noinspection PhpUnused
	 */
	protected function prepare_source_type( string $source_type ): string {
		return in_array( $source_type, self::VALID_SOURCE_TYPES, true ) ? $source_type : '';
	}

	/**
	 * Prepare the risk_level field by validating against allowed values.
	 *
	 * @param string $risk_level The risk level value.
	 *
	 * @return string Validated risk level.
	 * @noinspection PhpUnused
	 */
	protected function prepare_risk_level( string $risk_level ): string {
		return in_array( $risk_level, self::VALID_RISK_LEVELS, true ) ? $risk_level : 'low';
	}

	/**
	 * Generate a deterministic hash for deduplication based on source and location.
	 *
	 * @return string SHA-256 hash.
	 */
	public function generate_hash(): string {
		$key = implode( '|', [
			$this->scanner_id,
			$this->source_type,
			$this->source_path,
			$this->line_start,
			$this->line_end,
		] );

		$this->hash = hash( 'sha256', $key );

		return $this->hash;
	}

	/**
	 * Generate a content checksum for change detection between scans.
	 *
	 * @return string MD5 hash of the code content.
	 */
	public function generate_checksum(): string {
		$this->checksum = md5( $this->code );

		return $this->checksum;
	}

	/**
	 * Set the scan date to the current UTC time.
	 *
	 * @return void
	 */
	public function stamp_scan_date(): void {
		$this->scan_date = gmdate( 'c' );
	}

	/**
	 * Convert to an associative array suitable for JSON serialization.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return $this->get_fields();
	}

	/**
	 * Create a Discovered_Snippet from an associative array (e.g. from stored JSON).
	 *
	 * @param array<string, mixed> $data The snippet data.
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self( $data );
	}
}
