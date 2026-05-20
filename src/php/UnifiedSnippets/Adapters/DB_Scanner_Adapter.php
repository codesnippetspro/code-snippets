<?php

namespace Code_Snippets\UnifiedSnippets\Adapters;

use Code_Snippets\REST_API\Import\Plugins\Plugin_Importer;
use Code_Snippets\UnifiedSnippets\Model\Discovered_Snippet;
use Code_Snippets\UnifiedSnippets\Scanner_Base;

/**
 * Adapts an existing {@see Plugin_Importer} into a Unified Snippets scanner.
 *
 * @package Code_Snippets
 */
abstract class DB_Scanner_Adapter extends Scanner_Base {

	/**
	 * The wrapped importer that reads rows from the source plugin's storage.
	 *
	 * @var Plugin_Importer
	 */
	protected Plugin_Importer $importer;

	/**
	 * Class constructor.
	 *
	 * @param Plugin_Importer|null $importer Optional importer override, primarily for testing.
	 *                                       When null, the concrete subclass creates one via
	 *                                       {@see self::create_importer()}.
	 */
	public function __construct( ?Plugin_Importer $importer = null ) {
		$this->importer = $importer ?? $this->create_importer();
	}

	/**
	 * Construct the {@see Plugin_Importer} this adapter wraps.
	 *
	 * @return Plugin_Importer
	 */
	abstract protected function create_importer(): Plugin_Importer;

	/**
	 * The source plugin's storage table name (without the WordPress table prefix).
	 *
	 * @return string
	 */
	abstract protected function get_table_name(): string;

	/**
	 * Map a single raw row from the importer into Discovered_Snippet field overrides.
	 *
	 * Return null to skip the row (unsupported type, missing code, etc.).
	 *
	 * @param array<string, mixed> $row Raw row cast to associative array.
	 *
	 * @return array<string, mixed>|null
	 */
	abstract protected function map_row( array $row ): ?array;

	/**
	 * {@inheritDoc}
	 *
	 * Delegates to the wrapped importer's `is_active()` so the adapter is available exactly
	 * when the source plugin would be importable.
	 */
	public function is_available(): bool {
		return (bool) call_user_func( [ get_class( $this->importer ), 'is_active' ] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function scan(): array {
		if ( ! $this->is_available() ) {
			return [];
		}

		$snippets = [];

		foreach ( $this->importer->get_data() as $row ) {
			$row_array = is_array( $row ) ? $row : (array) $row;
			$fields    = $this->map_row( $row_array );

			if ( null === $fields ) {
				continue;
			}

			$snippets[] = $this->build_snippet( $this->with_defaults( $fields ) );
		}

		return $snippets;
	}

	/**
	 * Merge per-row field overrides on top of adapter-wide defaults.
	 *
	 * @param array<string, mixed> $fields Row-specific overrides from {@see self::map_row()}.
	 *
	 * @return array<string, mixed>
	 */
	private function with_defaults( array $fields ): array {
		return array_merge(
			[
				'source_type' => 'plugin',
				'source_name' => $this->get_label(),
				'line_start'  => 0,
				'line_end'    => 0,
			],
			$fields
		);
	}

	/**
	 * Build a synthetic URI identifying a row in the source plugin's table.
	 *
	 * @param int|string $id Row identifier.
	 *
	 * @return string e.g. 'db://hfcm_scripts/42'.
	 */
	protected function build_source_path( $id ): string {
		return 'db://' . $this->get_table_name() . '/' . $id;
	}

	/**
	 * Derive a {@see Discovered_Snippet} `type` from the source plugin's code-type value.
	 *
	 * @param string $code_type Source-plugin code type, e.g. 'html', 'universal'.
	 *
	 * @return string
	 */
	protected function derive_type( string $code_type ): string {
		switch ( strtolower( $code_type ) ) {
			case 'css':
				return 'css';
			case 'js':
			case 'javascript':
				return 'js';
			case 'html':
			case 'universal':
				return 'html';
			case 'php':
			case '':
				return 'php';
			default:
				return 'mixed';
		}
	}
}
