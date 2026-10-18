<?php

namespace Code_Snippets\Search;

/**
 * A universal search term, and the fields it is matched against.
 *
 * The term is split into words, and a result matches when every word appears
 * somewhere in the fields being searched, in any order and regardless of case.
 * The browser highlights the same words, so a row is never returned without
 * something in it to highlight.
 *
 * @package Code_Snippets
 */
final class Search_Query {

	/**
	 * Fields a result can be matched in.
	 */
	public const FIELDS = [ 'title', 'description', 'code' ];

	/**
	 * Ways a remote source can be searched: by keyword, or with AI, which matches by meaning.
	 */
	public const METHODS = [ 'term', 'ai' ];

	/**
	 * Shortest term worth searching for.
	 */
	public const MIN_LENGTH = 2;

	/**
	 * Longest term accepted, so a pasted document cannot become a search.
	 */
	public const MAX_LENGTH = 100;

	/**
	 * Largest number of words matched from a term.
	 */
	private const MAX_WORDS = 8;

	/**
	 * Length of a description excerpt, in characters.
	 */
	private const EXCERPT_LENGTH = 180;

	/**
	 * Characters of context kept ahead of a match that would otherwise fall outside the excerpt.
	 */
	private const EXCERPT_LEAD = 50;

	/**
	 * Search term.
	 *
	 * @var string
	 */
	private string $term;

	/**
	 * Fields to match the term against.
	 *
	 * @var string[]
	 */
	private array $fields;

	/**
	 * Whether long descriptions are shortened to an excerpt around the match.
	 *
	 * @var bool
	 */
	private bool $excerpts;

	/**
	 * How remote sources are searched.
	 *
	 * @var string
	 */
	private string $method;

	/**
	 * Words of the term, without duplicates.
	 *
	 * @var string[]
	 */
	private array $words;

	/**
	 * Regular expression matching any word of the term.
	 *
	 * @var string
	 */
	private string $pattern;

	/**
	 * Class constructor.
	 *
	 * @param string   $term     Search term.
	 * @param string[] $fields   Fields to match the term against. Unrecognised fields are ignored.
	 * @param bool     $excerpts Whether to shorten long descriptions to an excerpt around the match.
	 * @param string   $method   How remote sources are searched: one of `METHODS`.
	 */
	public function __construct( string $term, array $fields = self::FIELDS, bool $excerpts = true, string $method = 'term' ) {
		$this->term = mb_substr( trim( $term ), 0, self::MAX_LENGTH );
		$this->fields = array_values( array_intersect( self::FIELDS, $fields ) );
		$this->excerpts = $excerpts;
		$this->method = in_array( $method, self::METHODS, true ) ? $method : 'term';

		$words = preg_split( '/\s+/u', $this->term, -1, PREG_SPLIT_NO_EMPTY );
		$this->words = array_slice( array_values( array_unique( is_array( $words ) ? $words : [] ) ), 0, self::MAX_WORDS );

		$quoted = array_map( fn( string $word ) => preg_quote( $word, '/' ), $this->words );
		$this->pattern = '/' . implode( '|', $quoted ) . '/iu';
	}

	/**
	 * Clean a search term received from a request.
	 *
	 * The term is only ever compared against text and is escaped wherever it is
	 * displayed, so markup and code are kept intact: searching for `<?php` or
	 * `<div class="x">` has to find snippets containing exactly that. Only
	 * invalid UTF-8 and control characters are removed.
	 *
	 * @param mixed $term Raw search term.
	 *
	 * @return string
	 */
	public static function sanitize_term( $term ): string {
		$term = wp_check_invalid_utf8( is_scalar( $term ) ? (string) $term : '', true );

		return trim( (string) preg_replace( '/[\x00-\x1F\x7F]+/u', ' ', $term ) );
	}

	/**
	 * Retrieve the search term.
	 *
	 * @return string
	 */
	public function get_term(): string {
		return $this->term;
	}

	/**
	 * Retrieve the fields the term is matched against.
	 *
	 * @return string[]
	 */
	public function get_fields(): array {
		return $this->fields;
	}

	/**
	 * Retrieve how remote sources are searched.
	 *
	 * @return string
	 */
	public function get_method(): string {
		return $this->method;
	}

	/**
	 * Determine whether remote sources are searched with AI.
	 *
	 * @return bool
	 */
	public function is_ai(): bool {
		return 'ai' === $this->method;
	}

	/**
	 * Determine whether the term is long enough to search for.
	 *
	 * @return bool
	 */
	public function is_searchable(): bool {
		return mb_strlen( $this->term ) >= self::MIN_LENGTH && $this->words && $this->fields;
	}

	/**
	 * Determine whether a piece of text contains a word.
	 *
	 * @param string $text Text to look in.
	 * @param string $word Word to look for.
	 *
	 * @return bool
	 */
	private static function contains( string $text, string $word ): bool {
		$result = preg_match( '/' . preg_quote( $word, '/' ) . '/iu', $text );

		// The pattern fails outright on text that is not valid UTF-8.
		return false === $result ? false !== stripos( $text, $word ) : 1 === $result;
	}

	/**
	 * Determine which of the searched fields a result matches in.
	 *
	 * @param array<string, string> $values Text of each field, keyed by field name.
	 *
	 * @return string[] Names of the fields containing a word of the term, in the order of `FIELDS`.
	 *                  Empty unless every word was found in one of the searched fields.
	 */
	public function matched_fields( array $values ): array {
		$searched = array_intersect_key( $values, array_flip( $this->fields ) );
		$matched = [];

		foreach ( $this->words as $word ) {
			$found = array_filter( $searched, fn( string $text ) => '' !== $text && self::contains( $text, $word ) );

			if ( ! $found ) {
				return [];
			}

			$matched += $found;
		}

		return array_values( array_intersect( $this->fields, array_keys( $matched ) ) );
	}

	/**
	 * Count the distinct words of the term found in a piece of text.
	 *
	 * @param string $text Text to look in.
	 *
	 * @return int
	 */
	private function count_words_in( string $text ): int {
		return count( array_filter( $this->words, fn( string $word ) => self::contains( $text, $word ) ) );
	}

	/**
	 * Reduce markup to a single line of plain text.
	 *
	 * @param string $text Text, possibly containing markup.
	 *
	 * @return string
	 */
	public static function plain_text( string $text ): string {
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * Shorten plain text for display, keeping the first match in view.
	 *
	 * Text is returned whole when excerpts are turned off.
	 *
	 * @param string $text Plain text.
	 *
	 * @return string
	 */
	public function excerpt( string $text ): string {
		// Invalid UTF-8 would make the match unfindable and the excerpt start at the beginning.
		$text = wp_check_invalid_utf8( $text, true );

		if ( ! $this->excerpts || mb_strlen( $text ) <= self::EXCERPT_LENGTH ) {
			return $text;
		}

		$parts = $this->words ? preg_split( $this->pattern, $text, 2 ) : false;
		$match_offset = is_array( $parts ) && 2 === count( $parts ) ? mb_strlen( $parts[0] ) : 0;

		if ( $match_offset + self::EXCERPT_LEAD <= self::EXCERPT_LENGTH ) {
			return rtrim( mb_substr( $text, 0, self::EXCERPT_LENGTH ) ) . '…';
		}

		$start = max( 0, $match_offset - self::EXCERPT_LEAD );
		$excerpt = trim( mb_substr( $text, $start, self::EXCERPT_LENGTH ) );

		return '…' . $excerpt . ( $start + self::EXCERPT_LENGTH < mb_strlen( $text ) ? '…' : '' );
	}

	/**
	 * Shorten a line shown around a match, where it only gives a sense of the code nearby.
	 *
	 * @param string $line Line of code.
	 *
	 * @return string
	 */
	private static function context_line( string $line ): string {
		return mb_substr( trim( wp_check_invalid_utf8( $line, true ) ), 0, self::EXCERPT_LENGTH );
	}

	/**
	 * Find the line of code that best shows the match: the one containing the
	 * most words of the term, and the earliest of those.
	 *
	 * @param string $code Code to look in.
	 *
	 * @return array{line: int, text: string, before: string, after: string}|null Line number, counted
	 *         from one, the line itself, and the lines either side of it for context.
	 */
	public function find_code_line( string $code ): ?array {
		$lines = preg_split( '/\r\n|\r|\n/', $code );
		$best = null;
		$best_count = 0;

		$lines = is_array( $lines ) ? $lines : [];

		foreach ( $lines as $index => $line ) {
			$count = $this->count_words_in( $line );

			if ( $count > $best_count ) {
				$best_count = $count;
				$best = [
					'line'   => $index + 1,
					'text'   => $this->excerpt( trim( $line ) ),
					'before' => self::context_line( $lines[ $index - 1 ] ?? '' ),
					'after'  => self::context_line( $lines[ $index + 1 ] ?? '' ),
				];
			}
		}

		return $best;
	}
}
