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
	 * The constructor function for our class.
	 * Adds hooks, initializes variables, setups class.
	 *
	 * @phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
	 */
	public function __construct( $cloud_snippets ) {
        global $status, $page;
        
        $this->cloud_snippets = $cloud_snippets;
        
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
		return $hidden;
	}

    /**
	 * Set the colums for the cloud table.
	 *
	 * @return array
	 */
    public function get_columns(){
        $columns = array(
            'cb'            => '<input type="checkbox">',
            'name'          => __( 'Name', 'code-snippets' ),
            'category'      => __( 'Category', 'code-snippets' ),
            'description'   => __( 'Description', 'code-snippets' ),
            'created'       => __( 'Created', 'code-snippets' ),
        );
        return $columns;
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
        $hidden = ['id'];
        $sortable = ['category'];
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
    function column_default( $item, $column_name ) {
        switch( $column_name ) { 
            case 'name':
            case 'category':
            case 'description':
            case 'created':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }

    /**
	 * Handles the checkbox column output.
	 *
	 * @param CS_Cloud $item The cloud snippet being used for the current row.
	 *
	 * @return string The column content to be printed.
	 */
	protected function column_cb( $item ) {

		$out = sprintf(
			'<input type="checkbox" name="%s[]" value="%s">',
			$item->shared_network ? 'shared_ids' : 'ids',
			intval( $item->id )
		);

		return apply_filters( 'code_snippets/list_table/column_cb', $out, $item );
	}
}
