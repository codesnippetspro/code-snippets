<?php

namespace Code_Snippets\REST_API\Import\Plugins;

use Code_Snippets\Model\Snippet;
use WP_Post;

/**
 * Importer for Elementor Pro Custom Code (and compatible Elementor Custom Code CPT).
 *
 * Aligns with Elementor Pro Custom Code metabox: CPT {@see POST_TYPE}, meta keys
 * {@see META_LOCATION}, {@see META_CODE}, {@see META_PRIORITY_KEYS}. Location values:
 * `elementor_head`, `elementor_body_start`, `elementor_body_end`.
 *
 * @link https://elementor.com/help/custom-code-pro/
 */
class Elementor_Custom_Code_Plugin_Importer extends Plugin_Importer {

	private const POST_TYPE = 'elementor_snippet';

	private const META_LOCATION = '_elementor_location';

	private const META_LOCATION_LEGACY = '_elementor_code_location';

	private const META_CODE = '_elementor_code';

	private const META_CODE_KIND_KEYS = [
		'_elementor_snippet_type',
		'_elementor_code_type',
	];

	private const META_PRIORITY_KEYS = [
		'_elementor_priority',
		'_elementor_snippet_priority',
		'_elementor_code_priority',
	];

	protected const FIELD_MAPPINGS = [
		'name'             => 'name',
		'code'             => 'code',
		'import_location'  => 'scope',
		'priority'         => 'priority',
		'modified'         => 'modified',
		'desc'             => 'desc',
	];

	/**
	 * Get the unique name of the importer.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'elementor';
	}

	/**
	 * Get the human-readable title of the importer.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Elementor Custom Code', 'code-snippets' );
	}

	/**
	 * Check whether Elementor Custom Code snippets are available on this site.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return post_type_exists( self::POST_TYPE );
	}

	/**
	 * Retrieve snippet data from Elementor Custom Code posts.
	 *
	 * @param array $ids_to_import Optional array of post IDs to import.
	 *
	 * @return array
	 */
	public function get_data( array $ids_to_import = [] ): array {
		$query_args = [
			'post_type'      => self::POST_TYPE,
			'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		if ( ! empty( $ids_to_import ) ) {
			$query_args['post__in'] = array_map( 'absint', $ids_to_import );
		}

		$posts = get_posts( $query_args );
		$data = [];

		foreach ( $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$location_raw = $this->read_location_raw( $post->ID );
			$code_kind = $this->read_code_kind( $post );
			$desc = $this->build_description( $post, $location_raw );

			$data[] = [
				'name'              => $post->post_title,
				'code'              => $this->read_snippet_code( $post ),
				'import_location'   => $location_raw,
				'code_kind'         => $code_kind,
				'priority'          => $this->read_priority( $post->ID ),
				'modified'          => $post->post_modified,
				'desc'              => $desc,
				'table_data'        => [
					'id'    => $post->ID,
					'title' => $post->post_title,
				],
			];
		}

		return $data;
	}

	/**
	 * Create a Snippet object from the provided snippet data.
	 *
	 * @param array $snippet_data Snippet data object.
	 * @param bool  $multisite    Whether the snippet is for a multisite network.
	 *
	 * @return Snippet|null The created Snippet object or null if unsupported.
	 */
	public function create_snippet( array $snippet_data, bool $multisite ): ?Snippet {
		$kind = $snippet_data['code_kind'] ?? '';

		if ( ! in_array( $kind, [ 'html', 'css', 'js' ], true ) ) {
			return null;
		}

		return parent::create_snippet( $snippet_data, $multisite );
	}

	/**
	 * Transform field value to match Code Snippets format.
	 *
	 * @param string $target_field Name of field.
	 * @param mixed  $value        Field value.
	 * @param array  $snippet_data Snippet data.
	 *
	 * @return mixed|null
	 */
	protected function transform_field_value( string $target_field, $value, array $snippet_data ) {
		if ( 'scope' === $target_field ) {
			return $this->transform_scope_value( $value, $snippet_data );
		}

		if ( 'code' === $target_field ) {
			return $this->transform_code_value( (string) $value, $snippet_data );
		}

		return $value;
	}

	/**
	 * Map Elementor location + code kind to a Code Snippets scope.
	 *
	 * @param mixed $location_value Raw location meta from Elementor.
	 * @param array $snippet_data   Full row from {@see get_data()}.
	 *
	 * @return string|null
	 */
	private function transform_scope_value( $location_value, array $snippet_data ): ?string {
		$kind = $snippet_data['code_kind'] ?? '';
		$bucket = $this->normalize_location_bucket( is_scalar( $location_value ) ? (string) $location_value : '' );

		switch ( $bucket ) {
			case 'head':
				if ( 'html' === $kind ) {
					return 'head-content';
				}
				if ( 'css' === $kind ) {
					return 'site-css';
				}
				if ( 'js' === $kind ) {
					return 'site-head-js';
				}
				break;
			case 'body_start':
				if ( 'html' === $kind ) {
					return 'footer-content';
				}
				if ( 'css' === $kind ) {
					return 'site-css';
				}
				if ( 'js' === $kind ) {
					return 'site-footer-js';
				}
				break;
			case 'body_end':
				if ( 'html' === $kind ) {
					return 'footer-content';
				}
				if ( 'css' === $kind ) {
					return 'site-css';
				}
				if ( 'js' === $kind ) {
					return 'site-footer-js';
				}
				break;
			default:
				break;
		}

		return null;
	}

	/**
	 * Reduce Elementor location strings to a small set of buckets.
	 *
	 * Elementor Pro stores {@see META_LOCATION} as `elementor_head`, `elementor_body_start`, or
	 * `elementor_body_end`. Older or mistaken keys may use `head`, `body_start`, `body_end`, etc.
	 *
	 * @param string $location Raw location meta.
	 *
	 * @return string One of head, body_start, body_end, unknown.
	 */
	private function normalize_location_bucket( string $location ): string {
		$s = strtolower( trim( $location ) );

		if ( '' === $s ) {
			return 'unknown';
		}

		$slug_map = [
			'elementor_head'       => 'head',
			'elementor_body_start' => 'body_start',
			'elementor_body_end'   => 'body_end',
			'head'                 => 'head',
			'header'               => 'head',
			'page_head'            => 'head',
			'head_tag'             => 'head',
			'body_start'           => 'body_start',
			'body-start'        => 'body_start',
			'body_open'         => 'body_start',
			'body-open'         => 'body_start',
			'wp_body_open'      => 'body_start',
			'body_end'          => 'body_end',
			'body-end'          => 'body_end',
			'body_close'        => 'body_end',
			'before_body_close' => 'body_end',
		];

		if ( isset( $slug_map[ $s ] ) ) {
			return $slug_map[ $s ];
		}

		if ( preg_match( '/^\d+$/', $s ) ) {
			return $this->normalize_location_numeric_bucket( $s );
		}

		if ( preg_match( '/\b(head|header)\b/i', $s ) && ! preg_match( '/\bbody\b/i', $s ) ) {
			return 'head';
		}

		if ( preg_match( '/body[\s_-]*start|start[\s_-]*body|body[\s_-]*open|wp_body|after_body|beginning[^\w]*of[^\w]*body/i', $s ) ) {
			return 'body_start';
		}

		if ( preg_match( '/body[\s_-]*end|end[\s_-]*body|before[^\w]*<\/\s*body|wp_footer/i', $s ) ) {
			return 'body_end';
		}

		if ( preg_match( '/\bhead\b/i', $s ) ) {
			return 'head';
		}

		return 'unknown';
	}

	/**
	 * Map legacy or alternate numeric location IDs to buckets (when meta stores integers as strings).
	 *
	 * @param string $digits Only digits.
	 *
	 * @return string
	 */
	private function normalize_location_numeric_bucket( string $digits ): string {
		switch ( $digits ) {
			case '1':
				return 'head';
			case '2':
				return 'body_start';
			case '3':
				return 'body_end';
			default:
				return 'unknown';
		}
	}

	/**
	 * Decode entities; strip outer script/style wrappers when importing JS/CSS scopes.
	 *
	 * @param string $code_value   Post content.
	 * @param array  $snippet_data Snippet data.
	 *
	 * @return string
	 */
	private function transform_code_value( string $code_value, array $snippet_data ): string {
		$code = html_entity_decode( $code_value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$kind = $snippet_data['code_kind'] ?? '';

		if ( 'css' === $kind ) {
			$code = preg_replace( '/<\s*style[^>]*>|<\s*\/\s*style\s*>/i', '', $code );
		}

		if ( 'js' === $kind ) {
			$code = preg_replace( '/<\s*script[^>]*>|<\s*\/\s*script\s*>/i', '', $code );
		}

		return trim( $code );
	}

	/**
	 * Read Elementor snippet priority from post meta (first non-empty known key).
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return int
	 */
	private function read_priority( int $post_id ): int {
		foreach ( self::META_PRIORITY_KEYS as $key ) {
			$raw = get_post_meta( $post_id, $key, true );
			if ( '' !== $raw && null !== $raw && false !== $raw ) {
				$priority = absint( $raw );
				return max( 1, min( 100, $priority ? $priority : 10 ) );
			}
		}

		return 10;
	}

	/**
	 * Location meta: Elementor Pro uses `_elementor_location`; fall back to legacy mistaken key.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	private function read_location_raw( int $post_id ): string {
		$primary = get_post_meta( $post_id, self::META_LOCATION, true );
		if ( is_string( $primary ) && '' !== trim( $primary ) ) {
			return trim( $primary );
		}

		$legacy = get_post_meta( $post_id, self::META_LOCATION_LEGACY, true );
		if ( is_string( $legacy ) && '' !== trim( $legacy ) ) {
			return trim( $legacy );
		}

		return '';
	}

	/**
	 * Snippet body: Elementor Pro stores code in `_elementor_code`; {@see META_CODE}.
	 *
	 * @param WP_Post $post Post object.
	 *
	 * @return string
	 */
	private function read_snippet_code( WP_Post $post ): string {
		$from_meta = get_post_meta( $post->ID, self::META_CODE, true );
		if ( is_string( $from_meta ) && '' !== trim( $from_meta ) ) {
			return $from_meta;
		}

		return is_string( $post->post_content ) ? $post->post_content : '';
	}

	/**
	 * Resolve html / css / js from optional legacy meta or from stored snippet code.
	 *
	 * @param WP_Post $post Post object.
	 *
	 * @return string One of html, css, js.
	 */
	private function read_code_kind( WP_Post $post ): string {
		foreach ( self::META_CODE_KIND_KEYS as $key ) {
			$raw = get_post_meta( $post->ID, $key, true );
			if ( is_string( $raw ) && '' !== trim( $raw ) ) {
				$normalized = $this->normalize_code_kind( $raw );
				if ( '' !== $normalized ) {
					return $normalized;
				}
			}
		}

		return $this->infer_code_kind_from_content( $this->read_snippet_code( $post ) );
	}

	/**
	 * Map Elementor type strings to html, css, or js.
	 *
	 * @param string $raw Raw meta value.
	 *
	 * @return string Empty if unknown.
	 */
	private function normalize_code_kind( string $raw ): string {
		$s = strtolower( trim( $raw ) );

		if ( in_array( $s, [ 'html', 'text', 'markup' ], true ) ) {
			return 'html';
		}

		if ( in_array( $s, [ 'css', 'stylesheet', 'style' ], true ) ) {
			return 'css';
		}

		if ( in_array( $s, [ 'js', 'javascript', 'script' ], true ) ) {
			return 'js';
		}

		return '';
	}

	/**
	 * Guess code kind when meta is missing.
	 *
	 * @param string $content Post content.
	 *
	 * @return string One of html, css, js.
	 */
	private function infer_code_kind_from_content( string $content ): string {
		$trim = trim( $content );

		if ( preg_match( '/^\s*<\s*script\b/i', $trim ) ) {
			return 'js';
		}

		if ( preg_match( '/^\s*<\s*style\b/i', $trim ) ) {
			return 'css';
		}

		return 'html';
	}

	/**
	 * Optional description noting Elementor conditions and imperfect body-start mapping.
	 *
	 * @param WP_Post $post          Snippet post.
	 * @param string  $location_raw  Raw location meta.
	 *
	 * @return string
	 */
	private function build_description( WP_Post $post, string $location_raw ): string {
		$parts = [];

		if ( '' !== $location_raw ) {
			$parts[] = sprintf(
				/* translators: %s: Elementor code location label or raw value */
				__( 'Elementor location: %s', 'code-snippets' ),
				$location_raw
			);
		}

		if ( 'body_start' === $this->normalize_location_bucket( $location_raw ) ) {
			$parts[] = __( 'Imported from Elementor “Body - Start”. Review the snippet scope in Code Snippets if output order should differ.', 'code-snippets' );
		}

		$conditions_note = $this->read_display_conditions_note( $post->ID );
		if ( '' !== $conditions_note ) {
			$parts[] = $conditions_note;
		}

		return implode( "\n\n", array_filter( $parts ) );
	}

	/**
	 * Append a short note when Elementor stores display conditions in meta.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	private function read_display_conditions_note( int $post_id ): string {
		$keys = [
			'_elementor_conditions',
			'_elementor_snippet_conditions',
			'_elementor_custom_code_conditions',
		];

		foreach ( $keys as $key ) {
			$raw = get_post_meta( $post_id, $key, true );
			if ( ! empty( $raw ) ) {
				return __( 'Elementor display conditions were not converted. Recreate conditions using Code Snippets if needed.', 'code-snippets' );
			}
		}

		return '';
	}
}
