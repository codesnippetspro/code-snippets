<?php

namespace Code_Snippets\Admin;

use const Code_Snippets\PLUGIN_VERSION;

/**
 * Tracks whether the current user has seen the latest What's New page.
 */
class Whats_New_Badge {

	/**
	 * User meta key for the latest seen plugin version.
	 */
	public const USER_META_KEY = 'code_snippets_whats_new_seen_version';

	/**
	 * Determine whether the current release is unseen.
	 *
	 * @return bool
	 */
	public static function has_unseen_release(): bool {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$seen_version = get_user_meta( $user_id, self::USER_META_KEY, true );

		return ! is_string( $seen_version ) ||
			'' === $seen_version ||
			version_compare( PLUGIN_VERSION, $seen_version, '>' );
	}

	/**
	 * Mark the current release as seen.
	 *
	 * @return void
	 */
	public static function mark_seen_release(): void {
		$user_id = get_current_user_id();

		if ( $user_id ) {
			update_user_meta( $user_id, self::USER_META_KEY, PLUGIN_VERSION );
		}
	}

	/**
	 * Prevent the indicator from appearing immediately after a fresh install.
	 *
	 * @return void
	 */
	public static function seed_fresh_install(): void {
		if ( false === get_option( 'code_snippets_version' ) ) {
			self::mark_seen_release();
		}
	}
}
