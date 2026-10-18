<?php

namespace Code_Snippets\Search;

use Code_Snippets\Client\Welcome_Client;
use Code_Snippets\Settings\Settings_Fields;
use Code_Snippets\Settings\Settings_Layout;
use function Code_Snippets\code_snippets;

/**
 * Index of the plugin's own screens for universal search: every setting, the
 * Stats reports and the sections of the welcome page.
 *
 * The entries are built from the same definitions the screens are rendered
 * from rather than read out of the page, so they are available to a REST
 * request that never loads those screens.
 *
 * Every entry has the shape `{ id, type, title, description, url, path }`.
 *
 * @package Code_Snippets
 */
final class Search_Index {

	/**
	 * Prefix of the element identifier given to each row of the settings table.
	 */
	public const SETTING_ANCHOR_PREFIX = 'setting-';

	/**
	 * Prefix of the element identifier given to each Stats report.
	 */
	public const STATS_ANCHOR_PREFIX = 'stats-chart-';

	/**
	 * Admin URL context that links are built for.
	 *
	 * @var string
	 */
	private string $url_context;

	/**
	 * Class constructor.
	 *
	 * @param bool $network Whether links should lead to the network admin.
	 */
	public function __construct( bool $network = false ) {
		$this->url_context = $network ? 'network' : 'admin';
	}

	/**
	 * Join the parts of a section path for display.
	 *
	 * @param string ...$parts Section names, outermost first.
	 *
	 * @return string
	 */
	public static function build_path( string ...$parts ): string {
		return implode( ' › ', array_filter( $parts ) );
	}

	/**
	 * Build the text describing a setting from its definition.
	 *
	 * A checkbox carries its explanation in its label, while the label of a
	 * number field is only the unit shown beside it.
	 *
	 * @param array<string, mixed> $field Field definition.
	 *
	 * @return string
	 */
	private static function describe_setting( array $field ): string {
		$parts = [];

		if ( in_array( $field['type'] ?? '', [ 'checkbox', 'action' ], true ) && ! empty( $field['label'] ) ) {
			$parts[] = (string) $field['label'];
		}

		if ( ! empty( $field['desc'] ) ) {
			$parts[] = (string) $field['desc'];
		}

		if ( ! empty( $field['options'] ) && is_array( $field['options'] ) && 'checkboxes' === $field['type'] ) {
			$parts[] = implode( ', ', array_map( 'strval', $field['options'] ) );
		}

		return Search_Query::plain_text( implode( ' ', $parts ) );
	}

	/**
	 * Retrieve an entry for every setting shown on the settings screen.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_settings_entries(): array {
		$definitions = Settings_Fields::get_field_definitions();
		$descriptions = Settings_Layout::get_descriptions();
		$settings_url = code_snippets()->get_menu_url( 'settings', $this->url_context );
		$settings_label = __( 'Settings', 'code-snippets' );
		$entries = [];

		foreach ( Settings_Layout::get_available_tabs() as $tab_id => $tab_label ) {
			foreach ( Settings_Layout::get_visible_fields( $tab_id ) as $entry ) {
				list( $section_id, $field_id ) = $entry;
				$field = $definitions[ $section_id ][ $field_id ];

				if ( empty( $field['name'] ) ) {
					continue;
				}

				if ( isset( $descriptions[ $field_id ] ) ) {
					$field['desc'] = $descriptions[ $field_id ];
				}

				$entries[] = [
					'id'          => "$section_id-$field_id",
					'type'        => 'setting',
					'title'       => (string) $field['name'],
					'description' => self::describe_setting( $field ),
					'url'         => add_query_arg( 'section', $tab_id, $settings_url ) . '#' . self::SETTING_ANCHOR_PREFIX . $field_id,
					'path'        => self::build_path( $settings_label, (string) $tab_label ),
				];
			}
		}

		return $entries;
	}

	/**
	 * Retrieve an entry for every report on the Stats screen.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_stats_entries(): array {
		$stats_url = code_snippets()->get_menu_url( 'stats', $this->url_context );
		$path = self::build_path( __( 'Stats', 'code-snippets' ), __( 'Reports', 'code-snippets' ) );

		$reports = [
			'total'      => [
				__( 'Total snippets', 'code-snippets' ),
				__( 'The number of snippets saved on this site.', 'code-snippets' ),
			],
			'type'       => [
				__( 'Snippet type', 'code-snippets' ),
				__( 'Snippets broken down by type: functions, content, styles, scripts and conditions.', 'code-snippets' ),
			],
			'activation' => [
				__( 'Activation status', 'code-snippets' ),
				__( 'How many snippets are active and how many are inactive.', 'code-snippets' ),
			],
			'conditions' => [
				__( 'Condition usage', 'code-snippets' ),
				__( 'How many snippets only run when a condition is met.', 'code-snippets' ),
			],
			'location'   => [
				__( 'Location', 'code-snippets' ),
				__( 'Where snippets run: everywhere, the administration area, the site front-end or a part of the page.', 'code-snippets' ),
			],
			'tags'       => [
				__( 'Tags', 'code-snippets' ),
				__( 'The tags used across snippets, and how often each one appears.', 'code-snippets' ),
			],
		];

		$entries = [];

		foreach ( $reports as $report_id => $report ) {
			$entries[] = [
				'id'          => "report-$report_id",
				'type'        => 'stat',
				'title'       => $report[0],
				'description' => $report[1],
				'url'         => $stats_url . '#' . self::STATS_ANCHOR_PREFIX . $report_id,
				'path'        => $path,
			];
		}

		return $entries;
	}

	/**
	 * Flatten the changes of a release into a single line of text.
	 *
	 * @param array<string, mixed> $sections Changes keyed by section, then by plugin edition.
	 *
	 * @return string
	 */
	private static function flatten_changelog_entries( array $sections ): string {
		$changes = [];

		array_walk_recursive(
			$sections,
			function ( $change ) use ( &$changes ) {
				if ( is_string( $change ) ) {
					$changes[] = $change;
				}
			}
		);

		return Search_Query::plain_text( implode( ' ', $changes ) );
	}

	/**
	 * Retrieve an entry for every section of the welcome screen, along with the
	 * release notes and articles it lists.
	 *
	 * The articles come from data the welcome screen has already cached, and are
	 * left out rather than fetched when it is missing, so a search never waits on
	 * a remote request for a local result. Release notes are read from the
	 * changelog shipped with the plugin when nothing is cached.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_welcome_entries(): array {
		$welcome_url = code_snippets()->get_menu_url( 'welcome', $this->url_context );
		$welcome_label = __( 'Welcome', 'code-snippets' );
		$whats_new_path = self::build_path( $welcome_label, __( "What's new", 'code-snippets' ) );
		$getting_started_path = self::build_path( $welcome_label, __( 'Getting started', 'code-snippets' ) );

		$entries = [
			[
				'id'          => 'latest-changes',
				'type'        => 'guide',
				'title'       => __( "What's new in Code Snippets", 'code-snippets' ),
				'description' => __( 'Latest changes: the new features, improvements and bug fixes in recent releases.', 'code-snippets' ),
				'url'         => $welcome_url . '#latest-changes',
				'path'        => $whats_new_path,
			],
			[
				'id'          => 'helpful-articles',
				'type'        => 'guide',
				'title'       => __( 'Helpful articles', 'code-snippets' ),
				'description' => __( 'Guides and tutorials for getting started with snippets.', 'code-snippets' ),
				'url'         => $welcome_url . '#helpful-articles',
				'path'        => $getting_started_path,
			],
			[
				'id'          => 'partner-deals',
				'type'        => 'guide',
				'title'       => __( 'Exclusive deals from our partners', 'code-snippets' ),
				'description' => __( 'Offers on other WordPress products from Code Snippets partners.', 'code-snippets' ),
				'url'         => $welcome_url . '#partner-deals',
				'path'        => self::build_path( $welcome_label, __( 'Partners', 'code-snippets' ) ),
			],
		];

		$cached = Welcome_Client::get_cached_data();
		$changelog = is_array( $cached['changelog'] ?? null ) ? $cached['changelog'] : Welcome_Client::read_changelog();

		foreach ( $changelog as $release ) {
			if ( empty( $release['version'] ) || ! is_scalar( $release['version'] ) ) {
				continue;
			}

			$entries[] = [
				'id'          => 'release-' . sanitize_key( (string) $release['version'] ),
				'type'        => 'guide',
				// translators: %s: version number.
				'title'       => sprintf( __( "What's new in version %s", 'code-snippets' ), $release['version'] ),
				'description' => self::flatten_changelog_entries( is_array( $release['entries'] ?? null ) ? $release['entries'] : [] ),
				'url'         => $welcome_url . '#latest-changes',
				'path'        => $whats_new_path,
			];
		}

		foreach ( is_array( $cached['features'] ?? null ) ? $cached['features'] : [] as $index => $article ) {
			if ( empty( $article['title'] ) || ! is_scalar( $article['title'] ) ) {
				continue;
			}

			$entries[] = [
				'id'          => "article-$index",
				'type'        => 'guide',
				'title'       => Search_Query::plain_text( (string) $article['title'] ),
				'description' => Search_Query::plain_text( is_scalar( $article['description'] ?? null ) ? (string) $article['description'] : '' ),
				'url'         => $welcome_url . '#helpful-articles',
				'path'        => $getting_started_path,
			];
		}

		return $entries;
	}
}
