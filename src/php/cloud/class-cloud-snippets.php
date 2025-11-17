<?php

namespace Code_Snippets\Cloud;

use Code_Snippets\Data_Item;

/**
 * A list of snippets as retrieved from the cloud API.
 *
 * @since   3.4.0
 * @package Code_Snippets
 *
 * @property Cloud_Snippet[] $snippets        List of snippet items for the current page.
 * @property integer         $page            Page of data that this data belongs to.
 * @property integer         $total_pages     Total number of available pages of items.
 * @property integer         $total_snippets  Total number of available snippet items.
 * @property array           $cloud_id_rev    An array of all cloud snippet IDs and their revision numbers.
 * @property bool            $success         If the request has any results.
 */
class Cloud_Snippets extends Data_Item {

	/**
	 * Class constructor.
	 *
	 * @param array<string, Cloud_Snippet[]|integer> $initial_data Initial data.
	 */
	public function __construct( $initial_data = null ) {
		$initial_data = $this->normalize_cloud_api( $initial_data );
		parent::__construct(
			[
				'snippets'       => [],
				'total_snippets' => 0,
				'total_pages'    => 0,
				'page'           => 0,
				'cloud_id_rev'   => [],
			],
			$initial_data,
			[
				'items'        => 'snippets',
				'total_items'  => 'total_snippets',
				'page'         => 'page',
				'cloud_id_rev' => 'cloud_id_rev',
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
	protected function prepare_field( $value, string $field ) {
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
	protected function prepare_snippets( $snippets ): array {
		$result = [];
		$snippets = is_array( $snippets ) ? $snippets : [ $snippets ];

		foreach ( $snippets as $snippet ) {
			$result[] = $snippet instanceof Cloud_Snippet ? $snippet : new Cloud_Snippet( $snippet );
		}

		return $result;
	}

	/**
	 * Normalize payloads returned by the cloud API into the shape expected by this class.
	 *
	 * @param mixed $initial_data Raw data passed into the constructor.
	 *
	 * @return mixed Normalized data array or original value when no normalization is required.
	 */
	private function normalize_cloud_api( $initial_data ) {
		// pagination metadata is nested under a 'meta' key.
		if ( is_array( $initial_data ) && isset( $initial_data['meta'] ) ) {
			$meta = $initial_data['meta'];
			$normalized = [];
			$normalized['snippets'] = $initial_data['snippets'] ?? $initial_data['data'] ?? [];
			$normalized['total_snippets'] = isset( $meta['total'] ) ? (int) $meta['total'] : 0;
			$normalized['total_pages'] = isset( $meta['total_pages'] ) ? (int) $meta['total_pages'] : 0;
			$normalized['page'] = isset( $meta['page'] ) ? max( 0, (int) $meta['page'] - 1 ) : 0;
			$normalized['cloud_id_rev'] = $initial_data['cloud_id_rev'] ?? [];
			$initial_data = $normalized;
		}

		return $initial_data;
	}
}
