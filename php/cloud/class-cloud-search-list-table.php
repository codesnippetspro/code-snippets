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
 */
class Cloud_Search_List_Table extends Cloud_List_Table {

	/**
	 * Text displayed when no snippet data is available.
	 *
	 * @return void
	 */
	public function no_items() {
		if ( count( $this->cloud_snippets->snippets ) < 1 ) {
			echo '<p class="no-results">',
			esc_html__( 'No snippets could be found with that search term. Please try again.', 'code-snippets' ),
			'</p>';
		} else {
			esc_html_e( 'Please enter a search term to start searching code snippets in the cloud.', 'code-snippets' );
		}
	}

	/**
	 * Fetch the snippets used to populate the table.
	 *
	 * @return Cloud_Snippets
	 */
	public function fetch_snippets() {
		// Create an empty results object if there's no search query.
		if ( empty( $_REQUEST['s'] ) ) {
			return new Cloud_Snippets();
		}

		// If we have a search query, then send a search request to cloud server API search endpoint.
		$search_query = sanitize_text_field( $_REQUEST['s'] );
		return $this->cloud_api->fetch_search_results( $search_query, $this->get_pagenum() );
	}

	/**
	 * Define the output of the 'download' column
	 *
	 * @param Cloud_Snippet $item The snippet used for the current row.
	 *
	 * @return string The content of the column to output.
	 */
	protected function column_download( $item ) {
		$lang = $this->get_lang_from_scope( $item->scope );
		$link = $this->get_cloud_map_link( $item->cloud_id );

		if ( $link && ! $link->update_available ) {
			return sprintf(
				'<a href="%s" class="cloud-snippet-downloaded">%s</a>',
				esc_url( code_snippets()->get_snippet_edit_url( $link->local_id ) ),
				esc_html__( 'View', 'code-snippets' )
			);
		}

		$update_available = $link && $link->update_available;

		$download_url = add_query_arg( [
			'paged'   => $this->get_pagenum(),
			'type'    => 'cloud',
			'action'  => $update_available ? 'update' : 'download',
			'snippet' => $item->cloud_id,
			'source'  => 'search',
		] );

		$download_link = sprintf(
			'<a class="cloud-snippet-download" href="%s">%s</a>',
			esc_url( $download_url ),
			$update_available ?
				esc_html__( 'Update Available', 'code-snippets' ) :
				esc_html__( 'Download', 'code-snippets' )
		);

		$thickbox_link = sprintf(
			'<a href="#TB_inline?&width=700&height=500&inlineId=show-code-preview" class="cloud-snippet-preview thickbox" data-snippet="%s" data-lang="%s">%s</a>',
			esc_attr( $item->cloud_id ),
			esc_attr( $lang ),
			esc_html__( 'Preview', 'code-snippets' )
		);

		return $download_link . $thickbox_link;
	}
}
