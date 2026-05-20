<?php

namespace Code_Snippets\UnifiedSnippets\Scanners;

use Code_Snippets\REST_API\Import\Plugins\Header_Footer_Code_Manager_Plugin_Importer;
use Code_Snippets\REST_API\Import\Plugins\Plugin_Importer;
use Code_Snippets\UnifiedSnippets\Adapters\DB_Scanner_Adapter;

/**
 * Scanner for Header Footer Code Manager (HFCM).
 *
 * Reuses {@see Header_Footer_Code_Manager_Plugin_Importer} to read rows from the
 * `{prefix}hfcm_scripts` table and emits one {@see \Code_Snippets\UnifiedSnippets\Model\Discovered_Snippet}
 * per row.
 *
 * @package Code_Snippets
 */
class Header_Footer_Code_Manager_Scanner extends DB_Scanner_Adapter {

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return 'hfcm';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( 'Header Footer Code Manager', 'code-snippets' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function create_importer(): Plugin_Importer {
		return new Header_Footer_Code_Manager_Plugin_Importer();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_table_name(): string {
		return 'hfcm_scripts';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string, mixed> $row Raw HFCM row from `{prefix}hfcm_scripts`.
	 */
	protected function map_row( array $row ): ?array {
		$code = (string) ( $row['snippet'] ?? '' );

		if ( '' === trim( $code ) ) {
			return null;
		}

		$id    = (int) ( $row['script_id'] ?? 0 );
		$title = (string) ( $row['name'] ?? '' );

		return [
			'name'        => '' !== $title ? $title : sprintf( 'HFCM #%d', $id ),
			'code'        => $code,
			'type'        => $this->derive_type( (string) ( $row['snippet_type'] ?? '' ) ),
			'source_path' => $this->build_source_path( $id ),
			'is_active'   => 'active' === ( $row['status'] ?? '' ),
		];
	}
}
