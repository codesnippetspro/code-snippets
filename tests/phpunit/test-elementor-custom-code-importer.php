<?php

namespace Code_Snippets\Tests;

use Code_Snippets\REST_API\Import\Plugins\Elementor_Custom_Code_Plugin_Importer;

/**
 * Tests for the Elementor Custom Code plugin importer.
 *
 * @group import
 */
class Elementor_Custom_Code_Importer_Test extends TestCase {

	/**
	 * Register a stand-in CPT so tests do not require Elementor.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		register_post_type(
			'elementor_snippet',
			[
				'public' => false,
			]
		);
	}

	/**
	 * Tear down: unregister CPT.
	 *
	 * @return void
	 */
	public function tear_down() {
		unregister_post_type( 'elementor_snippet' );
		parent::tear_down();
	}

	/**
	 * Importer lists Elementor-like posts with expected keys (Elementor Pro meta keys).
	 *
	 * @return void
	 */
	public function test_get_data_returns_table_data_and_maps_head_html_to_scope() {
		$post_id = self::factory()->post->create(
			[
				'post_type'    => 'elementor_snippet',
				'post_title'   => 'GA Tag',
				'post_content' => '',
				'post_status'  => 'publish',
			]
		);

		update_post_meta( $post_id, '_elementor_location', 'elementor_head' );
		update_post_meta( $post_id, '_elementor_code', '<meta name="test" content="1" />' );
		update_post_meta( $post_id, '_elementor_priority', 1 );

		$importer = new Elementor_Custom_Code_Plugin_Importer();
		$data = $importer->get_data();

		$this->assertCount( 1, $data );
		$row = $data[0];
		$this->assertSame( 'GA Tag', $row['table_data']['title'] );
		$this->assertSame( $post_id, $row['table_data']['id'] );
		$this->assertSame( '<meta name="test" content="1" />', $row['code'] );

		$snippet = $importer->create_snippet( $row, false );
		$this->assertNotNull( $snippet );
		$this->assertSame( 'head-content', $snippet->scope );
	}

	/**
	 * Elementor Pro location enums map to scopes for body start/end.
	 *
	 * @return void
	 */
	public function test_body_start_and_body_end_slugs_map_to_scopes() {
		$start_id = self::factory()->post->create(
			[
				'post_type'    => 'elementor_snippet',
				'post_title'   => 'Body Start',
				'post_content' => '',
				'post_status'  => 'publish',
			]
		);
		update_post_meta( $start_id, '_elementor_location', 'elementor_body_start' );
		update_post_meta( $start_id, '_elementor_code', '<div class="x"></div>' );

		$end_id = self::factory()->post->create(
			[
				'post_type'    => 'elementor_snippet',
				'post_title'   => 'Body End',
				'post_content' => '',
				'post_status'  => 'publish',
			]
		);
		update_post_meta( $end_id, '_elementor_location', 'elementor_body_end' );
		update_post_meta( $end_id, '_elementor_code', '<span></span>' );

		$importer = new Elementor_Custom_Code_Plugin_Importer();

		$start_row = $importer->get_data( [ $start_id ] )[0];
		$end_row = $importer->get_data( [ $end_id ] )[0];

		$this->assertSame( 'footer-content', $importer->create_snippet( $start_row, false )->scope );
		$this->assertSame( 'footer-content', $importer->create_snippet( $end_row, false )->scope );
	}

	/**
	 * Head + JS maps to site-head-js.
	 *
	 * @return void
	 */
	public function test_head_js_maps_to_site_head_js() {
		$post_id = self::factory()->post->create(
			[
				'post_type'    => 'elementor_snippet',
				'post_title'   => 'Head JS',
				'post_content' => '',
				'post_status'  => 'publish',
			]
		);

		update_post_meta( $post_id, '_elementor_location', 'elementor_head' );
		update_post_meta( $post_id, '_elementor_code', 'window.x=1;' );

		$importer = new Elementor_Custom_Code_Plugin_Importer();
		$row = $importer->get_data()[0];
		$snippet = $importer->create_snippet( $row, false );

		$this->assertSame( 'site-head-js', $snippet->scope );
	}

	/**
	 * Unknown location meta cannot map to a Code Snippets scope.
	 *
	 * @return void
	 */
	public function test_unknown_location_returns_null_snippet() {
		$post_id = self::factory()->post->create(
			[
				'post_type'    => 'elementor_snippet',
				'post_title'   => 'Bad location',
				'post_content' => '',
				'post_status'  => 'publish',
			]
		);

		update_post_meta( $post_id, '_elementor_location', 'unknown_future_location_value' );
		update_post_meta( $post_id, '_elementor_code', '<p>x</p>' );

		$importer = new Elementor_Custom_Code_Plugin_Importer();
		$row = $importer->get_data()[0];
		$this->assertNull( $importer->create_snippet( $row, false ) );
	}

	/**
	 * Legacy mistaken location meta still resolves when Pro meta is empty.
	 *
	 * @return void
	 */
	public function test_legacy_code_location_meta_fallback() {
		$post_id = self::factory()->post->create(
			[
				'post_type'    => 'elementor_snippet',
				'post_title'   => 'Legacy',
				'post_content' => '<p>x</p>',
				'post_status'  => 'publish',
			]
		);

		update_post_meta( $post_id, '_elementor_code_location', 'head' );
		update_post_meta( $post_id, '_elementor_code', '<p>y</p>' );

		$importer = new Elementor_Custom_Code_Plugin_Importer();
		$row = $importer->get_data()[0];

		$this->assertSame( 'head-content', $importer->create_snippet( $row, false )->scope );
	}
}
