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
		if ( isset( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], array( 'active', 'inactive', 'recently_activated' ) ) )
			$status = $_REQUEST['status'];

		if ( isset( $_REQUEST['s'] ) )
			$_SERVER['REQUEST_URI'] = add_query_arg( 's', stripslashes($_REQUEST['s'] ) );

		$page = $this->get_pagenum();

		add_screen_option( 'per_page', array(
			'label' => __('Snippets per page', 'code-snippets'),
			'default' => 10,
			'option' => 'snippets_per_page'
		) );

		add_filter( "get_user_option_manage{$screen->id}columnshidden", array( $this, 'get_default_hidden_columns' ), 15 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_table_style' ) );

		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'activate', 'activate-multi', 'deactivate', 'deactivate-multi', 'delete', 'delete-multi' ) );

		parent::__construct( array(
			'singular' => 'snippet',
			'plural'   => 'snippets',
			'ajax'     => true,
		) );
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
	function load_table_style( $hook ) {
		global $code_snippets;

		if ( $hook !== $code_snippets->admin_manage )
			return;

		if ( 'mp6' === get_user_option( 'admin_color' ) ) {

			wp_enqueue_style(
				'snippets-table',
				plugins_url( 'assets/table.mp6.css', $code_snippets->file ),
				false,
				$code_snippets->version
			);

		} else {

			wp_enqueue_style(
				'snippets-table',
				plugins_url( 'assets/table.css', $code_snippets->file ),
				false,
				$code_snippets->version
			);
		}
	}

	function format_description( $desc ) {
		$desc = wptexturize( $desc );
		$desc = convert_smilies( $desc );
		$desc = convert_chars( $desc );
		$desc = wpautop( $desc );
		$desc = shortcode_unautop( $desc );
		$desc = capital_P_dangit( $desc );
		return $desc;
	}

	function column_default( $snippet, $column_name ) {

		switch( $column_name ) {
			case 'id':
				return $snippet->id;
			case 'description':
				if ( ! empty( $snippet->description ) )
					return $this->format_description( $snippet->description );
				else
					return '&#8212;';
			default:
				return do_action( "code_snippets_list_table_column_{$column_name}", $snippet );
		}
	}

	function column_name( $snippet ) {
		global $code_snippets;
		$screen = get_current_screen();
		$actions = array(); // Build row actions

		if ( $snippet->active ) {
			$actions['deactivate'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				add_query_arg( array(
					'page' => $_REQUEST['page'],
					'action' => 'deactivate',
					'id' =>	$snippet->id
				) ),
				$screen->is_network ? __('Network Deactivate', 'code-snippets') : __('Deactivate', 'code-snippets')
			);
		} else {
			$actions['activate'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				add_query_arg( array(
					'page' => $_REQUEST['page'],
					'action' => 'activate',
					'id' =>	$snippet->id
				) ),
				$screen->is_network ? __('Network Activate', 'code-snippets') : __('Activate', 'code-snippets')
			);
		}

		$actions['edit'] = sprintf(
			'<a href="%s&edit=%s">' . __( 'Edit', 'code-snippets' ) . '</a>',
			$code_snippets->admin_single_url,
			$snippet->id
		);
		$actions['export'] = sprintf(
			'<a href="%s">' . __( 'Export', 'code-snippets' ) . '</a>',
			add_query_arg( array(
				'page' => $_REQUEST['page'],
				'action' => 'export',
				'id' =>	$snippet->id
			) )
		);
		$actions['delete'] = sprintf(
			'<a href="%1$s" class="delete" onclick="%2$s">' . __( 'Delete', 'code-snippets' ) . '</a>',
			add_query_arg( array(
				'page' => $_REQUEST['page'],
				'action' => 'delete',
				'id' =>	$snippet->id
			) ),
			esc_js( sprintf(
				'return confirm("%s");',
				__("You are about to permanently delete the selected item.
				'Cancel' to stop, 'OK' to delete.", 'code-snippets')
			) )
		);

		// Return the name contents
		return apply_filters(
			'code_snippets_list_table_column_name',
			'<strong>' . stripslashes( $snippet->name ) . '</strong>' . $this->row_actions( $actions, true ),
			$snippet
		);
	}

    function column_cb( $snippet ) {
        return apply_filters(
			'code_snippets_list_table_column_cb',
			sprintf( '<input type="checkbox" name="ids[]" value="%s" />', $snippet->id ),
			$snippet
		);
    }

	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'name' => __('Name', 'code-snippets'),
			'id' => __('ID', 'code-snippets'),
			'description' => __('Description', 'code-snippets'),
		);
		return apply_filters( 'code_snippets_list_table_columns', $columns );
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'id' => array( 'id', true ),
			'name' => array( 'name', false ),
		);
		return apply_filters( 'code_snippets_list_table_sortable_columns', $sortable_columns );
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
		return apply_filters( 'code_snippets_bulk_actions', $actions );
	}

	function get_table_classes() {
		$classes = array( 'widefat', $this->_args['plural'] );
		return apply_filters( 'code_snippets_table_classes', $classes );
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

			$status_links[$type] = sprintf( '<a href="%s"%s>%s</a>',
				add_query_arg( 'status', $type ),
				( $type === $status ) ? ' class="current"' : '',
				sprintf( $text, number_format_i18n( $count ) )
			);

		}

		return apply_filters( 'code_snippets_list_table_views', $status_links );
	}

	function extra_tablenav( $which ) {
		global $status, $code_snippets;

		$screen = get_current_screen();

		if ( 'top' === $which && has_action( 'code_snippets_list_table_filter_controls' ) ) {
			?>
				<div class="alignleft actions">
				<?php
					do_action( 'code_snippets_list_table_filter_controls' );
					submit_button( __('Filter', 'code-snippets'), 'button', false, false );
				?>
				</div>
			<?php
		}

		echo '<div class="alignleft actions">';

		if ( 'recently_activated' === $status )
			submit_button( __('Clear List', 'code-snippets'), 'secondary', 'clear-recent-list', false );

		do_action( 'code_snippets_list_table_actions', $which );

		echo '</div>';
	}

	function required_form_fields( $context = 'main' ) {

		$vars = apply_filters( 'code_snippets_list_table_required_form_fields', array( 'page', 's', 'status', 'paged' ), $context );

		if ( 'search_box' === $context ) {
			// remove the 's' var if we're doing this for the search box
			$vars = array_diff( $vars, array( 's' ) );
		}

		foreach ( $vars as $var ) {
			if ( ! empty( $_REQUEST[ $var ] ) ) {
				printf ( '<input type="hidden" name="%s" value="%s" />', $var, $_REQUEST[ $var ] );
				print "\n";
			}
		}

		do_action( 'code_snippets_list_table_print_required_form_fields', $context );
	}


	function current_action() {
		if ( isset( $_POST['clear-recent-list'] ) )
			$action = 'clear-recent-list';
		else
			$action = parent::current_action();
		return apply_filters( 'code_snippets_list_table_current_action', $action );
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

		if ( ! isset( $_POST['ids'] ) ) return;
		$ids = $_POST['ids'];

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
			'all' => apply_filters( 'code_snippets_list_table_get_snippets', $code_snippets->get_snippets() ),
			'active' => array(),
			'inactive' => array(),
			'recently_activated' => array(),
		);

		// filter snippets based on search query
		if ( $s ) {
			$snippets['all'] = array_filter( $snippets[ 'all' ], array( &$this, '_search_callback' ) );
		}

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
			if ( $snippet->active ) {
				$snippets['active'][] = $snippet;
			} else {
				if ( isset( $recently_activated[ $snippet->id ] ) ) // Was the snippet recently activated?
					$snippets['recently_activated'][] = $snippet;
				$snippets['inactive'][] = $snippet;
			}
		}

		$totals = array();
		foreach ( $snippets as $type => $list )
			$totals[ $type ] = count( $list );

		if ( empty( $snippets[ $status ] ) )
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
				$result = $a->$orderby - $b->$orderby; // get the result for numerical data
			else
				$result = strcmp( $a->$orderby, $b->$orderby ); // get the result for string data

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

	function _search_callback( $snippet ) {
		static $term;
		if ( is_null( $term ) )
			$term = stripslashes( $_REQUEST['s'] );

		foreach ( $snippet as $value ) {

			if ( is_string( $value ) ) {
				if ( false !== stripos( $value, $term ) )
					return true;
			}
			elseif ( is_array( $value ) ) {
				if ( false !== in_array( $term, $value ) )
					return true;
			}
		}

		return false;
	}

	function search_notice() {
		if ( ! empty( $_REQUEST['s'] ) || apply_filters( 'code_snippets_list_table_search_notice', '' ) ) {

			echo '<span class="subtitle">' . __('Search results', 'code-snippets');

			if ( ! empty ( $_REQUEST['s'] ) )
				echo sprintf ( __( ' for &#8220;%s&#8221;', 'code-snippets' ), esc_html( $_REQUEST['s'] ) );

			echo apply_filters( 'code_snippets_list_table_search_notice', '' );
			echo '</span>';

			printf (
				'&nbsp;<a class="button" href="%s">' . __('Clear Filters', 'code-snippets') . '</a>',
				remove_query_arg( apply_filters( 'code_snippets_list_table_required_form_fields', array( 's' ), 'clear_filters' ) )
			);
		}
	}

	/**
	 * Generates content for a single row of the table
	 */
	function single_row( $snippet ) {
		static $row_class = '';
		$row_class = ( $snippet->active ? 'active' : 'inactive' );

		echo '<tr class="' . $row_class . '">';
		echo $this->single_row_columns( $snippet );
		echo '</tr>';
	}
}