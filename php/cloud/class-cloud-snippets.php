<?php

namespace Code_Snippets\Cloud;

use Data_Item;
use function Code_Snippets\code_snippets_build_tags_array;

/**
 * A list of snippets as retrieved from the cloud API.
 *
 * @since   3.4.0
 * @package Code_Snippets
 *
 * @property Cloud_Snippet[] $snippets       List of snippet items for the current page.
 * @property integer         $page           Page of data that this data belongs to.
 * @property integer         $total_pages    Total number of available pages of items.
 * @property integer         $total_snippets Total number of available snippet items.
 */
class Cloud_Snippets extends Data_Item {

	/**
	 * Class constructor.
	 *
	 * @param array<string, Cloud_Snippet[]|integer> $initial_data Initial data.
	 */
	public function __construct( $initial_data = null ) {
		parent::__construct(
			[
				'snippets'       => [],
				'page'           => 0,
				'total_pages'    => 0,
				'total_snippets' => 0,
			],
			$initial_data,
			[
				'items'       => 'snippets',
				'total_items' => 'total_snippets',
			]
		);
	}

	/**
	 * Prepare a value before it is stored.
	 *
	 * @param mixed  $value Value to prepare.
	 * @param string $field Field name.
	 *
	 * @return mixed Value in the correct format.
	 */
	protected function prepare_field( $value, $field ) {
		switch ( $field ) {
			case 'page':
			case 'total_pages':
			case 'total_snippets':
				return absint( $value );

			default:
				return $value;
		}
	}

	/**
	 * Prepare the `snippets` field by ensuring it is a list of Cloud_Snippets objects.
	 *
	 * @param mixed $snippets The field as provided.
	 *
	 * @return Cloud_Snippets[] The field in the correct format.
	 */
	protected function prepare_snippets( $snippets ) {
		$result = [];
		$snippets = is_array( $snippets ) ? $snippets : [ $snippets ];

		foreach ( $snippets as $snippet ) {
			$result[] = $snippet instanceof Cloud_Snippet ? $snippet : new Cloud_Snippet( $snippet );
		}

		return $result;
	}
}
