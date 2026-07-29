<?php

namespace Code_Snippets\Model;

use function Code_Snippets\code_snippets_build_tags_array;

/**
 * A snippet object as retrieved from the cloud API.
 *
 * @since   3.4.0
 * @package Code_Snippets
 *
 * @property int       $id               The remote ID.
 * @property string    $slug             The snippet slug.
 * @property string    $name             The snippet title.
 * @property string    $description      The formatted description.
 * @property string    $code             The executable code.
 * @property string[]  $tags             An array of the tags.
 * @property string    $scope            The scope name.
 * @property string    $codevault        Name of user codevault.
 * @property string    $total_votes      The total number of votes.
 * @property string    $vote_count       The number of actual votes.
 * @property string    $wp_tested        Tested with WP version.
 * @property string    $status           Snippet Status ID.
 * @property string    $created          The date and time when the snippet data was first created, in ISO format.
 * @property string    $updated          When the snippet was last updated, in ISO format.
 * @property int       $revision         The update revision number.
 * @property bool      $is_owner         If user is owner or author of snippet.
 * @property int|null  $local_id         The local snippet ID when this cloud snippet has been downloaded.
 * @property bool|null $update_available If synchronised, whether there is an update available on the cloud platform.
 */
class Cloud_Snippet extends Model {

	/**
	 * List of default values provided for fields.
	 *
	 * @var array<string, mixed>
	 */
	protected static array $default_values = [
		'id'               => '',
		'cloud_id'         => '',
		'name'             => '',
		'description'      => '',
		'code'             => '',
		'tags'             => [],
		'scope'            => '',
		'language'         => '',
		'status'           => '',
		'codevault'        => '',
		'total_votes'      => '',
		'vote_count'       => '',
		'wp_tested'        => '',
		'created'          => '',
		'updated'          => '',
		'revision'         => 0,
		'is_owner'         => false,
		'local_id'         => null,
		'update_available' => null,
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
			case 'id':
			case 'revision':
			case 'local_id':
				return absint( $value );

			case 'is_owner':
				return (bool) $value;
			case 'tags':
				return code_snippets_build_tags_array( $value );

			case 'language':
				return is_array( $value ) ? ( $value['name'] ?? '' ) : (string) $value;

			default:
				return $value;
		}
	}
}
