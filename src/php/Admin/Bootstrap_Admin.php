<?php

namespace Code_Snippets\Admin;

use Code_Snippets\Admin\Menus\Admin_Menu;
use Code_Snippets\Admin\Menus\Edit_Menu;
use Code_Snippets\Admin\Menus\Insights\Insights_Menu;
use Code_Snippets\Admin\Menus\Import_Menu;
use Code_Snippets\Admin\Menus\Manage\Manage_Menu;
use Code_Snippets\Admin\Menus\Settings_Menu;
use Code_Snippets\Admin\Menus\Welcome_Menu;
use Code_Snippets\Client\Welcome_Client;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
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
		add_action( 'code_snippets/admin/manage', array( $this, 'print_notices' ) );
	}

	/**
	 * Initialise classes
	 */
	public function load_classes() {
		$this->menus['manage'] = new Manage_Menu();
		$this->menus['edit'] = new Edit_Menu();
		$this->menus['insights'] = new Insights_Menu();
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
	 * Print any admin notices that have not been dismissed.
	 *
	 * @return void
	 * @noinspection PhpRedundantOptionalArgumentInspection
	 */
	public function print_notices() {
		global $current_user;

		if ( apply_filters( 'code_snippets/hide_welcome_banner', false ) ) {
			return;
		}

		$meta_key = 'ignore_code_snippets_survey_message';
		$dismissed = get_user_meta( $current_user->ID, $meta_key, false );

		if ( isset( $_GET[ $meta_key ], $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), $meta_key ) ) {
			add_user_meta( $current_user->ID, $meta_key, sanitize_key( wp_unslash( $_GET[ $meta_key ] ) ) );
			return;
		}

		$welcome = $this->welcome_client->get_banner();

		try {
			$now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		} catch ( Exception $e ) {
			$now = $welcome['start_datetime'];
		}

		if ( ! empty( $welcome['key'] ) && ! in_array( $welcome['key'], $dismissed, true ) &&
		     ( empty( $welcome['start_datetime'] ) || $now >= $welcome['start_datetime'] ) &&
		     ( empty( $welcome['end_datetime'] ) || $now <= $welcome['end_datetime'] ) ) {
			$notice = $welcome['key'];

			$text = $welcome['text_free'];
			$action_url = $welcome['action_url_free'];
			$action_label = $welcome['action_label_free'];

		} elseif ( ! in_array( 'survey', $dismissed, true ) && ! in_array( 'true', $dismissed, true ) ) {
			$notice = 'survey';
			$action_url = 'https://codesnippets.pro/survey/';
			$action_label = __( 'Take the survey now', 'code-snippets' );
			$text = __( "<strong>Have feedback on Code Snippets?</strong> Please take the time to answer a short survey on how you use this plugin and what you'd like to see changed or added in the future.", 'code-snippets' );
		} else {
			return;
		}

		printf(
			'<div class="banner notice-info code-snippets-notice code-snippets-%s-notice is-dismissible"><p>',
			esc_attr( sanitize_key( $notice ) )
		);

		echo wp_kses_post( $text );

		printf(
			'<a href="%s" class="button button-secondary" target="_blank" style="margin-block: auto; margin-inline: .5em;">%s</a>',
			esc_url( $action_url ),
			esc_html( $action_label )
		);

		printf(
			'<a href="%s" class="banner-dismiss"><span class="screen-reader-text">%s</span></a>',
			esc_url( wp_nonce_url( add_query_arg( $meta_key, $notice ), $meta_key ) ),
			esc_html__( 'Dismiss', 'code-snippets' )
		);

		echo '</p></div>';
	}
}
