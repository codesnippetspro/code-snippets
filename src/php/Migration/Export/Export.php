<?php

namespace Code_Snippets\Migration\Export;

use Code_Snippets\Model\Snippet;
use function Code_Snippets\get_snippets;

/**
 * Handles exporting snippets from the site in a downloadable format.
 *
 * @package Code_Snippets
 */
abstract class Export {

	/**
	 * Array of snippet data fetched from the database
	 *
	 * @var Snippet[]
	 */
	private array $snippets_list;

	/**
	 * Class constructor
	 *
	 * @param array<int> $ids     List of snippet IDs to export.
	 * @param bool|null  $network Whether to fetch snippets from local or network table.
	 */
	public function __construct( array $ids, ?bool $network = null ) {
		$this->snippets_list = get_snippets( $ids, $network );
	}

	/**
	 * Get the list of snippets to export.
	 *
	 * @return Snippet[] List of snippets to export.
	 */
	protected function get_snippets_list(): array {
		return $this->snippets_list;
	}

	/**
	 * Get the file extension for the export format.
	 *
	 * @return string File extension for the export format.
	 */
	abstract public function get_file_extension(): string;

	/**
	 * Build the export filename.
	 *
	 * @return string
	 */
	public function build_filename(): string {
		if ( 1 === count( $this->snippets_list ) ) {
			// If there is only snippet to export, use its name instead of the site name.
			$title = strtolower( $this->snippets_list[0]->name );
		} else {
			// Otherwise, use the site name as set in Settings > General.
			$title = strtolower( get_bloginfo( 'name' ) );
		}

		$filename = "$title.code-snippets.{$this->get_file_extension()}";
		return apply_filters( 'code_snippets/export/filename', $filename, $title, $this->snippets_list );
	}

	/**
	 * Generate the export data in the specified format.
	 *
	 * @return mixed
	 */
	abstract public function generate_export();
}
