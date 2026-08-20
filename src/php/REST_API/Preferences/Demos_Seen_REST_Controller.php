<?php

namespace Code_Snippets\REST_API\Preferences;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Controller for recording which feature demos have been watched through to the end.
 *
 * Unlike the other preferences, this one accumulates: the stored value is the list of
 * demos already seen, while an update names the single demo just watched and is appended
 * to that list. Appending on the server keeps concurrent recordings from clobbering each
 * other, so the read and write keys differ.
 *
 * @package Code_Snippets
 */
final class Demos_Seen_REST_Controller extends Preference_REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base suffix of this controller's route.
	 */
	public const BASE_ROUTE = 'demos-seen';

	/**
	 * The key naming the demo watched, used when updating the preference.
	 */
	protected const PREFERENCE_KEY = 'demo';

	/**
	 * The key listing the demos watched, used when reading the preference.
	 */
	protected const RESPONSE_KEY = 'demos';

	/**
	 * The name of the option recording which feature demos have been watched.
	 */
	public const OPTION_NAME = 'code_snippets_demos_seen';

	/**
	 * Feature demos whose completion is recorded.
	 */
	public const DEMOS = [ 'ai-agent', 'blueprints' ];

	/**
	 * Retrieve the feature demos that have been watched through to the end.
	 *
	 * @return string[]
	 */
	public static function get_demos_seen(): array {
		$seen = get_option( self::OPTION_NAME, [] );

		return is_array( $seen ) ? array_values( array_intersect( self::DEMOS, $seen ) ) : [];
	}

	/**
	 * Forget which feature demos have been watched, so their toolbar tabs
	 * announce themselves as new again.
	 *
	 * @return void
	 */
	public static function reset_demos_seen(): void {
		delete_option( self::OPTION_NAME );
	}

	/**
	 * Retrieve the stored preference value, discarding any unrecognised demo names.
	 *
	 * @return string[]
	 */
	protected function get_option_value(): array {
		return self::get_demos_seen();
	}

	/**
	 * Get the schema for the update request argument.
	 *
	 * @return array The schema for the update request argument.
	 */
	protected function get_update_request_schema(): array {
		return [
			'description' => esc_html__( 'Identifier of the feature demo that has been watched.', 'code-snippets' ),
			'type'        => 'string',
			'enum'        => self::DEMOS,
			'required'    => true,
		];
	}

	/**
	 * Retrieve the demos watched so far as a REST response.
	 *
	 * @return WP_REST_Response
	 */
	public function get_option_value_callback(): WP_REST_Response {
		return new WP_REST_Response( [ self::RESPONSE_KEY => $this->get_option_value() ] );
	}

	/**
	 * Record that a feature demo has been watched through to the end.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_option_value_callback( WP_REST_Request $request ): WP_REST_Response {
		$demos = self::get_demos_seen();
		$demos[] = $request->get_param( self::PREFERENCE_KEY );

		$demos = array_values( array_intersect( self::DEMOS, array_unique( $demos ) ) );
		update_option( self::OPTION_NAME, $demos );

		return new WP_REST_Response( [ self::RESPONSE_KEY => $demos ] );
	}
}
