<?php

namespace Code_Snippets\Cloud;

use WP_List_Table;
use function Code_Snippets\code_snippets;

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
 * @package Code_Snippets
 */
class Cloud_List_Table extends WP_List_Table {

	/**
	 * Base URL for cloud API.
	 *
	 * @var string
	 */
	const CLASS_NAME = 'Code_Snippets\Cloud\Cloud_List_Table';

	/**
	 * Instance of Cloud API class.
	 *
	 * @var Cloud_API
	 */
	protected $cloud_api;

	/**
	 * Items for the cloud list table.
	 *
	 * @var Cloud_Snippets
	 */
	protected $cloud_snippets;

	/**
	 * Full name of the class.
	 *
	 * @var string
	 */
	protected $class_name;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'cloud-snippet',
				'plural'   => 'cloud-snippets',
				'ajax'     => false,
			]
		);

		$this->cloud_api = code_snippets()->cloud_api;
		$this->class_name = get_class( $this );

		// Strip the result query arg from the URL.
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'result' );
	}

	/**
	 * Sets the list of columns that are hidden by default..
	 *
	 * @param array<string> $hidden List of hidden columns.
	 *
	 * @return array<string> Modified list of hidden columns
	 */
	public function default_hidden_columns( $hidden ) {
		return array_merge( $hidden, [ 'id', 'code', 'cloud_id', 'revision' ] );
	}

	/**
	 * Build the list of column headings.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'id'          => __( 'ID', 'code-snippets' ),
			'cloud_id'    => __( 'Cloud ID', 'code-snippets' ),
			'code'        => __( 'Code', 'code-snippets' ),
			'revision'    => __( 'Revision', 'code-snippets' ),
			'name'        => __( 'Name', 'code-snippets' ),
			'scope'       => __( 'Type', 'code-snippets' ),
			'status'      => __( 'Status', 'code-snippets' ),
			'description' => __( 'Description', 'code-snippets' ),
			'tags'        => __( 'Tags', 'code-snippets' ),
			'updated'     => __( 'Updated', 'code-snippets' ),
			'download'    => '',
		);

		return apply_filters( 'code_snippets/cloud_list_table/columns', $columns );
	}

	/**
	 * Text displayed when no snippet data is available.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'Looks like there are no snippets in your cloud codevault available.', 'code-snippets' );
	}

	/**
	 * Define the bulk actions to include in the drop-down menus
	 *
	 * @return array<string, string> An array of menu items with the ID paired to the label.
	 */
	public function get_bulk_actions() {
		$actions = array(
			'download-selected' => __( 'Download', 'code-snippets' ),
		);

		return apply_filters( 'code_snippets/cloud_list_table/bulk_actions', $actions );
	}

	/**
	 * Fetch the snippets used to populate the table.
	 *
	 * @return Cloud_Snippets
	 */
	protected function fetch_snippets() {
		if ( $_REQUEST['cloud_page'] )  {
			return $this->cloud_api->get_codevault_snippets( (int) $_REQUEST['cloud_page'] - 1 );
		}

		return $this->cloud_api->get_codevault_snippets( $this->get_pagenum() - 1 );
	}

	/**
	 * Prepare items for the table.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->process_actions();

		$columns = $this->get_columns();
		$hidden = [ 'id', 'code', 'cloud_id', 'revision' ];
		$this->_column_headers = array( $columns, $hidden );

		$this->cloud_snippets = $this->fetch_snippets();
		$this->items = $this->cloud_snippets->snippets;

		$this->set_pagination_args(
			[
				'per_page'    => count( $this->cloud_snippets->snippets ),
				'total_items' => $this->cloud_snippets->total_snippets,
				'total_pages' => (int) $this->cloud_snippets->total_pages,
			]
		);
	}

	/**
	 * Process any actions that have been submitted, such as downloading cloud snippets to the local database.
	 *
	 * @return void
	 */
	public function process_actions() {
		
		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'action', 'snippet', '_wpnonce', 'source' ) );
		
		if ( isset( $_REQUEST['action'], $_REQUEST['snippet'], $_REQUEST['source'] ) ) {
			if( 'download' === $_REQUEST['action'] || 'update' === $_REQUEST['action'] ){
				$result = $this->cloud_api->download_or_update_snippet(
					sanitize_key( $_REQUEST['snippet'] ),
					sanitize_key( $_REQUEST['source'] ),
					sanitize_key( $_REQUEST['action'] )
				);
				if ( $result['success'] ) {
					if( $result['snippet_id'] ){
						//Redirect to edit snippet page
						wp_safe_redirect( code_snippets()->get_snippet_edit_url( $result['snippet_id'] ) );
						exit;
					}
					wp_safe_redirect( esc_url_raw( add_query_arg( 'result', $result['action'] ) ) );
					exit;
				}
			}
		}		
		/* Only continue from this point if there are bulk actions to process */
		if ( ! isset( $_POST['cloud_ids'] ) && ! isset( $_POST['shared_cloud_ids'] ) ) {
			return;
		}

		$ids = isset( $_POST['cloud_ids'] ) ? array_map( 'intval', $_POST['cloud_ids'] ) : array();
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'action' );
		if( 'download-selected' == $this->current_action() ) {
				$this->download_snippets( $ids );
				$result = 'download-multi';
		}

		if ( isset( $result ) ) {
			wp_safe_redirect( esc_url_raw( add_query_arg( 'result', $result ) ) );
			exit;
		}
	}

	/**
	 * Build a hidden input field for a certain column and snippet value.
	 *
	 * @param string        $column_name Column name.
	 * @param Cloud_Snippet $snippet     Column item.
	 *
	 * @return string
	 */
	protected function build_column_hidden_input( $column_name, $snippet ) {
		return sprintf(
			'<input id="cloud-snippet-%s-%s" class="cloud-snippet-item" type="hidden" name="%s" value="%s" />',
			esc_attr( $column_name ),
			esc_attr( $snippet->id ),
			esc_attr( $column_name ),
			esc_attr( $snippet->$column_name )
		);
	}

	/**
	 * Define the output of all columns that have no callback function
	 *
	 * @param Cloud_Snippet $item        The snippet used for the current row.
	 * @param string        $column_name The name of the column being printed.
	 *
	 * @return string The content of the column to output.
	 */
	protected function column_default( $item, $column_name ) {
		$link = $this->get_cloud_map_link( $item->id );

		switch ( $column_name ) {
			case 'tags':
				return join( ', ', $item->tags );

			case 'description':
				return $item->description;

			case 'name':
				$edit_url = $link ? code_snippets()->get_snippet_edit_url( $link->local_id ) : '';
				$name_link = sprintf(
					$edit_url ? '<a href="%1$s">%2$s</a>' : '<a>%2$s</a>',
					esc_url( $edit_url ),
					esc_html( $item->name )
				);

				return $name_link . $this->build_column_hidden_input( $column_name, $item );

			case 'updated':
				return sprintf( '<span>%s</span>', esc_html( human_time_diff( strtotime($item->updated), current_time( 'U' ) ) ) );

			case 'id':
			case 'cloud_id':
			case 'code':
			case 'revision':
				return $item->$column_name . $this->build_column_hidden_input( $column_name, $item );

			case 'status':
				return sprintf(
					'<a class="snippet-type-badge snippet-status" data-type="%s">%s</a>',
					esc_attr( $this->get_style_from_status( $item->status ) ),
					esc_html( $this->get_status_name_from_status_id( $item->status) )
				);

			case 'scope':
				$type = $this->get_type_from_scope( $item->scope );

				return sprintf(
					'<a id="snippet-type-%s" class="snippet-type-badge snippet-type" data-type="%s">%s</a>',
					esc_attr( $item->cloud_id ),
					esc_attr( strtolower( $type ) ),
					esc_html( $type )
				);

			case 'download':
				return '';

			default:
				return apply_filters( "code_snippets/cloud_list_table/column_$column_name", '&#8212;', $item );
		}
	}

	/**
	 * Translate a snippet scope to a type.
	 *
	 * @param string $scope The scope of the snippet.
	 *
	 * @return string The type of the snippet.
	 */
	protected function get_type_from_scope( $scope ) {
		switch ( $scope ) {
			case 'global':
				return 'php';
			case 'site-css':
				return 'css';
			case 'site-footer-js':
				return 'js';
			case 'content':
				return 'html';
			default:
				return '';
		}
	}

	/**
	 * Translate a snippet status to a style class.
	 *
	 * @param int $status The scope of the snippet.
	 *
	 * @return string The style to be used for the stats badge.
	 */
	public function get_style_from_status( $status ) {
		switch ( $status ) {
			case 3: //Private
				return 'css';
			case 4: //Public
				return 'js';
			case 5: //Unverified
				return 'unverified';
			case 6: //AI Verified
				return 'html';
			default:
				return 'php';
		}
	}

	/**
	 * Translate a snippet status to a style class.
	 *
	 * @param int $status The scope of the snippet.
	 *
	 * @return string The style to be used for the stats badge.
	 */
	public function get_status_name_from_status_id( $status ) {
		switch ( $status ) {
			case 3:
				return 'Private';
			case 4:
				return 'Public';
			case 5:
				return 'Unverified';
			case 6:
				return 'AI Verified';
		}
	}


	/**
	 * Define the columns that can be sorted. TODO: Add the ability to sort columns by clicking on the column name
	 *
	 * @return array<string, string|array<string|boolean>> The IDs of the columns that can be sorted
	 */
	public function get_sortable_columns() {
		$sortable_columns = [
			'name'    => 'name',
			'type'    => [ 'type', true ],
			'status'  => [ 'status', true ],
			'updated' => [ 'updated', true ],
		];

		return apply_filters( 'code_snippets/cloud_list_table/sortable_columns', $sortable_columns );
	}

	/**
	 * Define the output of the 'download' column
	 *
	 * @param Cloud_Snippet $item The snippet used for the current row.
	 *
	 * @return string The content of the column to output.
	 */
	protected function column_download( $item ) {
		$lang = $this->get_type_from_scope( $item->scope );
		$link = $this->get_cloud_map_link( $item->id );

		if ( $link && ! $link->update_available ) {
			return sprintf(
				'<a href="%s" class="cloud-snippet-downloaded">%s</a>',
				esc_url( code_snippets()->get_snippet_edit_url( $link->local_id ) ),
				esc_html__( 'View', 'code-snippets' )
			);
		}

		$update_available = $link && $link->update_available;

		if( $update_available ) {
			$action_link = sprintf(
				'<a class="cloud-snippet-download" href="%s">%s</a>',
				esc_url( code_snippets()->get_snippet_edit_url( $link->local_id ) . '/#updated-code' ),
				esc_html__( 'Update Available', 'code-snippets' )
			);
		}else{
			//Get source of snippet - codevault or search use current class as snippet source is not set for every cloud snippet in cloud link
			$source = self::CLASS_NAME === $this->class_name ? 'codevault' : 'search';

			$download_url = add_query_arg(
				[
					'action'  => 'download',
					'snippet' => $item->id,
					'source'  => $source ,
				]
			);

			$action_link = sprintf(
				'<a class="cloud-snippet-download" href="%s">%s</a>',
					esc_url( $download_url ),
					esc_html__( 'Download', 'code-snippets' )
			);
		}

		$thickbox_url = '#TB_inline?&width=700&height=500&inlineId=show-code-preview';

		$thickbox_link = sprintf(
			'<a href="%s" class="cloud-snippet-preview thickbox" data-snippet="%s" data-lang="%s">%s</a>',
			esc_url( $thickbox_url ),
			esc_attr( $item->id ),
			esc_attr( $lang ),
			esc_html__( 'Preview', 'code-snippets' )
		);

		return $action_link . $thickbox_link;
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param Cloud_Snippet $item The snippet being used for the current row.
	 *
	 * @return string The column content to be printed.
	 */
	protected function column_cb( $item ) {
		$out = sprintf(
			'<input type="checkbox" name="%s[]" value="%s">',
			$item->shared_network ? 'shared_cloud_ids' : 'cloud_ids',
			$item->id
		);

		return apply_filters( 'code_snippets/list_table/column_cb', $out, $item );
	}

	/**
	 * Retrieve the classes for the table.
	 *
	 * We override this in order to add 'snippets' as a class for custom styling.
	 *
	 * @return array The classes to include on the table element.
	 */
	public function get_table_classes() {
		$classes = array( 'cloud-table', 'widefat', $this->_args['plural'] );

		return apply_filters( 'code_snippets/cloud_list_table/table_classes', $classes );
	}

	/**
	 * Outputs content for a single row of the table
	 *
	 * @param Cloud_Snippet $item The snippet being used for the current row.
	 */
	public function single_row( $item ) {
		$style = $this->get_style_from_status( $item->status );
		$link = $this->get_cloud_map_link( $item->id );
		
		$status = $link ? 'inactive' : 'active';
		$row_class = "snippet $status-snippet $style-snippet";

		printf(
			'<tr id="snippet-%s" class="%s" data-snippet-scope="%s">',
			esc_attr( $item->cloud_id ),
			esc_attr( $row_class ),
			esc_attr( $item->scope )
		);

		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Display the table.
	 *
	 * @return void
	 */
	public function display() {
		$this->render_cloud_snippet_thickbox();
		parent::display();
	}

	/**
	 * Renders the html for the preview thickbox popup.
	 *
	 * @return void
	 */
	public function render_cloud_snippet_thickbox() {
		add_thickbox();
		?>
		<div id="show-code-preview" style="display: none;">
			<h3 id="snippet-name-thickbox"></h3>
			<h4><?php esc_html_e( 'Snippet Code:', 'code-snippets' ); ?></h4>
			<pre class="thickbox-code-viewer">
				<code id="snippet-code-thickbox" class=""></code>
			</pre>
		</div>
		<?php
	}

	/**
	 * Fetch the local data for a downloaded snippet.
	 *
	 * @param string $cloud_id The cloud ID of the snippet.
	 *
	 * @return Cloud_Link|null
	 */
	protected function get_cloud_map_link( $cloud_id ) {
		$local_to_cloud_map = $this->cloud_api->get_local_to_cloud_map();
		$cloud_id_array = array_column( $local_to_cloud_map, 'cloud_id' );

		if ( in_array( $cloud_id, $cloud_id_array, false ) ) {
			$index = array_search( $cloud_id, $cloud_id_array, false );
			return $local_to_cloud_map[ $index ];
		}

		return null;
	}

	/**
	 * Bulk Download Snippets.
	 *
	 * @param array $ids array of int cloud ids to download
	 *
	 * @return void
	 */
	public function download_snippets( $ids ) {
		foreach ( $ids as $id ) {
			//Check if snippet already exists in cloud link transient and skip if it does 
			$cloud_link = $this->get_cloud_map_link( $id );
			if ( $cloud_link ) {
				continue;
			}
			//TODO: This doesn't work to distinguish between codevault and search results
			$source = Cloud_List_Table::CLASS_NAME === $this->class_name ? 'codevault' : 'search';
			//TODO: For bulk download codevault snippets this doesn't update cloud link for first snippet
			$snippet =  $this->cloud_api->download_or_update_snippet( $id, $source, 'download' );

			// Sleep for 1 second to avoid rate limiting
			sleep( 1 );
		}
	}

	/**
	 * Displays the pagination.
	 *
	 * @param string $which Context where the pagination will be displayed.
	 *
	 * @return void
	 */
	protected function pagination( $which ) {
		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		/* translators: %s: Number of items. */
		$num = sprintf( _n( '%s item', '%s items', $total_items, 'code-snippets' ), number_format_i18n( $total_items ) );
		$output = '<span class="displaying-num">' . $num . '</span>';

		$current = $_REQUEST['cloud_page'] ? (int) $_REQUEST['cloud_page'] : $this->get_pagenum();
		$current_class = Cloud_List_Table::CLASS_NAME === $this->class_name ? 'cloud' : 'search';
		$current_url = remove_query_arg( wp_removable_query_args() ). '#' . $current_class ;

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after = '</span></span>';

		$disable_first = false;
		$disable_last = false;
		$disable_prev = false;
		$disable_next = false;

		if ( 1 === $current ) {
			$disable_first = true;
			$disable_prev = true;
		}

		if ( $total_pages === $current ) {
			$disable_last = true;
			$disable_next = true;
		}

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( remove_query_arg( $current_class.'_page', $current_url ) ),
				__( 'First page' ),
				'&laquo;'
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( $current_class.'_page', max( 1, $current - 1 ), $current_url ) ),
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}

		if ( 'bottom' === $which ) {
			$html_current_page = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		} else {
			$html_current_page = sprintf(
				"%s<input class='current-page' id='current-page-selector' type='text' name=$current_class.'_page' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
				$current,
				strlen( $total_pages )
			);
		}
		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );

		/* translators: 1: Current page, 2: Total pages. */
		$current_html = _x( '%1$s of %2$s', 'paging', 'code-snippets' );
		$page_links[] = $total_pages_before . sprintf( $current_html, $html_current_page, $html_total_pages ) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( $current_class.'_page', min( $total_pages, $current + 1 ), $current_url ) ),
				__( 'Next page' ),
				'&rsaquo;'
			);
		}

		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( $current_class.'_page', $total_pages, $current_url ) ),
				__( 'Last page' ),
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class .= ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';

		$page_class = $total_pages ? ( $total_pages < 2 ? ' one-page' : '' ) : ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";
		echo wp_kses_post( $this->_pagination );
	}
}
