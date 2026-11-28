<?php

namespace Code_Snippets\Admin;

use Code_Snippets\Admin\Menus\Admin_Menu;
use Code_Snippets\Admin\Menus\Edit_Menu;
use Code_Snippets\Admin\Menus\Stats\Stats_Menu;
use Code_Snippets\Admin\Menus\Import_Menu;
use Code_Snippets\Admin\Menus\Manage\Manage_Menu;
use Code_Snippets\Admin\Menus\Settings_Menu;
use Code_Snippets\Admin\Menus\Welcome_Menu;
use Code_Snippets\Client\Welcome_Client;
use Code_Snippets\Utils\System_Info;
use DateTimeImmutable;
use DateTimeZone;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippets;
use function Code_Snippets\Settings\are_settings_unified;
use const Code_Snippets\PLUGIN_FILE;

/**
 * Functions which should only be run in the admin area.
 *
 * @package Code_Snippets
 */
class Bootstrap_Admin {

	/**
	 * User meta key listing the notices each user has dismissed.
	 *
	 * @var string
	 */
	private const DISMISSED_NOTICES_META_KEY = 'ignore_code_snippets_survey_message';

	/**
	 * Notice style that is rendered as the promotional banner above the plugin toolbar.
	 *
	 * @var string
	 */
	private const PROMO_BANNER_STYLE = 'promo';

	/**
	 * Admin_Menu class instances
	 *
	 * @var Admin_Menu[]
	 */
	public array $menus = [];

	/**
	 * Welcome_API class instance.
	 *
	 * @var Welcome_Client
	 */
	public Welcome_Client $welcome_client;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		$this->welcome_client = new Welcome_Client();
		new Notice_Filter();

		add_action( 'init', array( $this, 'load_classes' ), 11 );

		add_filter( 'mu_menu_items', array( $this, 'mu_menu_items' ) );
		add_filter( 'manage_sites_action_links', array( $this, 'add_sites_row_action' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( PLUGIN_FILE ), array( $this, 'plugin_action_links' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'debug_information', array( $this, 'debug_information' ) );
		add_action( 'admin_init', array( $this, 'handle_notice_dismissal' ) );
		add_action( 'code_snippets/admin/manage', array( $this, 'print_notices' ) );
	}

	/**
	 * Initialise classes
	 */
	public function load_classes() {
		$this->menus['manage'] = new Manage_Menu();
		$this->menus['edit'] = new Edit_Menu();
		$this->menus['stats'] = new Stats_Menu();
		$this->menus['import'] = new Import_Menu();

		if ( is_network_admin() === are_settings_unified() ) {
			$this->menus['settings'] = new Settings_Menu();
		}

		$this->menus['welcome'] = new Welcome_Menu( $this->welcome_client );
	}

	/**
	 * Allow super admins to control site admin access to
	 * snippet admin menus
	 *
	 * Adds a checkbox to the *Settings > Network Settings*
	 * network admin menu
	 *
	 * @param array<string, string> $menu_items Current mu menu items.
	 *
	 * @return array<string, string> The modified mu menu items
	 *
	 * @since 1.7.1
	 */
	public function mu_menu_items( array $menu_items ): array {
		$menu_items['snippets'] = __( 'Snippets', 'code-snippets' );
		$menu_items['snippets_settings'] = __( 'Snippets &raquo; Settings', 'code-snippets' );

		return $menu_items;
	}

	/**
	 * Add a "Snippets" row action to the Network Sites table.
	 *
	 * @param array<string, string> $actions Existing row actions.
	 * @param int                   $site_id Current site ID.
	 *
	 * @return array<string, string>
	 */
	public function add_sites_row_action( array $actions, int $site_id ): array {
		if ( ! is_multisite() || ! current_user_can( code_snippets()->get_network_cap_name() ) ) {
			return $actions;
		}

		$menu_slug = code_snippets()->get_menu_slug();
		$actions['code_snippets'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_admin_url( $site_id, 'admin.php?page=' . $menu_slug ) ),
			esc_html__( 'Snippets', 'code-snippets' )
		);

		return $actions;
	}

	/**
	 * Modify the action links for this plugin.
	 *
	 * @param array<string> $actions     Existing plugin action links.
	 * @param string        $plugin_file The plugin the links are for.
	 *
	 * @return array<string> Modified plugin action links.
	 * @since 2.0.0
	 */
	public function plugin_action_links( array $actions, string $plugin_file ): array {
		if ( plugin_basename( PLUGIN_FILE ) !== $plugin_file ) {
			return $actions;
		}

		$format = '<a href="%1$s" title="%2$s">%3$s</a>';

		$actions = array_merge(
			[
				sprintf(
					$format,
					esc_url( code_snippets()->get_menu_url( 'settings' ) ),
					esc_attr__( 'Change plugin settings', 'code-snippets' ),
					esc_html__( 'Settings', 'code-snippets' )
				),
				sprintf(
					$format,
					esc_url( code_snippets()->get_menu_url() ),
					esc_attr__( 'Manage your existing snippets', 'code-snippets' ),
					esc_html__( 'Snippets', 'code-snippets' )
				),
			],
			$actions
		);

		if ( ! code_snippets()->licensing->is_licensed() ) {
			$actions[] = sprintf(
				'<a href="%1$s" title="%2$s" style="color: #d46f4d; font-weight: bold;" target="_blank">%3$s</a>',
				'https://snipco.de/JE2i',
				esc_attr__( 'Upgrade to Code Snippets Pro', 'code-snippets' ),
				esc_attr__( 'Upgrade to Pro', 'code-snippets' )
			);
		}
		return $actions;
	}

	/**
	 * Adds extra links related to the plugin
	 *
	 * @param array<string> $plugin_meta Existing plugin info links.
	 * @param string        $plugin_file The plugin the links are for.
	 *
	 * @return array<string> Modified plugin info links.
	 * @since 2.0.0
	 */
	public function plugin_row_meta( array $plugin_meta, string $plugin_file ): array {
		if ( plugin_basename( PLUGIN_FILE ) !== $plugin_file ) {
			return $plugin_meta;
		}

		$format = '<a href="%1$s" title="%2$s" target="_blank">%3$s</a>';

		return array_merge(
			$plugin_meta,
			array(
				sprintf(
					$format,
					'https://codesnippets.pro/support/',
					esc_attr__( 'Find out how to get support with Code Snippets', 'code-snippets' ),
					esc_html__( 'Docs and Support', 'code-snippets' )
				),
				sprintf(
					$format,
					'https://www.facebook.com/groups/codesnippetsplugin/',
					esc_attr__( 'Join our community on Facebook', 'code-snippets' ),
					esc_html__( 'Community', 'code-snippets' )
				),
			)
		);
	}

	/**
	 * Add Code Snippets information to Site Health information.
	 *
	 * @param array<string, array<string, mixed>> $info Current Site Health information.
	 *
	 * @return array<string, array<string, mixed>> Updated Site Health information.
	 * @author sc0ttkclark
	 */
	public function debug_information( array $info ): array {
		$fields = array();

		// build the debug information from snippet data.
		foreach ( get_snippets() as $snippet ) {
			$values = [ $snippet->scope_name ];
			$debug = [];

			if ( ! $snippet->active ) {
				continue;
			}

			if ( $snippet->name ) {
				$debug[] = 'name: ' . $snippet->name;
			}

			$debug[] = 'scope: ' . $snippet->scope;

			if ( $snippet->modified ) {
				/* translators: %s: formatted last modified date */
				$values[] = sprintf( __( 'Last modified %s', 'code-snippets' ), $snippet->format_modified( false ) );
				$debug[] = 'modified: ' . $snippet->modified;
			}

			if ( $snippet->tags ) {
				$values[] = $snippet->tags_list;
				$debug[] = 'tags: [' . $snippet->tags_list . ']';
			}

			$fields[ 'snippet-' . $snippet->id ] = [
				'label' => $snippet->display_name,
				'value' => implode( "\n | ", $values ),
				'debug' => implode( ', ', $debug ),
			];
		}

		$snippets_info = array(
			'label'      => __( 'Active Snippets', 'code-snippets' ),
			'show_count' => true,
			'fields'     => $fields,
		);

		// attempt to insert the new section right after the Inactive Plugins section.
		$index = array_search( 'wp-plugins-inactive', array_keys( $info ), true );

		if ( false === $index ) {
			$info['code-snippets'] = $snippets_info;
		} else {
			$info = array_merge(
				array_slice( $info, 0, $index + 1 ),
				[ 'code-snippets' => $snippets_info ],
				array_slice( $info, $index + 1 )
			);
		}

		return $info;
	}

	/**
	 * Record the dismissal of a notice for the current user, then return to the page without the dismissal parameters.
	 *
	 * @return void
	 */
	public function handle_notice_dismissal() {
		$meta_key = self::DISMISSED_NOTICES_META_KEY;

		if ( ! isset( $_GET[ $meta_key ], $_REQUEST['_wpnonce'] ) ||
		     ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), $meta_key ) ||
		     ! code_snippets()->current_user_can() ) {
			return;
		}

		add_user_meta( get_current_user_id(), $meta_key, sanitize_key( wp_unslash( $_GET[ $meta_key ] ) ) );

		wp_safe_redirect( remove_query_arg( [ $meta_key, '_wpnonce' ] ) );
		exit;
	}

	/**
	 * Build the query arguments that dismiss a notice for the current user.
	 *
	 * @param string $notice Notice key.
	 *
	 * @return array<string, string>
	 */
	private function get_notice_dismiss_args( string $notice ): array {
		return [
			self::DISMISSED_NOTICES_META_KEY => $notice,
			'_wpnonce'                       => wp_create_nonce( self::DISMISSED_NOTICES_META_KEY ),
		];
	}

	/**
	 * Build the URL that dismisses a notice for the current user.
	 *
	 * @param string $notice Notice key.
	 *
	 * @return string Unescaped URL.
	 */
	private function get_notice_dismiss_url( string $notice ): string {
		return add_query_arg( $this->get_notice_dismiss_args( $notice ) );
	}

	/**
	 * Determine whether banner text shows anything once its markup is removed.
	 *
	 * @param string $text Banner text.
	 *
	 * @return bool
	 */
	private static function has_visible_text( string $text ): bool {
		$plain = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );

		// A non-breaking space shows nothing, just like a regular one.
		return '' !== trim( str_replace( "\u{00A0}", ' ', $plain ) );
	}

	/**
	 * Determine which notice, if any, should be shown to the current user.
	 *
	 * A banner from the remote welcome feed is shown as the promotional banner while it is scheduled and has not been
	 * dismissed, using its short headline when the feed provides one and its full text otherwise. Otherwise, the
	 * survey notice is shown until it is dismissed.
	 *
	 * @param DateTimeImmutable|null $now Current time. Defaults to now.
	 *
	 * @return array{
	 *     key: string,
	 *     style: string,
	 *     label: string,
	 *     text: string,
	 *     secondary: string,
	 *     action_url: string,
	 *     action_label: string,
	 *     end_datetime: ?DateTimeImmutable
	 * }|null
	 */
	public function get_active_notice( ?DateTimeImmutable $now = null ): ?array {
		if ( apply_filters( 'code_snippets/hide_welcome_banner', false ) ) {
			return null;
		}

		$now = $now ?? new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$dismissed = get_user_meta( get_current_user_id(), self::DISMISSED_NOTICES_META_KEY, false );
		$welcome = $this->welcome_client->get_banner();
		$welcome_text = '' === ( $welcome['headline_free'] ?? '' ) ? ( $welcome['text_free'] ?? '' ) : $welcome['headline_free'];

		if ( ! empty( $welcome['key'] ) && ! in_array( $welcome['key'], $dismissed, true ) &&
		     self::has_visible_text( $welcome_text ) &&
		     ( empty( $welcome['start_datetime'] ) || $now >= $welcome['start_datetime'] ) &&
		     ( empty( $welcome['end_datetime'] ) || $now <= $welcome['end_datetime'] ) ) {
			return [
				'key'          => $welcome['key'],
				'style'        => self::PROMO_BANNER_STYLE,
				'label'        => $welcome['label'] ?? '',
				'text'         => $welcome_text,
				'secondary'    => $welcome['secondary_free'] ?? '',
				'action_url'   => $welcome['action_url_free'] ?? '',
				'action_label' => $welcome['action_label_free'] ?? '',
				'end_datetime' => $welcome['end_datetime'] ?? null,
			];
		}

		if ( ! in_array( 'survey', $dismissed, true ) && ! in_array( 'true', $dismissed, true ) ) {
			return [
				'key'          => 'survey',
				'style'        => '',
				'label'        => '',
				'text'         => __( "<strong>Have feedback on Code Snippets?</strong> Please take the time to answer a short survey on how you use this plugin and what you'd like to see changed or added in the future.", 'code-snippets' ),
				'secondary'    => '',
				'action_url'   => 'https://codesnippets.pro/survey/',
				'action_label' => __( 'Take the survey now', 'code-snippets' ),
				'end_datetime' => null,
			];
		}

		return null;
	}

	/**
	 * Add the details the store uses to tailor an offer to an action URL from the welcome feed.
	 *
	 * @param string $url Action URL from the feed.
	 *
	 * @return string Unescaped URL, or an empty string when the feed provides none.
	 */
	private function add_offer_query_args( string $url ): string {
		if ( '' === $url ) {
			return '';
		}

		return add_query_arg(
			[
				'cs_user' => code_snippets()->licensing->is_licensed() ? 'existing' : 'new',
				'plugin'  => 'pro' === System_Info::get_edition() ? 'cs_pro' : 'cs_core',
			],
			$url
		);
	}

	/**
	 * Limit remote banner HTML to links and basic emphasis, with links opening safely in a new tab.
	 *
	 * @param string $html Banner HTML from the welcome feed.
	 *
	 * @return string Sanitized HTML.
	 */
	private static function kses_promo_html( string $html ): string {
		$allowed_html = [
			'a'      => [ 'href' => true ],
			'strong' => [],
			'em'     => [],
			'br'     => [],
		];

		return preg_replace(
			'/<a href="([^"]*)">/',
			'<a href="$1" target="_blank" rel="noopener noreferrer">',
			wp_kses( $html, $allowed_html, [ 'https' ] )
		);
	}

	/**
	 * Retrieve the active promotional banner, prepared for the manage screen script.
	 *
	 * Only welcome feed banners are returned; the survey notice keeps the standard notice markup printed by
	 * print_notices().
	 *
	 * @param DateTimeImmutable|null $now Current time. Defaults to now.
	 *
	 * @return array{
	 *     key: string,
	 *     label: string,
	 *     text: string,
	 *     secondary: string,
	 *     actionUrl: string,
	 *     actionLabel: string,
	 *     endsAt: ?string,
	 *     dismissUrl: string,
	 *     dismissArgs: array<string, string>
	 * }|null
	 */
	public function get_promo_banner( ?DateTimeImmutable $now = null ): ?array {
		$notice = $this->get_active_notice( $now );

		if ( ! $notice || self::PROMO_BANNER_STYLE !== $notice['style'] ) {
			return null;
		}

		return [
			'key'         => $notice['key'],
			'label'       => sanitize_text_field( $notice['label'] ),
			'text'        => self::kses_promo_html( $notice['text'] ),
			'secondary'   => self::kses_promo_html( $notice['secondary'] ),
			'actionUrl'   => esc_url_raw( $this->add_offer_query_args( $notice['action_url'] ) ),
			'actionLabel' => sanitize_text_field( $notice['action_label'] ),
			'endsAt'      => $notice['end_datetime'] ? $notice['end_datetime']->format( DATE_ATOM ) : null,
			'dismissUrl'  => esc_url_raw( $this->get_notice_dismiss_url( $notice['key'] ) ),
			'dismissArgs' => $this->get_notice_dismiss_args( $notice['key'] ),
		];
	}

	/**
	 * Print the active notice, unless it is a promotional banner, which the manage screen script renders instead.
	 *
	 * @return void
	 */
	public function print_notices() {
		$notice = $this->get_active_notice();

		if ( ! $notice || self::PROMO_BANNER_STYLE === $notice['style'] ) {
			return;
		}

		printf(
			'<div class="banner notice notice-info code-snippets-notice code-snippets-%s-notice is-dismissible"><p>',
			esc_attr( sanitize_key( $notice['key'] ) )
		);

		echo wp_kses_post( $notice['text'] );

		printf(
			'<a href="%s" class="button button-secondary" target="_blank" rel="noopener noreferrer" style="margin-block: auto; margin-inline: .5em;">%s</a>',
			esc_url( $notice['action_url'] ),
			esc_html( $notice['action_label'] )
		);

		printf(
			'<a href="%s" class="notice-dismiss"><span class="screen-reader-text">%s</span></a>',
			esc_url( $this->get_notice_dismiss_url( $notice['key'] ) ),
			esc_html__( 'Dismiss', 'code-snippets' )
		);

		echo '</p></div>';
	}
}
