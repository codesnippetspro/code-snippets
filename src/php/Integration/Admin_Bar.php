<?php

namespace Code_Snippets\Integration;

use Code_Snippets\Model\Snippet;
use Code_Snippets\REST_API\Snippets\Snippets_REST_Controller;
use WP_Admin_Bar;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippets;
use function Code_Snippets\Settings\are_settings_unified;
use function Code_Snippets\Settings\get_setting;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Register Code Snippets quick links in the WordPress Admin Bar.
 *
 * @package Code_Snippets
 */
class Admin_Bar {

	/**
	 * Root node ID.
	 *
	 * @var string
	 */
	private const ROOT_NODE_ID = 'code-snippets';

	/**
	 * Active snippets pagination query arg.
	 *
	 * @var string
	 */
	private const ACTIVE_PAGE_QUERY_ARG = 'code_snippets_ab_active_page';

	/**
	 * Inactive snippets pagination query arg.
	 *
	 * @var string
	 */
	private const INACTIVE_PAGE_QUERY_ARG = 'code_snippets_ab_inactive_page';

	/**
	 * Safe mode node ID.
	 *
	 * @var string
	 */
	private const SAFE_MODE_NODE_ID = 'code-snippets-safe-mode';

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	private const SCRIPT_HANDLE = 'code-snippets-admin-bar';

	/**
	 * Stylesheet handle.
	 *
	 * @var string
	 */
	private const STYLE_HANDLE = 'code-snippets-admin-bar';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_bar_menu', [ $this, 'register_nodes' ], 80 );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue scripts and styles for admin bar nodes.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			plugins_url( 'dist/admin-bar.css', PLUGIN_FILE ),
			[ 'admin-bar', 'dashicons' ],
			PLUGIN_VERSION
		);

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			plugins_url( 'dist/admin-bar.js', PLUGIN_FILE ),
			[ 'wp-i18n', 'wp-url' ],
			PLUGIN_VERSION,
			[ 'in_footer' => true ]
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'CODE_SNIPPETS_ADMIN_BAR',
			[
				'restUrl'            => esc_url_raw( rest_url( Snippets_REST_Controller::get_base_route() ) ),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'perPage'            => $this->get_snippet_limit(),
				'isNetwork'          => is_network_admin(),
				'excludeTypes'       => [ 'cond' ],
				// translators: %d: snippet identifier.
				'snippetPlaceholder' => esc_html__( 'Snippet #%d', 'code-snippets' ),
				'editUrlBase'        => code_snippets()->get_menu_url( 'edit' ),
				'activeNodeId'       => 'wp-admin-bar-' . self::ROOT_NODE_ID . '-active-snippets',
				'inactiveNodeId'     => 'wp-admin-bar-' . self::ROOT_NODE_ID . '-inactive-snippets',
			]
		);
	}

	/**
	 * Register admin bar nodes.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 *
	 * @return void
	 */
	public function register_nodes( WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! is_admin_bar_showing() || ! code_snippets()->current_user_can() ) {
			return;
		}

		// Always show safe mode indicator regardless of setting.
		$this->add_safe_mode_nodes( $wp_admin_bar );

		// Check if admin bar menu is enabled via settings.
		$is_enabled = get_setting( 'general', 'enable_admin_bar' );

		if ( ! $is_enabled && ! apply_filters( 'code_snippets/admin_bar/enabled', false ) ) {
			return;
		}

		$title = sprintf(
			'<span class="ab-icon code-snippets-admin-bar-icon" aria-hidden="true"></span><span class="ab-label">%s</span>',
			esc_html__( 'Snippets', 'code-snippets' )
		);

		$wp_admin_bar->add_node(
			[
				'id'    => self::ROOT_NODE_ID,
				'title' => $title,
				'href'  => code_snippets()->get_menu_url( 'manage' ),
			]
		);

		$this->add_quick_links( $wp_admin_bar );
		$this->add_snippet_listings( $wp_admin_bar );
		$this->add_safe_mode_link( $wp_admin_bar );
	}

	/**
	 * Add menu item for safe mode status.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 *
	 * @return void
	 */
	private function add_safe_mode_nodes( WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! Evaluate_Functions::is_safe_mode_active() ) {
			return;
		}

		$wp_admin_bar->add_node(
			[
				'id'     => self::SAFE_MODE_NODE_ID,
				'title'  => esc_html__( 'Snippets Safe Mode Active', 'code-snippets' ),
				'href'   => 'https://snipco.de/safe-mode',
				'parent' => 'top-secondary',
				'meta'   => [
					'class'  => 'code-snippets-safe-mode code-snippets-safe-mode-active',
					'target' => '_blank',
					'rel'    => 'noopener noreferrer',
				],
			]
		);
	}

	/**
	 * Add a safe mode documentation link under the Code Snippets menu.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 *
	 * @return void
	 */
	private function add_safe_mode_link( WP_Admin_Bar $wp_admin_bar ): void {
		$title = sprintf(
			'%s <span class="code-snippets-external-icon dashicons dashicons-external" aria-hidden="true"></span>',
			esc_html__( 'Safe Mode', 'code-snippets' )
		);

		$wp_admin_bar->add_node(
			[
				'id'     => self::ROOT_NODE_ID . '-safe-mode-doc',
				'title'  => $title,
				'href'   => 'https://snipco.de/safe-mode',
				'parent' => self::ROOT_NODE_ID,
				'meta'   => [
					'class'  => 'code-snippets-safe-mode',
					'target' => '_blank',
					'rel'    => 'noopener noreferrer',
				],
			]
		);
	}

	/**
	 * Add quick links to common Code Snippets screens.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 *
	 * @return void
	 */
	private function add_quick_links( WP_Admin_Bar $wp_admin_bar ): void {
		$plugin = code_snippets();
		$is_licensed = $plugin->licensing->is_licensed();
		$upgrade_url = self_admin_url( 'admin.php?page=code_snippets_upgrade' );

		$wp_admin_bar->add_node(
			[
				'id'     => self::ROOT_NODE_ID . '-manage',
				'title'  => esc_html_x( 'Manage', 'snippets', 'code-snippets' ),
				'href'   => $plugin->get_menu_url( 'manage' ),
				'parent' => self::ROOT_NODE_ID,
			]
		);

		$statuses = [
			'all'      => _x( 'All Snippets', 'snippets', 'code-snippets' ),
			'active'   => _x( 'Active Snippets', 'snippets', 'code-snippets' ),
			'inactive' => _x( 'Inactive Snippets', 'snippets', 'code-snippets' ),
		];

		foreach ( $statuses as $status => $label ) {
			$wp_admin_bar->add_node(
				[
					'id'     => self::ROOT_NODE_ID . "-status-$status",
					'title'  => esc_html( $label ),
					'href'   => esc_url( add_query_arg( 'status', $status, $plugin->get_menu_url( 'manage' ) ) ),
					'parent' => self::ROOT_NODE_ID . '-manage',
				]
			);
		}

		$wp_admin_bar->add_node(
			[
				'id'     => self::ROOT_NODE_ID . '-add',
				'title'  => esc_html_x( 'Add New', 'snippet', 'code-snippets' ),
				'href'   => $plugin->get_menu_url( 'add' ),
				'parent' => self::ROOT_NODE_ID,
			]
		);

		$types = [
			'php'  => _x( 'Functions (PHP)', 'snippet type', 'code-snippets' ),
			'html' => _x( 'Content (HTML)', 'snippet type', 'code-snippets' ),
			'css'  => _x( 'Styles (CSS)', 'snippet type', 'code-snippets' ),
			'js'   => _x( 'Scripts (JS)', 'snippet type', 'code-snippets' ),
			'cond' => _x( 'Conditions (COND)', 'snippet type', 'code-snippets' ),
		];

		$types = array_intersect_key( $types, array_flip( Snippet::get_types() ) );
		$pro_types = [ 'css', 'js', 'cond' ];

		foreach ( $types as $type => $label ) {
			$is_disabled = in_array( $type, $pro_types, true ) && ! $is_licensed;

			$url = $is_disabled ?
				$upgrade_url :
				add_query_arg( 'type', $type, $plugin->get_menu_url( 'add' ) );

			$wp_admin_bar->add_node(
				[
					'id'     => self::ROOT_NODE_ID . "-add-$type",
					'title'  => esc_html( $label ),
					'href'   => esc_url( $url ),
					'parent' => self::ROOT_NODE_ID . '-add',
					'meta'   => $is_disabled ? [ 'class' => 'code-snippets-disabled' ] : [],
				]
			);
		}

		$wp_admin_bar->add_node(
			[
				'id'     => self::ROOT_NODE_ID . '-import',
				'title'  => esc_html_x( 'Import', 'snippets', 'code-snippets' ),
				'href'   => $plugin->get_menu_url( 'import' ),
				'parent' => self::ROOT_NODE_ID,
			]
		);

		$wp_admin_bar->add_node(
			[
				'id'     => self::ROOT_NODE_ID . '-settings',
				'title'  => esc_html_x( 'Settings', 'snippets', 'code-snippets' ),
				'href'   => $plugin->get_menu_url( 'settings', are_settings_unified() ? 'network' : 'admin' ),
				'parent' => self::ROOT_NODE_ID,
			]
		);
	}

	/**
	 * Add a list of snippets under the active and inactive statuses.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 *
	 * @return void
	 */
	private function add_snippet_listings( WP_Admin_Bar $wp_admin_bar ): void {
		$items_per_page = $this->get_snippet_limit();
		if ( $items_per_page < 1 ) {
			return;
		}

		$plugin = code_snippets();

		$snippets = array_filter(
			get_snippets(),
			static function ( Snippet $snippet ): bool {
				return ! $snippet->trashed && ! $snippet->is_condition();
			}
		);

		$active_snippets = array_values(
			array_filter(
				$snippets,
				static function ( Snippet $snippet ): bool {
					return $snippet->active;
				}
			)
		);

		$inactive_snippets = array_values(
			array_filter(
				$snippets,
				static function ( Snippet $snippet ): bool {
					return ! $snippet->active;
				}
			)
		);

		usort(
			$active_snippets,
			static function ( Snippet $a, Snippet $b ): int {
				return strcasecmp( $a->display_name, $b->display_name );
			}
		);

		usort(
			$inactive_snippets,
			static function ( Snippet $a, Snippet $b ): int {
				return strcasecmp( $a->display_name, $b->display_name );
			}
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_page = isset( $_GET[ self::ACTIVE_PAGE_QUERY_ARG ] ) ? absint( $_GET[ self::ACTIVE_PAGE_QUERY_ARG ] ) : 1;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$inactive_page = isset( $_GET[ self::INACTIVE_PAGE_QUERY_ARG ] ) ? absint( $_GET[ self::INACTIVE_PAGE_QUERY_ARG ] ) : 1;

		$active_total_pages = max( 1, (int) ceil( count( $active_snippets ) / $items_per_page ) );
		$inactive_total_pages = max( 1, (int) ceil( count( $inactive_snippets ) / $items_per_page ) );

		$active_page = max( 1, min( $active_page, $active_total_pages ) );
		$inactive_page = max( 1, min( $inactive_page, $inactive_total_pages ) );

		$active_offset = ( $active_page - 1 ) * $items_per_page;
		$inactive_offset = ( $inactive_page - 1 ) * $items_per_page;

		$active_page_snippets = array_slice( $active_snippets, $active_offset, $items_per_page );
		$inactive_page_snippets = array_slice( $inactive_snippets, $inactive_offset, $items_per_page );

		$wp_admin_bar->add_node(
			[
				'id'     => self::ROOT_NODE_ID . '-active-snippets',
				// translators: %d: number of active snippets.
				'title'  => sprintf( esc_html__( 'Active snippets (%d)', 'code-snippets' ), count( $active_snippets ) ),
				'href'   => esc_url( add_query_arg( 'status', 'active', $plugin->get_menu_url( 'manage' ) ) ),
				'parent' => self::ROOT_NODE_ID,
			]
		);

		if ( $active_total_pages > 1 ) {
			$wp_admin_bar->add_node(
				[
					'id'     => self::ROOT_NODE_ID . '-active-pagination',
					'title'  => $this->get_pagination_controls_html( 'active', $active_page, $active_total_pages, self::ACTIVE_PAGE_QUERY_ARG ),
					'parent' => self::ROOT_NODE_ID . '-active-snippets',
					'meta'   => [ 'class' => 'code-snippets-pagination-node' ],
				]
			);
		}

		foreach ( $active_page_snippets as $snippet ) {
			$wp_admin_bar->add_node(
				[
					'id'     => self::ROOT_NODE_ID . '-snippet-' . $snippet->id,
					'title'  => esc_html( $this->format_snippet_title( $snippet ) ),
					'href'   => esc_url( add_query_arg( 'id', $snippet->id, $plugin->get_menu_url( 'edit' ) ) ),
					'parent' => self::ROOT_NODE_ID . '-active-snippets',
					'meta'   => [ 'class' => 'code-snippets-snippet-item' ],
				]
			);
		}

		$wp_admin_bar->add_node(
			[
				'id'     => self::ROOT_NODE_ID . '-inactive-snippets',
				// translators: %d: number of inactive snippets.
				'title'  => sprintf( esc_html__( 'Inactive snippets (%d)', 'code-snippets' ), count( $inactive_snippets ) ),
				'href'   => esc_url( add_query_arg( 'status', 'inactive', $plugin->get_menu_url( 'manage' ) ) ),
				'parent' => self::ROOT_NODE_ID,
			]
		);

		if ( $inactive_total_pages > 1 ) {
			$wp_admin_bar->add_node(
				[
					'id'     => self::ROOT_NODE_ID . '-inactive-pagination',
					'title'  => $this->get_pagination_controls_html( 'inactive', $inactive_page, $inactive_total_pages, self::INACTIVE_PAGE_QUERY_ARG ),
					'parent' => self::ROOT_NODE_ID . '-inactive-snippets',
					'meta'   => [ 'class' => 'code-snippets-pagination-node' ],
				]
			);
		}

		foreach ( $inactive_page_snippets as $snippet ) {
			$wp_admin_bar->add_node(
				[
					'id'     => self::ROOT_NODE_ID . '-snippet-' . $snippet->id,
					'title'  => esc_html( $this->format_snippet_title( $snippet ) ),
					'href'   => esc_url( add_query_arg( 'id', $snippet->id, $plugin->get_menu_url( 'edit' ) ) ),
					'parent' => self::ROOT_NODE_ID . '-inactive-snippets',
					'meta'   => [ 'class' => 'code-snippets-snippet-item' ],
				]
			);
		}
	}

	/**
	 * Build an admin bar snippet title including type prefix.
	 *
	 * @param Snippet $snippet Snippet object.
	 *
	 * @return string
	 */
	private function format_snippet_title( Snippet $snippet ): string {
		return sprintf( '(%s) %s', strtoupper( $snippet->type ), $snippet->display_name );
	}

	/**
	 * Retrieve the number of snippets to show per page in the admin bar.
	 *
	 * @return int
	 */
	private function get_snippet_limit(): int {
		$limit = (int) get_setting( 'general', 'admin_bar_snippet_limit' );

		if ( $limit < 1 ) {
			$limit = 20;
		}

		return max( 1, (int) apply_filters( 'code_snippets/admin_bar/snippet_limit', $limit ) );
	}

	/**
	 * Build pagination controls HTML for a snippet listing submenu.
	 *
	 * @param string $status         Snippet status: "active" or "inactive".
	 * @param int    $page           Current page.
	 * @param int    $total_pages    Total pages.
	 * @param string $page_query_arg Query arg used for progressive enhancement.
	 *
	 * @return string
	 */
	private function get_pagination_controls_html( string $status, int $page, int $total_pages, string $page_query_arg ): string {
		$first_url = remove_query_arg( $page_query_arg );
		$prev_url = $page > 2 ? add_query_arg( $page_query_arg, $page - 1 ) : remove_query_arg( $page_query_arg );
		$next_url = add_query_arg( $page_query_arg, $page + 1 );
		$last_url = add_query_arg( $page_query_arg, $total_pages );

		$disabled_first = $page <= 1 ? 'true' : 'false';
		$disabled_prev = $page <= 1 ? 'true' : 'false';
		$disabled_next = $page >= $total_pages ? 'true' : 'false';
		$disabled_last = $page >= $total_pages ? 'true' : 'false';

		$html = sprintf(
			'<span class="code-snippets-pagination-controls" data-status="%1$s" data-page="%2$d" data-total-pages="%3$d" data-query-arg="%15$s">' .
			'<a class="code-snippets-pagination-button" href="%4$s" data-action="first" aria-disabled="%9$s">&laquo;</a>' .
			'<a class="code-snippets-pagination-button" href="%5$s" data-action="prev" aria-disabled="%10$s">&lsaquo; %6$s</a>' .
			'<span class="code-snippets-pagination-button code-snippets-pagination-page">%7$s (%2$d/%3$d)</span>' .
			'<a class="code-snippets-pagination-button" href="%8$s" data-action="next" aria-disabled="%11$s">%12$s &rsaquo;</a>' .
			'<a class="code-snippets-pagination-button" href="%13$s" data-action="last" aria-disabled="%14$s">&raquo;</a>' .
			'</span>',
			esc_attr( $status ),
			$page,
			$total_pages,
			esc_url( $first_url ),
			esc_url( $prev_url ),
			esc_html__( 'Back', 'code-snippets' ),
			esc_html__( 'Page', 'code-snippets' ),
			esc_url( $next_url ),
			$disabled_first,
			$disabled_prev,
			$disabled_next,
			esc_html__( 'Next', 'code-snippets' ),
			esc_url( $last_url ),
			$disabled_last,
			esc_attr( $page_query_arg )
		);

		return wp_kses(
			$html,
			[
				'span' => [
					'class'            => [],
					'data-status'      => [],
					'data-page'        => [],
					'data-total-pages' => [],
					'data-query-arg'   => [],
				],
				'a'    => [
					'class'         => [],
					'href'          => [],
					'data-action'   => [],
					'aria-disabled' => [],
				],
			]
		);
	}
}
