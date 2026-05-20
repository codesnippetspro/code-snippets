<?php

namespace Code_Snippets\UnifiedSnippets\Scanners;

use Code_Snippets\REST_API\Import\Plugins\Insert_PHP_Code_Snippet_Plugin_Importer;
use Code_Snippets\REST_API\Import\Plugins\Plugin_Importer;
use Code_Snippets\UnifiedSnippets\Adapters\DB_Scanner_Adapter;

/**
 * Scanner for 'Insert PHP Code Snippet'.
 *
 * Reuses {@see Insert_PHP_Code_Snippet_Plugin_Importer} to read rows from the
 * `{prefix}xyz_ips_short_code` table and emits one
 * {@see \Code_Snippets\UnifiedSnippets\Model\Discovered_Snippet} per row.
 *
 * @package Code_Snippets
 */
class Insert_PHP_Code_Snippet_Scanner extends DB_Scanner_Adapter {

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return 'insert-php-code-snippet';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( 'Insert PHP Code Snippet', 'code-snippets' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * Discovered snippets execute arbitrary PHP, so the default risk is raised to 'high'.
	 */
	public function get_risk_level(): string {
		return 'high';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function create_importer(): Plugin_Importer {
		return new Insert_PHP_Code_Snippet_Plugin_Importer();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_table_name(): string {
		return 'xyz_ips_short_code';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string, mixed> $row Raw Insert PHP Code Snippet row from `{prefix}xyz_ips_short_code`.
	 */
	protected function map_row( array $row ): ?array {
		$code = (string) ( $row['content'] ?? '' );

		if ( '' === trim( $code ) ) {
			return null;
		}

		$id    = (int) ( $row['id'] ?? 0 );
		$title = (string) ( $row['title'] ?? '' );

		return [
			'name'        => '' !== $title ? $title : sprintf( 'Insert PHP #%d', $id ),
			'code'        => $code,
			'type'        => 'php',
			'source_path' => $this->build_source_path( $id ),
			'is_active'   => 1 === (int) ( $row['status'] ?? 0 ),
		];
	}
}
