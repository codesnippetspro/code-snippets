<?php
/**
 * Tests that the manage screen's list request asks for the right fields.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\REST_API;

use Code_Snippets\UnitTestCase;
use WP_REST_Request;
use WP_REST_Server;

/**
 * The snippets list is fetched without snippet code, because a list carrying
 * full code bodies grows without bound as a library does and is the largest
 * thing the manage screen transfers. The field list that does that lives in
 * JavaScript, so nothing in PHP stops a new schema property from being added
 * without it, which would silently stop that property reaching the browser.
 *
 * This checks the two lists still agree.
 *
 * @group rest-api
 */
class REST_API_Snippets_List_Fields_Test extends UnitTestCase {

	/**
	 * Path of the file holding the field list.
	 */
	private const SOURCE_FILE = 'src/js/hooks/useSnippetsAPI.tsx';

	/**
	 * The only schema property the list deliberately leaves out.
	 */
	private const OMITTED_FIELD = 'code';

	/**
	 * Set up the REST server.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	/**
	 * Property names the snippets item schema advertises.
	 *
	 * Read through the public schema route rather than by building a controller,
	 * so this holds wherever the controller's constructor differs.
	 *
	 * @return string[]
	 */
	private function schema_properties(): array {
		$response = rest_get_server()->dispatch(
			new WP_REST_Request( 'GET', '/code-snippets/v1/snippets/schema' )
		);

		$this->assertSame( 200, $response->get_status(), 'the snippets schema route should be readable' );

		$schema = $response->get_data();
		$this->assertArrayHasKey( 'properties', $schema, 'the schema should describe its properties' );

		return array_keys( $schema['properties'] );
	}

	/**
	 * Field names the list request asks for.
	 *
	 * @return string[]
	 */
	private function requested_fields(): array {
		$path = dirname( __DIR__, 3 ) . '/' . self::SOURCE_FILE;
		$this->assertFileExists( $path, 'the list request should still be defined here' );

		$source = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a source file in a test.
		$matched = preg_match( '/const LIST_FIELDS\s*=\s*\[(.*?)\]/s', $source, $matches );

		$this->assertSame( 1, $matched, 'LIST_FIELDS should still be a literal array in ' . self::SOURCE_FILE );

		preg_match_all( "/'([a-z_]+)'/", $matches[1], $fields );

		return $fields[1];
	}

	/**
	 * Every schema property except the code body is requested, and nothing else is.
	 *
	 * @return void
	 */
	public function test_list_request_asks_for_every_field_except_code(): void {
		$expected = array_values( array_diff( $this->schema_properties(), [ self::OMITTED_FIELD ] ) );
		$requested = $this->requested_fields();

		sort( $expected );
		sort( $requested );

		$this->assertSame(
			$expected,
			$requested,
			'LIST_FIELDS in ' . self::SOURCE_FILE . ' is out of step with the snippets schema. '
			. 'A property added to the schema has to be added there too, or it will not reach the manage screen.'
		);
	}

	/**
	 * The code body is left out on purpose, so guard against it creeping back in.
	 *
	 * @return void
	 */
	public function test_list_request_does_not_ask_for_code(): void {
		$this->assertContains(
			self::OMITTED_FIELD,
			$this->schema_properties(),
			'the schema should still offer a code property for the editor to request'
		);

		$this->assertNotContains(
			self::OMITTED_FIELD,
			$this->requested_fields(),
			'the snippets list should not request snippet code; it is fetched separately when a search needs it'
		);
	}
}
