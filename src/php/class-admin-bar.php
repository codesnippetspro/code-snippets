<?php

namespace Code_Snippets;

use WP_Admin_Bar;

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
	 * Safe mode node ID.
	 *
	 * @var string
	 */
	private const SAFE_MODE_NODE_ID = 'code-snippets-safe-mode';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_bar_menu', [ $this, 'register_nodes' ], 80 );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Enqueue styles for admin bar nodes.
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		wp_enqueue_style( 'admin-bar' );

		$css = '
			#wpadminbar .code-snippets-safe-mode > .ab-item,
			#wpadminbar .code-snippets-safe-mode.ab-item {
				background: #b32d2e;
				color: #fff;
				font-weight: 600;
			}
			#wpadminbar .code-snippets-safe-mode:hover > .ab-item,
			#wpadminbar .code-snippets-safe-mode.hover > .ab-item {
				background: #d63638;
				color: #fff;
			}
		';

		wp_add_inline_style( 'admin-bar', $css );
	}

	/**
	 * Register admin bar nodes.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 *
	 * @return void
	 */
	public function register_nodes( WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		if ( ! apply_filters( 'code_snippets/admin_bar/enabled', true ) ) {
			return;
		}

		if ( ! code_snippets()->current_user_can() ) {
			return;
		}

		$title = sprintf(
			'<span class="ab-icon dashicons dashicons-editor-code"></span><span class="ab-label">%s</span>',
			esc_html__( 'Snippets', 'code-snippets' )
		);

		$wp_admin_bar->add_node(
			[
				'id'    => self::ROOT_NODE_ID,
				'title' => wp_kses( $title, [ 'span' => [ 'class' => [] ] ] ),
				'href'  => code_snippets()->get_menu_url( 'manage' ),
			]
		);

		$this->add_safe_mode_nodes( $wp_admin_bar );
		$this->add_quick_links( $wp_admin_bar );
		$this->add_snippet_listings( $wp_admin_bar );
	}

	/**
	 * Add menu items for safe mode, if active.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 *
	 * @return void
	 */
	private function add_safe_mode_nodes( WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! code_snippets()->evaluate_functions->is_safe_mode_active() ) {
			return;
		}

		$wp_admin_bar->add_node(
			[
				'id'     => self::SAFE_MODE_NODE_ID,
				'title'  => esc_html__( 'Snippets Safe Mode Active', 'code-snippets' ),
				'href'   => 'https://help.codesnippets.pro/article/12-safe-mode',
				'parent' => 'top-secondary',
				'meta'   => [
					'class'  => 'code-snippets-safe-mode',
					'target' => '_blank',
					'rel'    => 'noopener noreferrer',
				],
			]
		);

		$wp_admin_bar->add_node(
			[
				'id'     => self::SAFE_MODE_NODE_ID . '-submenu',
				'title'  => esc_html__( 'Safe Mode Active', 'code-snippets' ),
				'href'   => 'https://help.codesnippets.pro/article/12-safe-mode',
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
			'php'  => _x( 'Function', 'snippet type', 'code-snippets' ),
			'html' => _x( 'Content', 'snippet type', 'code-snippets' ),
			'css'  => _x( 'Style', 'snippet type', 'code-snippets' ),
			'js'   => _x( 'Script', 'snippet type', 'code-snippets' ),
			'cond' => _x( 'Condition', 'snippet type', 'code-snippets' ),
		];

		$types = array_intersect_key( $types, array_flip( Snippet::get_types() ) );

		foreach ( $types as $type => $label ) {
			$wp_admin_bar->add_node(
				[
					'id'     => self::ROOT_NODE_ID . "-add-$type",
					'title'  => esc_html( $label ),
					'href'   => esc_url( add_query_arg( 'type', $type, $plugin->get_menu_url( 'add' ) ) ),
					'parent' => self::ROOT_NODE_ID . '-add',
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

		$settings_context = Settings\are_settings_unified() ? 'network' : 'admin';

		$wp_admin_bar->add_node(
			[
				'id'     => self::ROOT_NODE_ID . '-settings',
				'title'  => esc_html_x( 'Settings', 'snippets', 'code-snippets' ),
				'href'   => $plugin->get_menu_url( 'settings', $settings_context ),
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
		$max_items = (int) apply_filters( 'code_snippets/admin_bar/snippet_limit', 25 );
		if ( $max_items < 1 ) {
			return;
		}

		$plugin = code_snippets();

		$snippets = array_filter(
			get_snippets(),
			static function ( Snippet $snippet ): bool {
				return ! $snippet->is_trashed();
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

		$wp_admin_bar->add_node(
			[
				'id'     => self::ROOT_NODE_ID . '-active-snippets',
				'title'  => sprintf(
					/* translators: %d: number of active snippets. */
					esc_html__( 'Active Snippets (%d)', 'code-snippets' ),
					count( $active_snippets )
				),
				'href'   => esc_url( add_query_arg( 'status', 'active', $plugin->get_menu_url( 'manage' ) ) ),
				'parent' => self::ROOT_NODE_ID,
			]
		);

		foreach ( array_slice( $active_snippets, 0, $max_items ) as $snippet ) {
			$wp_admin_bar->add_node(
				[
					'id'     => self::ROOT_NODE_ID . '-snippet-' . $snippet->id,
					'title'  => esc_html( $snippet->display_name ),
					'href'   => esc_url( $plugin->get_snippet_edit_url( $snippet->id ) ),
					'parent' => self::ROOT_NODE_ID . '-active-snippets',
				]
			);
		}

		$wp_admin_bar->add_node(
			[
				'id'     => self::ROOT_NODE_ID . '-inactive-snippets',
				'title'  => sprintf(
					/* translators: %d: number of inactive snippets. */
					esc_html__( 'Inactive Snippets (%d)', 'code-snippets' ),
					count( $inactive_snippets )
				),
				'href'   => esc_url( add_query_arg( 'status', 'inactive', $plugin->get_menu_url( 'manage' ) ) ),
				'parent' => self::ROOT_NODE_ID,
			]
		);

		foreach ( array_slice( $inactive_snippets, 0, $max_items ) as $snippet ) {
			$wp_admin_bar->add_node(
				[
					'id'     => self::ROOT_NODE_ID . '-snippet-' . $snippet->id,
					'title'  => esc_html( $snippet->display_name ),
					'href'   => esc_url( $plugin->get_snippet_edit_url( $snippet->id ) ),
					'parent' => self::ROOT_NODE_ID . '-inactive-snippets',
				]
			);
		}
	}
}
