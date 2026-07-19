<?php

namespace Code_Snippets\Model;

use Code_Snippets\UnitTestCase;

/**
 * Tests for Cloud_Snippets normalisation of cloud API payloads.
 *
 * @group cloud
 */
class Cloud_Snippets_Test extends UnitTestCase {

	/**
	 * A full response envelope is normalised into snippets and pagination metadata.
	 *
	 * @return void
	 */
	public function test_normalizes_full_envelope(): void {
		$result = Cloud_Snippets::unpack_api_response(
			[
				'snippets'          => [
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
	 * Snippets are read from the `snippets` key of the envelope.
	 *
	 * @return void
	 */
	public function test_reads_snippets_key(): void {
		$result = Cloud_Snippets::unpack_api_response(
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
	 * The `snippets` key is authoritative; a legacy `data` key is ignored.
	 *
	 * @return void
	 */
	public function test_legacy_data_key_is_ignored(): void {
		$result = Cloud_Snippets::unpack_api_response(
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
						'name' => 'Used',
					],
				],
				'meta'     => [ 'total' => 1 ],
			]
		);

		$this->assertCount( 1, $result->snippets );
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
		$result = Cloud_Snippets::unpack_api_response(
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
	 * Null and empty payloads respond with null.
	 *
	 * @return void
	 */
	public function test_empty_input_returns_null(): void {
		$result = Cloud_Snippets::unpack_api_response( [] );
		$this->assertNull( $result );
	}

	/**
	 * Passing null results in a null result.
	 *
	 * @return void
	 */
	public function test_null_input_returns_null(): void {
		$result = Cloud_Snippets::unpack_api_response( null );
		$this->assertNull( $result );
	}

	/**
	 * Snippet data without a `meta` block keeps the default totals and filters.
	 *
	 * @return void
	 */
	public function test_missing_meta_keeps_default_totals(): void {
		$result = Cloud_Snippets::unpack_api_response(
			[
				'snippets' => [
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

	/**
	 * Malformed remote description values are normalised to strings at the model boundary.
	 *
	 * @return void
	 */
	public function test_malformed_description_is_normalised_to_string(): void {
		$this->assertSame( 'ok', ( new Cloud_Snippet( [ 'description' => 'ok' ] ) )->description );
		$this->assertSame( '123', ( new Cloud_Snippet( [ 'description' => 123 ] ) )->description );
		$this->assertSame( '', ( new Cloud_Snippet( [ 'description' => null ] ) )->description );
		$this->assertSame( '', ( new Cloud_Snippet( [ 'description' => [ 'nested' ] ] ) )->description );
		$this->assertSame( '', ( new Cloud_Snippet() )->description );
	}
}
