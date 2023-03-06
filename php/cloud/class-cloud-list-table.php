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
class Cloud_List_Table extends WP_List_Table {

    /**
	 * Items for the cloud list table
	 *
	 * @var array
	 */
	public $cloud_snippets = array();

	/**
	 * Whether the current screen is in the network admin
	 *
	 * @var bool
	 */
	public $is_network;

	/**
	 * Total number of snippets
	 *
	 * @var int
	 */
	public $total_items;

	/**
     * Curent page number of search results
     *
     * @var int
     */
    public $current_page;

	/**
	 * Number of items per page
	 *
	 * @var int
	 */
	const SNIPPETS_PER_PAGE = 10;

    /**
	 * The constructor function for our class.
	 * Adds hooks, initializes variables, setups class.
	 *
	 * @phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
	 */
	public function __construct($cloud_snippets) {
        global $status, $page;
        
        $this->cloud_snippets = $cloud_snippets;
		$this->total_items = count($this->cloud_snippets);
		$this->is_network = is_network_admin();

		/* Strip the result query arg from the URL */
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'result' );
        
        /* Set up the class */
        parent::__construct( array(
            'singular'  => 'snippet',
            'plural'    => 'snippets',
            'ajax'      => false
        ) );

		//Add Thickbox and Render 
		$this->render_cloud_snippet_thickbox();
    }

    /**
	 * Set the 'id' column as hidden by default.
	 *
	 * @param array $hidden List of hidden columns.
	 *
	 * @return array
	 */
	public function default_hidden_columns( $hidden ) {
		$hidden[] = 'id';
		$hidden[] = 'code';
		$hidden[] = 'cloud_id';
		$hidden[] = 'revision';
		return $hidden;
	}

    /**
	 * Set the colums for the cloud table.
	 *
	 * @return array
	 */
    public function get_columns(){
        $columns = array(
			'cb'            => '<input type="checkbox" />',
            'id'          	=> __( 'id', 'code-snippets' ),			//Hidden
            'cloud_id'      => __( 'cloud_id', 'code-snippets' ),	//Hidden
            'code'          => __( 'code', 'code-snippets' ),		//Hidden
            'revision'      => __( 'revision', 'code-snippets' ),	//Hidden
            'name'          => __( 'Name', 'code-snippets' ),
            'scope'      	=> __( 'Type', 'code-snippets' ),
            'status'      	=> __( 'Status', 'code-snippets' ),
            'description'   => __( 'Description', 'code-snippets' ),
            'tags'   		=> __( 'Tags', 'code-snippets' ),
            'updated'       => __( 'Updated', 'code-snippets' ),
            'download'      => '',
        );
        
		return apply_filters( 'code_snippets/cloud_list_table/columns', $columns );
    }

    /** Text displayed when no snippet data is available */
    public function no_items() {
        esc_html_e( 'Looks like there are no snippets in your cloud codevault avaliable.', 'code-snippets' );
    }

	/**
	 * Define the bulk actions to include in the drop-down menus
	 *
	 * @return array An array of menu items with the ID paired to the label
	 */
	public function get_bulk_actions() {
		$actions = array(
			'download-selected'   => __( 'Download', 'code-snippets' ),
		);

		return apply_filters( 'code_snippets/cloud_list_table/bulk_actions', $actions );
	}


    /**
	 * Prapare the items for the table.
	 *
	 * @return void
	 */
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = ['id', 'code', 'cloud_id', 'revision'];
        $this->_column_headers = array($columns, $hidden);

		//Process any actions
		$this->process_actions();
		
		/* Determine what page the user is currently looking at */
		$this->current_page = $this->get_pagenum();

		/* The WP_List_Table class does not handle pagination for us, so we need to ensure that the data is trimmed to only the current page. */
		$data = array_slice( $this->cloud_snippets, ( ( $this->current_page - 1 ) * self::SNIPPETS_PER_PAGE ), self::SNIPPETS_PER_PAGE );
		
		$this->items = $data;
		
		/* We register our pagination options and calculations */
		$this->prepare_pagniation();
	}

	/**
	 * Process any actions that have been submitted
	 * For Example Download Snippet to Database
	 *
	 * @return void
	 */
	public function process_actions() {
		
		//Check if any actions were submitted
		if ( isset( $_REQUEST['action'] ) ){
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'action', 'snippet', '_wpnonce', 'source' ) );
			$result = CS_Cloud::download_or_update_snippet( sanitize_key( $_GET['snippet'] ), sanitize_key( $_GET['source'] ), sanitize_key( $_REQUEST['action'] ) );
			if ( $result['success'] ) {
				wp_safe_redirect( esc_url_raw( add_query_arg( 'result', $result['action'] ) ) );
				exit;
			}
		}
		//***TODO: Add code to action bulk download of snippets ****//
	}


	/**
	 * Process pagination request
	 *
	 * @return array
	 */
    public function prepare_pagniation() {
		$this->set_pagination_args( array(
            'per_page'    => self::SNIPPETS_PER_PAGE,
            'total_items' => $this->total_items,
            'total_pages' => ceil( $this->total_items / self::SNIPPETS_PER_PAGE ),
        ) );
    }

    /**
	 * Define the output of all columns that have no callback function
	 *
	 * @param CS_Cloud $item The snippet used for the current row.
	 * @param string  $column_name The name of the column being printed.
	 *
	 * @return string The content of the column to output.
	 */
    protected function column_default( $item, $column_name ) {
		$downloaded = $this->is_downloaded($item['cloud_id']);
        switch( $column_name ) { 
            case 'tags':
            case 'description':
                return $item[ $column_name ] . sprintf(
					'<input id="cloud-snippet-%s-%s" class="cloud-snippet-item" type="hidden" name="%s" value="%s" />',
					esc_attr( $column_name ),
					esc_attr( $item[ 'cloud_id' ]),
					esc_attr( $column_name ),
					esc_attr( $item[ $column_name ] )
				);
			case 'name':
				return sprintf(
					'<a href="%s">%s</a><input id="cloud-snippet-%s-%s" class="cloud-snippet-item" type="hidden" name="%s" value="%s" />',
					esc_url( '/wp-admin/admin.php?page=edit-snippet&id=' . $downloaded['local_id'] ),
					esc_attr( $item[ $column_name ] ),
					esc_attr( $column_name ),
					esc_attr( $item[ 'cloud_id' ]),
					esc_attr( $column_name ),
					esc_attr( $item[ $column_name ] )
				);
			case 'updated':
				return sprintf(
					'<span>%s</span><input id="cloud-snippet-%s-%s" class="cloud-snippet-item" type="hidden" name="%s" value="%s" />',
					esc_attr( $item[ $column_name ] ),
					esc_attr( $column_name ),
					esc_attr( $item[ 'cloud_id' ]),
					esc_attr( $column_name ),
					esc_attr( $item[ $column_name ] )
				);

			case 'id':
			case 'cloud_id':
			case 'code':
			case 'revision':
				return sprintf(
					'<input id="cloud-snippet-%s-%s" class="cloud-snippet-item" type="hidden" name="%s" value="%s" />',
					esc_attr( $column_name ),
					esc_attr( $item[ 'cloud_id' ]),
					esc_attr( $column_name ),
					esc_html( $item[ $column_name ] )
				);
			case 'status':
				$style = $this->get_style_from_status($item['status']);
				return sprintf(
					'<a class="snippet-type-badge snippet-status" data-type="%s">%s</a>',
					esc_attr( $style ),
					esc_html( $item['status'] )
				);
			case 'scope':
				$type = $this->get_type_from_scope($item['scope']);
				return sprintf(
					'<a id="snippet-type-%s" class="snippet-type-badge snippet-type" data-type="%s">%s</a>',
					esc_attr( $item[ 'cloud_id' ]),
					esc_attr( strtolower($type) ),
					esc_html( $type )
				);
			case 'download':
				return '';
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }

	/**
	 * Transpose the scope to a type
	 * 
	 * @param string $scope The scope of the snippet
	 * 
	 * @return string The type of the snippet
	 */
	public function get_type_from_scope($scope) {
		switch ($scope) {
			case 'global':
				return 'PHP';
			case 'site-css':
				return 'CSS';
			case 'site-footer-js':
				return 'JS';
			case 'content':
				return 'HTML';
		}
	}

	/**
	 * Transpose the status to a style
	 * 
	 * @param string $status The scope of the snippet
	 * 
	 * @return string The style to be used for the stats badge
	 */
	public function get_style_from_status($status) {
		switch ($status) {
			case 'AI Verified':
				return 'html';
			case 'Public':
				return 'js';
			case 'Private':
				return 'css';
			case 'Unverified':
				return 'unverified';
			default:
				return 'php';
		}
	}

	

	/**
	 * Define the columns that can be sorted. TODO: Add the ability to sort columns by clicking on the column name
	 *
	 * @return array The IDs of the columns that can be sorted
	 */
	public function get_sortable_columns() {

		$sortable_columns = array(
			'name'     => 'name',
			'type'     => array( 'type', true ),
			'status'   => array( 'status', true ),
			'updated' => array( 'updated', true ),
		);

		return apply_filters( 'code_snippets/cloud_list_table/sortable_columns', $sortable_columns );
	}

	/**
	 * Define the output of the 'download' column
	 *
	 * @param CS_Cloud $item The snippet used for the current row.
	 *
	 * @return string The content of the column to output.
	 */
	protected function column_download( $item ) {	
		$lang = strtolower( $this->get_type_from_scope($item['scope'] ) );
		if($lang == 'js'){ $lang = 'javascript'; }
		if($lang == 'html'){ $lang = 'markup'; }
		$downloaded = $this->is_downloaded($item['cloud_id']);
		if( $downloaded['is_downloaded'] ){
			if( $downloaded['update_available'] ){
				return sprintf('<a class="cloud-snippet-download" href="?page=%s&type=cloud&action=%s&snippet=%s&source=%s">Update Snippet</a>
					<a href="#TB_inline?&width=700&height=500&inlineId=show-code-preview" class="cloud-snippet-preview thickbox" data-snippet=%s data-lang=%s>Preview Update</a>', 
					esc_attr( $_REQUEST['page'] ), 
					'update', 
					esc_attr( $item['cloud_id'] ),
					esc_attr( 'codevault' ),
					esc_attr( $item['cloud_id'] ),
					esc_attr( $lang ),
				);			
			}
			return sprintf('<a href="%s" class="cloud-snippet-downloaded">View</a>',
					esc_url( '/wp-admin/admin.php?page=edit-snippet&id=' . $downloaded['local_id'] ));	
		}
		

		return sprintf('<a class="cloud-snippet-download" href="?page=%s&type=cloud&action=%s&snippet=%s&source=%s">Download</a>
				<a href="#TB_inline?&width=700&height=500&inlineId=show-code-preview" class="cloud-snippet-preview thickbox" data-snippet=%s data-lang=%s>Preview</a>', 
				esc_attr( $_REQUEST['page'] ), 
				'download', 
				esc_attr( $item['cloud_id'] ),
                esc_attr( 'codevault' ),
				esc_attr( $item['cloud_id'] ),
				esc_attr( $lang ),
		);		
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param Snippet $item The snippet being used for the current row.
	 *
	 * @return string The column content to be printed.
	 */
	protected function column_cb( $item ) {

		$out = sprintf(
			'<input type="checkbox" name="%s[]" value="%s">',
			$item->shared_network ? 'shared_ids' : 'ids',
			intval( $item->id )
		);

		return apply_filters( 'code_snippets/cloud_list_table/column_cb', $out, $item );
	}

	/**
	 * Retrieve the classes for the table
	 *
	 * We override this in order to add 'snippets' as a class for custom styling
	 *
	 * @return array The classes to include on the table element
	 */
	public function get_table_classes() {
		$classes = array( 'cloud-table', 'widefat', $this->_args['plural'] );

		return apply_filters( 'code_snippets/cloud_list_table/table_classes', $classes );
	}

	/**
	 * Outputs content for a single row of the table
	 *
	 * @param Snippet $item The snippet being used for the current row.
	 */
	public function single_row( $item ) {
		$style =  $this->get_style_from_status($item['status']) ;
		$downloaded = $this->is_downloaded($item['cloud_id']);
		if($downloaded){
			$status = 'inactive';
		}else{
			$status = 'active';
		}
		
		$row_class = "snippet $status-snippet $style-snippet";

		if ( $item->shared_network ) {
			$row_class .= ' shared-network-snippet';
		}

		printf( '<tr id="snippet-%s" class="%s" data-snippet-scope="%s">', 
			esc_attr( $item['cloud_id'] ),
			esc_attr( $row_class ), 
			esc_attr( $item->scope ) 
		);
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
     * Render Cloud Snippet Thickbox Popup
     * Returns the html for the thickbox popup
     *
     * @return string
     */
    public function render_cloud_snippet_thickbox(){
		add_thickbox(); //Add thickbox to the page
		
        echo
        '<div id="show-code-preview" style="display:none;">
                <p id="snippet-name-thickbox"></p>
                <p>Snippet Code:</p>
                <pre class="thickbox-code-viewer">
					<code id="snippet-code-thickbox" class=""></code>
				</pre>
		</div>';
		
	}

	/**
	 * Check if a snippet has been downloaded
	 * 
	 * @param string $cloud_id The cloud id of the snippet
	 * 
	 * @return array|bool
	 */
	public function is_downloaded($cloud_id){
		//Get the local to cloud map
		$local_to_cloud_map = get_transient('cs_local_to_cloud_map');
		//Filter the local to cloud map to get the snippet that is to be saved to the database
		$downloaded = array_filter($local_to_cloud_map, function ($var) use ($cloud_id) {
			return ($var['cloud_id'] == $cloud_id);
		});
		if(count($downloaded) > 0){
			return [ 
				'is_downloaded' => true, 
				'local_id' => reset($downloaded)['local_id'],
				'update_available' => reset($downloaded)['update_available'],
			];
		}
		return false;
	}

}
