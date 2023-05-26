<?php

namespace Code_Snippets\Cloud;

use WP_Plugin_Install_List_Table;
use function Code_Snippets\code_snippets;

/**
 * Contains the class for handling the snippets table
 *
 * @package Code_Snippets
 *
 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 */

/* The WP_List_Table base class is not included by default, so we need to load it */
if ( ! class_exists( 'WP_Plugin_Install_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-plugin-install-list-table.php';
}

/**
 * This class handles the table for the manage cloud snippets search results
 *
 * @package Code_Snippets
 */
class Cloud_Search_List_Table extends WP_Plugin_Install_List_Table{
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
		//Declare global variable due to undeclared warning
		global $tab;
		parent::__construct(
			[
				'singular' => 'cloud-snippet',
				'plural'   => 'cloud-snippets',
				'ajax'     => false,
			]
		);

		$this->cloud_api = code_snippets()->cloud_api;

		// Strip the result query arg from the URL.
		$_SERVER['REQUEST_URI'] = remove_query_arg(['result' ]);
	}

	/**
	 * Prepare items for the table.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->cloud_snippets = $this->fetch_snippets();
		$this->items = $this->cloud_snippets->snippets;
		
		$this->process_actions();

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
		
		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'action', 'snippet', '_wpnonce', 'source', 'cloud-bundle-run', 'cloud-bundle-show', 'bundle_share_name', 'cloud_bundles' ) );
		$action = $_REQUEST['action'] ?? '';
		$snippet = $_REQUEST['snippet'] ?? '';
		$source = $_REQUEST['source'] ?? '';

		if ( isset( $action, $snippet, $source ) ) {
			cloud_lts_process_download_action( $action, $source, $snippet );
		}
						
	}

	public function display_rows() {
		foreach ( (array) $this->items as $item ) {			
			$name_link = $this->get_link_for_name( $item );
			$name			= esc_attr($item->name);
			$codevault 		= esc_attr($item->codevault);
			$description 	= esc_attr( $this->process_description( $item->description ) );
			$wp_tested 		= esc_attr( $item->wp_tested );
			$votes 			= esc_attr( $item->vote_count );
			$number_of_votes = esc_attr( $item->total_votes );
			$tags = $item->tags;
			//grab first tag in array of tags
			$category 		= strtolower( esc_attr( $tags[0] ) );
			?>
		<div class="plugin-card cloud-search-card plugin-card-<?php echo sanitize_html_class( $item->id ); ?>">
			<?php
			echo( cloud_lts_build_column_hidden_input( 'code', $item ) );
			echo( cloud_lts_build_column_hidden_input( 'name', $item ) );
			?>
			<div class="plugin-card-top">
				<div class="name column-name">					
					<h3>
						<a href="<?php echo esc_url( $name_link['cloud-snippet-link'] ); ?>" <?php if( !$name_link['cloud-snippet-downloaded'] ){ echo esc_attr('class="cloud-snippet-preview thickbox" data-snippet="17" data-lang="css"'); }  ?> >
						<?php echo __( $name ) ?>
						<img src="https://codesnippets.cloud/images/plugin-icons/<?php echo __($category) ?>-logo.png" class="plugin-icon" alt="<?php echo __($category) ?>" />
						</a>
					</h3>
				</div>
				<div class="action-links">
					<?php
						echo( $this->get_action_links( $item ) );
					?>
				</div>
				<div class="desc column-description">
					<p><?php echo __( $description ) ?></p>
					<p class="authors"><cite><?php echo sprintf( __( 'Codevault:  <a target="_blank" href="https://codesnippets.cloud/codevault/%s">%s</a>' ), $codevault, $codevault  ) ?></cite></p>
				</div>
			</div>
			<div class="plugin-card-bottom cloud-search-card-bottom">
				<div class="vers column-rating voted-info">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="thumbs-up">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23H5.904M14.25 9h2.25M5.904 18.75c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 01-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 10.203 4.167 9.75 5 9.75h1.053c.472 0 .745.556.5.96a8.958 8.958 0 00-1.302 4.665c0 1.194.232 2.333.654 3.375z"></path>
					</svg>
					<span class="num-ratings" aria-hidden="true"> <?php echo number_format_i18n( $votes ); ?> times by <?php echo number_format_i18n( $number_of_votes ); ?> users.</span>
				</div>
				<div class="column-updated">
					<strong><?php _e( 'Last Updated:' ); ?></strong>
					<?php
						/* translators: %s: Human-readable time difference. */
						printf( __( '%s ago' ), human_time_diff( strtotime( $item->updated ) ) );
					?>
				</div>
				<div class="column-downloaded">
					<?php
						echo sprintf(
							'<a class="snippet-type-badge snippet-status" data-type="%s">%s</a>',
							esc_attr( strtolower( Cloud_API::get_status_name_from_status( $item->status ) ) ),
							esc_html( Cloud_API::get_status_name_from_status( $item->status) )
						);
					?>
				</div>
				<div class="column-compatibility">
				<strong><?php _e( 'WP Compatability:' ); ?></strong>
					<?php
					if ( empty( $wp_tested ) ) {
						echo __( '<span class="compatibility-untested">' . __( 'Not indicated by author' ) . '</span>' );
					} else {
						echo sprintf( __( '<span class="compatibility-compatible">Author states %s</span>' ), $wp_tested );
					}
					?>
				</div>
			</div>
		</div>
		<?php }
	}

	/**
	 * Get the action links for a Code Snippet from the cloud
	 *
	 * @param Cloud_Snippet $snippet The snippet.
	 *
	 * @return string The HTML content to display.
	 */
	protected function get_action_links( $snippet ) {
		
		return cloud_lts_build_action_links( $snippet, 'search' );
	}

	/**
	 * Define the url for the name anchor tag
	 *
	 * @param Cloud_Snippet $snippet The snippet to get URL.
	 *
	 * @return string The URL to be used.
	 */
	protected function get_link_for_name( $snippet ) {
		$link = code_snippets()->cloud_api->get_cloud_link( $snippet->id, 'cloud' );
		
		if ( $link ) {
	
			return [
				'cloud-snippet-link' 		=>	esc_url( code_snippets()->get_snippet_edit_url( (int) $link->local_id ) ), 
				'cloud-snippet-downloaded' 	=> true,
			];
		}
		
		return [
			'cloud-snippet-link' 		=>	esc_url( '#TB_inline?&width=700&height=500&inlineId=show-code-preview' ),
			'cloud-snippet-downloaded' 	=> false,
		];
	}

	/**
	 * Process the description text - limit to 150 characters.
	 *
	 * @param string $description From API
	 *
	 * @return string formatted description string max 150 chars.
	 */
	protected function process_description( $description ) {
		$description = strip_tags( $description );
		$description = strlen( $description ) > 150 ? substr( $description, 0, 150 ) . '...' : $description;

		return $description;
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
	public function fetch_snippets() {
		$cloud_search 	= $_REQUEST['cloud_search'] ?? '';

		//Check if search term has been entered
		if ( !$cloud_search == '') {
			// If we have a search query, then send a search request to cloud server API search endpoint.
			$search_query = sanitize_text_field( wp_unslash( $_REQUEST['cloud_search'] ) );
			$search_by = sanitize_text_field( wp_unslash( $_REQUEST['cloud_select'] ) );
			return $this->cloud_api->fetch_search_results( $search_by, $search_query, $this->get_pagenum() - 1 );
		}
		
		//If no search results, then return empty object
		return new Cloud_Snippets();
	}

	/**
	 * Gets the current search result page number.
	 *
	 * @return integer
	 */
	public function get_pagenum() {
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
		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];
		$pagenum = $this->get_pagenum();

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		$paginate = cloud_lts_pagination( $which, 'search', $total_items, $total_pages, $pagenum );
		$page_class = $paginate['page_class'];
		$output = $paginate['output'];

		echo $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";
		//echo wp_kses_post( $this->_pagination ); TODO: This removes the top input box for page number
	}
}
