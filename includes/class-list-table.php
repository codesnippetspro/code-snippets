<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
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

	/**
	 * The constructor function for our class
	 *
	 * Adds hooks, initializes variables, setups class
	 */
	function __construct() {
		global $status, $page, $code_snippets;

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
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
		add_action( "admin_print_scripts-$code_snippets->admin_manage", array( $this, 'load_table_style' ) );

		parent::__construct( array(
			'singular' => 'snippet',
			'plural'   => 'snippets',
			'ajax'     => true,
		) );
	}

	/**
	 * Handles saving the user's screen option preference
	 *
	 * @since Code Snippets 1.5
	 * @access private
	 */
	function set_screen_option( $status, $option, $value ) {
		if ( 'snippets_per_page' === $option ) return $value;
	}

	/**
	 * Enqueue the table stylesheet
	 *
	 * @since Code Snippets 1.6
	 * @access private
	 *
	 * @uses wp_enqueue_style() To add the stylesheet to the queue
	 *
	 * @return void
	 */
	function load_table_style() {
		global $code_snippets;
		wp_enqueue_style(
			'snippets-table',
			plugins_url( 'assets/table.css', $code_snippets->file ),
			false,
			$code_snippets->version
		);
	}

	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'id':
				return intval( $item[ $column_name ] );
			case 'description':
				return stripslashes( html_entity_decode( $item[ $column_name ] ) );
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes
		}
	}

	function column_name( $item ) {
		global $code_snippets;
		$screen = get_current_screen();
		$actions = array(); // Build row actions

		if ( $item['active'] ) {
			$actions['deactivate'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				add_query_arg( array(
					'page' => $_REQUEST['page'],
					'action' => 'deactivate',
					'id' =>	$item['id']
				) ),
				$screen->is_network ? __('Network Deactivate', 'code-snippets') : __('Deactivate', 'code-snippets')
			);
		} else {
			$actions['activate'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				add_query_arg( array(
					'page' => $_REQUEST['page'],
					'action' => 'activate',
					'id' =>	$item['id']
				) ),
				$screen->is_network ? __('Network Activate', 'code-snippets') : __('Activate', 'code-snippets')
			);
		}

		$actions['edit'] = sprintf(
			'<a href="%s&edit=%s">Edit</a>',
			$code_snippets->admin_single_url,
			$item['id']
		);
		$actions['export'] = sprintf(
			'<a href="%s">Export</a>',
			add_query_arg( array(
				'page' => $_REQUEST['page'],
				'action' => 'export',
				'id' =>	$item['id']
			) )
		);
		$actions['delete'] = sprintf(
			'<a href="%1$s" class="delete" onclick="%2$s">Delete</a>',
			add_query_arg( array(
				'page' => $_REQUEST['page'],
				'action' => 'delete',
				'id' =>	$item['id']
			) ),
			esc_js( sprintf(
				'return confirm("%s");',
				__("You are about to permanently delete the selected item.
				'Cancel' to stop, 'OK' to delete.", 'code-snippets')
			) )
		);

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
		if ( ! $result )
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
			'export-php-selected' => __('Export to PHP', 'code-snippets'),
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
				$status_links[$type] = sprintf( '<a href="%s"%s>%s</a>',
					add_query_arg('status', $type, '?page=' . $_REQUEST['page'] ),
					( $type === $status ) ? ' class="current"' : '',
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

		if ( 'recently_activated' === $status )
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
	 * @uses $code_snippets->activate() To activate snippets
	 * @uses $code_snippets->deactivate() To deactivate snippets
	 * @uses $code_snippets->delete_snippet() To delete snippets
	 * @uses $code_snippets->export() To export selected snippets
	 * @uses wp_redirect To pass the results to the current page
	 * @uses add_query_arg() To append the results to the current URI
	 */
	function process_bulk_actions() {
		global $code_snippets;
		if ( ! isset( $_POST[ $this->_args['singular'] ] ) ) return;
		$ids = $_POST[ $this->_args['singular'] ];

		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'activate', 'deactivate', 'delete', 'activate-multi', 'deactivate-multi', 'delete-multi' ) );

		switch( $this->current_action() ) {

			case 'activate-selected':
				$code_snippets->activate( $ids );
				wp_redirect( add_query_arg( 'activate-multi', true ) );
				break;

			case 'deactivate-selected':
				$code_snippets->deactivate( $ids );
				wp_redirect( add_query_arg( 'deactivate-multi', true ) );
				break;

			case 'export-selected':
				$code_snippets->export( $ids );
				break;

			case 'export-php-selected':
				$code_snippets->export_php( $ids );
				break;

			case 'delete-selected':
				foreach( $ids as $id ) {
					$code_snippets->delete_snippet( $id );
				}
				wp_redirect( add_query_arg( 'delete-multi', true ) );
				break;

			case 'clear-recent-list':
				$screen = get_current_screen();
				if ( $screen->is_network )
					update_site_option( 'recently_activated_snippets', array() );
				else
					update_option( 'recently_activated_snippets', array() );
				break;
		}
	}

	/**
	 * Message to display if no snippets are found
	 */
	function no_items() {
		global $code_snippets;
		printf( __('You do not appear to have any snippets available at this time. <a href="%s">Add New&rarr;</a>', 'code-snippets'), $code_snippets->admin_single_url );
	}

	/**
	 * Prepares the items to later display in the table
	 *
	 * Should run before any headers are sent
	 */
	function prepare_items() {

		global $code_snippets, $status, $snippets, $totals, $page, $orderby, $order, $s;

		wp_reset_vars( array( 'orderby', 'order', 's' ) );

		$screen = get_current_screen();
		$user = get_current_user_id();

		// first, lets process the bulk actions
		$this->process_bulk_actions();

		$snippets = array(
			'all' => $code_snippets->get_snippets(),
			'search' => array(),
			'active' => array(),
			'inactive' => array(),
			'recently_activated' => array(),
		);

		if ( $screen->is_network )
			$recently_activated = get_site_option( 'recently_activated_snippets', array() );
		else
			$recently_activated = get_option( 'recently_activated_snippets', array() );

		$one_week = 7*24*60*60;
		foreach ( $recently_activated as $key => $time )
			if ( $time + $one_week < time() )
				unset( $recently_activated[$key] );

		if ( $screen->is_network )
			update_site_option( 'recently_activated_snippets', $recently_activated );
		else
			update_option( 'recently_activated_snippets', $recently_activated );

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

		if ( $s ) {
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
		 * by getting the user's setting in the Screen Options
		 * panel.
		 */
		$sort_by = $screen->get_option( 'per_page', 'option' );
		$screen_option = $screen->get_option( 'per_page', 'option' );

		$per_page = get_user_meta( $user, $screen_option, true );

		if ( empty ( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		$per_page = (int) $per_page;

		$this->_column_headers = $this->get_column_info();

        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         */
        function usort_reorder( $a, $b ) {

			// If no sort, default to id
            $orderby = ( ! empty($_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : apply_filters( 'code_snippets_default_orderby', 'id' );

			// If no order, default to asc
            $order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';

			// Determine sort order
			if ( 'id' === $orderby )
				$result = $a[$orderby] - $b[$orderby]; // get the result for numerical data
			else
				$result = strcmp( $a[$orderby], $b[$orderby] ); // get the result for string data

			// Send final sort direction to usort
            return ( 'asc' === $order ) ? $result : -$result;
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
        $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );


        /**
         * Now we can add our *sorted* data to the items property, where
         * it can be used by the rest of the class.
         */
        $this->items = $data;


        /**
         * We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
			'total_items' => $total_items,                  // WE have to calculate the total number of items
			'per_page'    => $per_page,                     // WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items/$per_page)   // WE have to calculate the total number of pages
        ) );
	}

	function _search_callback( $item ) {
		static $term;
		if ( is_null( $term ) )
			$term = stripslashes( $_REQUEST['s'] );

		foreach ( $item as $value )
			if ( false !== stripos( $value, $term ) )
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