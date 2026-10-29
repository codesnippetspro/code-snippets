<?php

namespace Code_Snippets\Integration;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use WP_UnitTest_Factory;
use function Code_Snippets\save_snippet;

/**
 * Tests for the snippet shortcodes.
 *
 * @group shortcodes
 */
class Shortcodes_Test extends UnitTestCase {

	/**
	 * Marker string stored in the fixture snippet code.
	 */
	private const MARKER = 'MARKER-4f1c';

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static int $admin_id;

	/**
	 * Contributor user ID.
	 *
	 * @var int
	 */
	protected static int $contributor_id;

	/**
	 * Create the fixture users.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$contributor_id = $factory->user->create( [ 'role' => 'contributor' ] );

		if ( is_multisite() ) {
			grant_super_admin( self::$admin_id );
		}
	}

	/**
	 * Save an inactive PHP snippet containing the marker string.
	 *
	 * @param bool $network Whether to save the snippet to the network table.
	 *
	 * @return int Snippet ID.
	 */
	private function create_snippet( bool $network = false ): int {
		$snippet = new Snippet(
			[
				'name'    => 'Private snippet',
				'code'    => '// ' . self::MARKER,
				'scope'   => 'global',
				'active'  => false,
				'network' => $network,
			]
		);

		return save_snippet( $snippet )->id;
	}

	/**
	 * Render shortcode content as part of a post written by the given author.
	 *
	 * @param string $content   Post content.
	 * @param int    $author_id Post author ID.
	 *
	 * @return string Rendered content.
	 */
	private function render_as_post( string $content, int $author_id ): string {
		$post_id = self::factory()->post->create(
			[
				'post_author'  => $author_id,
				'post_content' => $content,
				'post_status'  => 'draft',
			]
		);

		$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return do_shortcode( $content );
	}

	/**
	 * Data provider for the shortcode tags that can render snippet source.
	 *
	 * @return array<string, array<string>>
	 */
	public function source_shortcode_tags(): array {
		return [
			'source shortcode'  => [ Shortcodes::SOURCE_SHORTCODE ],
			'content shortcode' => [ Shortcodes::CONTENT_SHORTCODE ],
		];
	}

	/**
	 * A post written by an administrator renders the snippet source.
	 *
	 * @dataProvider source_shortcode_tags
	 *
	 * @param string $tag Shortcode tag.
	 *
	 * @return void
	 */
	public function test_admin_post_renders_source( string $tag ): void {
		$id = $this->create_snippet();

		$output = $this->render_as_post( "[$tag id=$id]", self::$admin_id );

		$this->assertStringContainsString( self::MARKER, $output );
	}

	/**
	 * A post written by a contributor does not render the snippet source.
	 *
	 * @dataProvider source_shortcode_tags
	 *
	 * @param string $tag Shortcode tag.
	 *
	 * @return void
	 */
	public function test_contributor_post_does_not_render_source( string $tag ): void {
		$id = $this->create_snippet();

		$output = $this->render_as_post( "[$tag id=$id]", self::$contributor_id );

		$this->assertStringNotContainsString( self::MARKER, $output );
	}

	/**
	 * Source rendered outside a post is not shown.
	 *
	 * @return void
	 */
	public function test_source_outside_post_is_not_rendered(): void {
		$id = $this->create_snippet();
		unset( $GLOBALS['post'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$output = do_shortcode( '[' . Shortcodes::SOURCE_SHORTCODE . " id=$id]" );

		$this->assertStringNotContainsString( self::MARKER, $output );
	}

	/**
	 * A post written by a site administrator does not render network snippet source.
	 *
	 * @return void
	 */
	public function test_site_admin_post_does_not_render_network_source(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$admin_id );
		$id = $this->create_snippet( true );
		$site_admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		$output = $this->render_as_post( '[' . Shortcodes::SOURCE_SHORTCODE . " id=$id network=1]", $site_admin_id );

		$this->assertStringNotContainsString( self::MARKER, $output );
	}

	/**
	 * A post written by a network administrator renders network snippet source.
	 *
	 * @return void
	 */
	public function test_super_admin_post_renders_network_source(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$admin_id );
		$id = $this->create_snippet( true );

		$output = $this->render_as_post( '[' . Shortcodes::SOURCE_SHORTCODE . " id=$id network=1]", self::$admin_id );

		$this->assertStringContainsString( self::MARKER, $output );
	}
}
