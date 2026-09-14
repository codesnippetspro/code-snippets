<?php

namespace Code_Snippets\Core;

use Code_Snippets\UnitTestCase;
use ReflectionMethod;

/**
 * Tests for the identifiers-only license snapshot exposed to Code Snippets Cloud.
 *
 * The mapping is exercised directly (via the private static mapper) so the
 * registered/expired/not-registered states are deterministic without a live
 * Freemius connection.
 *
 * @group licensing
 */
class Licensing_Cloud_Identifiers_Test extends UnitTestCase {

	/**
	 * The exact set of keys the cloud license object is allowed to contain.
	 *
	 * @var array<int, string>
	 */
	private const ALLOWED_KEYS = [
		'is_registered',
		'has_valid_license',
		'is_paying',
		'license_id',
		'install_id',
		'freemius_user_id',
		'plan_id',
	];

	/**
	 * Invoke the private static mapper with raw Freemius-like entities.
	 *
	 * @param bool        $is_registered     Registration flag.
	 * @param bool        $has_valid_license Valid-license flag.
	 * @param bool        $is_paying         Paying flag.
	 * @param object|null $license           License entity or null.
	 * @param object|null $site              Site/install entity or null.
	 * @param object|null $user              User entity or null.
	 *
	 * @return array<string, bool|int|null>
	 */
	private function map( bool $is_registered, bool $has_valid_license, bool $is_paying, $license, $site, $user ): array {
		$method = new ReflectionMethod( Licensing::class, 'map_cloud_identifiers' );
		$method->setAccessible( true );

		return $method->invoke( null, $is_registered, $has_valid_license, $is_paying, $license, $site, $user );
	}

	/**
	 * A registered site with a valid, paying license reports every identifier.
	 *
	 * @return void
	 */
	public function test_registered_valid_paying(): void {
		$license = (object) [
			'id'      => 123456,
			'plan_id' => 987,
		];
		$site = (object) [ 'id' => 7654321 ];
		$user = (object) [ 'id' => 42 ];

		$result = $this->map( true, true, true, $license, $site, $user );

		$this->assertSame(
			[
				'is_registered'     => true,
				'has_valid_license' => true,
				'is_paying'         => true,
				'license_id'        => 123456,
				'install_id'        => 7654321,
				'freemius_user_id'  => 42,
				'plan_id'           => 987,
			],
			$result
		);
	}

	/**
	 * A registered site whose license has expired keeps its identifiers but
	 * reports has_valid_license / is_paying false.
	 *
	 * @return void
	 */
	public function test_registered_but_expired(): void {
		$license = (object) [
			'id'      => 123456,
			'plan_id' => 987,
		];
		$site = (object) [ 'id' => 7654321 ];
		$user = (object) [ 'id' => 42 ];

		$result = $this->map( true, false, false, $license, $site, $user );

		$this->assertTrue( $result['is_registered'] );
		$this->assertFalse( $result['has_valid_license'] );
		$this->assertFalse( $result['is_paying'] );
		$this->assertSame( 123456, $result['license_id'] );
		$this->assertSame( 987, $result['plan_id'] );
		$this->assertSame( 7654321, $result['install_id'] );
		$this->assertSame( 42, $result['freemius_user_id'] );
	}

	/**
	 * An unregistered site reports registration false and null identifiers.
	 *
	 * @return void
	 */
	public function test_not_registered(): void {
		$result = $this->map( false, false, false, null, null, null );

		$this->assertSame(
			[
				'is_registered'     => false,
				'has_valid_license' => false,
				'is_paying'         => false,
				'license_id'        => null,
				'install_id'        => null,
				'freemius_user_id'  => null,
				'plan_id'           => null,
			],
			$result
		);
	}

	/**
	 * The mapper never emits a secret-bearing field, even when the source
	 * entities carry a secret_key / license_key.
	 *
	 * @return void
	 */
	public function test_never_leaks_secrets(): void {
		$license = (object) [
			'id'          => 123456,
			'plan_id'     => 987,
			'secret_key'  => 'sk_super_secret_value',
			'license_key' => 'lk_super_secret_value',
		];

		$result = $this->map( true, true, true, $license, (object) [ 'id' => 1 ], (object) [ 'id' => 2 ] );

		$this->assertSame( self::ALLOWED_KEYS, array_keys( $result ) );
		$this->assertArrayNotHasKey( 'secret_key', $result );
		$this->assertArrayNotHasKey( 'license_key', $result );
		$this->assertNotContains( 'sk_super_secret_value', $result, true );
		$this->assertNotContains( 'lk_super_secret_value', $result, true );
	}

	/**
	 * The public wrapper returns the strict 7-key contract in the live (here,
	 * unregistered) test environment, with no secret fields.
	 *
	 * @return void
	 */
	public function test_live_wrapper_shape_and_no_secrets(): void {
		$identifiers = \Code_Snippets\code_snippets()->licensing->get_cloud_identifiers();

		$this->assertSame( self::ALLOWED_KEYS, array_keys( $identifiers ) );
		$this->assertIsBool( $identifiers['is_registered'] );
	}
}
