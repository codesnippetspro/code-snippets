<?php

namespace Code_Snippets\Model;

/**
 * A connection between a local snippet and remote cloud snippet.
 *
 * @package Code_Snippets
 *
 * @property int  $local_id         ID of local snippet as stored in WordPress database, if applicable.
 * @property int  $cloud_id         ID of remote snippet on cloud platform, if applicable.
 * @property bool $is_owner         Ownership status of remote snippet on cloud platform.
 * @property bool $in_codevault     Whether the remote snippet is stored in the users' codevault.
 * @property bool $update_available If synchronised, whether there is an update available on the cloud platform.
 */
class Cloud_Link extends Model {

	/**
	 * List of default values provided for fields.
	 *
	 * @var array<string, mixed>
	 */
	protected static array $default_values = [
		'local_id'         => 0,
		'cloud_id'         => 0,
		'is_owner'         => false,
		'in_codevault'     => false,
		'update_available' => false,
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
			case 'local_id':
			case 'remote_id':
				return absint( $value );

			case 'is_owner':
			case 'in_codevault':
			case 'update_available':
				return is_bool( $value ) ? $value : (bool) $value;

			default:
				return $value;
		}
	}
}
