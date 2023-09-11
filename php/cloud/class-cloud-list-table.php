<?php
/**
 * Contains the class for handling the cloud table.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Cloud;

use WP_List_Table;
use function Code_Snippets\code_snippets;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * This class handles the table for the manage cloud snippets menu.
 *
 * @package Code_Snippets
 */
class Cloud_List_Table extends WP_List_Table {
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

		// Strip the result query arg from the URL.
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'result' );

		// Check if there is a GET request query parameter to refresh data from the cloud.
		if ( isset( $_GET['refresh'] ) && 'true' === $_GET['refresh'] ) {
			code_snippets()->cloud_api->refresh_synced_data();

			wp_safe_redirect(
				esc_url_raw(
					add_query_arg(
						'result',
						'cloud-refreshed',
						code_snippets()->get_menu_url( 'cloud' )
					)
				)
			);

		}
	}

	/**
	 * Sets the list of columns that are hidden by default.
	 *
	 * @param array<string> $hidden List of hidden columns.
	 *
	 * @return array<string> Modified list of hidden columns
	 */
	public function default_hidden_columns( array $hidden ): array {
		return array_merge( $hidden, [ 'id', 'code', 'cloud_id', 'revision' ] );
	}

	/**
	 * Build the list of column headings.
	 *
	 * @return array
	 */
	public function get_columns(): array {
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
	public function get_bulk_actions(): array {
		$actions = array(
			'download-codevault-selected' => __( 'Download', 'code-snippets' ),
		);

		return apply_filters( 'code_snippets/cloud_list_table/bulk_actions', $actions );
	}

	/**
	 * Fetch the snippets used to populate the table.
	 *
	 * @return Cloud_Snippets|null
	 */
	protected function fetch_snippets() {
		return $this->cloud_api->get_codevault_snippets(
			$this->get_current_page_number()
		);
	}

	/**
	 * Get the current page number.
	 *
	 * @return int $pagenum The current page number.
	 */
	public function get_current_page_number(): int {
		$pagenum = ( isset( $_REQUEST['cloud_page'] ) ? (int) $_REQUEST['cloud_page'] : $this->get_pagenum() ) - 1;
		return $pagenum;
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

		// Check if there are any snippets to display.
		if ( $this->cloud_snippets ) {
			$this->items = $this->cloud_snippets->snippets;
			$total_snippets = $this->cloud_snippets->total_snippets;
			$total_pages = $this->cloud_snippets->total_pages;
			$per_page_count = count( $this->cloud_snippets->snippets );
		} else {
			$this->items = array();
			$total_snippets = 0;
			$total_pages = 0;
			$per_page_count = 0;
		}

		$this->set_pagination_args(
			[
				'per_page'    => $per_page_count,
				'total_items' => $total_snippets,
				'total_pages' => $total_pages,
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
		$codevault_page = $this->get_current_page_number();

		// Check if the current page is the codevault page.
		if ( isset( $_REQUEST['type'] ) && 'cloud' === $_REQUEST['type'] && isset( $_REQUEST['action'], $_REQUEST['snippet'], $_REQUEST['source'] ) ) {
			cloud_lts_process_download_action(
				sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ),
				sanitize_text_field( wp_unslash( $_REQUEST['source'] ) ),
				sanitize_text_field( wp_unslash( $_REQUEST['snippet'] ) ),
				$codevault_page
			);
		}

		// Only continue from this point if there are bulk actions to process.
		if ( ! isset( $_POST['cloud_ids'] ) && ! isset( $_POST['shared_cloud_ids'] ) ) {
			return;
		}

		$ids = isset( $_POST['cloud_ids'] ) ? array_map( 'intval', $_POST['cloud_ids'] ) : array();
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'action' );

		$action = $this->current_action();
		if ( 'download-codevault-selected' === $action || 'download-search-selected' === $action ) {
			$this->download_snippets( $ids, $action, $codevault_page );
			$result = 'download-multi';
		}

		if ( isset( $result ) ) {
			wp_safe_redirect( esc_url_raw( add_query_arg( 'result', $result ) ) );
			exit;
		}
	}

	/**
	 * Define the output of all columns that have no callback function
	 *
	 * @param Cloud_Snippet $item        The snippet used for the current row.
	 * @param string        $column_name The name of the column being printed.
	 *
	 * @return string The content of the column to output.
	 */
	protected function column_default( $item, $column_name ): string {
		$link = code_snippets()->cloud_api->get_cloud_link( $item->id, 'cloud' );

		switch ( $column_name ) {
			case 'tags':
				return join( ', ', $item->tags );

			case 'description':
				return $item->description ?? '';

			case 'name':
				$cloud_icon = '';
				$cloud_link = code_snippets()->cloud_api->get_cloud_link( $item->id, 'cloud' );

				if ( $cloud_link ) {
					// If update available make cloud icon orange?
					if ( $cloud_link->update_available ) {
						$cloud_icon = '<span class="dashicons dashicons-cloud cloud-icon cloud-update"></span>';
					} elseif ( $cloud_link->in_codevault ) {
						// If snippet in codevault and no update available make cloud icon blue.
						$cloud_icon = '<span class="dashicons dashicons-cloud cloud-icon cloud-synced"></span>';
					}
				} else {
					// Make cloud icon grey to show it is from the cloud.
					$cloud_icon = '<span class="dashicons dashicons-cloud cloud-icon cloud-not-downloaded"></span>';
				}
				$edit_url = $link ? code_snippets()->get_snippet_edit_url( $link->local_id ) : '';
				$name_link = sprintf(
					$edit_url ? '<a href="%1$s">%2$s</a>' : '<a>%2$s</a>',
					esc_url( $edit_url ),
					esc_html( $item->name )
				);

				return $cloud_icon . $name_link . cloud_lts_build_column_hidden_input( $column_name, $item );

			case 'updated':
				return sprintf( '<span>%s</span>', esc_html( human_time_diff( strtotime( $item->updated ) ) ) );

			case 'id':
			case 'cloud_id':
			case 'code':
			case 'revision':
				return $item->$column_name . cloud_lts_build_column_hidden_input( $column_name, $item );
			case 'status':
				return sprintf(
					'<a class="snippet-type-badge snippet-status" data-type="%s">%s</a>',
					esc_attr( strtolower( Cloud_API::get_status_name_from_status( $item->status ) ) ),
					esc_html( Cloud_API::get_status_name_from_status( $item->status ) )
				);

			case 'scope':
				$type = Cloud_API::get_type_from_scope( $item->scope );

				return sprintf(
					'<a id="snippet-type-%s" class="snippet-type-badge snippet-type" data-type="%s">%s</a>',
					esc_attr( $type ),
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
	 * Define the columns that can be sorted. TODO: Add the ability to sort columns by clicking on the column name
	 *
	 * @return array<string, string|array<string|boolean>> The IDs of the columns that can be sorted
	 */
	public function get_sortable_columns(): array {
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
	public function column_download( Cloud_Snippet $item ): string {
		return cloud_lts_build_action_links( $item, 'codevault' );
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param Cloud_Snippet $item The snippet being used for the current row.
	 *
	 * @return string The column content to be printed.
	 */
	protected function column_cb( $item ): string {
		$out = sprintf( '<input type="checkbox" name="cloud_ids[]" value="%s">', $item->id );
		return apply_filters( 'code_snippets/cloud_list_table/column_cb', $out, $item );
	}

	/**
	 * Handles the hidden code column
	 *
	 * @param Cloud_Snippet $item The snippet being used for the current row.
	 *
	 * @return string The column content to be printed.
	 */
	protected function column_code( Cloud_Snippet $item ): string {
		$out = sprintf(
			'<input id="cloud-snippet-code-%s" class="cloud-snippet-item hidden" type="hidden" name="code" value="%s" />',
			esc_attr( $item->id ),
			esc_attr( $item->code )
		);

		return apply_filters( 'code_snippets/cloud_list_table/column_code', $out, $item );
	}

	/**
	 * Retrieve the classes for the table.
	 *
	 * We override this in order to add 'snippets' as a class for custom styling.
	 *
	 * @return array The classes to include on the table element.
	 */
	public function get_table_classes(): array {
		$classes = array( 'cloud-table', 'widefat', $this->_args['plural'] );

		return apply_filters( 'code_snippets/cloud_list_table/table_classes', $classes );
	}

	/**
	 * Outputs content for a single row of the table
	 *
	 * @param Cloud_Snippet $item The snippet being used for the current row.
	 */
	public function single_row( $item ) {
		$type = Cloud_API::get_type_from_scope( $item->scope );
		$status_name = strtolower( Cloud_API::get_status_name_from_status( $item->status ) );
		$row_class = "snippet $status_name-snippet $type-snippet";

		printf(
			'<tr id="snippet-%s" class="%s" data-snippet-scope="%s">',
			esc_attr( $item->id ),
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
		Cloud_API::render_cloud_snippet_thickbox();
		parent::display();
	}


	/**
	 * Bulk Download Snippets.
	 *
	 * @param array  $ids            List of int cloud ids to download.
	 * @param string $source         Whether the download is from the codevault or search results i.e. download-codevault-selected.
	 * @param int    $codevault_page The current page of the codevault.
	 *
	 * @return void
	 */
	public function download_snippets( array $ids, string $source, int $codevault_page ) {
		$source = explode( '-', $source )[1];
		foreach ( $ids as $id ) {
			// Check if snippet already exists in cloud link transient and skip if it does.
			$cloud_link = code_snippets()->cloud_api->get_cloud_link( $id, 'cloud' );
			if ( $cloud_link ) {
				continue;
			}
			// TODO: For bulk download codevault snippets this doesn't update cloud link for first snippet.
			$this->cloud_api->download_or_update_snippet( $id, $source, 'download', $codevault_page );
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
		$pagenum = $this->get_pagenum();

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		$paginate = cloud_lts_pagination( $which, 'cloud', $total_items, $total_pages, $pagenum );
		$page_class = $paginate['page_class'];
		$output = $paginate['output'];

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>{$output}</div>";

		// TODO: Add proper input escaping. wp_kses_post removes the top input box for page number.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->_pagination;
	}
}
