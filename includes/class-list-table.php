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
			'ajax'	 => true,
		) );
	}

	/**
	 * Define the output of all columns that have no callback function
	 * @param  object $snippet	 The snippet object used for the current row
	 * @param  string $column_name The name of the column being printed
	 * @return string			  The content of the column to output
	 */
	function column_default( $snippet, $column_name ) {

		switch ( $column_name ) {
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
	 * @return string		  The content of the column to output
	 */
	function column_name( $snippet ) {

		/* Build row actions */

		$actions = array();
		$screen = get_current_screen();

		if ( $snippet->active ) {
			$actions['deactivate'] = sprintf(
				'<a href="%2$s">%1$s</a>',
				$screen->is_network ? __( 'Network Deactivate', 'code-snippets' ) : __( 'Deactivate', 'code-snippets' ),
				esc_url( add_query_arg( array(
					'action' => 'deactivate',
					'id' => $snippet->id,
				) ) )
			);
		} else {
			$actions['activate'] = sprintf(
				'<a href="%2$s">%1$s</a>',
				$screen->is_network ? __( 'Network Activate', 'code-snippets' ) : __( 'Activate', 'code-snippets' ),
				esc_url( add_query_arg( array(
					'action' => 'activate',
					'id' => $snippet->id,
				) ) )
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

		if ( ! empty( $snippet->name ) ) {
			$title = $snippet->name;
		} else {
			$title = sprintf( __( 'Untitled #%d', 'code-snippets' ), $snippet->id );
		}

		$row_actions = $this->row_actions( $actions,
			apply_filters( 'code_snippets/list_table/row_actions_always_visiable', false )
		);

		/* Return the name contents */
		return apply_filters(
			'code_snippets/list_table/column_name',
			sprintf( '<a href="%2$s"><strong>%1$s</strong></a>', $title,
				get_snippet_edit_url( $snippet->id )
			) . $row_actions,
			$snippet
		);
	}

	/**
	 * Builds the checkbox column content
	 * @param  object $snippet The snippet object being used for the current row
	 * @return string		  The column content to be printed
	 */
	function column_cb( $snippet ) {
		return apply_filters(
			'code_snippets/list_table/column_cb',
			sprintf( '<input type="checkbox" name="ids[]" value="%s" />', $snippet->id ),
			$snippet
		);
	}

	/**
	* Output the content of the tags column
	* This function is used once for each row
	* @since 2.0
	* @param object $snippet
	*/
	function column_tags( $snippet ) {

		if ( ! empty( $snippet->tags ) ) {

			foreach ( $snippet->tags as $tag ) {
				$out[] = sprintf( '<a href="%s">%s</a>',
					esc_url( add_query_arg( 'tag', esc_attr( $tag ) ) ),
					esc_html( $tag )
				);
			}
			echo join( ', ', $out );
		} else {
			echo '&#8212;';
		}
	}

	/**
	 * Define the column headers for the table
	 * @return array The column headers, ID paired with label
	 */
	function get_columns() {
		$columns = array(
			'cb'		  => '<input type="checkbox" />',
			'name'		=> __( 'Name', 'code-snippets' ),
			'id'		  => __( 'ID', 'code-snippets' ),
			'description' => __( 'Description', 'code-snippets' ),
			'tags'		=> __( 'Tags', 'code-snippets' ),
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
			'export-selected'	 => __( 'Export', 'code-snippets' ),
			'delete-selected'	 => __( 'Delete', 'code-snippets' ),
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
				case 'admin':
					$text = _n( 'Admin <span class="count">(%s)</span>', 'Admin <span class="count">(%s)</span>', $count, 'code-snippets' );
					break;
				case 'frontend':
					$text = _n( 'Front End <span class="count">(%s)</span>', 'Front End <span class="count">(%s)</span>', $count, 'code-snippets' );
					break;
			}

			$status_links[ $type ] = sprintf( '<a href="%s"%s>%s</a>',
				esc_url( add_query_arg( 'status', $type ) ),
				( $type === $status ) ? ' class="current"' : '',
				sprintf( $text, number_format_i18n( $count ) )
			);

		}

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
			$tags = array_merge( $snippet->tags, $tags );
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
	 * @uses activate_snippet() To activate snippets
	 * @uses deactivate_snippet() To deactivate snippets
	 * @uses delete_snippet() To delete snippets
	 * @uses export_snippets() To export selected snippets
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
				export_snippets( $id, $network, 'php' );
			}

			if ( ! in_array( $action, array( 'export', 'export-php' ) ) ) {
				wp_redirect( apply_filters(
					"code_snippets/{$action}_redirect",
					esc_url_raw( add_query_arg( $action, true ) )
				) );
			}

		endif;

		if ( ! isset( $_POST['ids'] ) ) {
			return;
		}

		$ids = $_POST['ids'];

		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'activate', 'deactivate', 'delete', 'activate-multi', 'deactivate-multi', 'delete-multi' ) );

		switch ( $this->current_action() ) {

			case 'activate-selected':
				foreach ( $ids as $id ) {
					activate_snippet( $id, $network );
				}
				wp_redirect( esc_url_raw( add_query_arg( 'activate-multi', true ) ) );
				break;

			case 'deactivate-selected':
				foreach ( $ids as $id ) {
					deactivate_snippet( $id, $network );
				}
				wp_redirect( esc_url_raw( add_query_arg( 'deactivate-multi', true ) ) );
				break;

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
				wp_redirect( esc_url_raw( add_query_arg( 'delete-multi', true ) ) );
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
			'admin' => array(),
			'frontend' => array(),
		);

		/* Filter snippets by tag */
		if ( isset( $_POST['tag'] ) ) {
			$location = empty( $_POST['tag'] ) ? remove_query_arg( 'tag' ) : add_query_arg( 'tag', $_POST['tag'] );
			wp_redirect( esc_url_raw( $location ) );
		}

		if ( ! empty( $_GET['tag'] ) ) {
			$snippets['all'] = array_filter( $snippets['all'], array( $this, '_tags_filter_callback' ) );
		}

		/* Filter snippets based on search query */
		if ( $s ) {
			$snippets['all'] = array_filter( $snippets['all'], array( $this, '_search_callback' ) );
		}

		if ( $screen->is_network ) {
			$recently_activated = get_site_option( 'recently_activated_snippets', array() );
		} else {
			$recently_activated = get_option( 'recently_activated_snippets', array() );
		}

		$one_week = 7 * 24 * 60 * 60;
		foreach ( $recently_activated as $key => $time ) {

			if ( $time + $one_week < time() ) {
				unset( $recently_activated[ $key ] );
			}
		}

		if ( $screen->is_network ) {
			update_site_option( 'recently_activated_snippets', $recently_activated );
		} else {
			update_option( 'recently_activated_snippets', $recently_activated );
		}

		$scopes_enabled = code_snippets_get_setting( 'general', 'snippet_scope_enabled' );
		foreach ( (array) $snippets['all'] as $snippet ) {
			/* Filter into individual sections */
			if ( $snippet->active ) {
				$snippets['active'][] = $snippet;
			} else {
				// Was the snippet recently activated?
				if ( isset( $recently_activated[ $snippet->id ] ) ) {
					$snippets['recently_activated'][] = $snippet;
				}
				$snippets['inactive'][] = $snippet;
			}

			if ( $scopes_enabled ) {

				if ( '1' == $snippet->scope ) {
					$snippets['admin'][] = $snippet;
				} elseif ( '2' == $snippet->scope ) {
					$snippets['frontend'][] = $snippet;
				}
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
		$sort_by = $screen->get_option( 'per_page', 'option' );
		$screen_option = $screen->get_option( 'per_page', 'option' );
		$per_page = get_user_meta( $user, $screen_option, true );

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
	function usort_reorder_callback( $a, $b ) {

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
	* Used internally
	* @ignore
	*/
	function _tags_filter_callback( $snippet ) {
		$tags = explode( ',', $_GET['tag'] );

		foreach ( $tags as $tag ) {
			if ( in_array( $tag, $snippet->tags ) ) {
				return true;
			}
		}
	}

	/**
	 * Display a notice showing the current search terms
	 *
	 * @since  1.7
	 * @access public
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
	 * @since 2.3.0
	 * @param  int $scope the scope number
	 * @return string the scope name
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
