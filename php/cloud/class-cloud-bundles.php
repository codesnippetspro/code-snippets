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
class Cloud_Bundles extends Cloud_Search_List_Table{ 

    /**
	 * Process any actions that have been submitted, such as downloading cloud snippets to the local database.
	 *
	 * @return void
	 */
	public function process_actions() {
	
		$_SERVER['REQUEST_URI'] = remove_query_arg( array( '_wpnonce', 'cloud-bundle-run', 'cloud-bundle-show', 'bundle_share_name', 'cloud_bundles' ) );

		if ( isset( $_REQUEST['cloud-bundle-run'] ) ) {
			if ($_REQUEST['cloud-bundle-run']== 'true' ) {
				$this->run_bundle_action( $this->items );
			}
		}
				
	}


    /**
	 * Fetch the snippets used to populate the table.
	 *
	 * @return Cloud_Snippets
	 */
	public function fetch_snippets() {
		$bundle 		= $_REQUEST['cloud_bundles'] ?? 0;
		$bundle_share_name = $_REQUEST['bundle_share_name'] ?? '';
        
		//Check if user own bundle selected
		if( !$bundle == '0' || !$bundle == 0 ){
			$bundle = (int) sanitize_text_field( wp_unslash( $_REQUEST['cloud_bundles'] ) );
			return $this->cloud_api->get_snippets_from_bundle( $bundle );
		}
		//Check if user shared bundle entered
		if( !$bundle_share_name == '' ){
			$bundle_share_name = sanitize_text_field( wp_unslash( $_REQUEST['bundle_share_name'] ) );
			return $this->cloud_api->get_snippets_from_shared_bundle( $bundle_share_name );
		}
		
		//If no search or bundle is set, then return empty object
		return new Cloud_Snippets();
	}

    /**
	 * Run the bundle action
	 *
	 * @param array $snippets Array of Cloud Snippets
	 *
	 * @return void
	 */
	public function run_bundle_action( $items ) {
		
		foreach($items as $snippet_to_store){
			// Check if the snippet already exists in the database.
			$codevault_snippet = get_snippet_by_cloud_id( $snippet_to_store->id.'_'.$snippet_to_store->is_owner );
			//If the snippet exists then set in_codevault to true otherwise false
			$in_codevault = $codevault_snippet ? true : false;
		
			$snippet = new Snippet( $snippet_to_store );

			// Set the snippet id to 0 to ensure that the snippet is saved as a new snippet.
			$ownership = $snippet_to_store->is_owner ? '1' : '0';
			$snippet->id = 0;
			$snippet->active = 0;
			$snippet->cloud_id = $snippet_to_store->id.'_'.$ownership;
			$snippet->desc = $snippet_to_store->description ? $snippet_to_store->description : ''; //if no description is set, set it to empty string

			// Save the snippet to the database.
			$new_snippet = save_snippet( $snippet );

			$link = new Cloud_Link();
			$link->local_id = $new_snippet->id;
			$link->cloud_id = $snippet->cloud_id;
			$link->is_owner = $snippet_to_store->is_owner;
			$link->in_codevault = $in_codevault;
			$link->update_available = false;

			code_snippets()->cloud_api->add_map_link( $link );
		}

		// Redirect to the snippets page.
		wp_safe_redirect( admin_url( 'admin.php?page=snippets&type=all' ) );
	}

	/**
	 * Text displayed when no bundle data is available.
	 *
	 * @return void
	 */
	public function no_items() {
		
		echo '<p>', esc_html__( 'Sorry, we cannot find a bundle with that share code or any snippets in this bundle please check and try again.', 'code-snippets' ), '</p>';
	
	}

}