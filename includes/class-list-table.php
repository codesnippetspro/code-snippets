<?php

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Code Snippets List Table class.
 *
 * @package Code Snippets
 * @since 1.5
 * @access private
 */
class Code_Snippets_List_Table extends WP_List_Table {
	
	function __construct() {
		global $status, $page;
		
		$screen = get_current_screen();
		
		$status = 'all';
		if ( isset( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], array( 'active', 'inactive', 'recently_activated', 'search' ) ) )
			$status = $_REQUEST['status'];

		if ( isset( $_REQUEST['s'] ) )
			$_SERVER['REQUEST_URI'] = add_query_arg( 's', stripslashes($_REQUEST['s'] ) );

		$page = $this->get_pagenum();
		
		add_screen_option( 'per_page', array(
			'label' => __('Snippets per page', 'code-snippets'),
			'default' => 10,
			'option' => 'snippets_per_page'
		) );
		
		add_filter( "get_user_option_manage{$screen->id}columnshidden", array( $this, 'get_default_hidden_columns' ) );
		
		parent::__construct( array(
			'singular'  => 'snippet',
			'plural'	=> 'snippets',
			'ajax'	  => true,
		) );
	}
	
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'id':
				return intval( $item[$column_name] );
			case 'description':
				return stripslashes( html_entity_decode( $item[$column_name] ) );
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes
		}
	}
	
	function column_name( $item ) {
		global $cs;
		$screen = get_current_screen();
		$actions = array(); // Build row actions
		
		if( $item['active'] ) {
			$actions['deactivate'] = sprintf('<a href="?page=%s&action=deactivate&id=%s">%s</a>',$_REQUEST['page'],$item['id'], $screen->is_network ? __('Network Deactivate', 'code-snippets') : __('Deactivate', 'code-snippets') );
		} else {
			$actions['activate'] = sprintf('<a href="?page=%s&action=activate&id=%s">%s</a>',$_REQUEST['page'],$item['id'], $screen->is_network ? __('Network Activate', 'code-snippets') : __('Activate', 'code-snippets') );
		}
	
		$actions['edit'] = sprintf('<a href="%s&edit=%s">Edit</a>',$cs->admin_single_url,$item['id']);
		$actions['export'] = sprintf('<a href="?page=%s&action=export&id=%s">Export</a>',$_REQUEST['page'],$item['id']);
		$actions['delete'] = sprintf('<a href="?page=%s&action=delete&id=%s" class="delete" onclick="return confirm( "You are about to permanently delete the selected item.\n  \'Cancel\' to stop, \'OK\' to delete.">Delete</a>', $_REQUEST['page'], $item['id'] );
		
		// Return the name contents
		return '<strong>' . stripslashes( $item['name'] ) . '</strong>' . $this->row_actions( $actions, true );
	}
	
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  // Let's simply repurpose the table's singular label ("snippet")
            /*$2%s*/ $item['id']                // The value of the checkbox should be the snippet's id
        );
    }
	
	function get_columns() {
		return array(
			'cb' => '<input type="checkbox" />',
			'name' => __('Name', 'code-snippets'),
			'id' => __('ID', 'code-snippets'),
			'description' => __('Description', 'code-snippets'),
		);
	}
	
	function get_sortable_columns() {
		return array(
			'id' => array( 'id', true ),
			'name' => array( 'name', false ),
		);
	}
	
	function get_default_hidden_columns( $result ) {
		if( ! $result )
			return array( 'id' );
		else
			return $result;
	}
	
	function get_bulk_actions() {
		$screen = get_current_screen();
		$actions = array(
			'activate-selected' => $screen->is_network ? __('Network Activate', 'code-snippets') : __('Activate', 'code-snippets'),
			'deactivate-selected' => $screen->is_network ? __('Network Deactivate', 'code-snippets') : __('Deactivate', 'code-snippets'),
			'export-selected' => __('Export', 'code-snippets'),
			'delete-selected' => __('Delete', 'code-snippets'),
			'exportphp-selected' => __('Export to PHP', 'code-snippets'),
		);
		return $actions;
	}
	
	function get_table_classes() {
		return array( 'widefat', $this->_args['plural'] );
	}
	
	function get_views() {
		global $totals, $status;

		$status_links = array();
		foreach ( $totals as $type => $count ) {
			if ( !$count )
				continue;

			switch ( $type ) {
				case 'all':
					$text = _n( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $count, 'code-snippets');
					break;
				case 'active':
					$text = _n( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', $count, 'code-snippets');
					break;
				case 'recently_activated':
					$text = _n( 'Recently Active <span class="count">(%s)</span>', 'Recently Active <span class="count">(%s)</span>', $count, 'code-snippets');
					break;
				case 'inactive':
					$text = _n( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', $count, 'code-snippets');
					break;
			}

			if ( 'search' != $type ) {
				$status_links[$type] = sprintf( "<a href='%s' %s>%s</a>",
					add_query_arg('status', $type, '?page=' . $_REQUEST['page'] ),
					( $type == $status ) ? ' class="current"' : '',
					sprintf( $text, number_format_i18n( $count ) )
					);
			}
		}

		return $status_links;
	}
	
	function extra_tablenav( $which ) {
		global $status;

		if ( ! in_array($status, array('recently_activated') ) )
			return;

		echo '<div class="alignleft actions">';

		$screen = get_current_screen();

		if ( 'recently_activated' == $status )
			submit_button( __('Clear List', 'code-snippets'), 'secondary', 'clear-recent-list', false );

		echo '</div>';
	}

	function current_action() {
		if ( isset( $_POST['clear-recent-list'] ) )
			return 'clear-recent-list';

		return parent::current_action();
	}
	
	/**
	 * Processes a bulk action
	 *
	 * @uses $cs->activate() To activate snippets
	 * @uses $cs->deactivate() To deactivate snippets
	 * @uses $cs->delete_snippet() To delete snippets
	 * @uses cs_export() To export selected snippets
	 * @uses wp_redirect To pass the results to the current page
	 * @uses add_query_arg() To append the results to the current URI
	 */
	function process_bulk_actions() {
		global $cs;
		$ids = $_POST[ $this->_args['singular'] ];
		
		switch( $this->current_action() ) {
				
			case 'activate-selected':
				foreach( $ids as $id ) {
					$cs->activate( $id );
				}
				wp_redirect( add_query_arg( 'activate-multi', true ) );
				break;
					
			case 'deactivate-selected':
				foreach( $ids as $id ) {
					$cs->deactivate( $id );
				}
				wp_redirect( add_query_arg( 'deactivate-multi', true ) );
				break;
				
			case 'export-selected':
				if( ! function_exists( 'cs_export' ) )
					require_once $cs->plugin_dir . 'includes/export.php';
				cs_export( $ids );
				break;
				
			case 'exportphp-selected':
				if( ! function_exists( 'cs_export' ) )
					require_once $cs->plugin_dir . 'includes/export.php';
				cs_export( $ids, 'php' );
				break;
				
			case 'delete-selected':
				foreach( $ids as $id ) {
					$cs->delete_snippet( $id );
				}
				wp_redirect( add_query_arg( 'delete-multi', true ) );
				break;
				
			case 'clear-recent-list':
				$screen = get_current_screen();
				$option = ( $screen->is_network ? 'recently_network_activated_snippets' : 'recently_activated_snippets' );
				update_option( $option,	array() );
				break;
		}
	}
	
	function no_items() {
		global $cs;
		printf( __('You do not appear to have any snippets available at this time. <a href="%s">Add New&rarr;</a>', 'code-snippets'), $cs->admin_single_url );
	}
	
	function prepare_items() {
	
		global $wpdb, $cs, $status, $snippets, $totals, $page, $orderby, $order, $s;

		wp_reset_vars( array( 'orderby', 'order', 's' ) );
		
		$screen = get_current_screen();
		$user = get_current_user_id();
		
		$snippets = array(
			'all' => $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $cs->table" ), ARRAY_A ),
			'search' => array(),
			'active' => array(),
			'inactive' => array(),
			'recently_activated' => array(),
		);
		
		$option = $screen->is_network ? 'recently_network_activated_snippets' : 'recently_activated_snippets';
		$recently_activated = get_option( $option, array() );

		$one_week = 7*24*60*60;
		foreach ( $recently_activated as $key => $time )
			if ( $time + $one_week < time() )
				unset( $recently_activated[$key] );
		update_option( $option, $recently_activated );
		
		foreach ( (array) $snippets['all'] as $snippet ) {
			// Filter into individual sections
			if ( $snippet['active'] ) {
				$snippets['active'][] = $snippet;
			} else {
				if ( isset( $recently_activated[ $snippet['id'] ] ) ) // Was the snippet recently activated?
					$snippets['recently_activated'][] = $snippet;
				$snippets['inactive'][] = $snippet;
			}
		}

		if( $s ) {
			$status = 'search';
			$snippets['search'] = array_filter( $snippets['all'], array( &$this, '_search_callback' ) );
		}
		
		$totals = array();
		foreach ( $snippets as $type => $list )
			$totals[ $type ] = count( $list );
			
		if ( empty( $snippets[ $status ] ) && !in_array( $status, array( 'all', 'search' ) ) )
			$status = 'all';
			
		$data = $snippets[ $status ];
		
		/**
		 * First, lets decide how many records per page to show
		 * by getting the user's setting in the Screen Opions
		 * panel.
		 */
		$sort_by = $screen->get_option( 'per_page', 'option' );
		$screen_option = $screen->get_option( 'per_page', 'option' );
		
		$per_page = get_user_meta( $user, $screen_option, true );
		
		if( empty ( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}
		
		$per_page = (int) $per_page;
		
		$this->_column_headers = $this->get_column_info();
		
		$this->process_bulk_actions();
		
        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         */
        function usort_reorder( $a, $b ) {
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'id'; //If no sort, default to id
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');
        
                
        /**
         * Let's figure out what page the user is currently 
         * looking at.
         */
        $current_page = $this->get_pagenum();
        
        /**
         * Let's check how many items are in our data array.
         */
        $total_items = count($data);
        
        
        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page.
         */
        $data = array_slice($data,(($current_page-1)*$per_page), $per_page);
      
	  
        /**
         * Now we can add our *sorted* data to the items property, where 
         * it can be used by the rest of the class.
         */
        $this->items = $data;
        
        
        /**
         * We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
	}
	
	function _search_callback( $item ) {
		static $term;
		if ( is_null( $term ) )
			$term = stripslashes( $_REQUEST['s'] );

		foreach ( $item as $value )
			if ( stripos( $value, $term ) !== false )
				return true;

		return false;
	}
	
	/**
	 * Generates content for a single row of the table
	 */
	function single_row( $item ) {
		static $row_class = '';
		$row_class = ( $item['active'] ? 'active' : 'inactive' );

		echo '<tr class="' . $row_class . '">';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	}
}

?>