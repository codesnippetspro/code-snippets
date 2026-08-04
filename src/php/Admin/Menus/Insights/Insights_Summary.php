<?php

namespace Code_Snippets\Admin\Menus\Insights;

use Code_Snippets\Model\Snippet;
use function Code_Snippets\get_snippets;

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
	 *     locationCounts: array<string, array{label: string, count: int}>,
	 *     tagCounts: array<string, array{label: string, count: int}>
	 * }
	 */
	public function get(): array {
		return $this->create_summary( get_snippets() );
	}

	/**
	 * Create summary data from snippet models.
	 *
	 * @param Snippet[] $snippets Snippet models.
	 *
	 * @return array{
	 *     active: int,
	 *     inactive: int,
	 *     typeCounts: array<string, array{label: string, count: int}>,
	 *     locationCounts: array<string, array{label: string, count: int}>,
	 *     tagCounts: array<string, array{label: string, count: int}>
	 * }
	 */
	private function create_summary( array $snippets ): array {
		$snippets = array_values(
			array_filter(
				$snippets,
				static function ( Snippet $snippet ): bool {
					return ! $snippet->trashed;
				}
			)
		);
		$active_condition_ids = [];

		foreach ( $snippets as $snippet ) {
			if ( 'condition' !== $snippet->scope && $snippet->active && $snippet->condition_id ) {
				$active_condition_ids[ $snippet->condition_id ] = true;
			}
		}

		$type_counts = array_fill_keys( self::TYPE_ORDER, 0 );
		$location_counts = array_fill_keys( array_keys( $this->get_location_labels() ), 0 );
		$tag_counts = [];
		$active = 0;

		foreach ( $snippets as $snippet ) {
			$type = Snippet::get_type_from_scope( $snippet->scope );

			if ( isset( $type_counts[ $type ] ) ) {
				++$type_counts[ $type ];
			}

			if ( 'condition' === $snippet->scope ) {
				$is_active = isset( $active_condition_ids[ $snippet->id ] );
			} else {
				$is_active = $snippet->active;

				if ( isset( $location_counts[ $snippet->scope ] ) ) {
					++$location_counts[ $snippet->scope ];
				}
			}

			if ( $is_active ) {
				++$active;
			}

			foreach ( array_unique( $snippet->tags, SORT_STRING ) as $tag ) {
				if ( '' !== $tag ) {
					$tag_counts[ $tag ] = ( $tag_counts[ $tag ] ?? 0 ) + 1;
				}
			}
		}

		return [
			'active'         => $active,
			'inactive'       => count( $snippets ) - $active,
			'typeCounts'     => $this->create_chart_entries( $type_counts, $this->get_type_labels(), true ),
			'locationCounts' => $this->create_chart_entries( $location_counts, $this->get_location_labels() ),
			'tagCounts'      => $this->create_tag_chart_entries( $tag_counts ),
		];
	}

	/**
	 * Convert tag counts into usage-ordered chart entries.
	 *
	 * @param array<string, int> $counts Count for each tag.
	 *
	 * @return array<string, array{label: string, count: int}>
	 */
	private function create_tag_chart_entries( array $counts ): array {
		uksort(
			$counts,
			static function ( string $first_tag, string $second_tag ) use ( $counts ): int {
				$count_comparison = $counts[ $second_tag ] <=> $counts[ $first_tag ];

				if ( 0 !== $count_comparison ) {
					return $count_comparison;
				}

				$label_comparison = strnatcasecmp( $first_tag, $second_tag );
				return 0 !== $label_comparison ? $label_comparison : strcmp( $first_tag, $second_tag );
			}
		);

		$entries = [];

		foreach ( $counts as $tag => $count ) {
			$entries[ $tag ] = [
				'label' => $tag,
				'count' => $count,
			];
		}

		return $entries;
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
