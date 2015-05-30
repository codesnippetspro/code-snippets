<?php

/**
 * Contains the class for handling the snippets table
 *
 * @package	Code_Snippets
 * @subpackage Administration
 */

/* The WP_List_Table base class is not included by default, so we need to load it */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * This class handles the table for the manage snippets menu
 *
 * @since 1.5
 * @package Code_Snippets
 */
class Code_Snippets_List_Table extends WP_List_Table {

	/**
	 * The constructor function for our class.
	 * Adds hooks, initializes variables, setups class.
	 */
	public function __construct() {
		global $status, $page;
		$screen = get_current_screen();

		/* Determine the status */
		$status = 'all';
		$statuses = array( 'active', 'inactive', 'recently_activated', 'admin', 'frontend' );
		if ( isset( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], $statuses ) ) {
			$status = $_REQUEST['status'];
		}

		/* Add the search query to the URL */
		if ( isset( $_REQUEST['s'] ) ) {
			$_SERVER['REQUEST_URI'] = add_query_arg( 's', stripslashes( $_REQUEST['s'] ) );
		}

		/* Add a snippets per page screen option */
		$page = $this->get_pagenum();

		add_screen_option( 'per_page', array(
			'label' => __( 'Snippets per page', 'code-snippets' ),
			'default' => 10,
			'option' => 'snippets_per_page',
		) );

		/* Set the table columns hidden in Screen Options by default */
		add_filter( "get_user_option_manage{$screen->id}columnshidden", array( $this, 'get_default_hidden_columns' ), 15 );

		/* Strip the result query arg from the URL */
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'result' );

		/* Add filters to format the snippet description in the same way the post content is formatted */
		$filters = array( 'wptexturize', 'convert_smilies', 'convert_chars', 'wpautop', 'shortcode_unautop', 'capital_P_dangit' );

		foreach ( $filters as $filter ) {
			add_filter( 'code_snippets/list_table/print_snippet_description', $filter );
		}

		/* Setup the class */
		parent::__construct( array(
			'ajax' => true,
			'plural' => 'snippets',
			'singular' => 'snippet',
		) );
	}

	/**
	 * Define the output of all columns that have no callback function
	 *
	 * @param  object $snippet	   The snippet object used for the current row
	 * @param  string $column_name The name of the column being printed
	 * @return string			   The content of the column to output
	 */
	function column_default( $snippet, $column_name ) {

		switch ( $column_name ) {
			case 'id':
				return $snippet->id;
			case 'description':
				return empty( $snippet->description ) ? '&#8212;' :
					apply_filters( 'code_snippets/list_table/column_description', $snippet->description );
			default:
				return apply_filters( "code_snippets/list_table/column_{$column_name}", $snippet );
		}
	}

	/**
	 * Build the content of the snippet name column
	 *
	 * @param  object $snippet The snippet object being used for the current row
	 * @return string		   The content of the column to output
	 */
	function column_name( $snippet ) {

		/* Build row actions */

		$actions = array();
		$is_network = get_current_screen()->is_network;
		$link_format = '<a href="%2$s">%1$s</a>';

		if ( $snippet->active ) {
			$actions['deactivate'] = sprintf(
				$link_format,
				$is_network ? __( 'Network Deactivate', 'code-snippets' ) : __( 'Deactivate', 'code-snippets' ),
				esc_url( add_query_arg( array(
					'action' => 'deactivate',
					'id' => $snippet->id,
				) ) )
			);
		} else {
			$actions['activate'] = sprintf(
				$link_format,
				$is_network ? __( 'Network Activate', 'code-snippets' ) : __( 'Activate', 'code-snippets' ),
				esc_url( add_query_arg( array(
					'action' => 'activate',
					'id' => $snippet->id,
				) ) )
			);
		}

		$actions['edit'] = sprintf(
			$link_format,
			__( 'Edit', 'code-snippets' ),
			get_snippet_edit_url( $snippet->id )
		);

		$actions['export'] = sprintf(
			$link_format,
			__( 'Export', 'code-snippets' ),
			esc_url( add_query_arg( array(
				'action' => 'export',
				'id' => $snippet->id,
			) ) )
		);

		$actions['delete'] = sprintf(
			'<a href="%2$s" class="delete" onclick="%3$s">%1$s</a>',
			__( 'Delete', 'code-snippets' ),
			esc_url( add_query_arg( array(
				'action' => 'delete',
				'id' => $snippet->id,
			) ) ),
			esc_js( sprintf(
				'return confirm("%s");',
				__("You are about to permanently delete the selected item.
				'Cancel' to stop, 'OK' to delete.", 'code-snippets' )
			) )
		);

		$title = empty( $snippet->name ) ? sprintf( __( 'Untitled #%d', 'code-snippets' ), $snippet->id ) : $snippet->name;

		$row_actions = $this->row_actions( $actions,
			apply_filters( 'code_snippets/list_table/row_actions_always_visiable', false )
		);

		$out = sprintf( '<a href="%2$s"><strong>%1$s</strong></a>', $title,	get_snippet_edit_url( $snippet->id ) );

		/* Return the name contents */
		return apply_filters( 'code_snippets/list_table/column_name', $out, $snippet ) . $row_actions;
	}

	/**
	 * Builds the checkbox column content
	 *
	 * @param  object $snippet The snippet object being used for the current row
	 * @return string		   The column content to be printed
	 */
	function column_cb( $snippet ) {
		$out = sprintf( '<input type="checkbox" name="ids[]" value="%s" />', $snippet->id );
		return apply_filters( 'code_snippets/list_table/column_cb', $out, $snippet );
	}

	/**
	 * Output the content of the tags column
	 * This function is used once for each row
	 *
	 * @since 2.0
	 * @param object $snippet
	 */
	function column_tags( $snippet ) {

		/* Return a placeholder if there are no tags */
		if ( empty( $snippet->tags ) ) {
			return '&#8212;';
		}

		$out = array();

		/* Loop through the tags and create a link for each one */
		foreach ( $snippet->tags_array as $tag ) {
			$out[] = sprintf( '<a href="%s">%s</a>',
				esc_url( add_query_arg( 'tag', esc_attr( $tag ) ) ),
				esc_html( $tag )
			);
		}

		return join( ', ', $out );
	}

	/**
	 * Define the column headers for the table
	 *
	 * @return array The column headers, ID paired with label
	 */
	function get_columns() {
		$columns = array(
			'cb'          => '<input type="checkbox">',
			'name'        => __( 'Name', 'code-snippets' ),
			'id'          => __( 'ID', 'code-snippets' ),
			'description' => __( 'Description', 'code-snippets' ),
			'tags'        => __( 'Tags', 'code-snippets' ),
		);
		return apply_filters( 'code_snippets/list_table/columns', $columns );
	}

	/**
	 * Define the columns that can be sorted
	 *
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
	 *
	 * @param  unknown $result
	 * @return unknown
	 */
	function get_default_hidden_columns( $result ) {
		return $result ? $result : array( 'id' );
	}

	/**
	 * Define the bulk actions to include in the drop-down menus
	 *
	 * @return array An array of menu items with the ID paired to the label
	 */
	function get_bulk_actions() {
		$is_network = get_current_screen()->is_network;
		$actions = array(
			'activate-selected'   => $is_network ? __( 'Network Activate', 'code-snippets' ) : __( 'Activate', 'code-snippets' ),
			'deactivate-selected' => $is_network ? __( 'Network Deactivate', 'code-snippets' ) : __( 'Deactivate', 'code-snippets' ),
			'export-selected'	  => __( 'Export', 'code-snippets' ),
			'delete-selected'	  => __( 'Delete', 'code-snippets' ),
			'export-php-selected' => __( 'Export to PHP', 'code-snippets' ),
		);
		return apply_filters( 'code_snippets/list_table/bulk_actions', $actions );
	}

	/**
	 * Retrieve the classes for the table
	 *
	 * We override this in order to add 'snippets' as a class for custom styling
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

		/* Loop through the view counts */
		foreach ( $totals as $type => $count ) {

			/* Don't show the view if there is no count or the label is not set */
			if ( ! $count || ! isset( $labels[ $type ] ) ) {
				continue;
			}

			/* Define the labels for each view */
			$labels = array(
				'all' => _n( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $count, 'code-snippets' ),
				'admin' => _n( 'Admin <span class="count">(%s)</span>', 'Admin <span class="count">(%s)</span>', $count, 'code-snippets' ),
				'active' => _n( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', $count, 'code-snippets' ),
				'inactive' => _n( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', $count, 'code-snippets' ),
				'frontend' => _n( 'Front End <span class="count">(%s)</span>', 'Front End <span class="count">(%s)</span>', $count, 'code-snippets' ),
				'recently_activated' => _n( 'Recently Active <span class="count">(%s)</span>', 'Recently Active <span class="count">(%s)</span>', $count, 'code-snippets' ),
			);

			/* The page URL with the status parameter */
			$url = esc_url( add_query_arg( 'status', $type ) );

			/* Add a class if this view is currently being viewed */
			$class = $type === $status ? ' class="current"' : '';

			/* Add the view count to the label */
			$text = sprintf( $labels[ $type ], number_format_i18n( $count ) );

			/* Construct the link */
			$status_links[ $type ] = sprintf( '<a href="%s"%s>%s</a>', $url, $class, $text );
		}

		/* Filter and return the list of views */
		return apply_filters( 'code_snippets/list_table/views', $status_links );
	}

	/**
	 * Gets the tags of the snippets currently being viewed in the table
	 * @since 2.0
	 */
	function get_current_tags() {
		global $snippets, $status;

		/* If we're not viewing a snippets table, get all used tags instead */
		if ( ! isset( $snippets, $status ) ) {
			return get_all_snippet_tags();
		}

		$tags = array();

		/* Merge all tags into a single array */
		foreach ( $snippets[ $status ] as $snippet ) {
			$tags = array_merge( $snippet->tags_array, $tags );
		}

		/* Remove duplicate tags */
		return array_values( array_unique( $tags, SORT_REGULAR ) );
	}

	/**
	 * Add filters and extra actions above and below the table
	 * @param string $which Are the actions displayed on the table top or bottom
	 */
	function extra_tablenav( $which ) {
		global $status, $wpdb;

		$screen = get_current_screen();

		if ( 'top' === $which ) {

			/* Tags dropdown filter */
			$tags = $this->get_current_tags();

			if ( count( $tags ) ) {
				$query = isset( $_GET['tag'] ) ? $_GET['tag'] : '';

				echo '<div class="alignleft actions">';
				echo '<select name="tag">';

				printf( "<option %s value=''>%s</option>\n",
					selected( $query, '', false ),
					__( 'Show all tags', 'code-snippets' )
				);

				foreach ( $tags as $tag ) {

					printf( "<option %s value='%s'>%s</option>\n",
						selected( $query, $tag, false ),
						esc_attr( $tag ),
						$tag
					);
				}

				echo '</select>';

				submit_button( __( 'Filter', 'code-snippets' ), 'button', false, false );
				echo '</div>';
			}
		}

		echo '<div class="alignleft actions">';

		if ( 'recently_activated' === $status ) {
			submit_button( __( 'Clear List', 'code-snippets' ), 'secondary', 'clear-recent-list', false );
		}

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
			array( 'page', 's', 'status', 'paged', 'tag' ),
			$context
		);

		if ( 'search_box' === $context ) {
			/* Remove the 's' var if we're doing this for the search box */
			$vars = array_diff( $vars, array( 's' ) );
		}

		foreach ( $vars as $var ) {
			if ( ! empty( $_REQUEST[ $var ] ) ) {
				printf( '<input type="hidden" name="%s" value="%s" />', $var, $_REQUEST[ $var ] );
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
	 * @uses activate_snippet() to activate snippets
	 * @uses deactivate_snippet() to deactivate snippets
	 * @uses delete_snippet() to delete snippets
	 * @uses export_snippets() to export selected snippets
	 * @uses wp_redirect() to pass the results to the current page
	 * @uses add_query_arg() to append the results to the current URI
	 */
	function process_bulk_actions() {
		$network = get_current_screen()->is_network;

		if ( isset( $_GET['action'], $_GET['id'] ) ) {

			$id = absint( $_GET['id'] );
			$action = sanitize_key( $_GET['action'] );
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'action', 'id' ) );

			if ( 'activate' === $action ) {
				activate_snippet( $id, $network );
				$result = 'activated';
			}
			elseif ( 'deactivate' === $action ) {
				deactivate_snippet( $id, $network );
				$result = 'deactivated';
			}
			elseif ( 'delete' === $action ) {
				delete_snippet( $id, $network );
				$result = 'deleted';
			}
			elseif ( 'export' === $action ) {
				export_snippets( $id, $network );
			}
			elseif ( 'export-php' === $action ) {
				export_snippets( $id, $network, 'php' );
			}

			if ( isset( $result ) ) {
				wp_redirect( esc_url_raw( add_query_arg( 'result', $result ) ) );
				exit;
			}
		}

		if ( ! isset( $_POST['ids'] ) ) {
			return;
		}

		$ids = $_POST['ids'];

		$_SERVER['REQUEST_URI'] = remove_query_arg( 'action' );

		switch ( $this->current_action() ) {

			case 'activate-selected':
				foreach ( $ids as $id ) {
					activate_snippet( $id, $network );
				}
				wp_redirect( esc_url_raw( add_query_arg( 'result', 'activated-multi' ) ) );
				exit;

			case 'deactivate-selected':
				foreach ( $ids as $id ) {
					deactivate_snippet( $id, $network );
				}
				wp_redirect( esc_url_raw( add_query_arg( 'result', 'deactivated-multi' ) ) );
				exit;

			case 'export-selected':
				export_snippets( $ids, $network );
				break;

			case 'export-php-selected':
				export_snippets( $ids, $network, 'php' );
				break;

			case 'delete-selected':
				foreach ( $ids as $id ) {
					delete_snippet( $id, $network );
				}
				wp_redirect( esc_url_raw( add_query_arg( 'result', 'deleted-multi' ) ) );
				exit;

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
			'admin' => array(),
			'frontend' => array(),
		);

		/* Redirect POST'ed tag filter to GET */
		if ( isset( $_POST['tag'] ) ) {
			$location = empty( $_POST['tag'] ) ? remove_query_arg( 'tag' ) : add_query_arg( 'tag', $_POST['tag'] );
			wp_redirect( esc_url_raw( $location ) );
			exit;
		}

		/* Filter snippets by tag */
		if ( ! empty( $_GET['tag'] ) ) {
			$snippets['all'] = array_filter( $snippets['all'], array( $this, 'tags_filter_callback' ) );
		}

		/* Filter snippets based on search query */
		if ( $s ) {
			$snippets['all'] = array_filter( $snippets['all'], array( $this, 'search_callback' ) );
		}

		/* Clear recently activated snippets older than a week */
		$recently_activated = $screen->is_network ?
			get_site_option( 'recently_activated_snippets', array() ) :
			get_option( 'recently_activated_snippets', array() );

		foreach ( $recently_activated as $key => $time ) {

			if ( $time + WEEK_IN_SECONDS < time() ) {
				unset( $recently_activated[ $key ] );
			}
		}

		$screen->is_network ?
			update_site_option( 'recently_activated_snippets', $recently_activated ) :
			update_option( 'recently_activated_snippets', $recently_activated );

		/* Filter snippets into individual sections */
		foreach ( (array) $snippets['all'] as $snippet ) {

			if ( $snippet->active ) {
				$snippets['active'][] = $snippet;
			} else {
				// Was the snippet recently activated?
				if ( isset( $recently_activated[ $snippet->id ] ) ) {
					$snippets['recently_activated'][] = $snippet;
				}
				$snippets['inactive'][] = $snippet;
			}

			if ( code_snippets_get_setting( 'general', 'snippet_scope_enabled' ) ) {

				if ( '1' == $snippet->scope ) {
					$snippets['admin'][] = $snippet;
				} elseif ( '2' == $snippet->scope ) {
					$snippets['frontend'][] = $snippet;
				}
			}
		}

		/* Count the totals for each section */
		$totals = array();
		foreach ( $snippets as $type => $list ) {
			$totals[ $type ] = count( $list );
		}

		/* If the current status is empty, default tp all */
		if ( empty( $snippets[ $status ] ) ) {
			$status = 'all';
		}

		/* Get the current data */
		$data = $snippets[ $status ];

		/*
		 * First, lets decide how many records per page to show
		 * by getting the user's setting in the Screen Options
		 * panel.
		 */
		$sort_by = $screen->get_option( 'per_page', 'option' );
		$per_page = get_user_meta( $user, $sort_by, true );

		if ( empty ( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		$per_page = (int) $per_page;

		$this->_column_headers = $this->get_column_info();

		usort( $data, array( $this, 'usort_reorder_callback' ) );

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
			'total_items' => $total_items, // Calculate the total number of items
			'per_page'	=> $per_page, // Determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page ), // Calculate the total number of pages
		) );
	}

	/**
	 * This checks for sorting input and sorts the data in our array accordingly.
	 * @ignore
	 */
	private function usort_reorder_callback( $a, $b ) {

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

	/**
	 * Callback for search function
	 * @ignore
	 */
	private function search_callback( $snippet ) {
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
	 * Callback for filtering snippets by tag
	 * @ignore
	 */
	private function tags_filter_callback( $snippet ) {
		$tags = explode( ',', $_GET['tag'] );

		foreach ( $tags as $tag ) {
			if ( in_array( $tag, $snippet->tags_array ) ) {
				return true;
			}
		}
	}

	/**
	 * Display a notice showing the current search terms
	 *
	 * @since 1.7
	 */
	public function search_notice() {
		if ( ! empty( $_REQUEST['s'] ) || ! empty( $_GET['tag'] ) ) {

			echo '<span class="subtitle">' . __( 'Search results', 'code-snippets' );

			if ( ! empty ( $_REQUEST['s'] ) ) {
				echo sprintf( __( ' for &#8220;%s&#8221;', 'code-snippets' ), esc_html( $_REQUEST['s'] ) );
			}

			if ( ! empty( $_GET['tag'] ) ) {
				echo sprintf( __( ' in tag &#8220;%s&#8221;', 'code-snippets' ), $_GET['tag'] );
			}

			echo '</span>';

			printf(
				'&nbsp;<a class="button clear-filters" href="%s">' . __( 'Clear Filters', 'code-snippets' ) . '</a>',
				esc_url( remove_query_arg( array( 's', 'tag' ) ) )
			);
		}
	}

	/**
	 * Retrieve the string representation of a snippet scope number
	 *
	 * @since 2.3.0
	 *
	 * @param  int    $scope The scope number
	 * @return string        The scope name
	 */
	private function get_scope_name( $scope ) {

		switch ( intval( $scope ) ) {
			case 1:
				return 'admin';
			case 2:
				return 'frontend';
			default:
			case 0:
				return 'global';
		}
	}

	/**
	 * Outputs content for a single row of the table
	 *
	 * @param object $snippet The snippet being used for the current row
	 */
	function single_row( $snippet ) {
		static $row_class = '';
		$row_class = ( $snippet->active ? 'active' : 'inactive' );
		$row_class .= sprintf( ' %s-scope', $this->get_scope_name( $snippet->scope ) );
		printf( '<tr class="%s">', $row_class );
		$this->single_row_columns( $snippet );
		echo '</tr>';
	}

} // end of class
