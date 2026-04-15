<?php

namespace Code_Snippets\UnifiedSnippets;

use Code_Snippets\UnifiedSnippets\Model\Discovered_Snippet;

/**
 * Manages persistence of scan results in the WordPress options table.
 *
 * Results are stored as a JSON-serializable array in the `code_snippets_scan_results` option
 * with autoload disabled. The option contains the discovered snippets plus metadata about
 * the scan (date, which scanners ran, total counts).
 *
 * **Previous snapshot (`code_snippets_scan_results_previous`):** Each call to {@see self::save()}
 * copies the current option to `…_previous` before overwriting. That means the “previous”
 * snapshot is always the state immediately before the last save — including incremental
 * per-scanner merges via {@see self::merge_scanner_results()}. It is not a dedicated
 * “last full-site scan only” baseline; API clients should assume change detection compares
 * consecutive persisted snapshots unless a separate baseline is introduced later.
 *
 * @package Code_Snippets
 */
class Scan_Results_Store {

	/**
	 * WordPress option name for storing scan results.
	 */
	public const OPTION_NAME = 'code_snippets_scan_results';

	/**
	 * WordPress option name for storing the previous scan (for change detection).
	 */
	public const PREVIOUS_OPTION_NAME = 'code_snippets_scan_results_previous';

	/**
	 * Save a complete set of scan results, replacing any existing data.
	 *
	 * If there is already stored data, it is copied to `code_snippets_scan_results_previous`
	 * before this write. Any caller that replaces the main option (including
	 * {@see self::merge_scanner_results()}) therefore advances “previous” by one save.
	 *
	 * @param Discovered_Snippet[] $snippets    The discovered snippets.
	 * @param string[]             $scanner_ids IDs of scanners that ran in this scan.
	 *
	 * @return bool True on success.
	 */
	public function save( array $snippets, array $scanner_ids ): bool {
		$current = $this->get_raw();

		if ( $current ) {
			update_option( self::PREVIOUS_OPTION_NAME, $current, false );
		}

		$data = [
			'scan_date'   => gmdate( 'c' ),
			'scanners'    => $scanner_ids,
			'total_count' => count( $snippets ),
			'snippets'    => array_map(
				static fn( Discovered_Snippet $snippet ) => $snippet->to_array(),
				$snippets
			),
		];

		return update_option( self::OPTION_NAME, $data, false );
	}

	/**
	 * Merge results from a single scanner into the existing scan results.
	 *
	 * Replaces any existing snippets from the same scanner (by scanner_id),
	 * leaving snippets from other scanners untouched. Persists via {@see self::save()},
	 * so the pre-merge snapshot becomes `code_snippets_scan_results_previous` (see class doc).
	 *
	 * @param string               $scanner_id The scanner that produced these results.
	 * @param Discovered_Snippet[] $snippets   The newly discovered snippets.
	 *
	 * @return bool True on success.
	 */
	public function merge_scanner_results( string $scanner_id, array $snippets ): bool {
		$existing = $this->get_all();

		$kept = array_filter(
			$existing,
			static fn( Discovered_Snippet $s ) => $s->scanner_id !== $scanner_id
		);

		$merged = array_merge( $kept, $snippets );

		$scanners = $this->get_metadata()['scanners'] ?? [];
		if ( ! in_array( $scanner_id, $scanners, true ) ) {
			$scanners[] = $scanner_id;
		}

		return $this->save( $merged, $scanners );
	}

	/**
	 * Retrieve all discovered snippets from the most recent scan.
	 *
	 * @return Discovered_Snippet[]
	 */
	public function get_all(): array {
		$data = $this->get_raw();

		if ( ! $data || empty( $data['snippets'] ) ) {
			return [];
		}

		return array_map(
			static fn( array $item ) => Discovered_Snippet::from_array( $item ),
			$data['snippets']
		);
	}

	/**
	 * Retrieve discovered snippets filtered by source type.
	 *
	 * @param string $source_type e.g. 'theme', 'plugin', 'builder', 'server'.
	 *
	 * @return Discovered_Snippet[]
	 */
	public function get_by_source_type( string $source_type ): array {
		return array_filter(
			$this->get_all(),
			static fn( Discovered_Snippet $s ) => $s->source_type === $source_type
		);
	}

	/**
	 * Retrieve discovered snippets filtered by scanner ID.
	 *
	 * @param string $scanner_id The scanner identifier.
	 *
	 * @return Discovered_Snippet[]
	 */
	public function get_by_scanner( string $scanner_id ): array {
		return array_filter(
			$this->get_all(),
			static fn( Discovered_Snippet $s ) => $s->scanner_id === $scanner_id
		);
	}

	/**
	 * Find a specific discovered snippet by its hash.
	 *
	 * @param string $hash The snippet's deduplication hash.
	 *
	 * @return Discovered_Snippet|null
	 */
	public function get_by_hash( string $hash ): ?Discovered_Snippet {
		foreach ( $this->get_all() as $snippet ) {
			if ( $snippet->hash === $hash ) {
				return $snippet;
			}
		}

		return null;
	}

	/**
	 * Retrieve scan metadata (scan date, scanners that ran, total count).
	 *
	 * @return array{scan_date: string, scanners: string[], total_count: int}
	 */
	public function get_metadata(): array {
		$data = $this->get_raw();

		return [
			'scan_date'   => $data['scan_date'] ?? '',
			'scanners'    => $data['scanners'] ?? [],
			'total_count' => $data['total_count'] ?? 0,
		];
	}

	/**
	 * Detect changes between the current and previous persisted snapshots.
	 *
	 * Identity is by {@see Discovered_Snippet::generate_hash()} (location: scanner, path,
	 * line range, etc.); “modified” is same hash with different {@see Discovered_Snippet::$checksum}.
	 * Scanner authors: if `line_start` / `line_end` participate in the hash, an edit that only
	 * shifts lines but leaves the same code can appear as removed + new instead of unchanged.
	 *
	 * @return array{new: Discovered_Snippet[], modified: Discovered_Snippet[], removed: Discovered_Snippet[]}
	 */
	public function detect_changes(): array {
		$current_snippets  = $this->get_all();
		$previous_snippets = $this->get_previous();

		$previous_by_hash = [];
		foreach ( $previous_snippets as $snippet ) {
			$previous_by_hash[ $snippet->hash ] = $snippet;
		}

		$current_by_hash = [];
		foreach ( $current_snippets as $snippet ) {
			$current_by_hash[ $snippet->hash ] = $snippet;
		}

		$new      = [];
		$modified = [];
		$removed  = [];

		foreach ( $current_snippets as $snippet ) {
			if ( ! isset( $previous_by_hash[ $snippet->hash ] ) ) {
				$new[] = $snippet;
			} elseif ( $snippet->checksum !== $previous_by_hash[ $snippet->hash ]->checksum ) {
				$modified[] = $snippet;
			}
		}

		foreach ( $previous_snippets as $snippet ) {
			if ( ! isset( $current_by_hash[ $snippet->hash ] ) ) {
				$removed[] = $snippet;
			}
		}

		return compact( 'new', 'modified', 'removed' );
	}

	/**
	 * Check whether any scan results exist.
	 *
	 * @return bool
	 */
	public function has_results(): bool {
		$data = $this->get_raw();

		return ! empty( $data['snippets'] );
	}

	/**
	 * Clear all scan results.
	 *
	 * @return bool True on success.
	 */
	public function clear(): bool {
		delete_option( self::PREVIOUS_OPTION_NAME );

		return delete_option( self::OPTION_NAME );
	}

	/**
	 * Retrieve the raw option data.
	 *
	 * @return array|null The stored data array, or null if no results exist.
	 */
	private function get_raw(): ?array {
		$data = get_option( self::OPTION_NAME, null );

		return is_array( $data ) ? $data : null;
	}

	/**
	 * Retrieve discovered snippets from the previous scan.
	 *
	 * @return Discovered_Snippet[]
	 */
	private function get_previous(): array {
		$data = get_option( self::PREVIOUS_OPTION_NAME, null );

		if ( ! is_array( $data ) || empty( $data['snippets'] ) ) {
			return [];
		}

		return array_map(
			static fn( array $item ) => Discovered_Snippet::from_array( $item ),
			$data['snippets']
		);
	}
}
