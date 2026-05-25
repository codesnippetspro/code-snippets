<?php

namespace Code_Snippets\Model;

use WP_REST_Response;

/**
 * A list of snippets as retrieved from the cloud API.
 *
 * @since   3.4.0
 * @package Code_Snippets
 *
 * @property Cloud_Snippet[] $snippets          List of snippet items for the current page.
 * @property int             $page              Page of data that this data belongs to.
 * @property int             $total_pages       Total number of available pages of items.
 * @property int             $total_snippets    Total number of available snippet items.
 * @property array           $cloud_id_rev      An array of all cloud snippet IDs and their revision numbers.
 * @property array           $available_filters An array of available filters that can be applied to the collection.
 */
class Cloud_Snippets extends Model {

	/**
	 * List of default values provided for fields.
	 *
	 * @var array<string, mixed>
	 */
	protected static array $default_values = [
		'snippets'          => [],
		'total_snippets'    => 0,
		'total_pages'       => 0,
		'page'              => 0,
		'cloud_id_rev'      => [],
		'available_filters' => [],
	];

	/**
	 * List of field name aliases to map when resolving a field name.
	 *
	 * @var array<string, string> Field alias names keyed to actual field names.
	 */
	protected static array $field_aliases = [
		'items'        => 'snippets',
		'total_items'  => 'total_snippets',
		'page'         => 'page',
		'cloud_id_rev' => 'cloud_id_rev',
	];


	/**
	 * Class constructor.
	 *
	 * @param array|null $initial_data Initial data from the cloud API response.
	 */
	public function __construct( $initial_data = null ) {
		parent::__construct( $this->normalize_cloud_api( $initial_data ) );
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
	 * @noinspection PhpUnused
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
		if ( is_array( $initial_data ) && isset( $initial_data['meta'] ) ) {
			$meta = $initial_data['meta'];
			$normalized = [];
			$normalized['snippets'] = $initial_data['data'] ?? $initial_data['snippets'] ?? [];
			$normalized['total_snippets'] = isset( $meta['total'] ) ? (int) $meta['total'] : 0;
			$normalized['total_pages'] = isset( $meta['total_pages'] ) ? (int) $meta['total_pages'] : 0;
			$normalized['page'] = isset( $meta['page'] ) ? max( 0, (int) $meta['page'] - 1 ) : 0;
			$normalized['cloud_id_rev'] = $initial_data['cloud_id_rev'] ?? [];
			$normalized['available_filters'] = $initial_data['available_filters'] ?? [];
			return $normalized;
		}

		return $initial_data;
	}

	/**
	 * Transform the data stored into this class into a REST API response.
	 *
	 * @return WP_REST_Response
	 */
	public function to_rest_response(): WP_REST_Response {
		$response = rest_ensure_response(
			[
				'snippets'          => array_map(
					fn( Cloud_Snippet $snippet ) => $snippet->get_fields(),
					$this->snippets
				),
				'page'              => $this->page,
				'total_pages'       => $this->total_pages,
				'total_snippets'    => $this->total_snippets,
				'available_filters' => $this->available_filters,
			]
		);

		$response->header( 'X-WP-Total', $this->total_snippets );
		$response->header( 'X-WP-TotalPages', $this->total_pages );

		return $response;
	}
}
