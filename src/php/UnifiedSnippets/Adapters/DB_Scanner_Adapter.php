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

	protected Plugin_Importer $importer;

	public function __construct( ?Plugin_Importer $importer = null ) {
		$this->importer = $importer ?? $this->create_importer();
	}

	abstract protected function create_importer(): Plugin_Importer;

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

	public function is_available(): bool {
		return (bool) call_user_func( [ get_class( $this->importer ), 'is_active' ] );
	}

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
