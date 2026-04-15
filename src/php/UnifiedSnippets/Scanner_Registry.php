<?php

namespace Code_Snippets\UnifiedSnippets;

/**
 * Registry of Unified Snippets scanners.
 *
 * Scanners are registered by ID. Use {@see self::get_all()} for every registered
 * scanner, {@see self::get_available()} for those that can run in the current
 * environment, and {@see self::get_scanner_info()} for REST summaries.
 * Third-party code can register scanners on the `code_snippets/scanning/register_scanners` action.
 *
 * @package Code_Snippets
 */
class Scanner_Registry {

	/**
	 * Registered scanners keyed by ID.
	 *
	 * @var array<string, Scanner_Base>
	 */
	private array $scanners = [];

	/**
	 * Whether the third-party registration hook has fired.
	 *
	 * @var bool
	 */
	private bool $did_external_registration = false;

	/**
	 * Register a scanner.
	 *
	 * @param Scanner_Base $scanner The scanner instance to register.
	 *
	 * @return void
	 */
	public function register( Scanner_Base $scanner ): void {
		$this->scanners[ $scanner->get_id() ] = $scanner;
	}

	/**
	 * Unregister a scanner by ID.
	 *
	 * @param string $id The scanner ID.
	 *
	 * @return bool True if the scanner was removed, false if it was not registered.
	 */
	public function unregister( string $id ): bool {
		if ( ! isset( $this->scanners[ $id ] ) ) {
			return false;
		}

		unset( $this->scanners[ $id ] );
		return true;
	}

	/**
	 * Retrieve a scanner by its ID.
	 *
	 * @param string $id The scanner ID.
	 *
	 * @return Scanner_Base|null The scanner, or null if not registered.
	 */
	public function get( string $id ): ?Scanner_Base {
		$this->maybe_register_external_scanners();

		return $this->scanners[ $id ] ?? null;
	}

	/**
	 * Retrieve all registered scanners.
	 *
	 * @return array<string, Scanner_Base> Scanners keyed by ID.
	 */
	public function get_all(): array {
		$this->maybe_register_external_scanners();

		return $this->scanners;
	}

	/**
	 * Retrieve only scanners that are available in the current environment.
	 *
	 * @return array<string, Scanner_Base> Available scanners keyed by ID.
	 */
	public function get_available(): array {
		return array_filter(
			$this->get_all(),
			static fn( Scanner_Base $scanner ) => $scanner->is_available()
		);
	}

	/**
	 * Get a summary of all registered scanners for REST API responses.
	 *
	 * @return array<array<string, mixed>> List of scanner info arrays.
	 */
	public function get_scanner_info(): array {
		$info = [];

		foreach ( $this->get_all() as $scanner ) {
			$info[] = [
				'id'               => $scanner->get_id(),
				'label'            => $scanner->get_label(),
				'available'        => $scanner->is_available(),
				'risk_level'       => $scanner->get_risk_level(),
				'supports_import'  => $scanner->supports_import(),
				'supports_editing' => $scanner->supports_editing(),
			];
		}

		return $info;
	}

	/**
	 * Fire the registration hook for third-party scanners (once).
	 *
	 * @return void
	 */
	private function maybe_register_external_scanners(): void {
		if ( $this->did_external_registration ) {
			return;
		}

		$this->did_external_registration = true;

		/**
		 * Fires when the scanner registry is ready for third-party registrations.
		 *
		 * @param Scanner_Registry $registry The scanner registry instance.
		 */
		do_action( 'code_snippets/scanning/register_scanners', $this );
	}
}
