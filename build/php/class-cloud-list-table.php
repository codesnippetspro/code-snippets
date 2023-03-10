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
	 * The constructor function for our class.
	 * Adds hooks, initializes variables, setups class.
	 *
	 * @phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
	 */
	public function __construct( $cloud_snippets ) {
        global $status, $page;
        
        $this->cloud_snippets = $cloud_snippets;
		$this->is_network = is_network_admin();
        
        /* Set up the class */
        parent::__construct( array(
            'singular'  => 'snippet',
            'plural'    => 'snippets',
            'ajax'      => false
        ) );
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
        
		return apply_filters( 'code_snippets/list_table/columns', $columns );
    }

    /** Text displayed when no snippet data is available */
    public function no_items() {
        esc_html_e( 'Looks like there are no snippets in your cloud codevault avaliable.', 'code-snippets' );
    }

    /**
	 * Prapare the items for the table.
	 *
	 * @return void
	 */
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = ['id', 'code', 'cloud_id', 'revision'];
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $this->cloud_snippets;
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
        switch( $column_name ) { 
            case 'name':
            case 'tags':
            case 'description':
            case 'updated':
                return $item[ $column_name ] . sprintf(
					'<input id="cloud-snippet-%s-%s" class="cloud-snippet-item" type="hidden" name="%s" value="%s" />',
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
					'<a class="snippet-type-badge" data-type="%s">%s</a>',
					esc_attr( $style ),
					esc_html( $item['status'] )
				);
			
			case 'scope':
				$type = $this->get_type_from_scope($item['scope']);
				return sprintf(
					'<a class="snippet-type-badge" data-type="%s">%s</a>',
					esc_attr( $type ),
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
	private function get_type_from_scope($scope) {
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
	private function get_style_from_status($status) {
		switch ($status) {
			case 'AI Verified':
				return 'html';
			case 'Public':
				return 'js';
			case 'Private':
				return 'css';
			case 'Unverified':
				return 'css';
			default:
				return 'php';
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
		
		//**TODO** CLEAN THIS UP 

		if($item['update_available'] == 'true'){
			return sprintf('<a class="cloud-snippet-download" href="?page=%s&action=%s&snippet=%s">Update Available</a>
						<a href="#TB_inline?&width=700&height=500&inlineId=show-code-preview" class="cloud-snippet-preview thickbox" data-snippet=%s>Preview Snippet</a>', 
						esc_attr( $_REQUEST['page'] ), 
						'download', 
						absint( $item['cloud_id'] ),
						esc_attr( $item['cloud_id'] ),
				);
		}else{
			if($item['downloaded'] == 'true'){
				return sprintf('<p>Already Downloaded</p>
						<a href="#TB_inline?&width=700&height=500&inlineId=show-code-preview" class="cloud-snippet-preview thickbox" data-snippet=%s>Preview Snippet</a>', 
						esc_attr( $item['cloud_id'] ),
				);
			}else{
				return sprintf('<a class="cloud-snippet-download" href="?page=%s&action=%s&snippet=%s">Download</a>
						<a href="#TB_inline?&width=700&height=500&inlineId=show-code-preview" class="cloud-snippet-preview thickbox" data-snippet=%s>Preview Snippet</a>', 
						esc_attr( $_REQUEST['page'] ), 
						'download', 
						absint( $item['cloud_id'] ),
						esc_attr( $item['cloud_id'] ),
				);
			}
		}		
	}


	/**
	 * Define the output of the 'name' column
	 *
	 * @param CS_Cloud $item The snippet used for the current row.
	 *
	 * @return string The content of the column to output.
	 */
	// function column_name( $item ) {
	// 	$actions = array(
	// 		'install' => sprintf(
	// 			'<a href="?page=%s&action=%s&snippet=%s">Install</a>',
	// 			esc_attr( $_REQUEST['page'] ),
	// 			'install',
	// 			absint( $item['cloud_id'] )
	// 		),
	// 	);
		
	// 	return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions) );
	// }


}
