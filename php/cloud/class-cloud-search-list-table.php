<?php

namespace Code_Snippets\Cloud;

use function Code_Snippets\code_snippets;

/**
 * Contains the class for handling the snippets table
 *
 * @package Code_Snippets
 *
 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 */

/* The WP_List_Table base class is not included by default, so we need to load it */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * This class handles the table for the manage cloud snippets menu
 *
 * @property string $_pagination
 */
class Cloud_Search_List_Table extends Cloud_List_Table {

	/**
	 * Text displayed when no snippet data is available.
	 *
	 * @return void
	 */
	public function no_items() {
		if ( ! empty( $_REQUEST['cloud_search'] ) && count( $this->cloud_snippets->snippets ) < 1 ) {
			echo '<p class="no-results">',
			esc_html__( 'No snippets could be found with that search term. Please try again.', 'code-snippets' ),
			'</p>';
		} else {
			echo '<p>', esc_html__( 'Please enter a search term to start searching code snippets in the cloud.', 'code-snippets' ), '</p>';
		}
	}

	/**
	 * Fetch the snippets used to populate the table.
	 *
	 * @return Cloud_Snippets
	 */
	public function fetch_snippets() {
		// Create an empty results object if there's no search query.
		if ( empty( $_REQUEST['cloud_search'] ) ) {
			return new Cloud_Snippets();
		}

		// If we have a search query, then send a search request to cloud server API search endpoint.
		$search_query = sanitize_text_field( wp_unslash( $_REQUEST['cloud_search'] ) );
		return $this->cloud_api->fetch_search_results( $search_query, $this->get_pagenum() - 1 );
	}

	/**
	 * Gets the current search result page number.
	 *
	 * @return integer
	 */
	public function get_pagenum() {
		$page = isset( $_REQUEST['search_page'] ) ? absint( $_REQUEST['search_page'] ) : 0;

		if ( isset( $this->_pagination_args['total_pages'] ) && $page > $this->_pagination_args['total_pages'] ) {
			$page = $this->_pagination_args['total_pages'];
		}

		return max( 1, $page );
	}

	/**
	 * Displays the pagination.
	 *
	 * @param string $which Context where the pagination will be displayed.
	 *
	 * @return void
	 */
	protected function pagination( $which ) {
		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		/* translators: %s: Number of items. */
		$num = sprintf( _n( '%s item', '%s items', $total_items, 'code-snippets' ), number_format_i18n( $total_items ) );
		$output = '<span class="displaying-num">' . $num . '</span>';

		$current = $this->get_pagenum();
		$current_url = remove_query_arg( wp_removable_query_args() );

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after = '</span></span>';

		$disable_first = false;
		$disable_last = false;
		$disable_prev = false;
		$disable_next = false;

		if ( 1 === $current ) {
			$disable_first = true;
			$disable_prev = true;
		}

		if ( $total_pages === $current ) {
			$disable_last = true;
			$disable_next = true;
		}

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( remove_query_arg( 'search_page', $current_url ) ),
				__( 'First page' ),
				'&laquo;'
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'search_page', max( 1, $current - 1 ), $current_url ) ),
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}

		if ( 'bottom' === $which ) {
			$html_current_page = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		} else {
			$html_current_page = sprintf(
				"%s<input class='current-page' id='current-page-selector' type='text' name='search_page' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
				$current,
				strlen( $total_pages )
			);
		}
		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );

		/* translators: 1: Current page, 2: Total pages. */
		$current_html = _x( '%1$s of %2$s', 'paging', 'code-snippets' );
		$page_links[] = $total_pages_before . sprintf( $current_html, $html_current_page, $html_total_pages ) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'search_page', min( $total_pages, $current + 1 ), $current_url ) ),
				__( 'Next page' ),
				'&rsaquo;'
			);
		}

		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'search_page', $total_pages, $current_url ) ),
				__( 'Last page' ),
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class .= ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';

		$page_class = $total_pages ? ( $total_pages < 2 ? ' one-page' : '' ) : ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";
		echo wp_kses_post( $this->_pagination );
	}
}
