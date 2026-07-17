<?php

namespace Code_Snippets\Admin\Menus;

use function Code_Snippets\code_snippets;
use function Code_Snippets\Settings\get_setting;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * Registers manage menu pages and their shared stylesheet.
 */
class Manage_Menu_Registration {

	/**
	 * Register the top-level Snippets menu.
	 *
	 * @param Manage_Menu $menu Manage menu instance.
	 *
	 * @return void
	 */
	public function register_top_level( Manage_Menu $menu ): void {
		add_menu_page(
			__( 'Snippets', 'code-snippets' ),
			_x( 'Snippets', 'top-level menu label', 'code-snippets' ),
			code_snippets()->get_cap(),
			code_snippets()->get_menu_slug(),
			[ $menu, 'render' ],
			'none', // Added through CSS as a mask to prevent loading 'blinking'.
			apply_filters( 'code_snippets/admin/menu_position', is_network_admin() ? 21 : 67 )
		);
	}

	/**
	 * Register the upgrade menu item.
	 *
	 * @param Manage_Menu $menu Manage menu instance.
	 *
	 * @return void
	 */
	public function register_upgrade_menu( Manage_Menu $menu ): void {
		if ( code_snippets()->licensing->is_licensed() || get_setting( 'general', 'hide_upgrade_menu' ) ) {
			return;
		}

		$menu_title = sprintf(
			'<span class="button button-primary code-snippets-upgrade-button">%s %s</span>',
			_x( 'Go Pro', 'top-level menu label', 'code-snippets' ),
			'<span class="dashicons dashicons-external" aria-hidden="true"></span>'
		);

		$hook = add_submenu_page(
			code_snippets()->get_menu_slug(),
			__( 'Upgrade to Pro', 'code-snippets' ),
			$menu_title,
			code_snippets()->get_cap(),
			'code_snippets_upgrade',
			'__return_empty_string',
			100
		);

		add_action( "load-$hook", [ $menu, 'load_upgrade_menu' ] );
	}

	/**
	 * Enqueue the admin menu icon stylesheet.
	 *
	 * @return void
	 */
	public function enqueue_menu_css(): void {
		wp_enqueue_style(
			'code-snippets-menu',
			plugins_url( 'dist/menu.css', PLUGIN_FILE ),
			[],
			PLUGIN_VERSION
		);
	}

	/**
	 * Redirect the upgrade menu to the product site.
	 *
	 * @return void
	 */
	public function load_upgrade_menu(): void {
		wp_safe_redirect( 'https://snipco.de/JE2f' );
		exit;
	}

	/**
	 * Register the compact menu page under Tools.
	 *
	 * @param Manage_Menu $menu Manage menu instance.
	 *
	 * @return void
	 */
	public function register_compact_menu( Manage_Menu $menu ): void {
		if ( ! code_snippets()->is_compact_menu() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Value is matched to known classes.
		$sub = code_snippets()->get_menu_slug( isset( $_GET['sub'] ) ? sanitize_key( $_GET['sub'] ) : 'snippets' );
		$classmap = [
			'snippets'             => 'manage',
			'add-snippet'          => 'edit',
			'edit-snippet'         => 'edit',
			'import-code-snippets' => 'import',
			'snippets-settings'    => 'settings',
		];
		$menus = code_snippets()->admin->menus;
		$class = isset( $classmap[ $sub ], $menus[ $classmap[ $sub ] ] ) ? $menus[ $classmap[ $sub ] ] : $menu;

		$hook = add_submenu_page(
			'tools.php',
			__( 'Snippets', 'code-snippets' ),
			_x( 'Snippets', 'tools submenu label', 'code-snippets' ),
			code_snippets()->get_cap(),
			code_snippets()->get_menu_slug(),
			[ $class, 'render' ]
		);

		add_action( 'load-' . $hook, [ $class, 'load' ] );
	}
}
