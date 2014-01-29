<?php

/**
 * Contains the class for handling the snippets table
 *
 * @package    Code_Snippets
 * @subpackage Administration
 */

/* The WP_List_Table base class is not included by default, so we need to load it */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * This class handles the table for the manage snippets menu
 *
 * @since   1.5
 * @access  private
 * @package Code_Snippets
 */
class Code_Snippets_List_Table extends WP_List_Table {

	/**#@+
	 * @since  1.5
	 * @access private
	 */

	/**
	 * The constructor function for our class.
	 * Adds hooks, initializes variables, setups class.
	 */
	function __construct() {
		global $status, $page;
		$screen = get_current_screen();

		/* Determine the status */
		$status = 'all';
		if ( isset( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], array( 'active', 'inactive', 'recently_activated' ) ) )
			$status = $_REQUEST['status'];

		/* Add the search query to the URL */
		if ( isset( $_REQUEST['s'] ) ) {
			$_SERVER['REQUEST_URI'] = add_query_arg( 's', stripslashes($_REQUEST['s'] ) );
		}

		/* Add a snippets per page screen option */
		$page = $this->get_pagenum();

		add_screen_option( 'per_page', array(
			'label' => __( 'Snippets per page', 'code-snippets' ),
			'default' => 10,
			'option' => 'snippets_per_page'
		) );

		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );

		/* Set the table columns hidden in Screen Options by default */
		add_filter( "get_user_option_manage{$screen->id}columnshidden", array( $this, 'get_default_hidden_columns' ), 15 );

		/* Strip once-off query args from the URL */
		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'activate', 'activate-multi', 'deactivate', 'deactivate-multi', 'delete', 'delete-multi' ) );

		/* Add filters to format the snippet description in the same way the post content is formatted */
		$filters = array( 'wptexturize', 'convert_smilies', 'convert_chars', 'wpautop', 'shortcode_unautop', 'capital_P_dangit' );

		foreach ( $filters as $filter ) {
			add_filter( 'code_snippets/list_table/print_snippet_description', $filter );
		}

		/* Setup the class */
		parent::__construct( array(
			'singular' => 'snippet',
			'plural'   => 'snippets',
			'ajax'     => true,
		) );
	}

	/**
	 * Handles saving the user's snippets per page preference
	 *
	 * @param  unknown $status
	 * @param  string  $option
	 * @param  unknown $value
	 * @return unknown
	 */
	function set_screen_option( $status, $option, $value ) {
		if ( 'snippets_per_page' === $option ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Define the output of all columns that have no callback function
	 * @param  object $snippet     The snippet object used for the current row
	 * @param  string $column_name The name of the column being printed
	 * @return string              The content of the column to output
	 */
	function column_default( $snippet, $column_name ) {

		switch( $column_name ) {
			case 'id':
				return $snippet->id;
			case 'description':
				if ( ! empty( $snippet->description ) ) {
					return apply_filters( 'code_snippets/list_table/print_snippet_description', $snippet->description );
				} else {
					return '&#8212;';
				}
			default:
				return do_action( "code_snippets/list_table/column_{$column_name}", $snippet );
		}
	}

	/**
	 * Builds content of the snippet name column
	 * @param  object $snippet The snippet object being used for the current row
	 * @return string          The content of the column to output
	 */
	function column_name( $snippet ) {

		/* Build row actions */

		$actions = array();
		$screen = get_current_screen();

		if ( $snippet->active ) {
			$actions['deactivate'] = sprintf(
				'<a href="%2$s">%1$s</a>',
				$screen->is_network ? __( 'Network Deactivate', 'code-snippets' ) : __( 'Deactivate', 'code-snippets' ),
				add_query_arg( array(
					'action' => 'deactivate',
					'id' =>	$snippet->id
				) )
			);
		} else {
			$actions['activate'] = sprintf(
				'<a href="%2$s">%1$s</a>',
				$screen->is_network ? __( 'Network Activate', 'code-snippets' ) : __( 'Activate', 'code-snippets' ),
				add_query_arg( array(
					'action' => 'activate',
					'id' =>	$snippet->id
				) )
			);
		}

		$actions['edit'] = sprintf(
			'<a href="%2$s">%1$s</a>',
			__( 'Edit', 'code-snippets' ),
			get_snippet_edit_url( $snippet->id )
		);

		$actions['export'] = sprintf(
			'<a href="%2$s">%1$s</a>',
			__( 'Export', 'code-snippets' ),
			add_query_arg( array(
				'action' => 'export',
				'id' =>	$snippet->id
			) )
		);

		$actions['delete'] = sprintf(
			'<a href="%2$s" class="delete" onclick="%3$s">%1$s</a>',
			__( 'Delete', 'code-snippets' ),
			add_query_arg( array(
				'action' => 'delete',
				'id' =>	$snippet->id
			) ),
			esc_js( sprintf(
				'return confirm("%s");',
				__("You are about to permanently delete the selected item.
				'Cancel' to stop, 'OK' to delete.", 'code-snippets' )
			) )
		);

		if ( ! empty( $snippet->name ) ) {
			$title = $snippet->name;
		} else {
			$title = sprintf ( __( 'Untitled #%d', 'code-snippets' ), $snippet->id );
		}

		$row_actions = $this->row_actions( $actions,
			apply_filters( 'code_snippets/list_table/row_actions_always_visiable', false )
		);

		/* Return the name contents */
		return apply_filters(
			'code_snippets/list_table/column_name',
			sprintf ( '<a href="%2$s"><strong>%1$s</strong></a>', $title,
				get_snippet_edit_url( $snippet->id )
			) . $row_actions,
			$snippet
		);
	}

	/**
	 * Builds the checkbox column content
	 * @param  object $snippet The snippet object being used for the current row
	 * @return string          The column content to be printed
	 */
    function column_cb( $snippet ) {
        return apply_filters(
			'code_snippets/list_table/column_cb',
			sprintf( '<input type="checkbox" name="ids[]" value="%s" />', $snippet->id ),
			$snippet
		);
    }

    /**
     * Define the column headers for the table
     * @return array The column headers, ID paired with label
     */
	function get_columns() {
		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'name'        => __( 'Name', 'code-snippets' ),
			'id'          => __( 'ID', 'code-snippets' ),
			'description' => __( 'Description', 'code-snippets' ),
		);
		return apply_filters( 'code_snippets/list_table/columns', $columns );
	}

	/**
	 * Define the columns that can be sorted
	 * @return array The IDs of the columns that can be sorted
	 */
	function get_sortable_columns() {
		$sortable_columns = array(
			'id'   => array( 'id', true ),
			'name' => array( 'name', false ),
		);
		return apply_filters( 'code_snippets/list_table/sortable_columns', $sortable_columns );
	}

	/**
	 * Define the columns that are hidden by default
	 * @param  unknown $result
	 * @return unknown
	 */
	function get_default_hidden_columns( $result ) {
		if ( ! $result ) {
			return array( 'id' );
		} else {
			return $result;
		}
	}

	/**
	 * Define the bulk actions to include in the drop-down menus
	 * @return array An array of menu items with the ID paired to the label
	 */
	function get_bulk_actions() {
		$screen = get_current_screen();
		$actions = array(
			'activate-selected'   => $screen->is_network ? __( 'Network Activate', 'code-snippets' ) : __( 'Activate', 'code-snippets' ),
			'deactivate-selected' => $screen->is_network ? __( 'Network Deactivate', 'code-snippets' ) : __( 'Deactivate', 'code-snippets' ),
			'export-selected'     => __( 'Export', 'code-snippets' ),
			'delete-selected'     => __( 'Delete', 'code-snippets' ),
			'export-php-selected' => __( 'Export to PHP', 'code-snippets' ),
		);
		return apply_filters( 'code_snippets/list_table/bulk_actions', $actions );
	}

	/**
	 * Retrieve the classes for the table
	 *
	 * We override this in order to add 'snippets' as a class
	 * for custom styling
	 *
	 * @return array The classes to include on the table element
	 */
	function get_table_classes() {
		$classes = array( 'widefat', $this->_args['plural'] );
		return apply_filters( 'code_snippets/list_table/table_classes', $classes );
	}

	/**
	 * Retrieve the 'views' of the table
	 *
	 * Example: active, inactive, recently active
	 *
	 * @return array A list of the view labels linked to the view
	 */
	function get_views() {
		global $totals, $status;

		$status_links = array();
		foreach ( $totals as $type => $count ) {

			if ( ! $count ) {
				continue;
			}

			switch ( $type ) {
				case 'all':
					$text = _n( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $count, 'code-snippets' );
					break;
				case 'active':
					$text = _n( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', $count, 'code-snippets' );
					break;
				case 'recently_activated':
					$text = _n( 'Recently Active <span class="count">(%s)</span>', 'Recently Active <span class="count">(%s)</span>', $count, 'code-snippets' );
					break;
				case 'inactive':
					$text = _n( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', $count, 'code-snippets' );
					break;
			}

			$status_links[$type] = sprintf( '<a href="%s"%s>%s</a>',
				add_query_arg( 'status', $type ),
				( $type === $status ) ? ' class="current"' : '',
				sprintf( $text, number_format_i18n( $count ) )
			);

		}

		return apply_filters( 'code_snippets/list_table/views', $status_links );
	}

	/**
	 * Add filters and extra actions above and below the table
	 * @param string $which Are the actions displayed on the table top or bottom
	 */
	function extra_tablenav( $which ) {
		global $status;

		$screen = get_current_screen();

		if ( 'top' === $which && has_action( 'code_snippets_list_table_filter_controls' ) ) {

			echo '<div class="alignleft actions">';

			do_action( 'code_snippets/list_table/filter_controls' );
			submit_button( __( 'Filter', 'code-snippets' ), 'button', false, false );

			echo '</div>';
		}

		echo '<div class="alignleft actions">';

		if ( 'recently_activated' === $status )
			submit_button( __( 'Clear List', 'code-snippets' ), 'secondary', 'clear-recent-list', false );

		do_action( 'code_snippets/list_table/actions', $which );

		echo '</div>';
	}

	/**
	 * Output form fields needed to preserve important
	 * query vars over form submissions
	 *
	 * @param string $context In what context are the fields being outputted?
	 */
	function required_form_fields( $context = 'main' ) {

		$vars = apply_filters(
			'code_snippets/list_table/required_form_fields',
			array( 'page', 's', 'status', 'paged' ),
			$context
		);

		if ( 'search_box' === $context ) {
			/* Remove the 's' var if we're doing this for the search box */
			$vars = array_diff( $vars, array( 's' ) );
		}

		foreach ( $vars as $var ) {
			if ( ! empty( $_REQUEST[ $var ] ) ) {
				printf ( '<input type="hidden" name="%s" value="%s" />', $var, $_REQUEST[ $var ] );
				print "\n";
			}
		}

		do_action( 'code_snippets/list_table/print_required_form_fields', $context );
	}


	/**
	 * Clear the recently activated snippets list if we've clicked the button
	 * @return string The action to execute
	 */
	function current_action() {
		if ( isset( $_POST['clear-recent-list'] ) ) {
			$action = 'clear-recent-list';
		} else {
			$action = parent::current_action();
		}
		return apply_filters( 'code_snippets/list_table/current_action', $action );
	}

	/**
	 * Processes a bulk action
	 *
	 * @uses activate_snippet() To activate snippets
	 * @uses deactivate_snippet() To deactivate snippets
	 * @uses delete_snippet() To delete snippets
	 * @uses export_snippet() To export selected snippets
	 * @uses wp_redirect() To pass the results to the current page
	 * @uses add_query_arg() To append the results to the current URI
	 */
	function process_bulk_actions() {
		$network = get_current_screen()->is_network;

		if ( isset( $_GET['action'], $_GET['id'] ) ) :

			$id = absint( $_GET['id'] );
			$action = sanitize_key( $_GET['action'] );
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'action', 'id' ) );

			if ( 'activate' === $action ) {
				activate_snippet( $id, $network );
			}
			elseif ( 'deactivate' === $action ) {
				deactivate_snippet( $id, $network );
			}
			elseif ( 'delete' === $action ) {
				delete_snippet( $id, $network );
			}
			elseif ( 'export' === $action ) {
				export_snippets( $id, $network );
			}
			elseif ( 'export-php' === $action ) {
				export_snippets_to_php( $id, $network );
			}

			if ( ! in_array( $action, array( 'export', 'export-php' ) ) ) {
				wp_redirect( apply_filters(
					"code_snippets/{$action}_redirect",
					add_query_arg( $action, true )
				) );
			}

		endif;

		if ( ! isset( $_POST['ids'] ) ) {
			return;
		}

		$ids = $_POST['ids'];

		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'activate', 'deactivate', 'delete', 'activate-multi', 'deactivate-multi', 'delete-multi' ) );

		switch( $this->current_action() ) {

			case 'activate-selected':
				foreach ( $ids as $id ) {
					activate_snippet( $id, $network );
				}
				wp_redirect( add_query_arg( 'activate-multi', true ) );
				break;

			case 'deactivate-selected':
				foreach ( $ids as $id ) {
					deactivate_snippet( $id, $network );
				}
				wp_redirect( add_query_arg( 'deactivate-multi', true ) );
				break;

			case 'export-selected':
				export_snippets( $ids, $network );
				break;

			case 'export-php-selected':
				export_snippets_to_php( $ids, $network );
				break;

			case 'delete-selected':
				foreach( $ids as $id ) {
					delete_snippet( $id, $network );
				}
				wp_redirect( add_query_arg( 'delete-multi', true ) );
				break;

			case 'clear-recent-list':
				if ( $network ) {
					update_site_option( 'recently_activated_snippets', array() );
				} else {
					update_option( 'recently_activated_snippets', array() );
				}
				break;
		}
	}

	/**
	 * Message to display if no snippets are found
	 */
	function no_items() {
		printf(
			__( 'You do not appear to have any snippets available at this time. <a href="%s">Add New&rarr;</a>', 'code-snippets' ),
			code_snippets_get_menu_url( 'add' )
		);
	}

	/**
	 * Prepares the items to later display in the table.
	 * Should run before any headers are sent.
	 */
	function prepare_items() {
		global $status, $snippets, $totals, $page, $orderby, $order, $s;

		wp_reset_vars( array( 'orderby', 'order', 's' ) );

		$screen = get_current_screen();
		$user = get_current_user_id();

		/* First, lets process the bulk actions */
		$this->process_bulk_actions();

		$snippets = array(
			'all' => apply_filters( 'code_snippets/list_table/get_snippets', get_snippets( $screen->is_network ) ),
			'active' => array(),
			'inactive' => array(),
			'recently_activated' => array(),
		);

		/* Filter snippets based on search query */
		if ( $s ) {
			$snippets['all'] = array_filter( $snippets['all'], array( &$this, '_search_callback' ) );
		}

		if ( $screen->is_network ) {
			$recently_activated = get_site_option( 'recently_activated_snippets', array() );
		} else {
			$recently_activated = get_option( 'recently_activated_snippets', array() );
		}

		$one_week = 7*24*60*60;
		foreach ( $recently_activated as $key => $time ) {

			if ( $time + $one_week < time() ) {
				unset( $recently_activated[$key] );
			}
		}

		if ( $screen->is_network ) {
			update_site_option( 'recently_activated_snippets', $recently_activated );
		} else {
			update_option( 'recently_activated_snippets', $recently_activated );
		}

		foreach ( (array) $snippets['all'] as $snippet ) {
			/* Filter into individual sections */
			if ( $snippet->active ) {
				$snippets['active'][] = $snippet;
			} else {
				if ( isset( $recently_activated[ $snippet->id ] ) ) // Was the snippet recently activated?
					$snippets['recently_activated'][] = $snippet;
				$snippets['inactive'][] = $snippet;
			}
		}

		$totals = array();
		foreach ( $snippets as $type => $list ) {
			$totals[ $type ] = count( $list );
		}

		if ( empty( $snippets[ $status ] ) ) {
			$status = 'all';
		}

		$data = $snippets[ $status ];

		/*
		 * First, lets decide how many records per page to show
		 * by getting the user's setting in the Screen Options
		 * panel.
		 */
		$sort_by       = $screen->get_option( 'per_page', 'option' );
		$screen_option = $screen->get_option( 'per_page', 'option' );
		$per_page      = get_user_meta( $user, $screen_option, true );

		if ( empty ( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		$per_page = (int) $per_page;

		$this->_column_headers = $this->get_column_info();

        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         *
         * @ignore
         */
        function usort_reorder( $a, $b ) {

			/* If no sort, default to ID */
            $orderby = (
            	! empty( $_REQUEST['orderby'] )
            	? $_REQUEST['orderby']
            	: apply_filters( 'code_snippets/list_table/default_orderby', 'id' )
            );

			/* If no order, default to ascending */
            $order = (
            	! empty( $_REQUEST['order'] )
            	? $_REQUEST['order']
            	: apply_filters( 'code_snippets/list_table/default_order', 'asc' )
            );

			/* Determine sort order */
			if ( 'id' === $orderby ) {
				$result = $a->$orderby - $b->$orderby; // get the result for numerical data
			} else {
				$result = strcmp( $a->$orderby, $b->$orderby ); // get the result for string data
			}

			/* Send final sort direction to usort */
            return ( 'asc' === $order ) ? $result : -$result;
        }

        usort( $data, 'usort_reorder' );

        /*
         * Let's figure out what page the user is currently
         * looking at.
         */
        $current_page = $this->get_pagenum();

        /*
         * Let's check how many items are in our data array.
         */
        $total_items = count( $data );

        /*
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page.
         */
        $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

        /*
         * Now we can add our *sorted* data to the items property, where
         * it can be used by the rest of the class.
         */
        $this->items = $data;

        /*
         * We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
			'total_items' => $total_items,                  // WE have to calculate the total number of items
			'per_page'    => $per_page,                     // WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items/$per_page)   // WE have to calculate the total number of pages
        ) );
	}

	/**
	 * Used internally
	 * @ignore
	 */
	function _search_callback( $snippet ) {
		static $term;
		if ( is_null( $term ) ) {
			$term = stripslashes( $_REQUEST['s'] );
		}

		foreach ( $snippet as $value ) {

			if ( is_string( $value ) ) {
				if ( false !== stripos( $value, $term ) ) {
					return true;
				}
			}
			elseif ( is_array( $value ) ) {
				if ( false !== in_array( $term, $value ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Display a notice showing the current search terms
	 *
	 * @since  1.7
	 * @access public
	 */
	public function search_notice() {
		if ( ! empty( $_REQUEST['s'] ) || apply_filters( 'code_snippets/list_table/search_notice', '' ) ) {

			echo '<span class="subtitle">' . __( 'Search results', 'code-snippets' );

			if ( ! empty ( $_REQUEST['s'] ) )
				echo sprintf ( __( ' for &#8220;%s&#8221;', 'code-snippets' ), esc_html( $_REQUEST['s'] ) );

			echo apply_filters( 'code_snippets/list_table/search_notice', '' );
			echo '</span>';

			printf (
				'&nbsp;<a class="button" href="%s">' . __( 'Clear Filters', 'code-snippets' ) . '</a>',
				remove_query_arg(
					apply_filters( 'code_snippets/list_table/required_form_fields',
						array( 's' ),
						'clear_filters'
					)
				)
			);
		}
	}

	/**
	 * Outputs content for a single row of the table
	 * @param object $snippet The snippet being used for the current row
	 */
	function single_row( $snippet ) {
		static $row_class = '';
		$row_class = ( $snippet->active ? 'active' : 'inactive' );
		printf ( '<tr class="%s">', $row_class );
		$this->single_row_columns( $snippet );
		echo '</tr>';
	}

} // end of class
