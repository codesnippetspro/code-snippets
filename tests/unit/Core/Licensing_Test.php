<?php

namespace Code_Snippets\Core;

use Code_Snippets\UnitTestCase;
use Freemius;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Tests for Freemius licensing integration.
 */
class Licensing_Test extends UnitTestCase {

	/**
	 * Create a licensing service with an injected SDK test double.
	 *
	 * @param Freemius $sdk SDK test double.
	 *
	 * @return Licensing
	 * @throws ReflectionException Reflection setup fails.
	 */
	private function create_licensing( Freemius $sdk ): Licensing {
		$reflection = new ReflectionClass( Licensing::class );
		$licensing = $reflection->newInstanceWithoutConstructor();
		$sdk_property = new ReflectionProperty( Licensing::class, 'sdk' );

		if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
			$sdk_property->setAccessible( true );
		}

		$sdk_property->setValue( $licensing, $sdk );
		return $licensing;
	}

	/**
	 * The connect screen update message keeps the established consent wording.
	 *
	 * @return void
	 * @throws ReflectionException Reflection setup fails.
	 */
	public function test_connect_message_on_update_keeps_consent_wording(): void {
		$licensing = $this->create_licensing(
			$this->getMockBuilder( Freemius::class )->disableOriginalConstructor()->getMock()
		);

		$method = new ReflectionMethod( Licensing::class, 'connect_message_on_update' );

		if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
			$method->setAccessible( true );
		}

		$message = $method->invoke(
			$licensing,
			'Original message',
			'Ada',
			'Code Snippets Pro',
			'ada',
			'https://example.com',
			'freemius.com'
		);

		$this->assertSame(
			'Please help us improve Code Snippets! If you opt-in, some data about your usage of ' .
			"https://example.com will be sent to freemius.com. If you skip this, that's okay, " .
			'Code Snippets will still work just fine.',
			$message
		);
	}

	/**
	 * A valid site license key is activated through the Freemius SDK.
	 *
	 * @return void
	 * @throws ReflectionException Reflection setup fails.
	 */
	public function test_activate_license_key_activates_a_site_license(): void {
		$sdk = $this->getMockBuilder( Freemius::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'can_use_premium_code', 'opt_in' ] )
			->getMock();

		$sdk->method( 'can_use_premium_code' )->willReturn( false );
		$sdk->expects( $this->once() )
			->method( 'opt_in' )
			->with(
				false,
				false,
				false,
				'license-key',
				false,
				false,
				false,
				false,
				[],
				false
			)
			->willReturn( true );

		$result = $this->create_licensing( $sdk )->activate_license_key( 'license-key' );

		$this->assertSame(
			[
				'success' => true,
				'message' => 'License activated successfully.',
			],
			$result
		);
	}
}
