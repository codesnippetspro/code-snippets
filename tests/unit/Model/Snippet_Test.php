<?php

namespace Code_Snippets\Model;

use Code_Snippets\UnitTestCase;
use function Code_Snippets\get_snippet;
use function Code_Snippets\save_snippet;

/**
 * Tests for snippet field normalization.
 */
class Snippet_Test extends UnitTestCase {

	/**
	 * Legacy descriptions round-trip through storage unchanged.
	 *
	 * @return void
	 */
	public function test_description_round_trips_without_sanitization(): void {
		$description = '<strong>Allowed</strong><script>alert("unsafe")</script>';
		$saved = save_snippet(
			new Snippet(
				[
					'name' => 'Legacy description',
					'desc' => $description,
				]
			)
		);
		$snippet = get_snippet( $saved->id );

		$this->assertSame( $description, $snippet->desc );
	}

	/**
	 * The modification date is exposed to clients with an explicit UTC offset.
	 *
	 * Stored as 'Y-m-d H:i:s' in UTC, the raw value carries no offset, so a
	 * browser reads it as local time and shows a snippet saved moments ago as
	 * hours into the future on any site behind UTC.
	 *
	 * @return void
	 */
	public function test_modified_is_exposed_as_iso_8601_utc(): void {
		$snippet = new Snippet(
            [
				'name' => 'Timezone',
				'modified' => '2026-08-27 07:35:20',
			]
        );

		$this->assertSame( '2026-08-27T07:35:20+00:00', $snippet->modified_iso );
		$this->assertNotSame(
			$snippet->modified,
			$snippet->modified_iso,
			'the stored value has no offset, so it must not be handed to clients as-is'
		);
	}

	/**
	 * A snippet with no modification date exposes null rather than an epoch date.
	 *
	 * @return void
	 */
	public function test_modified_iso_is_null_when_unset(): void {
		$snippet = new Snippet( [ 'name' => 'Never modified' ] );

		$this->assertNull( $snippet->modified_iso );
	}
}
