<?php

namespace Code_Snippets\REST_API\Preferences;

/**
 * Controller for reading and updating the user's preference as to whether snippet lists
 * display as a table or a grid of cards.
 *
 * @package Code_Snippets
 */
final class Snippet_View_REST_Controller extends Preference_REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base suffix of this controller's route.
	 */
	public const BASE_ROUTE = 'snippet-view';

	/**
	 * The key used to identify this preference in the REST API.
	 */
	protected const PREFERENCE_KEY = 'view';

	/**
	 * The name of the option used to store the snippet view preference.
	 */
	public const OPTION_NAME = 'code_snippets_snippet_view';

	/**
	 * Valid snippet view values.
	 */
	public const VALID_VIEWS = [ 'card', 'table' ];

	/**
	 * The snippet view shown when no preference has been saved.
	 */
	protected const DEFAULT_VIEW = 'table';

	/**
	 * Retrieve the current snippet view preference, falling back to the
	 * default when the stored value is missing or invalid.
	 *
	 * @return string Either 'card' or 'table'.
	 */
	public static function get_snippet_view(): string {
		$view = get_option( self::OPTION_NAME, self::DEFAULT_VIEW );

		return in_array( $view, self::VALID_VIEWS, true )
			? $view
			: self::DEFAULT_VIEW;
	}

	/**
	 * Retrieve the stored preference value, falling back to the default when the stored value is missing or invalid.
	 *
	 * @return string
	 */
	protected function get_option_value(): string {
		return self::get_snippet_view();
	}

	/**
	 * Get the schema for the update request argument.
	 *
	 * @return array The schema for the update request argument.
	 */
	protected function get_update_request_schema(): array {
		return [
			'description' => esc_html__( 'Whether snippet lists display as a grid of cards or a table.', 'code-snippets' ),
			'type'        => 'string',
			'enum'        => self::VALID_VIEWS,
			'required'    => true,
		];
	}
}
