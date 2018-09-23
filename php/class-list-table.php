<?php

/**
 * Contains the class for handling the snippets table
 *
 * @package    Code_Snippets
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
	 * true if the current screen is in the network admin
	 * @var bool
	 */
	public $is_network;

	/**
	 * A list of statuses (views)
	 * @var array
	 */
	public $statuses = array( 'all', 'active', 'inactive', 'recently_activated' );

	/**
	 * The constructor function for our class.
	 * Adds hooks, initializes variables, setups class.
	 */
	public function __construct() {
		global $status, $page;
		$screen = get_current_screen();
		$this->is_network = is_network_admin();

		/* Determine the status */
		$status = apply_filters( 'code_snippets/list_table/default_view', 'all' );
		if ( isset( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], $this->statuses ) ) {
			$status = $_REQUEST['status'];
		}

		/* Add the search query to the URL */
		if ( isset( $_REQUEST['s'] ) ) {
			$_SERVER['REQUEST_URI'] = add_query_arg( 's', stripslashes( $_REQUEST['s'] ) );
		}

		/* Add a snippets per page screen option */
		$page = $this->get_pagenum();

		add_screen_option( 'per_page', array(
			'label'   => __( 'Snippets per page', 'code-snippets' ),
			'default' => 999,
			'option'  => 'snippets_per_page',
		) );

		/* Set the table columns hidden in Screen Options by default */
		if ( false === get_user_option( "manage{$screen->id}columnshidden" ) ) {
			$user = wp_get_current_user();
			update_user_option( $user->ID, "manage{$screen->id}columnshidden", array( 'id' ) );
		}

		/* Strip the result query arg from the URL */
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'result' );

		/* Add filters to format the snippet description in the same way the post content is formatted */
		$filters = array( 'wptexturize', 'convert_smilies', 'convert_chars', 'wpautop', 'shortcode_unautop', 'capital_P_dangit' );

		foreach ( $filters as $filter ) {
			add_filter( 'code_snippets/list_table/column_description', $filter );
		}

		/* Setup the class */
		parent::__construct( array(
			'ajax'     => true,
			'plural'   => 'snippets',
			'singular' => 'snippet',
		) );
	}

	/**
	 * Define the output of all columns that have no callback function
	 *
	 * @param Code_Snippet $snippet The snippet used for the current row
	 * @param string       $column_name The name of the column being printed
	 *
	 * @return string The content of the column to output
	 */
	protected function column_default( $snippet, $column_name ) {

		switch ( $column_name ) {
			case 'id':
				return $snippet->id;
			case 'description':
				return empty( $snippet->desc ) ? '&#8212;' :
					apply_filters( 'code_snippets/list_table/column_description', $snippet->desc );
			default:
				return apply_filters( "code_snippets/list_table/column_{$column_name}", $snippet );
		}
	}

	/**
	 * Retrieve a URL to perform an action on a snippet
	 *
	 * @param string       $action
	 * @param Code_Snippet $snippet
	 * @param bool         $escape
	 *
	 * @return string
	 */
	public function get_action_link( $action, $snippet, $escape = true ) {

		// redirect actions to the network dashboard for shared network snippets
		$network_redirect = $snippet->shared_network && ! $this->is_network;

		// edit links go to a different menu
		if ( 'edit' === $action ) {
			return code_snippets()->get_snippet_edit_url( $snippet->id, $network_redirect ? 'network' : 'self' );
		}

		$query_args = array( 'action' => $action, 'id' => $snippet->id );

		$url = $network_redirect ? add_query_arg( $query_args, code_snippets()->get_menu_url( 'manage', 'network' ) ) : add_query_arg( $query_args );
		return $escape ? esc_url( $url ) : $url;
	}

	/**
	 * Build a list of action links for individual snippets
	 *
	 * @param Code_Snippet $snippet The current snippet
	 *
	 * @return array The action links HTML
	 */
	private function get_snippet_action_links( Code_Snippet $snippet ) {
		$actions = array();

		if ( ! $this->is_network && $snippet->network && ! $snippet->shared_network ) {
			// display special links if on a subsite and dealing with a network-active snippet

			if ( $snippet->active ) {
				$actions['network_active'] = esc_html__( 'Network Active', 'code-snippets' );
			} else {
				$actions['network_only'] = esc_html__( 'Network Only', 'code-snippets' );
			}
		} elseif ( ! $snippet->shared_network || current_user_can( code_snippets()->get_network_cap_name() ) ) {
			// if the snippet is a shared network snippet, only display extra actions if the user has network permissions

			$simple_actions = array(
				'edit'   => esc_html__( 'Edit', 'code-snippets' ),
				'clone'  => esc_html__( 'Clone', 'code-snippets' ),
				'export' => esc_html__( 'Export', 'code-snippets' ),
			);

			foreach ( $simple_actions as $action => $label ) {
				$actions[ $action ] = sprintf( '<a href="%s">%s</a>', $this->get_action_link( $action, $snippet ), $label );
			}

			$actions['delete'] = sprintf(
				'<a href="%2$s" class="delete" onclick="%3$s">%1$s</a>',
				esc_html__( 'Delete', 'code-snippets' ),
				$this->get_action_link( 'delete', $snippet ),
				esc_js( sprintf(
					'return confirm("%s");',
					esc_html__( 'You are about to permanently delete the selected item.', 'code-snippets' ) . "\n" .
					esc_html__( "'Cancel' to stop, 'OK' to delete.", 'code-snippets' )
				) )
			);
		}

		return apply_filters( 'code_snippets/list_table/row_actions', $actions );
	}

	/**
	 * Retrieve the code for a snippet activation switch
	 * @param Code_Snippet $snippet
	 * @return string
	 */
	protected function get_activation_switch( $snippet ) {
		$action = $snippet->active ? 'deactivate' : 'activate';

		if ( $this->is_network && $snippet->shared_network || ( ! $this->is_network && $snippet->network && ! $snippet ) ) {
			return '';
		}

		if ( $snippet->shared_network ) {
			$action .= 'shared';
		}

		/*
		if ( $snippet->active ) {
			$actions['deactivate'] = sprintf(
				$link_format,
				$snippet->network ? esc_html__( 'Network Deactivate', 'code-snippets' ) : esc_html__( 'Deactivate', 'code-snippets' ),
				$this->get_action_link( 'deactivate', $snippet )
			);
		} elseif ( 'single-use' === $snippet->scope ) {

			$actions['run_once'] = sprintf( $link_format, esc_html__( 'Run Once', 'code-snippets' ), $this->get_action_link( 'run-once', $snippet ) );

		} else {
			$actions['activate'] = sprintf(
				$link_format,
				$snippet->network ? esc_html__( 'Network Activate', 'code-snippets' ) : esc_html__( 'Activate', 'code-snippets' ),
				$this->get_action_link( 'activate', $snippet )
			);
		}
		*/

		return sprintf(
			'<a class="snippet-activation-switch" href="%s"></a> ',
			$this->get_action_link( $action, $snippet )
		);
	}

	/**
	 * Build the content of the snippet name column
	 *
	 * @param Code_Snippet $snippet The snippet being used for the current row
	 *
	 * @return string The content of the column to output
	 */
	protected function column_name( $snippet ) {

		$title = empty( $snippet->name ) ? sprintf( __( 'Untitled #%d', 'code-snippets' ), $snippet->id ) : $snippet->name;

		$row_actions = $this->row_actions(
			$this->get_snippet_action_links( $snippet ),
			apply_filters( 'code_snippets/list_table/row_actions_always_visible', true )
		);

		$out = esc_html( $title );

		if ( 'global' !== $snippet->scope ) {
			$out .= ' <span class="dashicons dashicons-' . $snippet->scope_icon . '"></span>';
		}

		/* Add a link to the snippet if it isn't an unreadable network-only snippet */
		if ( $this->is_network || ! $snippet->network || current_user_can( code_snippets()->get_network_cap_name() ) ) {

			$out = sprintf(
				'<a href="%s" class="snippet-name">%s</a>',
				code_snippets()->get_snippet_edit_url( $snippet->id, $snippet->network ? 'network' : 'admin' ),
				$out
			);
		}

		if ( $snippet->shared_network ) {
			$out .= ' <span class="badge">' . esc_html__( 'Shared on Network', 'code-snippets' ) . '</span>';
		}

		/* Return the name contents */
		return $this->get_activation_switch( $snippet ) .
		       apply_filters( 'code_snippets/list_table/column_name', $out, $snippet ) . $row_actions;
	}

	/**
	 * Builds the checkbox column content
	 *
	 * @param Code_Snippet $snippet The snippet being used for the current row
	 *
	 * @return string The column content to be printed
	 */
	protected function column_cb( $snippet ) {

		$out = sprintf(
			'<input type="checkbox" name="%s[]" value="%s" />',
			$snippet->shared_network ? 'shared_ids' : 'ids',
			$snippet->id
		);

		return apply_filters( 'code_snippets/list_table/column_cb', $out, $snippet );
	}

	/**
	 * Output the content of the tags column
	 * This function is used once for each row
	 *
	 * @since 2.0
	 *
	 * @param Code_Snippet $snippet The snippet being used for the current row
	 *
	 * @return string The column output
	 */
	protected function column_tags( $snippet ) {

		/* Return a placeholder if there are no tags */
		if ( empty( $snippet->tags ) ) {
			return '&#8212;';
		}

		$out = array();

		/* Loop through the tags and create a link for each one */
		foreach ( $snippet->tags as $tag ) {
			$out[] = sprintf( '<a href="%s">%s</a>',
				esc_url( add_query_arg( 'tag', esc_attr( $tag ) ) ),
				esc_html( $tag )
			);
		}

		return join( ', ', $out );
	}

	/**
	 * Output the content of the priority column
	 *
	 * @param Code_Snippet $snippet
	 *
	 * @return string
	 */
	protected function column_priority( $snippet ) {

		return sprintf( '<input type="number" class="snippet-priority" value="%d" step="1" disabled>', $snippet->priority );
	}

	/**
	 * Define the column headers for the table
	 *
	 * @return array The column headers, ID paired with label
	 */
	public function get_columns() {
		$columns = array(
			'cb'          => '<input type="checkbox">',
			'name'        => __( 'Name', 'code-snippets' ),
			'id'          => __( 'ID', 'code-snippets' ),
			'description' => __( 'Description', 'code-snippets' ),
			'tags'        => __( 'Tags', 'code-snippets' ),
			'priority'    => __( 'Priority', 'code-snippets' ),
		);

		if ( ! code_snippets_get_setting( 'general', 'enable_description' ) ) {
			unset( $columns['description'] );
		}

		if ( ! code_snippets_get_setting( 'general', 'enable_tags' ) ) {
			unset( $columns['tags'] );
		}

		return apply_filters( 'code_snippets/list_table/columns', $columns );
	}

	/**
	 * Define the columns that can be sorted
	 *
	 * @return array The IDs of the columns that can be sorted
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'id'       => array( 'id', true ),
			'name'     => array( 'name', false ),
			'tags'     => array( 'tags_list', true ),
			'priority' => array( 'priority', true ),
		);

		return apply_filters( 'code_snippets/list_table/sortable_columns', $sortable_columns );
	}

	/**
	 * Define the bulk actions to include in the drop-down menus
	 *
	 * @return array An array of menu items with the ID paired to the label
	 */
	public function get_bulk_actions() {
		$actions = array(
			'activate-selected'   => $this->is_network ? __( 'Network Activate', 'code-snippets' ) : __( 'Activate', 'code-snippets' ),
			'deactivate-selected' => $this->is_network ? __( 'Network Deactivate', 'code-snippets' ) : __( 'Deactivate', 'code-snippets' ),
			'clone-selected'      => __( 'Clone', 'code-snippets' ),
			'download-selected'   => __( 'Download', 'code-snippets' ),
			'export-selected'     => __( 'Export', 'code-snippets' ),
			'delete-selected'     => __( 'Delete', 'code-snippets' ),
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
	public function get_table_classes() {
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
	public function get_views() {
		global $totals, $status;
		$status_links = array();

		/* Loop through the view counts */
		foreach ( $totals as $type => $count ) {

			/* Don't show the view if there is no count */
			if ( ! $count ) {
				continue;
			}

			/* Define the labels for each view */
			$labels = array(
				'all'                => _n( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $count, 'code-snippets' ),
				'active'             => _n( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', $count, 'code-snippets' ),
				'inactive'           => _n( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', $count, 'code-snippets' ),
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
	public function get_current_tags() {
		global $snippets, $status;

		/* If we're not viewing a snippets table, get all used tags instead */
		if ( ! isset( $snippets, $status ) ) {
			$tags = get_all_snippet_tags();
		} else {
			$tags = array();

			/* Merge all tags into a single array */
			foreach ( $snippets[ $status ] as $snippet ) {
				$tags = array_merge( $snippet->tags, $tags );
			}

			/* Remove duplicate tags */
			$tags = array_unique( $tags );
		}

		sort( $tags );

		return $tags;
	}

	/**
	 * Add filters and extra actions above and below the table
	 *
	 * @param string $which Are the actions displayed on the table top or bottom
	 */
	public function extra_tablenav( $which ) {
		global $status, $wpdb;

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

				submit_button( __( 'Filter', 'code-snippets' ), 'button', 'filter_action', false );
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
	public function required_form_fields( $context = 'main' ) {

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
				printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $var ), esc_attr( $_REQUEST[ $var ] ) );
				print "\n";
			}
		}

		do_action( 'code_snippets/list_table/print_required_form_fields', $context );
	}


	/**
	 * Clear the recently activated snippets list if we've clicked the button
	 * @return string The action to execute
	 */
	public function current_action() {
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
	 * @uses wp_redirect() to pass the results to the current page
	 * @uses add_query_arg() to append the results to the current URI
	 */
	public function process_bulk_actions() {

		if ( isset( $_GET['action'], $_GET['id'] ) ) {

			$id = absint( $_GET['id'] );
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'action', 'id' ) );

			switch ( sanitize_key( $_GET['action'] ) ) {

				case 'activate':
					activate_snippet( $id, $this->is_network );
					$result = 'activated';
					break;

				case 'run-once':
					activate_snippet( $id, $this->is_network );
					$result = 'executed';
					break;

				case 'deactivate':
					deactivate_snippet( $id, $this->is_network );
					$result = 'deactivated';
					break;

				case 'activate-shared':
					$active_shared_snippets = get_option( 'active_shared_network_snippets', array() );

					if ( ! in_array( $id, $active_shared_snippets ) ) {
						$active_shared_snippets[] = $id;
						update_option( 'active_shared_network_snippets', $active_shared_snippets );
					}

					$result = 'activated';
					break;

				case 'deactivate-shared':
					$active_shared_snippets = get_option( 'active_shared_network_snippets', array() );
					update_option( 'active_shared_network_snippets', array_diff( $active_shared_snippets, array( $id ) ) );

					$result = 'deactivated';
					break;

				case 'clone':
					$this->clone_snippets( array( $id ) );
					$result = 'cloned';
					break;

				case 'delete':
					delete_snippet( $id, $this->is_network );
					$result = 'deleted';
					break;

				case 'export':
					export_snippets( array( $id ) );
					break;

				case 'download':
					download_snippets( array( $id ) );
					break;
			}

			if ( isset( $result ) ) {
				wp_redirect( esc_url_raw( add_query_arg( 'result', $result ) ) );
				exit;
			}
		}

		if ( ! isset( $_POST['ids'] ) && ! isset( $_POST['shared_ids'] ) ) {
			return;
		}

		$ids = isset( $_POST['ids'] ) ? $_POST['ids'] : array();
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'action' );

		switch ( $this->current_action() ) {

			case 'activate-selected':

				foreach ( $ids as $id ) {
					activate_snippet( $id, $this->is_network );
				}

				/* Process the shared network snippets */
				if ( isset( $_POST['shared_ids'] ) && is_multisite() && ! $this->is_network ) {
					$active_shared_snippets = get_option( 'active_shared_network_snippets', array() );

					foreach ( $_POST['shared_ids'] as $id ) {
						if ( ! in_array( $id, $active_shared_snippets ) ) {
							$active_shared_snippets[] = $id;
						}
					}

					update_option( 'active_shared_network_snippets', $active_shared_snippets );
				}

				$result = 'activated-multi';
				break;

			case 'deactivate-selected':

				foreach ( $ids as $id ) {
					deactivate_snippet( $id, $this->is_network );
				}

				/* Process the shared network snippets */
				if ( isset( $_POST['shared_ids'] ) && is_multisite() && ! $this->is_network ) {
					$active_shared_snippets = get_option( 'active_shared_network_snippets', array() );
					$active_shared_snippets = ( '' === $active_shared_snippets ) ? array() : $active_shared_snippets;
					$active_shared_snippets = array_diff( $active_shared_snippets, $_POST['shared_ids'] );
					update_option( 'active_shared_network_snippets', $active_shared_snippets );
				}

				$result = 'deactivated-multi';
				break;

			case 'export-selected':
				export_snippets( $ids );
				break;

			case 'download-selected':
				download_snippets( $ids );
				break;

			case 'clone-selected':
				$this->clone_snippets( $ids );
				$result = 'cloned-multi';
				break;

			case 'delete-selected':
				foreach ( $ids as $id ) {
					delete_snippet( $id, $this->is_network );
				}
				$result = 'deleted-multi';
				break;

			case 'clear-recent-list':
				if ( $this->is_network ) {
					update_site_option( 'recently_activated_snippets', array() );
				} else {
					update_option( 'recently_activated_snippets', array() );
				}
				break;
		}

		if ( isset( $result ) ) {
			wp_redirect( esc_url_raw( add_query_arg( 'result', $result ) ) );
			exit;
		}
	}

	/**
	 * Message to display if no snippets are found
	 */
	public function no_items() {
		esc_html_e( "It looks like you don't have any snippets.", 'code-snippets' );
		printf(
			' <a href="%s">%s</a>',
			esc_url( code_snippets()->get_menu_url( 'add' ) ),
			esc_html__( 'Perhaps you would like to add a new one?', 'code-snippets' )
		);
	}

	/**
	 *
	 */
	private function fetch_shared_network_snippets() {
		/** @var wpdb $wpdb */
		global $snippets, $wpdb;

		if ( ! is_multisite() || ! $ids = get_site_option( 'shared_network_snippets', false ) ) {
			return;
		}

		if ( $this->is_network ) {
			$limit = count( $snippets['all'] );

			/** @var Code_Snippet $snippet */
			for ( $i = 0; $i < $limit; $i++ ) {
				$snippet = &$snippets['all'][ $i ];

				if ( in_array( $snippet->id, $ids ) ) {
					$snippet->shared_network = true;
					$snippet->tags = array_merge( $snippet->tags, array( 'shared on network' ) );
					$snippet->active = false;
				}
			}
		} else {

			$active_shared_snippets = get_option( 'active_shared_network_snippets', array() );

			$sql = sprintf( "SELECT * FROM {$wpdb->ms_snippets} WHERE id IN (%s)",
				implode( ',', array_fill( 0, count( $ids ), '%d' ) )
			);

			$shared_snippets = $wpdb->get_results( $wpdb->prepare( $sql, $ids ), ARRAY_A );

			foreach ( $shared_snippets as $index => $snippet ) {
				$snippet = new Code_Snippet( $snippet );
				$snippet->network = true;
				$snippet->shared_network = true;
				$snippet->tags = array_merge( $snippet->tags, array( 'shared on network' ) );
				$snippet->active = in_array( $snippet->id, $active_shared_snippets );

				$shared_snippets[ $index ] = $snippet;
			}

			$snippets['all'] = array_merge( $snippets['all'], $shared_snippets );
		}
	}

	/**
	 * Prepares the items to later display in the table.
	 * Should run before any headers are sent.
	 */
	public function prepare_items() {
		global $status, $snippets, $totals, $s;

		wp_reset_vars( array( 'orderby', 'order', 's' ) );

		$screen = get_current_screen();
		$user = get_current_user_id();

		/* First, lets process the bulk actions */
		$this->process_bulk_actions();

		/* Initialize the $snippets array */
		$snippets = array_fill_keys( $this->statuses, array() );

		/* Fetch all snippets */
		$snippets['all'] = apply_filters( 'code_snippets/list_table/get_snippets', get_snippets( array() ) );
		$this->fetch_shared_network_snippets();

		/* Redirect POST'ed tag filter to GET */
		if ( isset( $_POST['tag'] ) ) {
			$location = empty( $_POST['tag'] ) ? remove_query_arg( 'tag' ) : add_query_arg( 'tag', $_POST['tag'] );
			wp_redirect( esc_url_raw( $location ) );
			exit;
		}

		/* Add scope tags */
		if ( code_snippets_get_setting( 'general', 'snippet_scope_enabled' ) ) {
			foreach ( $snippets['all'] as $snippet ) {

				if ( 'global' !== $snippet->scope ) {
					$snippet->tags = array_merge( $snippet->tags, array( $snippet->scope ) );
				}
			}
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
		$recently_activated = $this->is_network ?
			get_site_option( 'recently_activated_snippets', array() ) :
			get_option( 'recently_activated_snippets', array() );

		foreach ( $recently_activated as $key => $time ) {

			if ( $time + WEEK_IN_SECONDS < time() ) {
				unset( $recently_activated[ $key ] );
			}
		}

		$this->is_network ?
			update_site_option( 'recently_activated_snippets', $recently_activated ) :
			update_option( 'recently_activated_snippets', $recently_activated );

		/* Filter snippets into individual sections */
		foreach ( $snippets['all'] as $snippet ) {

			if ( $snippet->active ) {
				$snippets['active'][] = $snippet;
			} else {
				$snippets['inactive'][] = $snippet;

				/* Was the snippet recently activated? */
				if ( isset( $recently_activated[ $snippet->id ] ) ) {
					$snippets['recently_activated'][] = $snippet;
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

		/* Decide how many records per page to show by
		   getting the user's setting in the Screen Options panel */
		$sort_by = $screen->get_option( 'per_page', 'option' );
		$per_page = get_user_meta( $user, $sort_by, true );

		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		$per_page = (int) $per_page;

		$this->_column_headers = $this->get_column_info();

		usort( $data, array( $this, 'usort_reorder_callback' ) );

		/* Determine what page the user is currently looking at */
		$current_page = $this->get_pagenum();

		/* Check how many items are in the data array */
		$total_items = count( $data );

		/* The WP_List_Table class does not handle pagination for us, so we need
		   to ensure that the data is trimmed to only the current page. */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		/* Now we can add our *sorted* data to the items property,
		   where it can be used by the rest of the class. */
		$this->items = $data;

		/* We register our pagination options and calculations */
		$this->set_pagination_args( array(
			'total_items' => $total_items, // Calculate the total number of items
			'per_page'    => $per_page, // Determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page ), // Calculate the total number of pages
		) );
	}

	/**
	 * Determine the sort ordering for two pieces of data
	 *
	 * @ignore
	 *
	 * @param string $field name of the field that the data belongs to
	 * @param string $a_data
	 * @param string $b_data
	 *
	 * @return int
	 */
	private function get_sort_direction( $field, $a_data, $b_data ) {

		// if the data is numeric, then calculate the ordering directly
		if ( is_numeric( $a_data ) ) {
			return $a_data - $b_data;
		}

		// if only one of the data points is empty, then place it before the one which is not
		if ( '' === $a_data xor '' === $b_data ) {
			return '' === $a_data ? 1 : -1;
		}

		// otherwise, sort using the default string sort order
		return strcmp( $a_data, $b_data );
	}

	/**
	 * Callback for usort() used to sort snippets
	 *
	 * @ignore
	 *
	 * @param Code_Snippet $a The first snippet to compare
	 * @param Code_Snippet $b The second snippet to compare
	 *
	 * @return int The sort order
	 */
	private function usort_reorder_callback( $a, $b ) {

		// sort by ID by default
		$orderby = (
		! empty( $_REQUEST['orderby'] )
			? $_REQUEST['orderby']
			: apply_filters( 'code_snippets/list_table/default_orderby', 'priority' )
		);

		// sort ascending by default
		$order = (
		! empty( $_REQUEST['order'] )
			? $_REQUEST['order']
			: apply_filters( 'code_snippets/list_table/default_order', 'asc' )
		);

		$result = $this->get_sort_direction( $orderby, $a->$orderby, $b->$orderby );

		if ( 0 === $result && 'id' !== $orderby ) {
			$result = $this->get_sort_direction( 'id', $a->id, $b->id );
		}

		// apply the sort direction to the calculated order
		return ( 'asc' === $order ) ? $result : -$result;
	}

	/**
	 * Callback for search function
	 * @ignore
	 *
	 * @param Code_Snippet $snippet The snippet being filtered
	 *
	 * @return bool The result of the filter
	 */
	private function search_callback( $snippet ) {
		static $term;
		if ( is_null( $term ) ) {
			$term = stripslashes( $_REQUEST['s'] );
		}

		$fields = array( 'name', 'desc', 'code', 'tags_list' );

		foreach ( $fields as $field ) {
			if ( false !== stripos( $snippet->$field, $term ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Callback for filtering snippets by tag
	 * @ignore
	 *
	 * @param Code_Snippet $snippet The snippet being filtered
	 *
	 * @return bool The result of the filter
	 */
	private function tags_filter_callback( $snippet ) {
		$tags = explode( ',', $_GET['tag'] );

		foreach ( $tags as $tag ) {
			if ( in_array( $tag, $snippet->tags ) ) {
				return true;
			}
		}
	}

	/**
	 * Display a notice showing the current search terms
	 * @since 1.7
	 */
	public function search_notice() {
		if ( ! empty( $_REQUEST['s'] ) || ! empty( $_GET['tag'] ) ) {

			echo '<span class="subtitle">' . __( 'Search results', 'code-snippets' );

			if ( ! empty( $_REQUEST['s'] ) ) {
				echo sprintf( __( ' for &#8220;%s&#8221;', 'code-snippets' ), esc_html( $_REQUEST['s'] ) );
			}

			if ( ! empty( $_GET['tag'] ) ) {
				echo sprintf( __( ' in tag &#8220;%s&#8221;', 'code-snippets' ), esc_html( $_GET['tag'] ) );
			}

			echo '</span>';

			printf(
				'&nbsp;<a class="button clear-filters" href="%s">%s</a>',
				esc_url( remove_query_arg( array( 's', 'tag' ) ) ),
				__( 'Clear Filters', 'code-snippets' )
			);
		}
	}

	/**
	 * Outputs content for a single row of the table
	 *
	 * @param Code_Snippet $snippet The snippet being used for the current row
	 */
	public function single_row( $snippet ) {
		$row_class = ( $snippet->active ? 'active-snippet' : 'inactive-snippet' );

		if ( code_snippets_get_setting( 'general', 'snippet_scope_enabled' ) ) {
			$row_class .= sprintf( ' %s-scope', $snippet->scope );
		}

		if ( $snippet->shared_network ) {
			$row_class .= ' shared-network-snippet';
		}

		printf( '<tr class="%s">', $row_class );
		$this->single_row_columns( $snippet );
		echo '</tr>';
	}

	/**
	 * Clone a selection of snippets
	 * @param array $ids
	 */
	private function clone_snippets( $ids ) {
		$snippets = get_snippets( $ids, $this->is_network );

		/** @var Code_Snippet $snippet */
		foreach ( $snippets as $snippet ) {

			// copy all data from the previous snippet aside from the ID and active status
			$snippet->id = 0;
			$snippet->active = false;

			save_snippet( $snippet );
		}
	}
}
