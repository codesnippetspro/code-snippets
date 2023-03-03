<?php

namespace Code_Snippets;

use WP_List_Table;

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
     * Whether there are no search results
     *
     * @var bool
     */
    public $no_results;

    /**
     * Total number of pages of search results
     *
     * @var int
     */
    public $total_pages_results;

    /**
     * Curent page number of search results
     *
     * @var int
     */
    public $current_page;

    /**
     * Items for the cloud list table
     *
     * @var array
     */
    public $cloud_snippets = array();

    /**
     * The constructor function for our class.
     * Adds hooks, initializes variables, setups class.
     *
     * @phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
     */
    public function __construct($cloud_snippets, $no_results, $total_search_snippets, $total_pages_results ) {
        $this->no_results = $no_results;
        $this->cloud_snippets = $cloud_snippets;
        $this->$total_search_snippets = $total_search_snippets;
        $this->total_pages_results = $total_pages_results;
        parent::__construct($this->cloud_snippets);
    }

    /** Text displayed when no snippet data is available */
    public function no_items() {
        if($this->no_results){
            return print( '<p class="no-results">No snippets where found with that search term, please try again.</p>' );
        }
        esc_html_e( 'Please enter a search term to start searching code snippets in the cloud.', 'code-snippets' );
    }

    /**
	 * Prapare the items for the table.
	 *
	 * @return void
	 */
    public function prepare_items() {
        parent::prepare_items();
        $this->set_pagination_args( array(
            'total_items' => $this->total_search_snippets,
            'total_pages' => $this->total_pages_results,
            'per_page'    => $this->per_page,
        ) );
        
        //Get the paged number fro URL query params 
        if(isset($_GET['paged'])){
            $this->current_page = intval( $_GET['paged'] );
            //Get the search term ....???
        }
	}

    /**
     * Define the output of the 'download' column
     *
     * @param CS_Cloud $item The snippet used for the current row.
     *
     * @return string The content of the column to output.
     */
    protected function column_download( $item ) {
        $downloaded = $this->is_downloaded($item['cloud_id']);
        if($downloaded['in_local_site']){
            if( $downloaded['update_available'] ){
                return sprintf('<a class="cloud-snippet-download" href="?page=%s&type=cloud&action=%s&snippet=%s&source=%s">Update Available</a>
                    <a href="#TB_inline?&width=700&height=500&inlineId=show-code-preview" class="cloud-snippet-preview thickbox" data-snippet=%s>Preview</a>', 
                    esc_attr( $_REQUEST['page'] ), 
                    'udpate', 
                    esc_attr( $item['cloud_id'] ),
                    esc_attr( 'codevault' ),
                    esc_attr( $item['cloud_id'] ),
                );			
            }
            return sprintf('<a href="%s" class="cloud-snippet-downloaded">View</a>',
                esc_url( '/wp-admin/admin.php?page=edit-snippet&id=' . $downloaded['snippet_id'] ));
        }

        return sprintf('<a class="cloud-snippet-download" href="?page=%s&type=cloud&action=%s&snippet=%s&source=%s">Download</a>
				<a href="#TB_inline?&width=700&height=500&inlineId=show-code-preview" class="cloud-snippet-preview thickbox" data-snippet=%s>Preview</a>',
            esc_attr( $_REQUEST['page'] ),
            'download',
            esc_attr( $item['id'] ),
            esc_attr( 'search' ),
            esc_attr( $item['cloud_id'] ),
		);
    }

    /**
     * Define the output of the 'name' column
     *
     * @param CS_Cloud $item The snippet used for the current row.
     *
     * @return string The content of the column to output.
     */
    protected function column_name( $item ) {
        $downloaded = $this->is_downloaded( $item['cloud_id'] );
        if($downloaded['in_local_site']){
            return sprintf(
                '<a href="%s">%s</a><input id="cloud-snippet-%s-%s" class="cloud-snippet-item" type="hidden" name="%s" value="%s" />',
                esc_url( '/wp-admin/admin.php?page=edit-snippet&id=' . $downloaded['snippet_id'] ),
                esc_attr( $item[ 'name' ] ),
                esc_attr( 'name'),
                esc_attr( $item[ 'cloud_id' ]),
                esc_attr( 'name' ),
                esc_attr( $item[ 'name' ] )
            );
        }

        return sprintf(
            '<a>%s</a><input id="cloud-snippet-%s-%s" class="cloud-snippet-item" type="hidden" name="%s" value="%s" />',
            esc_attr( $item[ 'name' ] ),
            esc_attr( 'name' ),
            esc_attr( $item[ 'cloud_id' ]),
            esc_attr( 'name' ),
            esc_attr( $item[ 'name' ] )
        );
    }

    /**
     * Outputs content for a single row of the table
     *
     * @param Snippet $item The snippet being used for the current row.
     */
    public function single_row( $item ) {
        //$status = $item->active ? 'active' : 'inactive';
        $style =  $this->get_style_from_status($item['status']) ;
        $downloaded = $this->is_downloaded($item['cloud_id']);
        if($downloaded['downloaded']){
            $status = 'inactive';
        }else{
            $status = 'active';
        }

        $row_class = "snippet $status-snippet $style-snippet";

        if ( $item->shared_network ) {
            $row_class .= ' shared-network-snippet';
        }

        printf( '<tr class="%s" data-snippet-scope="%s">', esc_attr( $row_class ), esc_attr( $item->scope ) );
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    /**
     * Check if a snippet has been downloaded
     *
     * @param string $cloud_id The cloud id of the snippet
     *
     * @return array
     */
    public function is_downloaded($cloud_id){
        //Get the local to cloud map
        $local_to_cloud_map = get_transient('cs_local_to_cloud_map');
        //Filter the local to cloud map to get the snippet that is to be saved to the database
        $downloaded = array_filter($local_to_cloud_map, function ($var) use ($cloud_id) {
            return ($var['cloud_id'] == $cloud_id);
        });
        if(count($downloaded) > 0){
            $matches = array_values($downloaded);
            return [
                'in_local_site' => true,
                'snippet_id' => $matches[0]['local_id'],
                'update_available' => $matches[0]['update_available'],
            ];
        }
        return ['in_local_site' => false];
    }

}
