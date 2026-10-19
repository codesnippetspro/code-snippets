<?php

namespace Code_Snippets\Client;

use Code_Snippets\Search\Search_Query;
use function Code_Snippets\Utils\unpack_response_body;

/**
 * Loads the index of documentation articles published at codesnippets.pro/docs.
 *
 * Trust model: the index is a static JSON file read from codesnippets.pro over
 * HTTPS, without credentials, and is treated as untrusted text. Titles, excerpts
 * and category names are reduced to plain text before they are stored, and links
 * are kept only when they lead back to codesnippets.pro. Nothing from the
 * response is executed or rendered as markup.
 *
 * @package Code_Snippets
 */
class Docs_Client {

	/**
	 * Transient holding the cached index.
	 */
	public const CACHE_KEY = 'code_snippets_docs_index';

	/**
	 * URL of the documentation index.
	 */
	protected const INDEX_URL = 'https://codesnippets.pro/wp-content/uploads/cs_docs/cs-docs-index.json';

	/**
	 * Host that article links must belong to.
	 */
	private const DOCS_HOST = 'codesnippets.pro';

	/**
	 * How long a fetched index is reused for.
	 */
	private const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * How long to wait before retrying after the index could not be fetched.
	 */
	private const FAILURE_TTL = 10 * MINUTE_IN_SECONDS;

	/**
	 * Request timeout, in seconds.
	 */
	private const REQUEST_TIMEOUT = 10;

	/**
	 * Largest number of articles kept from the index.
	 */
	private const MAX_ARTICLES = 500;

	/**
	 * Characters of each article's excerpt kept for matching.
	 */
	private const MAX_TEXT_LENGTH = 1000;

	/**
	 * Length of the identifier derived from an article's link.
	 */
	private const ID_LENGTH = 12;

	/**
	 * Retrieve the index of documentation articles, fetching it if it is not cached.
	 *
	 * @return array<int, array{id: string, title: string, url: string, text: string, category: string}>
	 */
	public function get_articles(): array {
		$cached = get_transient( self::CACHE_KEY );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$articles = $this->fetch_articles();
		set_transient( self::CACHE_KEY, $articles ?? [], is_null( $articles ) ? self::FAILURE_TTL : self::CACHE_TTL );

		return $articles ?? [];
	}

	/**
	 * Purge the cached index.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Reduce a value from the index to plain text.
	 *
	 * @param mixed $value Value, expected to be a string.
	 *
	 * @return string
	 */
	private static function text( $value ): string {
		return is_string( $value ) ? Search_Query::plain_text( $value ) : '';
	}

	/**
	 * Fetch the documentation index and keep the articles that are safe to show.
	 *
	 * @return array<int, array{id: string, title: string, url: string, text: string, category: string}>|null
	 *         Index of articles, or null if it could not be fetched.
	 */
	private function fetch_articles(): ?array {
		$response = wp_remote_get( self::INDEX_URL, [ 'timeout' => self::REQUEST_TIMEOUT ] );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = unpack_response_body( $response );

		if ( ! is_array( $body ) || ! is_array( $body['articles'] ?? null ) ) {
			return null;
		}

		$articles = [];

		foreach ( array_slice( $body['articles'], 0, self::MAX_ARTICLES ) as $article ) {
			if ( ! is_array( $article ) ) {
				continue;
			}

			$url = esc_url_raw( is_string( $article['url'] ?? null ) ? $article['url'] : '', [ 'https' ] );
			$title = self::text( $article['title'] ?? null );

			if ( '' === $title || self::DOCS_HOST !== wp_parse_url( $url, PHP_URL_HOST ) ) {
				continue;
			}

			$categories = is_array( $article['category'] ?? null ) ? $article['category'] : [];

			$articles[] = [
				'id'       => substr( md5( $url ), 0, self::ID_LENGTH ),
				'title'    => $title,
				'url'      => $url,
				'text'     => mb_substr( self::text( $article['excerpt'] ?? null ), 0, self::MAX_TEXT_LENGTH ),
				'category' => self::text( reset( $categories ) ),
			];
		}

		return $articles;
	}
}
