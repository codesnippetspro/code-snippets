<?php

namespace Code_Snippets;

use Code_Snippets\REST_API\Snippets_REST_Controller;
use function Code_Snippets\Settings\get_setting;

/**
 * Functions specific to the administration interface
 *
 * @package Code_Snippets
 */
class Admin {

	/**
	 * Admin_Menu class instances
	 *
	 * @var array<string, Admin_Menu>
	 */
	public $menus = array();

	/**
	 * Class constructor
	 */
	public function __construct() {
		if ( is_admin() ) {
			$this->run();
		}
	}

	/**
	 * Initialise classes
	 */
	public function load_classes() {
		$this->menus['manage'] = new Manage_Menu();
		$this->menus['edit'] = new Edit_Menu();
		$this->menus['import'] = new Import_Menu();

		if ( is_network_admin() === Settings\are_settings_unified() ) {
			$this->menus['settings'] = new Settings_Menu();
		}

		foreach ( $this->menus as $menu ) {
			$menu->run();
		}
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		add_action( 'init', array( $this, 'load_classes' ), 11 );

		add_filter( 'mu_menu_items', array( $this, 'mu_menu_items' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PLUGIN_FILE ), array( $this, 'plugin_settings_link' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_meta_links' ), 10, 2 );
		add_filter( 'debug_information', array( $this, 'debug_information' ) );
		add_action( 'code_snippets/admin/manage', array( $this, 'print_notices' ) );
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
	 * Adds a link pointing to the Manage Snippets page
	 *
	 * @param array<string> $links Existing plugin action links.
	 *
	 * @return array<string> Modified plugin action links
	 * @since 2.0.0
	 */
	public function plugin_settings_link( array $links ): array {
		$format = '<a href="%1$s" title="%2$s">%3$s</a>';

		array_unshift(
			$links,
			sprintf(
				$format,
				esc_url( code_snippets()->get_menu_url( 'settings' ) ),
				esc_html__( 'Change plugin settings', 'code-snippets' ),
				esc_html__( 'Settings', 'code-snippets' )
			)
		);

		array_unshift(
			$links,
			sprintf(
				$format,
				esc_url( code_snippets()->get_menu_url() ),
				esc_html__( 'Manage your existing snippets', 'code-snippets' ),
				esc_html__( 'Snippets', 'code-snippets' )
			)
		);

		return $links;
	}

	/**
	 * Adds extra links related to the plugin
	 *
	 * @param array<string> $links Existing plugin info links.
	 * @param string        $file  The plugin the links are for.
	 *
	 * @return array<string> The modified plugin info links.
	 * @since 2.0.0
	 */
	public function plugin_meta_links( array $links, string $file ): array {

		// We only want to affect the Code Snippets plugin listing.
		if ( plugin_basename( PLUGIN_FILE ) !== $file ) {
			return $links;
		}

		$format = '<a href="%1$s" title="%2$s" target="_blank">%3$s</a>';

		/* array_merge appends the links to the end */

		return array_merge(
			$links,
			array(
				sprintf(
					$format,
					'https://codesnippets.pro/about/',
					esc_attr__( 'Find out more about Code Snippets', 'code-snippets' ),
					esc_html__( 'About', 'code-snippets' )
				),
				sprintf(
					$format,
					'https://help.codesnippets.pro/',
					esc_attr__( 'Find out how to get support with Code Snippets', 'code-snippets' ),
					esc_html__( 'Support', 'code-snippets' )
				),
				sprintf(
					$format,
					'https://www.facebook.com/groups/codesnippetsplugin/',
					esc_attr__( 'Join our community on Facebook', 'code-snippets' ),
					esc_html__( 'FB Community', 'code-snippets' )
				),
				sprintf(
					'<a href="%1$s" title="%2$s" style="color: #d46f4d;">%3$s</a>',
					'https://codesnippets.pro/pricing/',
					esc_attr__( 'Upgrade to Code Snippets Pro', 'code-snippets' ),
					esc_html__( 'Upgrade to Pro', 'code-snippets' )
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
	 */
	public function print_notices() {
		global $current_user;

		$key = 'ignore_code_snippets_survey_message';
		$dismissed = get_user_meta( $current_user->ID, $key );

		if ( isset( $_GET[ $key ], $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), $key ) ) {
			add_user_meta( $current_user->ID, $key, sanitize_key( wp_unslash( $_GET[ $key ] ) ) );
			return;
		}

		if ( ! in_array( 'pro', $dismissed, true ) ) {
			$notice = 'pro';
			$action_url = 'https://codesnippets.pro/pricing/';
			$action_label = __( 'Upgrade now', 'code-snippets' );
			$text = __( '<strong>Code Snippets Pro is here!</strong> Find more about the new features in Pro and our introductory launch offers.', 'code-snippets' );

		} elseif ( ! in_array( 'survey', $dismissed, true ) && ! in_array( 'true', $dismissed, true ) ) {
			$notice = 'survey';
			$action_url = 'https://codesnippets.pro/survey/';
			$action_label = __( 'Take the survey now', 'code-snippets' );
			$text = __( "<strong>Have feedback on Code Snippets?</strong> Please take the time to answer a short survey on how you use this plugin and what you'd like to see changed or added in the future.", 'code-snippets' );
		} else {
			return;
		}

		printf( '<div class="notice notice-info code-snippets-notice code-snippets-%s-notice is-dismissible"><p>', esc_attr( sanitize_key( $notice ) ) );
		echo wp_kses( $text, [ 'strong' => [] ] );

		printf(
			'<a href="%s" class="button secondary" target="_blank" style="margin: auto .5em;">%s</a>',
			esc_url( $action_url ),
			esc_html( $action_label )
		);

		printf(
			'<a href="%s" class="notice-dismiss"><span class="screen-reader-text">%s</span></a>',
			esc_url( wp_nonce_url( add_query_arg( $key, $notice ), $key ) ),
			esc_attr__( 'Dismiss', 'code-snippets' )
		);

		echo '</p></div>';
	}

	/**
	 * Render a nav tab for a snippet type.
	 *
	 * @param string $type_name    Type identifier.
	 * @param string $label        Type label.
	 * @param string $current_type Identifier of currently-selected type.
	 *
	 * @return void
	 */
	public static function render_snippet_type_tab( string $type_name, string $label, string $current_type = '' ) {
		if ( $type_name === $current_type ) {
			printf( '<a class="nav-tab nav-tab-active" data-snippet-type="%s">', esc_attr( $type_name ) );

		} elseif ( Plugin::is_pro_type( $type_name ) ) {
			printf(
				'<a class="nav-tab nav-tab-inactive" data-snippet-type="%s" title="%s" href="https://codesnippets.pro/pricing/" target="_blank">',
				esc_attr( $type_name ),
				esc_attr__( 'Available in Code Snippets Pro (external link)', 'code-snippets' )
			);

		} else {
			printf(
				'<a class="nav-tab" href="%s" data-snippet-type="%s">',
				esc_url( add_query_arg( 'type', $type_name ) ),
				esc_attr( $type_name )
			);
		}

		echo esc_html( $label );

		if ( 'all' !== $type_name ) {
			echo ' <span class="badge">' . esc_html( $type_name ) . '</span>';
		}

		echo '</a>';
	}
}
