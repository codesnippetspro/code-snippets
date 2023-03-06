<?php

namespace Code_Snippets\Cloud;

use Data_Item;
use function Code_Snippets\code_snippets_build_tags_array;

/**
 * A snippet object as retrieved from the cloud API.
 *
 * @since   3.4.0
 * @package Code_Snippets
 *
 * @property int           $id               The remote ID.
 * @property string        $name             The snippet title.
 * @property string        $description      The formatted description.
 * @property string        $code             The executable code.
 * @property array<string> $tags             An array of the tags.
 * @property string        $scope            The scope name.
 * @property string        $status           Verification status.
 * @property string        $created          The date and time when the snippet data was first created, in DD/MM/YY format.
 * @property string        $updated          When the snippet was last updated, in human-readable format.
 * @property integer       $revision         The update revision number.
 * @property string        $cloud_id         Cloud ID and ownership status of snippet.
 */
class Cloud_Snippet extends Data_Item {

	/**
	 * Constructor function.
	 *
	 * @param array<string, mixed>|object $initial_data Initial snippet data.
	 */
	public function __construct( $initial_data = null ) {
		parent::__construct(
			[
				'id'          => 0,
				'name'        => '',
				'description' => '',
				'code'        => '',
				'tags'        => [],
				'scope'       => '',
				'status'      => '',
				'created'     => '',
				'updated'     => '',
				'revision'    => 0,
				'cloud_id'    => '',
			],
			$initial_data
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
			case 'id':
			case 'revision':
				return absint( $value );

			case 'cloud_id':
				return (string) $value;

			case 'tags':
				return code_snippets_build_tags_array( $value );

			default:
				return $value;
		}
	}
}
