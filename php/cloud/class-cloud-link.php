<?php

namespace Code_Snippets\Cloud;

use Data_Item;

/**
 * A connection between a local snippet and remote cloud snippet.
 *
 * @package Code_Snippets
 *
 * @property integer $local_id         ID of local snippet as stored in WordPress database, if applicable.
 * @property string  $cloud_id         ID and ownership status of remote snippet on cloud platform, if applicable.
 * @property boolean $in_codevault     Whether the remote snippet is stored in the users' codevault.
 * @property boolean $update_available If synchronised, whether there is an update available on the cloud platform.
 */
class Cloud_Link extends Data_Item {

	/**
	 * Constructor function
	 *
	 * @param array<string, mixed>|object $data Initial data fields.
	 */
	public function __construct( $data = null ) {
		parent::__construct(
			[
				'local_id'         => 0,
				'cloud_id'         => '',
				'in_codevault'     => false,
				'update_available' => false,
			],
			$data
		);
	}

	/**
	 * Prepare `local_id` values by ensuring they are an absolute integer.
	 *
	 * @param mixed $local_id The field as provided.
	 *
	 * @return integer The field in the correct format.
	 */
	protected function prepare_local_id( $local_id ) {
		return absint( $local_id );
	}

	/**
	 * Prepare `remote_id` values by ensuring they are in the correct format.
	 *
	 * @param mixed $remote_id The field as provided.
	 *
	 * @return string The field in the correct format.
	 */
	protected function prepare_remote_id( $remote_id ) {
		// TODO: add better sanitization here.
		return (string) $remote_id;
	}

	/**
	 * Prepare `in_codevault` values by ensuring they are a boolean value.
	 *
	 * @param mixed $in_codevault The field as provided.
	 *
	 * @return boolean The field in the correct format.
	 */
	protected function prepare_in_codevault( $in_codevault ) {
		return is_bool( $in_codevault ) ? $in_codevault : (bool) $in_codevault;
	}

	/**
	 * Prepare `update_available` values by ensuring they are a boolean value.
	 *
	 * @param mixed $update_available The field as provided.
	 *
	 * @return boolean The field in the correct format.
	 */
	protected function prepare_update_available( $update_available ) {
		return is_bool( $update_available ) ? $update_available : (bool) $update_available;
	}
}
