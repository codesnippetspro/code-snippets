<?php
/**
 * Contains the class for handling the cloud search results table
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Cloud;

use WP_Plugin_Install_List_Table;
use function Code_Snippets\code_snippets;

if ( ! class_exists( 'WP_Plugin_Install_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-plugin-install-list-table.php';
}

/**
 * Class for handling the cloud search results table.
 *
 * @property string $_pagination Pagination HTML.
 *
 * @package Code_Snippets
 */
class Cloud_Search_List_Table extends WP_Plugin_Install_List_Table {

	/**
	 * Instance of Cloud API class.
	 *
	 * @var Cloud_API
	 */
	protected Cloud_API $cloud_api;

	/**
	 * Items for the cloud list table.
	 *
	 * @var Cloud_Snippets
	 */
	protected Cloud_Snippets $cloud_snippets;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		/**
		 * Declare global variable due to undeclared warning.
		 *
		 * @noinspection PhpUnusedLocalVariableInspection
		 */
		global $tab;

		parent::__construct(
			[
				'singular' => 'cloud-snippet',
				'plural'   => 'cloud-snippets',
				'ajax'     => false,
			]
		);

		// Strip the result query arg from the URL.
		$_SERVER['REQUEST_URI'] = remove_query_arg( [ 'result' ] );

		$this->cloud_api = code_snippets()->cloud_api;
	}

	/**
	 * Prepare items for the table.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'snippets_per_page', 10 );
		$user_per_page = (int) get_user_option( 'snippets_per_page', get_current_user_id() );
		if ( $user_per_page > 0 ) {
			$per_page = $user_per_page;
		}

		// Fetch snippets, passing a 0-based page index to the Cloud API (WP list tables are 1-based).
		$page_index = max( 0, $this->get_pagenum() - 1 );
		$this->cloud_snippets = $this->fetch_snippets( $per_page, $page_index );
		$this->items = $this->cloud_snippets->snippets;

		$this->process_actions();

		$this->set_pagination_args(
			[
				'per_page'    => $per_page,
				'total_items' => $this->cloud_snippets->total_snippets,
				'total_pages' => $this->cloud_snippets->total_pages,
			]
		);
	}

	/**
	 * Process any actions that have been submitted, such as downloading cloud snippets to the local database.
	 *
	 * @return void
	 */
	public function process_actions() {
		$_SERVER['REQUEST_URI'] = remove_query_arg(
			[ 'action', 'snippet', '_wpnonce', 'source', 'cloud-bundle-run', 'cloud-bundle-show', 'bundle_share_name', 'cloud_bundles' ]
		);

		// Check request is coming from the cloud search page.
		if ( isset( $_REQUEST['type'] ) && 'cloud_search' === $_REQUEST['type'] ) {
				if ( isset( $_REQUEST['action'], $_REQUEST['snippet'], $_REQUEST['source'] ) ) {
					cloud_lts_process_download_action(
						sanitize_key( wp_unslash( $_REQUEST['action'] ) ),
						sanitize_key( wp_unslash( $_REQUEST['source'] ) ),
						sanitize_key( wp_unslash( $_REQUEST['snippet'] ) ),
					);
				}
		}
	}

	/**
	 * Output table rows.
	 *
	 * @return void
	 */
	public function display_rows() {
		$status_descriptions = [
			Cloud_API::STATUS_PUBLIC      =>
				__( 'Snippet has passed basic review.', 'code-snippets' ),
			Cloud_API::STATUS_AI_VERIFIED =>
				__( 'Snippet has been tested by our AI bot.', 'code-snippets' ),
			Cloud_API::STATUS_UNVERIFIED  =>
				__( 'Snippet has not undergone any review yet.', 'code-snippets' ),
		];

		/**
		 * The current table item.
		 *
		 * @var $item Cloud_Snippet
		 */
		foreach ( $this->items as $item ) {
			?>
			<div class="plugin-card cloud-snippet-card plugin-card-<?php echo esc_attr( $item->id ); ?>">
				<?php
				cloud_lts_display_column_hidden_input( 'code', $item );
				cloud_lts_display_column_hidden_input( 'name', $item );
				?>
				<div class="plugin-card-top">
					<div class="column-name">
						<h3>
							<?php

							// Grab first tag in array of tags.
							$category = count( $item->tags ) > 0 ? strtolower( esc_attr( $item->tags[0] ) ) : 'general';

							printf(
								'<img src="%s" class="title-icon" alt="%s">',
								esc_url( "https://codesnippets.cloud/images/plugin-icons/$category-logo.png" ),
								esc_attr( $category )
							);

							$link = code_snippets()->cloud_api->get_link_for_cloud_snippet( $item );

							if ( $link ) {
								printf( '<a href="%s">', esc_url( code_snippets()->get_snippet_edit_url( $link->local_id ) ) );
							} else {
								printf(
									'<a href="%s" title="%s" class="cloud-snippet-preview thickbox" data-snippet="%s" data-lang="%s">',
									'#TB_inline?&width=700&height=500&inlineId=show-code-preview',
									esc_attr__( 'Preview this snippet', 'code-snippets' ),
									esc_attr( $item->id ),
									esc_attr( Cloud_API::get_type_from_scope( $item->scope ) )
								);
							}

							echo esc_html( $item->name );



							echo '</a>';
							?>
						</h3>
						<ul class="action-buttons">
							<?php echo cloud_lts_build_action_links( $item, 'search' ); ?>
						</ul>
					</div>
						<div class="column-description">
						<p><?php echo wp_kses_post( $this->process_description( $item->description ) ); ?></p>
						<p class="authors">
							<cite>
								<?php
								printf(
									'%s <a target="_blank" href="%s">%s</a>',
									esc_html__( 'Codevault:', 'code-snippets' ),
									esc_url( sprintf( 'https://codesnippets.cloud/codevault/%s', $item->codevault ) ),
									esc_html( $item->codevault )
								);
								?>
							</cite>
						</p>
					</div>
				</div>
				<div class="plugin-card-bottom cloud-search-card-bottom">
					<div class="cloud-meta-row">
						<div class="column-downloaded">
							<div class="badge <?php echo esc_attr( $this->cloud_api->get_status_badge( $item->status ) ); ?>-badge tooltip tooltip-block tooltip-end">
								<?php

								echo esc_html( $this->cloud_api->get_status_label( $item->status ) );

								if ( isset( $status_descriptions[ $item->status ] ) ) {
									echo '<span class="dashicons dashicons-info-outline"></span>';
									printf( '<div class="tooltip-content">%s</div>', esc_html( $status_descriptions[ $item->status ] ) );
								}
								?>
							</div>
						</div>

						<div class="column-votes">
							<svg xmlns="http://www.w3.org/2000/svg"
							     fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="thumbs-up">
								<path stroke-linecap="round" stroke-linejoin="round"
								      d="M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23H5.904M14.25 9h2.25M5.904 18.75c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 01-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 10.203 4.167 9.75 5 9.75h1.053c.472 0 .745.556.5.96a8.958 8.958 0 00-1.302 4.665c0 1.194.232 2.333.654 3.375z"></path>
							</svg>
							<span class="num-votes" aria-hidden="true">
								<?php
								echo esc_html( number_format_i18n( $item->vote_count ) );
								?>
							</span>
						</div>

						<div class="column-updated">
							<strong><?php esc_html_e( 'Last Updated:', 'code-snippets' ); ?></strong>
							<?php
							// translators: %s: Human-readable time difference.
							echo esc_html( sprintf( __( '%s ago', 'code-snippets' ), human_time_diff( strtotime( $item->updated ) ) ) );
							?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Process the description text - limit to 150 characters.
	 *
	 * @param string|null $description Description as provided by the API.
	 *
	 * @return string formatted description string max 150 chars.
	 */
	protected function process_description( ?string $description ): string {
		$description = wp_strip_all_tags( $description );
		return strlen( $description ) > 150 ? substr( $description, 0, 150 ) . '…' : $description;
	}

	/**
	 * Text displayed when no snippet data is available.
	 *
	 * @return void
	 */
	public function no_items() {
		if ( ! empty( $_REQUEST['cloud_search'] ) && count( $this->cloud_snippets->snippets ) < 1 ) {
			echo '<p class="no-results">',
			esc_html__( 'No snippets or codevault could be found with that search term. Please try again.', 'code-snippets' ),
			'</p>';
		} else {
			echo '<p>', esc_html__( 'Please enter a term to start searching code snippets in the cloud.', 'code-snippets' ), '</p>';
		}
	}

	/**
	 * Fetch the snippets used to populate the table.
	 *
	 * @return Cloud_Snippets
	 */
	public function fetch_snippets( int $per_page = 10, int $page_index = 0 ): Cloud_Snippets {
		// Check if search term has been entered.
		if ( isset( $_REQUEST['type'], $_REQUEST['cloud_search'], $_REQUEST['cloud_select'] ) &&
				 'cloud_search' === sanitize_key( wp_unslash( $_REQUEST['type'] ) )
		) {
			// If we have a search query, then send a search request to cloud server API search endpoint.
			$search_query = sanitize_text_field( wp_unslash( $_REQUEST['cloud_search'] ) );
			$search_by = sanitize_text_field( wp_unslash( $_REQUEST['cloud_select'] ) );

			// Pass the provided 0-based page index to the API.
			return Cloud_API::fetch_search_results( $search_by, $search_query, $page_index );
		}

		// If no search results, then return empty object.
		return new Cloud_Snippets();
	}

	/**
	 * Gets the current search result page number.
	 *
	 * @return integer
	 */
	public function get_pagenum(): int {
		$page = isset( $_REQUEST['search_page'] ) ? absint( $_REQUEST['search_page'] ) : 0;

		if ( isset( $this->_pagination_args['total_pages'] ) && $page > $this->_pagination_args['total_pages'] ) {
			$page = $this->_pagination_args['total_pages'];
		}

		return max( 1, $page );
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
	 * Displays the pagination.
	 *
	 * @param string $which Context where the pagination will be displayed.
	 *
	 * @return void
	 */
	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args ) ) {
			return;
		}

		$total_items = $this->_pagination_args['total_items'] ?? 0;
		$total_pages = $this->_pagination_args['total_pages'] ?? 0;
		// get_pagenum already returns a 1-based page number used for display.
		$pagenum_display = $this->get_pagenum();

		if ( 'top' === $which && $total_pages >= 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		$paginate = cloud_lts_pagination( $which, 'search', $total_items, $total_pages, $pagenum_display );
		$page_class = $paginate['page_class'];
		$output = $paginate['output'];

		$this->_pagination = "<div class='tablenav-pages$page_class'>$output</div>";

		echo wp_kses_post( $this->_pagination );
	}
}
