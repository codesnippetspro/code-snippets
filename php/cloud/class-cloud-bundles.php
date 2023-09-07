<?php

namespace Code_Snippets\Cloud;

use Code_Snippets\Snippet;

use function Code_Snippets\save_snippet;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippet_by_cloud_id;


/**
 * This class handles the table for cloud bundles.
 *
 * @package Code_Snippets
 */
class Cloud_Bundles extends Cloud_Search_List_Table {

	/**
	 * Process any actions that have been submitted, such as downloading cloud snippets to the local database.
	 *
	 * @return void
	 */
	public function process_actions() {
		$_SERVER['REQUEST_URI'] = remove_query_arg( array( '_wpnonce', 'cloud-bundle-run', 'cloud-bundle-show', 'bundle_share_name', 'cloud_bundles' ) );

		if ( isset( $_REQUEST['cloud-bundle-run'] ) && sanitize_key( wp_unslash( $_REQUEST['cloud-bundle-run'] ) ) ) {
			$this->run_bundle_action( $this->items );
		}
	}

	/**
	 * Fetch the snippets used to populate the table.
	 *
	 * @return Cloud_Snippets
	 */
	public function fetch_snippets(): Cloud_Snippets {
		$bundle = intval( $_REQUEST['cloud_bundles'] ?? 0 );
		$bundle_share_name = sanitize_text_field( wp_unslash( $_REQUEST['bundle_share_name'] ?? '' ) );

		// Check if user own bundle selected.
		if ( 0 !== $bundle ) {
			return $this->cloud_api->get_snippets_from_bundle( $bundle );
		}

		// Check if user shared bundle entered.
		if ( $bundle_share_name ) {
			return $this->cloud_api->get_snippets_from_shared_bundle( $bundle_share_name );
		}

		// If no search or bundle is set, then return empty object.
		return new Cloud_Snippets();
	}

	/**
	 * Run the bundle action
	 *
	 * @param array $items List of cloud Snippets.
	 *
	 * @return void
	 */
	public function run_bundle_action( array $items ) {
		foreach ( $items as $snippet_to_store ) {
			// Check if the snippet already exists in the database.
			$codevault_snippet = get_snippet_by_cloud_id( $snippet_to_store->id . '_' . $snippet_to_store->is_owner );
			code_snippets()->cloud_api->download_snippet_from_cloud( $snippet_to_store, (bool) $codevault_snippet );
		}

		wp_safe_redirect( esc_url_raw( code_snippets()->get_menu_url() ) );
	}

	/**
	 * Text displayed when no bundle data is available.
	 *
	 * @return void
	 */
	public function no_items() {
		echo '<p>', esc_html__( 'Sorry, we cannot find a bundle with that share code or any snippets in this bundle. Please check and try again.', 'code-snippets' ), '</p>';
	}
}
