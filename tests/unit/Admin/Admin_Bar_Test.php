<?php

namespace Code_Snippets\Admin;

use Code_Snippets\AdminUnitTestCase;
use Code_Snippets\Integration\Admin_Bar;
use Code_Snippets\Model\Snippet;
use ReflectionClass;
use WP_Admin_Bar;
use function Code_Snippets\code_snippets;
use function Code_Snippets\save_snippet;
use function Code_Snippets\Settings\update_setting;

/**
 * Tests for the Admin Bar "Snippets QuickNav" integration.
 *
 * @group admin-bar
 */
class Admin_Bar_Test extends AdminUnitTestCase {

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_filter( 'show_admin_bar', '__return_true' );

		update_setting( 'general', 'enable_admin_bar', true );
		update_setting( 'general', 'admin_bar_snippet_limit', 20 );

		remove_all_filters( 'code_snippets/execute_snippets' );
		remove_all_filters( 'code_snippets/admin_bar/enabled' );

		$this->truncate_snippets_table();
		unset( $_GET['code_snippets_ab_active_page'], $_GET['code_snippets_ab_inactive_page'] );
	}

	/**
	 * Clear all snippets from the database.
	 *
	 * @return void
	 */
	private function truncate_snippets_table(): void {
		global $wpdb;
		$table_name = code_snippets()->db->get_table_name();
		$wpdb->query( "TRUNCATE TABLE $table_name" );
	}

	/**
	 * Create a snippet test fixture.
	 *
	 * @param string $name   Snippet name.
	 * @param bool   $active Whether the snippet should be active.
	 * @param string $type   Snippet type.
	 *
	 * @return Snippet
	 */
	private function create_snippet( string $name, bool $active, string $type = 'php' ): Snippet {
		$scope = 'html' === $type ?
			'content' :
			( 'css' === $type ?
				'site-css' :
				( 'js' === $type ?
					'site-footer-js' :
					( 'cond' === $type ? 'condition' : 'global' )
				)
			);

		$code = 'html' === $type ?
			"<p>$name</p>\n" :
			"<?php\n// $name\n";

		$snippet = new Snippet(
			[
				'name'   => $name,
				'code'   => $code,
				'scope'  => $scope,
				'active' => $active,
				'tags'   => [],
			]
		);

		save_snippet( $snippet );
		return $snippet;
	}

	/**
	 * Build an isolated admin bar instance for assertions.
	 *
	 * @return WP_Admin_Bar
	 */
	private function build_admin_bar(): WP_Admin_Bar {
		if ( ! class_exists( 'WP_Admin_Bar' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		}

		$wp_admin_bar = new WP_Admin_Bar();

		if ( method_exists( $wp_admin_bar, 'initialize' ) ) {
			$wp_admin_bar->initialize();
		}

		return $wp_admin_bar;
	}

	/**
	 * Read nodes from a WP_Admin_Bar instance.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 *
	 * @return array
	 */
	private function get_nodes( WP_Admin_Bar $wp_admin_bar ): array {
		if ( method_exists( $wp_admin_bar, 'get_nodes' ) ) {
			return (array) $wp_admin_bar->get_nodes();
		}

		$ref = new ReflectionClass( $wp_admin_bar );
		if ( $ref->hasProperty( 'nodes' ) ) {
			$prop = $ref->getProperty( 'nodes' );
			$prop->setAccessible( true );
			return (array) $prop->getValue( $wp_admin_bar );
		}

		return [];
	}

	/**
	 * Admin bar menu is hidden when the setting is disabled.
	 *
	 * @return void
	 */
	public function test_admin_bar_menu_is_disabled_by_setting(): void {
		update_setting( 'general', 'enable_admin_bar', false );

		$wp_admin_bar = $this->build_admin_bar();
		$admin_bar = new Admin_Bar();
		$admin_bar->register_nodes( $wp_admin_bar );

		$this->assertNull( $wp_admin_bar->get_node( 'code-snippets' ) );
	}

	/**
	 * Safe mode indicator is shown even if the main Snippets menu is disabled.
	 *
	 * @return void
	 */
	public function test_safe_mode_indicator_is_shown_even_when_menu_disabled(): void {
		update_setting( 'general', 'enable_admin_bar', false );
		add_filter( 'code_snippets/execute_snippets', '__return_false' );

		$wp_admin_bar = $this->build_admin_bar();
		$admin_bar = new Admin_Bar();
		$admin_bar->register_nodes( $wp_admin_bar );

		$this->assertNull( $wp_admin_bar->get_node( 'code-snippets' ) );
		$this->assertNotNull( $wp_admin_bar->get_node( 'code-snippets-safe-mode' ) );
	}

	/**
	 * Pro-only snippet types should be disabled in the free plugin.
	 *
	 * @return void
	 */
	public function test_pro_types_are_disabled_when_unlicensed(): void {
		update_setting( 'general', 'enable_admin_bar', true );

		$wp_admin_bar = $this->build_admin_bar();
		$admin_bar = new Admin_Bar();
		$admin_bar->register_nodes( $wp_admin_bar );

		$php = $wp_admin_bar->get_node( 'code-snippets-add-php' );
		$this->assertNotNull( $php );
		$this->assertStringContainsString( 'type=php', $php->href );

		$css = $wp_admin_bar->get_node( 'code-snippets-add-css' );
		$this->assertNotNull( $css );
		$this->assertStringContainsString( 'page=code_snippets_upgrade', $css->href );
		$this->assertArrayHasKey( 'class', $css->meta );
		$this->assertStringContainsString( 'code-snippets-disabled', (string) $css->meta['class'] );
	}

	/**
	 * Active/inactive snippet listings paginate and accept progressive enhancement query args.
	 *
	 * @return void
	 */
	public function test_snippet_listings_paginate_and_respect_query_arg(): void {
		update_setting( 'general', 'admin_bar_snippet_limit', 2 );

		$this->create_snippet( 'QuickNav Active A', true );
		$this->create_snippet( 'QuickNav Active B', true );
		$this->create_snippet( 'QuickNav Active C', true );

		$this->create_snippet( 'QuickNav Inactive A', false );
		$this->create_snippet( 'QuickNav Inactive B', false );
		$this->create_snippet( 'QuickNav Inactive Z HTML', false, 'html' );

		$wp_admin_bar = $this->build_admin_bar();
		$admin_bar = new Admin_Bar();
		$admin_bar->register_nodes( $wp_admin_bar );

		$this->assertNotNull( $wp_admin_bar->get_node( 'code-snippets-active-pagination' ) );
		$this->assertNotNull( $wp_admin_bar->get_node( 'code-snippets-inactive-pagination' ) );

		$nodes = $this->get_nodes( $wp_admin_bar );

		$active_children = array_filter(
			$nodes,
			static fn( $node ) => isset( $node->parent ) && 'code-snippets-active-snippets' === $node->parent
		);

		$active_titles = array_map(
			static fn( $node ) => (string) ( $node->title ?? '' ),
			array_values( $active_children )
		);

		$active_titles = array_values( array_filter( $active_titles, static fn( $title ) => false !== strpos( $title, 'QuickNav Active' ) ) );
		$this->assertCount( 2, $active_titles );
		$this->assertStringContainsString( '(PHP) QuickNav Active A', $active_titles[0] );
		$this->assertStringContainsString( '(PHP) QuickNav Active B', $active_titles[1] );

		$_GET['code_snippets_ab_active_page'] = 2;

		$wp_admin_bar_page_2 = $this->build_admin_bar();
		$admin_bar->register_nodes( $wp_admin_bar_page_2 );

		$nodes_page_2 = $this->get_nodes( $wp_admin_bar_page_2 );
		$active_children_page_2 = array_filter(
			$nodes_page_2,
			static fn( $node ) => isset( $node->parent ) && 'code-snippets-active-snippets' === $node->parent
		);

		$active_titles_page_2 = array_map(
			static fn( $node ) => (string) ( $node->title ?? '' ),
			array_values( $active_children_page_2 )
		);

		$active_titles_page_2 = array_values( array_filter( $active_titles_page_2, static fn( $title ) => false !== strpos( $title, 'QuickNav Active' ) ) );
		$this->assertCount( 1, $active_titles_page_2 );
		$this->assertStringContainsString( '(PHP) QuickNav Active C', $active_titles_page_2[0] );

		$_GET['code_snippets_ab_inactive_page'] = 2;

		$wp_admin_bar_inactive_page_2 = $this->build_admin_bar();
		$admin_bar->register_nodes( $wp_admin_bar_inactive_page_2 );

		$nodes_inactive_page_2 = $this->get_nodes( $wp_admin_bar_inactive_page_2 );
		$inactive_children_page_2 = array_filter(
			$nodes_inactive_page_2,
			static fn( $node ) => isset( $node->parent ) && 'code-snippets-inactive-snippets' === $node->parent
		);

		$inactive_titles_page_2 = array_map(
			static fn( $node ) => (string) ( $node->title ?? '' ),
			array_values( $inactive_children_page_2 )
		);

		$inactive_titles_page_2 = array_values(
			array_filter( $inactive_titles_page_2, static fn( $title ) => false !== strpos( $title, 'QuickNav Inactive' ) )
		);

		$this->assertCount( 1, $inactive_titles_page_2 );
		$this->assertStringContainsString( '(HTML) QuickNav Inactive Z HTML', $inactive_titles_page_2[0] );
	}

	/**
	 * Admin bar menu can be enabled via filter even when the setting is disabled.
	 *
	 * @return void
	 */
	public function test_admin_bar_menu_can_be_enabled_via_filter(): void {
		update_setting( 'general', 'enable_admin_bar', false );
		add_filter( 'code_snippets/admin_bar/enabled', '__return_true' );

		$wp_admin_bar = $this->build_admin_bar();
		$admin_bar = new Admin_Bar();
		$admin_bar->register_nodes( $wp_admin_bar );

		$this->assertNotNull( $wp_admin_bar->get_node( 'code-snippets' ) );
	}

	/**
	 * QuickNav nodes include Manage status quick links.
	 *
	 * @return void
	 */
	public function test_manage_quick_links_are_registered(): void {
		$wp_admin_bar = $this->build_admin_bar();
		$admin_bar = new Admin_Bar();
		$admin_bar->register_nodes( $wp_admin_bar );

		$this->assertNotNull( $wp_admin_bar->get_node( 'code-snippets-manage' ) );
		$this->assertNotNull( $wp_admin_bar->get_node( 'code-snippets-status-all' ) );
		$this->assertNotNull( $wp_admin_bar->get_node( 'code-snippets-status-active' ) );
		$this->assertNotNull( $wp_admin_bar->get_node( 'code-snippets-status-inactive' ) );
	}

	/**
	 * Snippet listing links use the edit screen ID query arg.
	 *
	 * @return void
	 */
	public function test_snippet_listing_links_use_id_query_arg(): void {
		$active = $this->create_snippet( 'QuickNav Edit Link Active', true );
		$inactive = $this->create_snippet( 'QuickNav Edit Link Inactive', false );

		$wp_admin_bar = $this->build_admin_bar();
		$admin_bar = new Admin_Bar();
		$admin_bar->register_nodes( $wp_admin_bar );

		$active_node = $wp_admin_bar->get_node( 'code-snippets-snippet-' . $active->id );
		$inactive_node = $wp_admin_bar->get_node( 'code-snippets-snippet-' . $inactive->id );

		$this->assertNotNull( $active_node );
		$this->assertNotNull( $inactive_node );
		$this->assertStringContainsString( 'id=' . $active->id, $active_node->href );
		$this->assertStringNotContainsString( 'edit=' . $active->id, $active_node->href );
		$this->assertStringContainsString( 'id=' . $inactive->id, $inactive_node->href );
		$this->assertStringNotContainsString( 'edit=' . $inactive->id, $inactive_node->href );
	}

	/**
	 * Safe mode documentation link is registered under the Snippets root node.
	 *
	 * @return void
	 */
	public function test_safe_mode_docs_link_is_registered(): void {
		$wp_admin_bar = $this->build_admin_bar();
		$admin_bar = new Admin_Bar();
		$admin_bar->register_nodes( $wp_admin_bar );

		$node = $wp_admin_bar->get_node( 'code-snippets-safe-mode-doc' );
		$this->assertNotNull( $node );
		$this->assertStringContainsString( 'https://snipco.de/safe-mode', $node->href );
	}
}
