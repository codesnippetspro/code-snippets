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
	 * Unpack meta values from API response.
	 *
	 * @param array $meta Response meta data.
	 *
	 * @return void
	 */
	public function unpack_api_meta( array $meta ) {
		if ( isset( $meta['total'] ) && is_numeric( $meta['total'] ) ) {
			$this->total_snippets = $meta['total'];
		}

		if ( isset( $meta['total_pages'] ) && is_numeric( $meta['total_pages'] ) ) {
			$this->total_pages = $meta['total_pages'];
		}

		if ( isset( $meta['page'] ) && is_numeric( $meta['page'] ) ) {
			$this->page = max( 0, (int) $meta['page'] - 1 );
		}
	}

	/**
	 * Normalize payloads returned by the cloud API into the shape expected by this class.
	 *
	 * @param array|null $response Response data as returned from API.
	 *
	 * @return Cloud_Snippets Constructed cloud snippets object from response data.
	 */
	public static function unpack_api_response( ?array $response ): Cloud_Snippets {
		$result = new Cloud_Snippets();

		if ( ! $response ) {
			return $result;
		}

		$snippets_data = $response['data'] ?? $response['snippets'] ?? [];

		foreach ( $snippets_data as $snippet_data ) {
			$result->snippets[] = new Cloud_Snippet( $snippet_data );
		}

		$result->cloud_id_rev = $response['cloud_id_rev'] ?? [];
		$result->available_filters = $response['available_filters'] ?? [];

		if ( isset( $response['meta'] ) && is_array( $response['meta'] ) ) {
			$result->unpack_api_meta( $response['meta'] );
		}

		return $result;
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
