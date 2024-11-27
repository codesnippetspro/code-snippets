<?php

namespace Code_Snippets;

use DateTimeImmutable;
use Exception;
use WP_Filesystem_Direct;

/**
 * Class for loading data from the codesnippets.pro website.
 *
 * @package Code_Snippets
 */
class Welcome_API {

	/**
	 *  URL for the welcome page data.
	 *
	 * @var string
	 */
	protected const WELCOME_JSON_URL = 'https://codesnippets.pro/wp-content/uploads/cs_welcome/cs_welcome.json';

	/**
	 * Limit of number of items to display when loading lists of items.
	 *
	 * @var int
	 */
	protected const ITEM_LIMIT = 4;

	/**
	 * Limit of number of items of historic versions to display in the changelog.
	 *
	 * @var int
	 */
	protected const MAX_CHANGELOG_ENTRIES = 4;

	/**
	 * Key used for caching welcome page data.
	 *
	 * @var string
	 */
	protected const CACHE_KEY = 'code_snippets_welcome_data';

	/**
	 * Data fetched from the remote API.
	 *
	 * @var array{
	 *     banner: ?array,
	 *     hero-item: ?array,
	 *     features: ?array,
	 *     partners: ?array,
	 *     changelog: ?array
	 * }
	 */
	private array $welcome_data;

	/**
	 * Populate the $welcome_data variable when the class is loaded.
	 *
	 * @return void
	 */
	public function __construct() {
		$stored_data = get_transient( self::CACHE_KEY );

		if ( is_array( $stored_data ) ) {
			$this->welcome_data = $stored_data;
		} else {
			$this->welcome_data = [];
			$this->fetch_remote_welcome_data();
			$this->build_changelog_data();
			set_transient( self::CACHE_KEY, $this->welcome_data, DAY_IN_SECONDS * 2 );
		}
	}

	/**
	 * Purge the welcome data cache.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Safely retrieve an array from an object, ensuring it exists and is valid, returning a default value if not.
	 *
	 * @param array<string, mixed> $items Associative array containing array to extract.
	 * @param string               $key   Array key.
	 *
	 * @return array Extracted array, or empty array if array is missing.
	 */
	private static function safe_get_array( array $items, string $key ): array {
		return isset( $items[ $key ] ) && is_array( $items[ $key ] ) ? $items[ $key ] : [];
	}

	/**
	 * Parse DateTime value from a string without triggering an error.
	 *
	 * @param string $datetime String representation of DateTime value.
	 *
	 * @return DateTimeImmutable|null
	 */
	private static function safe_parse_datetime( string $datetime ): ?DateTimeImmutable {
		try {
			return new DateTimeImmutable( $datetime );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Parse remote hero item data.
	 *
	 * @param array $remote Remote hero item data.
	 *
	 * @return array
	 */
	private function parse_hero_item( array $remote ): array {
		return [
			'name'       => $remote[0]['name'] ?? '',
			'follow_url' => $remote[0]['follow_url'] ?? '',
			'image_url'  => $remote[0]['image_url'] ?? '',
		];
	}

	/**
	 * Parse remote banner data.
	 *
	 * @param array $remote Remote hero item data.
	 *
	 * @return array
	 */
	private function parse_banner( array $remote ): array {
		return [
			'key'               => sanitize_key( $remote['key'] ) ?? '',
			'start_datetime'    => self::safe_parse_datetime( $remote['start_datetime'] ),
			'end_datetime'      => self::safe_parse_datetime( $remote['end_datetime'] ),
			'text_free'         => $remote['text_free'] ?? '',
			'action_url_free'   => $remote['action_url_free'] ?? '',
			'action_label_free' => $remote['action_label_free'] ?? '',
			'text_pro'          => $remote['text_pro'] ?? '',
			'action_url_pro'    => $remote['action_url_pro'] ?? '',
			'action_label_pro'  => $remote['action_label_pro'] ?? '',
		];
	}

	/**
	 * Parse a list of features from a remote dataset.
	 *
	 * @param array $remote Remote data.
	 *
	 * @return array[] Parsed feature data.
	 */
	private function parse_features( array $remote ): array {
		$limit = max( self::ITEM_LIMIT, count( $remote ) );
		$features = [];

		for ( $i = 0; $i < $limit; $i++ ) {
			$feature = $remote[ $i ];

			$features[] = [
				'title'       => $feature['title'] ?? '',
				'follow_url'  => $feature['follow_url'] ?? '',
				'image_url'   => $feature['image_url'] ?? '',
				'category'    => $feature['category'] ?? '',
				'description' => $feature['description'] ?? '',
			];
		}

		return $features;
	}

	/**
	 * Parse a list of partners from a remote dataset.
	 *
	 * @param array $remote Remote data.
	 *
	 * @return array[] Parsed partner data.
	 */
	private function parse_partners( array $remote ): array {
		$limit = max( self::ITEM_LIMIT, count( $remote ) );
		$partners = [];

		for ( $i = 0; $i < $limit; $i++ ) {
			$partner = $remote[ $i ];

			$partners[] = [
				'title'      => $partner['title'] ?? '',
				'follow_url' => $partner['follow_url'] ?? '',
				'image_url'  => $partner['image_url'] ?? '',
			];
		}

		return $partners;
	}

	/**
	 * Fetch remote welcome data from the remote server and add it to the stored data.
	 *
	 * @return void
	 */
	protected function fetch_remote_welcome_data() {
		$remote_welcome_data = wp_remote_get( self::WELCOME_JSON_URL );

		if ( is_wp_error( $remote_welcome_data ) ) {
			return;
		}

		$remote_welcome_data = json_decode( wp_remote_retrieve_body( $remote_welcome_data ), true );

		if ( ! is_array( $remote_welcome_data ) ) {
			return;
		}

		$this->welcome_data['banner'] = $this->parse_banner( self::safe_get_array( $remote_welcome_data, 'banner' ) );
		$this->welcome_data['hero-item'] = $this->parse_hero_item( self::safe_get_array( $remote_welcome_data, 'hero-item' ) );
		$this->welcome_data['features'] = $this->parse_features( self::safe_get_array( $remote_welcome_data, 'features' ) );
		$this->welcome_data['partners'] = $this->parse_partners( self::safe_get_array( $remote_welcome_data, 'partners' ) );
	}

	/**
	 * Build the full list of latest changes for caching.
	 *
	 * @return void
	 */
	protected function build_changelog_data() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		$filesystem = new WP_Filesystem_Direct( null );

		$changelog_filename = 'CHANGELOG.md';
		$changelog = [];

		$changelog_dir = plugin_dir_path( PLUGIN_FILE );

		while ( plugin_dir_path( $changelog_dir ) !== $changelog_dir && ! $filesystem->exists( $changelog_dir . $changelog_filename ) ) {
			$changelog_dir = plugin_dir_path( $changelog_dir );
		}

		if ( ! $filesystem->exists( $changelog_dir . $changelog_filename ) ) {
			return;
		}

		$changelog_contents = $filesystem->get_contents( $changelog_dir . $changelog_filename );
		$changelog_releases = explode( "\n## ", $changelog_contents );

		foreach ( array_slice( $changelog_releases, 1, self::MAX_CHANGELOG_ENTRIES ) as $changelog_release ) {
			$sections = explode( "\n### ", $changelog_release );

			if ( count( $sections ) < 2 ) {
				continue;
			}

			$header_parts = explode( '(', $sections[0], 2 );
			$version = trim( trim( $header_parts[0] ), '[]' );

			$changelog[ $version ] = [];

			foreach ( array_slice( $sections, 1 ) as $section_contents ) {
				$lines = array_filter( array_map( 'trim', explode( "\n", $section_contents ) ) );
				$section_type = $lines[0];

				foreach ( array_slice( $lines, 1 ) as $line ) {
					$entry = trim( str_replace( '(PRO)', '', str_replace( '*', '', $line ) ) );
					$core_or_pro = false === strpos( $line, '(PRO)' ) ? 'core' : 'pro';

					if ( ! isset( $changelog[ $version ][ $section_type ] ) ) {
						$changelog[ $version ][ $section_type ] = [
							$core_or_pro => [ $entry ],
						];
					} elseif ( ! isset( $changelog[ $version ][ $section_type ][ $core_or_pro ] ) ) {
						$changelog[ $version ][ $section_type ][ $core_or_pro ] = [ $entry ];
					} else {
						$changelog[ $version ][ $section_type ][ $core_or_pro ][] = $entry;
					}
				}
			}
		}

		$this->welcome_data['changelog'] = $changelog;
	}

	/**
	 * Retrieve banner information.
	 *
	 * @return array{
	 *      key: string,
	 *      start_datetime: ?DateTimeImmutable,
	 *      end_datetime: ?DateTimeImmutable,
	 *      text_free: string,
	 *      action_url_free: string,
	 *      action_label_free: string,
	 *      text_pro: string,
	 *      action_url_pro: string,
	 *      action_label_pro: string                                                                                                                           tet
	 *  }
	 */
	public function get_banner(): array {
		return $this->welcome_data['banner'] ?? [];
	}

	/**
	 * Retrieve hero information.
	 *
	 * @return array{
	 *     name: string,
	 *     follow_url: string,
	 *     image_url: string
	 * }
	 */
	public function get_hero_item(): array {
		return $this->welcome_data['hero-item'] ?? [];
	}

	/**
	 * Retrieve the list of features retrieved from the remote API.
	 *
	 * @return array{
	 *     title: string,
	 *     follow_url: string,
	 *     image_url: string,
	 *     category: string,
	 *     description: string
	 * }[] Feature details.
	 */
	public function get_features(): array {
		return $this->welcome_data['features'] ?? [];
	}

	/**
	 * Retrieve the list of partners retrieved from the remote API.
	 *
	 * @return array{
	 *     title: string,
	 *     follow_url: string,
	 *     image_url: string
	 * }[] Partner details.
	 */
	public function get_partners(): array {
		return $this->welcome_data['partners'] ?? [];
	}

	/**
	 * Retrieve a list of latest changes for display.
	 *
	 * @return array<string, array{
	 *     'Added': ?array<'core' | 'pro', string>,
	 *     'Fixed': ?array<'core' | 'pro', string>,
	 *     'Improved': ?array<'core' | 'pro', string>,
	 *     'Other': ?array<'core' | 'pro', string>
	 * }>
	 */
	public function get_changelog(): array {
		return $this->welcome_data['changelog'] ?? [];
	}
}
