<?php

namespace Code_Snippets\Utils;

use Code_Snippets\UnitTestCase;

/**
 * Tests for the PHP validator's duplicate declaration checks.
 */
class Validator_Test extends UnitTestCase {

	/**
	 * The same short name in two namespaces is two different names.
	 *
	 * @return void
	 */
	public function test_same_name_in_different_namespaces_is_allowed(): void {
		$first = new Validator( "namespace Acme\\Alpha;\nfunction shared_name() {}" );
		$this->assertFalse( $first->validate() );

		$second = new Validator( "namespace Acme\\Beta;\nfunction shared_name() {}", $first->get_claimed_identifiers() );
		$this->assertFalse( $second->validate(), 'a different namespace is a different function' );

		$third = new Validator( "namespace Acme\\Alpha;\nfunction shared_name() {}", $second->get_claimed_identifiers() );
		$this->assertIsArray( $third->validate(), 'the same namespace and name is a redeclaration' );
	}

	/**
	 * Claimed names are stored fully qualified.
	 *
	 * @return void
	 */
	public function test_claims_are_fully_qualified(): void {
		$validator = new Validator( "namespace Acme\\Alpha;\nfunction shared_name() {}\nclass Widget {}" );
		$validator->validate();

		$claimed = $validator->get_claimed_identifiers();
		$this->assertContains( 'acme\\alpha\\shared_name', $claimed[ T_FUNCTION ] );
		$this->assertContains( 'acme\\alpha\\widget', $claimed[ T_CLASS ] );
	}

	/**
	 * Un-namespaced code still collides with itself, and a namespace block scopes only what it wraps.
	 *
	 * @return void
	 */
	public function test_global_and_braced_namespaces(): void {
		$first = new Validator( 'function plain_name() {}' );
		$this->assertFalse( $first->validate() );

		$duplicate = new Validator( 'function plain_name() {}', $first->get_claimed_identifiers() );
		$this->assertIsArray( $duplicate->validate() );

		$braced = new Validator( "namespace Acme {\n\tfunction plain_name() {}\n}\nnamespace {\n\tfunction other_name() {}\n}", $first->get_claimed_identifiers() );
		$this->assertFalse( $braced->validate(), 'a braced namespace scopes its function, and the global block after it is global again' );
		$this->assertContains( 'acme\\plain_name', $braced->get_claimed_identifiers()[ T_FUNCTION ] );
		$this->assertContains( 'other_name', $braced->get_claimed_identifiers()[ T_FUNCTION ] );
	}

	/**
	 * A relative name such as namespace\foo() is a call, not a declaration.
	 *
	 * @return void
	 */
	public function test_relative_namespace_names_are_not_declarations(): void {
		$validator = new Validator( "namespace Acme;\nnamespace\\helper();\nfunction helper() {}" );
		$this->assertFalse( $validator->validate() );
		$this->assertContains( 'acme\\helper', $validator->get_claimed_identifiers()[ T_FUNCTION ] );
	}
}
