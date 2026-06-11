<?php

namespace Code_Snippets\Tests;

use Code_Snippets\Model\Cloud_Snippet;
use Code_Snippets\Model\Cloud_Snippets;

/**
 * Tests for Cloud_Snippets normalisation of cloud API payloads.
 *
 * @group cloud
 */
class Cloud_Snippets_Test extends TestCase {

	/**
	 * A full response envelope is normalised into snippets and pagination metadata.
	 *
	 * @return void
	 */
	public function test_normalizes_full_envelope(): void {
		$result = new Cloud_Snippets(
			[
				'data'              => [
					[
						'id'   => 1,
						'name' => 'First',
					],
					[
						'id'   => 2,
						'name' => 'Second',
					],
				],
				'meta'              => [
					'total'       => 42,
					'total_pages' => 5,
					'page'        => 3,
				],
				'available_filters' => [
					'types' => [
						[
							'id'   => 2,
							'name' => 'PHP',
						],
					],
				],
				'cloud_id_rev'      => [ '1' => 7 ],
			]
		);

		$this->assertCount( 2, $result->snippets );
		$this->assertContainsOnlyInstancesOf( Cloud_Snippet::class, $result->snippets );
		$this->assertSame( 42, $result->total_snippets );
		$this->assertSame( 5, $result->total_pages );
		$this->assertSame( 2, $result->page, 'Page should be stored as a zero-based offset of the API page.' );
		$this->assertNotEmpty( $result->available_filters );
		$this->assertSame( [ '1' => 7 ], $result->cloud_id_rev );
	}

	/**
	 * The `snippets` key is used when no `data` key is present.
	 *
	 * @return void
	 */
	public function test_falls_back_to_snippets_key(): void {
		$result = new Cloud_Snippets(
			[
				'snippets' => [
					[
						'id'   => 1,
						'name' => 'Only',
					],
				],
				'meta'     => [ 'total' => 1 ],
			]
		);

		$this->assertCount( 1, $result->snippets );
		$this->assertSame( 1, $result->total_snippets );
	}

	/**
	 * The `data` key takes precedence over the `snippets` key when both are present.
	 *
	 * @return void
	 */
	public function test_data_key_takes_precedence_over_snippets_key(): void {
		$result = new Cloud_Snippets(
			[
				'data'     => [
					[
						'id'   => 1,
						'name' => 'A',
					],
					[
						'id'   => 2,
						'name' => 'B',
					],
				],
				'snippets' => [
					[
						'id'   => 9,
						'name' => 'Ignored',
					],
				],
				'meta'     => [ 'total' => 2 ],
			]
		);

		$this->assertCount( 2, $result->snippets );
	}

	/**
	 * A bare list of snippets, without the response envelope, yields no snippets.
	 *
	 * Guards against regressing to passing the unpacked `data` array straight into the model:
	 * such an array has no `data`/`snippets`/`meta` keys and normalises to an empty result,
	 * which is what made cloud search return nothing.
	 *
	 * @return void
	 */
	public function test_bare_list_without_envelope_yields_no_snippets(): void {
		$result = new Cloud_Snippets(
			[
				[
					'id'   => 1,
					'name' => 'First',
				],
				[
					'id'   => 2,
					'name' => 'Second',
				],
			]
		);

		$this->assertCount( 0, $result->snippets );
		$this->assertSame( 0, $result->total_snippets );
	}

	/**
	 * Null and empty payloads fall back to the default values.
	 *
	 * @return void
	 */
	public function test_empty_input_uses_defaults(): void {
		foreach ( [ null, [] ] as $input ) {
			$result = new Cloud_Snippets( $input );

			$this->assertCount( 0, $result->snippets );
			$this->assertSame( 0, $result->total_snippets );
			$this->assertSame( 0, $result->total_pages );
			$this->assertSame( 0, $result->page );
			$this->assertSame( [], $result->available_filters );
		}
	}

	/**
	 * Snippet data without a `meta` block keeps the default totals and filters.
	 *
	 * @return void
	 */
	public function test_missing_meta_keeps_default_totals(): void {
		$result = new Cloud_Snippets(
			[
				'data' => [
					[
						'id'   => 1,
						'name' => 'Lonely',
					],
				],
			]
		);

		$this->assertCount( 1, $result->snippets );
		$this->assertSame( 0, $result->total_snippets );
		$this->assertSame( 0, $result->page );
		$this->assertSame( [], $result->available_filters );
	}
}
