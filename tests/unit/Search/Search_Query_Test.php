<?php

namespace Code_Snippets\Search;

use Code_Snippets\UnitTestCase;

/**
 * Tests for the universal search query matcher.
 *
 * @group search
 */
class Search_Query_Test extends UnitTestCase {

	/**
	 * A term shorter than the minimum is not searched for.
	 *
	 * @return void
	 */
	public function test_short_terms_are_not_searchable() {
		$this->assertFalse( ( new Search_Query( 'a' ) )->is_searchable() );
		$this->assertFalse( ( new Search_Query( '   ' ) )->is_searchable() );
		$this->assertTrue( ( new Search_Query( 'ab' ) )->is_searchable() );
	}

	/**
	 * A query with no recognised fields has nothing to search.
	 *
	 * @return void
	 */
	public function test_unrecognised_fields_are_ignored() {
		$query = new Search_Query( 'admin', [ 'title', 'author' ] );

		$this->assertSame( [ 'title' ], $query->get_fields() );
		$this->assertFalse( ( new Search_Query( 'admin', [ 'author' ] ) )->is_searchable() );
	}

	/**
	 * Matching ignores case and reports each field that contains the term.
	 *
	 * @return void
	 */
	public function test_reports_the_fields_that_match() {
		$query = new Search_Query( 'Admin Bar' );

		$matched = $query->matched_fields(
			[
				'title'       => 'Disable admin bar',
				'description' => 'Nothing relevant here.',
				'code'        => 'show_ADMIN_bar( false );',
			]
		);

		$this->assertSame( [ 'title', 'code' ], $matched );
	}

	/**
	 * The words of a term may be spread across different fields, in any order.
	 *
	 * @return void
	 */
	public function test_words_may_be_spread_across_fields() {
		$query = new Search_Query( 'toolbar disable' );

		$matched = $query->matched_fields(
			[
				'title'       => 'Disable admin bar',
				'description' => 'Hides the toolbar for subscribers.',
			]
		);

		$this->assertSame( [ 'title', 'description' ], $matched );
	}

	/**
	 * Nothing matches unless every word of the term is found.
	 *
	 * @return void
	 */
	public function test_every_word_must_be_found() {
		$query = new Search_Query( 'admin footer' );

		$this->assertSame(
			[],
			$query->matched_fields(
				[
					'title'       => 'Disable admin bar',
					'description' => 'Hides the toolbar.',
				]
			)
		);
	}

	/**
	 * A field that is not being searched cannot produce a match.
	 *
	 * @return void
	 */
	public function test_only_searches_the_requested_fields() {
		$query = new Search_Query( 'show_admin_bar', [ 'title', 'description' ] );

		$this->assertSame(
			[],
			$query->matched_fields(
				[
					'title' => 'Disable admin bar',
					'code'  => 'show_admin_bar( false );',
				]
			)
		);
	}

	/**
	 * Characters with a meaning in regular expressions are matched literally.
	 *
	 * @return void
	 */
	public function test_special_characters_are_matched_literally() {
		$query = new Search_Query( '$wp->add(' );

		$this->assertSame( [ 'code' ], $query->matched_fields( [ 'code' => 'return $wp->add( 1 );' ] ) );
		$this->assertSame( [], $query->matched_fields( [ 'code' => 'return $wp_add;' ] ) );
	}

	/**
	 * Markup is flattened to a single line of plain text.
	 *
	 * @return void
	 */
	public function test_plain_text_strips_markup() {
		$this->assertSame(
			'Hides the bar & more',
			Search_Query::plain_text( "<p>Hides the <strong>bar</strong>\n &amp; more</p>" )
		);
	}

	/**
	 * Short text is returned whole.
	 *
	 * @return void
	 */
	public function test_excerpt_leaves_short_text_alone() {
		$this->assertSame( 'Hides the toolbar.', ( new Search_Query( 'toolbar' ) )->excerpt( 'Hides the toolbar.' ) );
	}

	/**
	 * A match far into long text is brought into the excerpt.
	 *
	 * @return void
	 */
	public function test_excerpt_keeps_a_late_match_in_view() {
		$text = str_repeat( 'lorem ipsum dolor ', 30 ) . 'the needle is here ' . str_repeat( 'sit amet ', 30 );
		$excerpt = ( new Search_Query( 'needle' ) )->excerpt( $text );

		$this->assertStringContainsString( 'needle', $excerpt );
		$this->assertStringStartsWith( '…', $excerpt );
		$this->assertStringEndsWith( '…', $excerpt );
		$this->assertLessThan( 200, mb_strlen( $excerpt ) );
	}

	/**
	 * The code excerpt is the line containing the most words of the term, with
	 * the lines either side of it.
	 *
	 * @return void
	 */
	public function test_finds_the_most_relevant_line_of_code() {
		$code = "add_action( 'admin_init', function () {\n\t// nothing\n\tshow_admin_bar( false );\n} );";

		$this->assertSame(
			[
				'line'   => 3,
				'text'   => 'show_admin_bar( false );',
				'before' => '// nothing',
				'after'  => '} );',
			],
			( new Search_Query( 'admin bar' ) )->find_code_line( $code )
		);

		$this->assertSame( '', ( new Search_Query( 'admin_init' ) )->find_code_line( $code )['before'] );

		$this->assertNull( ( new Search_Query( 'missing' ) )->find_code_line( $code ) );
	}

	/**
	 * A long line of code is shortened around the match even when excerpts are turned off.
	 *
	 * @return void
	 */
	public function test_long_code_line_is_shortened_without_excerpts() {
		$line = str_repeat( '.a{color:red}', 200 ) . '.needle{color:blue}' . str_repeat( '.b{color:red}', 200 );
		$found = ( new Search_Query( 'needle', Search_Query::FIELDS, false ) )->find_code_line( $line );

		$this->assertStringContainsString( 'needle', $found['text'] );
		$this->assertLessThan( 200, mb_strlen( $found['text'] ) );
	}
}
