<?php

namespace Code_Snippets\Cloud;

use Code_Snippets\Data_Item;
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
 * @property string        $codevault        Name of user codevault.
 * @property string        $total_votes      The total number of votes.
 * @property string        $vote_count       The number of actual votes.
 * @property string        $wp_tested        Tested with WP version.
 * @property string        $status           Snippet Status ID.
 * @property string        $created          The date and time when the snippet data was first created, in ISO format.
 * @property string        $updated          When the snippet was last updated, in ISO format.
 * @property integer       $revision         The update revision number.
 * @property bool          $is_owner         If user is owner or author of snippet.
 */
class Cloud_Snippet extends Data_Item {

	/**
	 * Constructor function.
	 *
	 * @param array<string, mixed>|null $initial_data Initial snippet data.
	 */
	public function __construct( array $initial_data = null ) {
		parent::__construct(
			[
				'id'             => '',
				'cloud_id'       => '',
				'name'           => '',
				'description'    => '',
				'code'           => '',
				'tags'           => [],
				'scope'          => '',
				'status'         => '',
				'codevault'      => '',
				'total_votes'    => '',
				'vote_count'     => '',
				'wp_tested'      => '',
				'created'        => '',
				'updated'        => '',
				'revision'       => 0,
				'is_owner'       => false,
				'shared_network' => false,
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
	protected function prepare_field( $value, string $field ) {
		switch ( $field ) {
			case 'id':
			case 'revision':
				return absint( $value );

			case 'is_owner':
				return (bool) $value;
			case 'description':
				return ( null === $value ) ? '' : $value;
			case 'tags':
				return code_snippets_build_tags_array( $value );

			default:
				return $value;
		}
	}
}
