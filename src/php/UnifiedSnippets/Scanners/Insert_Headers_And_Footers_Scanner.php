<?php

namespace Code_Snippets\UnifiedSnippets\Scanners;

use Code_Snippets\REST_API\Import\Plugins\Insert_Headers_And_Footers_Plugin_Importer;
use Code_Snippets\REST_API\Import\Plugins\Plugin_Importer;
use Code_Snippets\UnifiedSnippets\Adapters\DB_Scanner_Adapter;

/**
 * Scanner for WPCode (formerly Insert Headers and Footers).
 *
 * Reuses {@see Insert_Headers_And_Footers_Plugin_Importer} to read snippets stored
 * as `wpcode` custom posts and emits one {@see \Code_Snippets\UnifiedSnippets\Model\Discovered_Snippet}
 * per supported row.
 *
 * @package Code_Snippets
 */
class Insert_Headers_And_Footers_Scanner extends DB_Scanner_Adapter {

	private const SUPPORTED_CODE_TYPES = [ 'php', 'css', 'js', 'html', 'universal' ];

	public function get_id(): string {
		return 'wpcode';
	}

	public function get_label(): string {
		return __( 'WPCode (Insert Headers and Footers)', 'code-snippets' );
	}

	protected function create_importer(): Plugin_Importer {
		return new Insert_Headers_And_Footers_Plugin_Importer();
	}

	protected function get_table_name(): string {
		return 'wpcode_snippets';
	}

	protected function map_row( array $row ): ?array {
		$code_type = (string) ( $row['code_type'] ?? '' );

		if ( ! in_array( $code_type, self::SUPPORTED_CODE_TYPES, true ) ) {
			return null;
		}

		$code = (string) ( $row['code'] ?? '' );

		if ( '' === trim( $code ) ) {
			return null;
		}

		$id    = $row['table_data']['id'] ?? ( $row['id'] ?? 0 );
		$title = (string) ( $row['table_data']['title'] ?? ( $row['title'] ?? '' ) );

		return [
			'name'        => '' !== $title ? $title : sprintf( 'WPCode #%d', (int) $id ),
			'code'        => $code,
			'type'        => $this->derive_type( $code_type ),
			'source_path' => $this->build_source_path( (int) $id ),
			'is_active'   => true,
		];
	}
}
