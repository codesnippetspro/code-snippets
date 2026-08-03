<?php

namespace Code_Snippets\Admin\Menus\Insights;

use Code_Snippets\Model\Snippet;
use function Code_Snippets\code_snippets;

/**
 * Builds aggregate data for the Insights screen.
 */
final class Insights_Summary {

	/**
	 * Display order for snippet types.
	 *
	 * @var string[]
	 */
	private const TYPE_ORDER = [ 'php', 'html', 'css', 'js', 'cond' ];

	/**
	 * Build an aggregate of saved snippets for the Insights screen.
	 *
	 * @return array{
	 *     active: int,
	 *     inactive: int,
	 *     typeCounts: array<string, array{label: string, count: int}>,
	 *     locationCounts: array<string, array{label: string, count: int}>
	 * }
	 */
	public function get(): array {
		global $wpdb;
		$table_name = code_snippets()->db->get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixed-column read omits source code and other large fields.
		$rows = $wpdb->get_results(
			"SELECT id, scope, condition_id, active FROM $table_name",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching

		return $this->create_summary( is_array( $rows ) ? $rows : [] );
	}

	/**
	 * Create summary data from the minimal snippet database fields.
	 *
	 * @param array<int, array{id: string, scope: string, condition_id: string, active: string}> $rows Snippet database rows.
	 *
	 * @return array{
	 *     active: int,
	 *     inactive: int,
	 *     typeCounts: array<string, array{label: string, count: int}>,
	 *     locationCounts: array<string, array{label: string, count: int}>
	 * }
	 */
	private function create_summary( array $rows ): array {
		$rows = array_values(
			array_filter(
				$rows,
				static function ( array $row ): bool {
					return -1 !== intval( $row['active'] );
				}
			)
		);
		$active_condition_ids = [];

		foreach ( $rows as $row ) {
			if ( 'condition' !== $row['scope'] && 1 === intval( $row['active'] ) && $row['condition_id'] ) {
				$active_condition_ids[ $row['condition_id'] ] = true;
			}
		}

		$type_counts = array_fill_keys( self::TYPE_ORDER, 0 );
		$location_counts = array_fill_keys( array_keys( $this->get_location_labels() ), 0 );
		$active = 0;

		foreach ( $rows as $row ) {
			$type = Snippet::get_type_from_scope( $row['scope'] );

			if ( isset( $type_counts[ $type ] ) ) {
				++$type_counts[ $type ];
			}

			if ( 'condition' === $row['scope'] ) {
				$is_active = isset( $active_condition_ids[ $row['id'] ] );
			} else {
				$is_active = 1 === intval( $row['active'] );

				if ( isset( $location_counts[ $row['scope'] ] ) ) {
					++$location_counts[ $row['scope'] ];
				}
			}

			if ( $is_active ) {
				++$active;
			}
		}

		return [
			'active'         => $active,
			'inactive'       => count( $rows ) - $active,
			'typeCounts'     => $this->create_chart_entries( $type_counts, $this->get_type_labels(), true ),
			'locationCounts' => $this->create_chart_entries( $location_counts, $this->get_location_labels() ),
		];
	}

	/**
	 * Convert non-zero aggregate counts into labelled chart entries.
	 *
	 * @param array<string, int>    $counts Count for each type or scope.
	 * @param array<string, string> $labels Display label for each type or scope.
	 * @param bool                  $include_zero_counts Whether zero-count entries should be included.
	 *
	 * @return array<string, array{label: string, count: int}>
	 */
	private function create_chart_entries( array $counts, array $labels, bool $include_zero_counts = false ): array {
		$entries = [];

		foreach ( $counts as $key => $count ) {
			if ( ( $include_zero_counts || 0 < $count ) && isset( $labels[ $key ] ) ) {
				$entries[ $key ] = [
					'label' => $labels[ $key ],
					'count' => $count,
				];
			}
		}

		return $entries;
	}

	/**
	 * Retrieve display labels for snippet types.
	 *
	 * @return array<string, string>
	 */
	private function get_type_labels(): array {
		return [
			'php'  => __( 'PHP', 'code-snippets' ),
			'html' => __( 'HTML', 'code-snippets' ),
			'css'  => __( 'CSS', 'code-snippets' ),
			'js'   => __( 'JS', 'code-snippets' ),
			'cond' => __( 'Conditions', 'code-snippets' ),
		];
	}

	/**
	 * Retrieve display labels for executable snippet locations.
	 *
	 * @return array<string, string>
	 */
	private function get_location_labels(): array {
		return [
			'global'         => __( 'Run everywhere', 'code-snippets' ),
			'admin'          => __( 'Only run in administration area', 'code-snippets' ),
			'front-end'      => __( 'Only run on site front-end', 'code-snippets' ),
			'single-use'     => __( 'Only run once', 'code-snippets' ),
			'content'        => __( 'Where inserted in editor', 'code-snippets' ),
			'head-content'   => __( 'In site header (<head> section)', 'code-snippets' ),
			'body-content'   => __( 'In site content (start of <body>)', 'code-snippets' ),
			'footer-content' => __( 'In site footer (end of <body>)', 'code-snippets' ),
			'admin-css'      => __( 'Administration area', 'code-snippets' ),
			'site-css'       => __( 'Site front-end', 'code-snippets' ),
			'site-head-js'   => __( 'In site header (<head> section)', 'code-snippets' ),
			'site-footer-js' => __( 'In site footer (end of <body>)', 'code-snippets' ),
		];
	}
}
