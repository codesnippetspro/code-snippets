<?php

namespace Code_Snippets;

use Code_Snippets\Cloud\Cloud_Search_List_Table;
use function Code_Snippets\Settings\get_setting;

/**
 * This class handles the welcome menu.
 *
 * @since   3.7.0
 * @package Code_Snippets
 */
class Welcome_Menu extends Admin_Menu {

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
	 * @var ?array
	 */
	private $welcome_data = null;

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			'welcome',
			_x( "What's New", 'menu label', 'code-snippets' ),
			__( 'Welcome to Code Snippets', 'code-snippets' )
		);
	}

	/**
	 * Enqueue assets necessary for the welcome menu.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'code-snippets-welcome',
			plugins_url( 'dist/welcome.css', PLUGIN_FILE ),
			[],
			PLUGIN_VERSION
		);
	}

	/**
	 * Load remote welcome data when the page is loaded.
	 *
	 * @return void
	 */
	public function load() {
		parent::load();

		if ( ! is_array( $this->welcome_data ) ) {
			$this->welcome_data = get_transient( self::CACHE_KEY );
		}

		if ( ! is_array( $this->welcome_data ) ) {
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

		$this->welcome_data['hero-item'] = array_merge(
			[
				'follow_url' => '',
				'name'       => '',
				'image_url'  => '',
			],
			isset( $remote_welcome_data['hero-item'][0] ) && is_array( $remote_welcome_data['hero-item'][0] ) ?
				$remote_welcome_data['hero-item'][0] : []
		);

		$default_item = [
			'follow_url' => '',
			'image_url'  => '',
			'title'      => '',
		];

		foreach ( [ 'features', 'partners' ] as $items_key ) {
			$this->welcome_data[ $items_key ] = [];

			if ( ! isset( $this->welcome_data[ $items_key ] ) || ! is_array( $this->welcome_data[ $items_key ] ) ) {
				continue;
			}

			$limit = max( self::ITEM_LIMIT, count( $this->welcome_data[ $items_key ] ) );

			for ( $i = 0; $i < $limit; $i++ ) {
				$this->welcome_data[ $items_key ][] = array_merge(
					$default_item,
					$remote_welcome_data[ $items_key ][ $i ]
				);
			}
		}
	}

	/**
	 * Build the full list of latest changes for caching.
	 *
	 * @return void
	 */
	protected function build_changelog_data() {
		$valid_sections = [ 'Added', 'Improved', 'Fixed' ];
		$changelog = [];

		$changelog_file = file_get_contents( plugin_dir_path( PLUGIN_FILE ) . 'CHANGELOG.md' );
		$changelog_entries = explode( "\n## ", $changelog_file );

		foreach ( array_slice( $changelog_entries, 1, self::MAX_CHANGELOG_ENTRIES ) as $lines ) {
			$lines = explode( "\n", $lines );
			$version = explode( '(', $lines[0], 2 );
			$version = $version[0];

			$changelog[ $version ] = [];

			foreach ( array_slice( $lines, 1 ) as $raw_line ) {
				$entry = trim( str_replace( '(PRO)', '', str_replace( '*', '', $raw_line ) ) );
				$parts = explode( ': ', $entry, 2 );
				$entry = end( $parts );

				if ( $entry ) {
					$section = in_array( $parts[0], $valid_sections, true ) ? $parts[0] : 'Other';
					$subsection = str_contains( $raw_line, '(PRO)' ) ? 'pro' : 'core';

					if ( ! isset( $changelog[ $version ][ $section ] ) ) {
						$changelog[ $version ][ $section ] = [
							$subsection => [ $entry ],
						];
					} elseif ( ! isset( $changelog[ $version ][ $section ][ $subsection ] ) ) {
						$changelog[ $version ][ $section ][ $subsection ] = [ $entry ];
					} else {
						$changelog[ $version ][ $section ][ $subsection ][] = $entry;
					}
				}
			}
		}

		$this->welcome_data['changelog'] = $changelog;
	}

	/**
	 * Retrieve a list of links to display in the page header.
	 *
	 * @return array<string, array{url: string, icon: string, label: string}>
	 */
	protected function get_header_links(): array {
		return [
			'cloud'     => [
				'url'   => 'https://codesnippets.cloud',
				'icon'  => 'cloud',
				'label' => __( 'Cloud', 'code-snippets' ),
			],
			'resources' => [
				'url'   => 'https://help.codesnippets.pro/',
				'icon'  => 'sos',
				'label' => __( 'Support', 'code-snippets' ),
			],
			'facebook'  => [
				'url'   => 'https://www.facebook.com/groups/282962095661875/',
				'icon'  => 'facebook',
				'label' => __( 'Community', 'code-snippets' ),
			],
			'discord'   => [
				'url'   => 'https://snipco.de/discord',
				'icon'  => 'discord',
				'label' => __( 'Discord', 'code-snippets' ),
			],
			'pro'       => [
				'url'   => 'https://codesnippets.pro/pricing/',
				'icon'  => 'cart',
				'label' => __( 'Get Pro', 'code-snippets' ),
			],
		];
	}

	/**
	 * Retrieve remote data for the hero item.
	 *
	 * @return array{follow_url: string, name: string, image_url: string}
	 */
	protected function get_hero_item(): array {
		return $this->welcome_data['hero-item'] ?? [];
	}

	/**
	 * Parse a list of remote items, ensuring there are no missing keys and a limit number is enforced.
	 *
	 * @param 'features'|'partners' $remote_key Key from remote data to parse.
	 *
	 * @return array<array{follow_url: string, image_url: string, title: string}>
	 */
	protected function get_remote_items( string $remote_key ): array {
		return $this->welcome_data[ $remote_key ] ?? [];
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
	protected function get_changelog(): array {
		return $this->welcome_data['changelog'] ?? [];
	}
}
