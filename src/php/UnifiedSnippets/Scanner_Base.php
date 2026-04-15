<?php

namespace Code_Snippets\UnifiedSnippets;

use Code_Snippets\UnifiedSnippets\Model\Discovered_Snippet;

/**
 * Abstract base class for all site scanners.
 *
 * Each scanner knows how to find code in a specific source (theme files, plugins, builder data, etc.)
 * and returns an array of Discovered_Snippet objects.
 *
 * @package Code_Snippets
 */
abstract class Scanner_Base {

	/**
	 * Get the unique identifier for this scanner.
	 *
	 * @return string e.g. 'functions-php', 'additional-css', 'htaccess'.
	 */
	abstract public function get_id(): string;

	/**
	 * Get the human-readable label for this scanner.
	 *
	 * @return string e.g. 'Theme functions.php', 'Additional CSS'.
	 */
	abstract public function get_label(): string;

	/**
	 * Determine whether this scanner can run in the current environment.
	 *
	 * For generic scanners this is usually true. For builder-specific scanners
	 * it checks whether the target plugin is installed and active.
	 *
	 * @return bool
	 */
	abstract public function is_available(): bool;

	/**
	 * Perform the scan and return discovered snippets.
	 *
	 * @return Discovered_Snippet[] Array of discovered snippet objects.
	 */
	abstract public function scan(): array;

	/**
	 * Get the default risk level for snippets found by this scanner.
	 *
	 * Individual snippets may override this during scan().
	 *
	 * @return string 'low', 'medium', or 'high'.
	 */
	public function get_risk_level(): string {
		return 'low';
	}

	/**
	 * Whether discovered snippets from this scanner can be imported into Code Snippets.
	 *
	 * @return bool
	 */
	public function supports_import(): bool {
		return true;
	}

	/**
	 * Whether discovered snippets from this scanner can be edited in-place.
	 *
	 * @return bool
	 */
	public function supports_editing(): bool {
		return false;
	}

	/**
	 * Get the scanner tier (1 = generic, 2 = DB-aware, 3 = builder-specific).
	 *
	 * @return int
	 */
	public function get_tier(): int {
		return 1;
	}

	/**
	 * Build a Discovered_Snippet with common fields pre-filled from this scanner's defaults.
	 *
	 * Scanners should use this helper instead of constructing Discovered_Snippet directly
	 * to ensure consistent scanner_id, scan_date, risk_level, and hash generation.
	 *
	 * @param array<string, mixed> $fields Snippet-specific field values.
	 *
	 * @return Discovered_Snippet
	 */
	protected function build_snippet( array $fields ): Discovered_Snippet {
		$fields = array_merge(
			[
				'scanner_id'    => $this->get_id(),
				'risk_level'    => $this->get_risk_level(),
				'is_importable' => $this->supports_import(),
			],
			$fields
		);

		$snippet = new Discovered_Snippet( $fields );
		$snippet->stamp_scan_date();
		$snippet->generate_checksum();
		$snippet->generate_hash();

		return $snippet;
	}
}
