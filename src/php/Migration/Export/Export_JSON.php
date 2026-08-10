<?php

namespace Code_Snippets\Migration\Export;

use Code_Snippets\Model\Snippet;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Handles exporting snippets from the site in various downloadable formats
 *
 * @package Code_Snippets
 */
class Export_JSON extends Export {

	/**
	 * Retrieve the file extension for the export format.
	 *
	 * @return string
	 */
	public function get_file_extension(): string {
		return 'json';
	}

	/**
	 * Bundle snippets together into JSON format.
	 *
	 * @return array<string, string|Snippet[]> Snippets as JSON object.
	 */
	public function generate_export(): array {
		$snippets = [];

		foreach ( $this->get_snippets_list() as $snippet ) {
			$snippets[] = array_map(
				function ( $value ) {
					return is_string( $value ) ?
						str_replace( "\r\n", "\n", $value ) :
						$value;
				},
				$snippet->get_modified_fields()
			);
		}

		return [
			'generator'    => 'Code Snippets v' . PLUGIN_VERSION,
			'date_created' => gmdate( 'Y-m-d H:i' ),
			'snippets'     => $snippets,
		];
	}
}
