<?php

namespace Code_Snippets\Admin;

use Code_Snippets\REST_API\Feedback\Feedback_REST_Controller;
use Code_Snippets\Utils\System_Info;
use function Code_Snippets\code_snippets;
use function Code_Snippets\Settings\get_setting;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Attaches the feedback reporter to this plugin's admin screens.
 *
 * The reporter is opt-in and is only mounted where it is useful: on a Code Snippets screen,
 * for somebody allowed to manage snippets, once the Advanced setting has been switched on.
 *
 * @package Code_Snippets
 */
class Feedback_Panel {

	/**
	 * Setting field that gates the reporter.
	 */
	public const SETTING_FIELD = 'enable_feedback_reporter';

	/**
	 * Identifier of the element the panel mounts into.
	 */
	public const CONTAINER_ID = 'code-snippets-feedback-container';

	/**
	 * Script handle.
	 */
	private const SCRIPT_HANDLE = 'code-snippets-feedback';

	/**
	 * Stylesheet handle.
	 */
	private const STYLE_HANDLE = 'code-snippets-feedback';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_footer', [ $this, 'render_container' ] );
	}

	/**
	 * Determine whether the reporter has been switched on.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return (bool) get_setting( 'general', self::SETTING_FIELD );
	}

	/**
	 * Determine whether the reporter belongs on the current request.
	 *
	 * @return bool
	 */
	public function should_render(): bool {
		return self::is_enabled() && code_snippets()->current_user_can() && $this->is_snippets_screen();
	}

	/**
	 * Enqueue the panel assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			plugins_url( 'dist/feedback.css', PLUGIN_FILE ),
			[ 'wp-components' ],
			PLUGIN_VERSION
		);

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			plugins_url( 'dist/feedback.js', PLUGIN_FILE ),
			[ 'react', 'react-dom', 'wp-components', 'wp-element', 'wp-i18n' ],
			PLUGIN_VERSION,
			true
		);

		wp_set_script_translations( self::SCRIPT_HANDLE, 'code-snippets' );

		$user = wp_get_current_user();
		$info = System_Info::get_system_info();

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'CODE_SNIPPETS_FEEDBACK',
			[
				'restUrl' => esc_url_raw( rest_url( Feedback_REST_Controller::get_base_route() ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'user'    => [
					'name'  => $user->display_name,
					'email' => $user->user_email,
				],
				'summary' => System_Info::get_summary( $info ),
				'badge'   => self::get_badge_label(),
				'version' => $info['plugin_version'],
				'edition' => $info['edition'],
			]
		);
	}

	/**
	 * Print the element the panel mounts into.
	 *
	 * @return void
	 */
	public function render_container(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		printf( '<div id="%s"></div>', esc_attr( self::CONTAINER_ID ) );
	}

	/**
	 * Describe the build a report was sent from, when it is not a released one.
	 *
	 * A released build carries no badge: labelling every install as a test build would
	 * misrepresent it. Pre-release builds are named so that a report can be read against
	 * the build it came from.
	 *
	 * @param string|null $version Version to describe. Defaults to the running version.
	 *
	 * @return string Badge text, empty when there is nothing to say.
	 */
	public static function get_badge_label( ?string $version = null ): string {
		$version = null === $version ? PLUGIN_VERSION : $version;
		$label = '';

		if ( preg_match( '/-(alpha|beta|rc)/i', $version, $matches ) ) {
			$names = [
				'alpha' => _x( 'Alpha', 'pre-release build', 'code-snippets' ),
				'beta'  => _x( 'Beta', 'pre-release build', 'code-snippets' ),
				'rc'    => _x( 'RC', 'pre-release build', 'code-snippets' ),
			];

			$label = sprintf( '%s %s', $names[ strtolower( $matches[1] ) ], $version );
		}

		return apply_filters( 'code_snippets_feedback_badge_label', $label, $version );
	}

	/**
	 * Determine whether the current screen belongs to this plugin.
	 *
	 * Matching this plugin's own menu slugs, rather than looking for 'snippet' anywhere in
	 * the screen identifier, keeps the reporter off screens belonging to other plugins.
	 *
	 * @return bool
	 */
	private function is_snippets_screen(): bool {
		if ( ! is_admin() ) {
			return false;
		}

		$slugs = [];

		foreach ( [ '', 'add', 'edit', 'import', 'settings', 'insights', 'welcome' ] as $menu ) {
			$slugs[] = code_snippets()->get_menu_slug( $menu );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( $page && in_array( $page, $slugs, true ) ) {
			return true;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen ) {
			return false;
		}

		foreach ( $slugs as $slug ) {
			if ( $slug && substr( $screen->id, -strlen( '_page_' . $slug ) ) === '_page_' . $slug ) {
				return true;
			}
		}

		return false;
	}
}
