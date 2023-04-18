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
 * This class handles the table for the manage cloud snippets menu
 *
 * @property string $_pagination
 */
class Cloud_Search_List_Table extends WP_Plugin_Install_List_Table{
	/**
	 * Base URL for cloud API.
	 *
	 * @var string
	 */
	const CLASS_NAME = 'Code_Snippets\Cloud\Cloud_Search_List_Table';

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
	 * Prepare items for the table.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->process_actions();

		//$columns = $this->get_columns();
		//$hidden = [ 'id', 'code', 'cloud_id', 'revision' ];
		//$this->_column_headers = array( $columns, $hidden );

		$this->cloud_snippets = $this->fetch_snippets();
		$this->items = $this->cloud_snippets->snippets;

		// $this->set_pagination_args(
		// 	[
		// 		'per_page'    => count( $this->cloud_snippets->snippets ),
		// 		'total_items' => $this->cloud_snippets->total_snippets,
		// 		'total_pages' => (int) $this->cloud_snippets->total_pages,
		// 	]
		// );
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
		if( 'download-codevault-selected' == $this->current_action() || 'download-search-selected' == $this->current_action()) {
				$this->download_snippets( $ids, $this->current_action() );
				$result = 'download-multi';
		}

		if ( isset( $result ) ) {
			wp_safe_redirect( esc_url_raw( add_query_arg( 'result', $result ) ) );
			exit;
		}
	}

	public function display_rows() {
		foreach ( (array) $this->items as $item ) {			
			//wp_die( var_dump( $item ) );
			$name_link = $this->get_link_for_name( $item );
			$name			= esc_attr($item->name);
			$codevault 		= esc_attr($item->codevault);
			$description 	= esc_attr( $this->process_description( $item->description ) );
			//TODO: investigate why this is not showing
			$wp_tested 		= esc_attr( $item->wp_tested );
			$votes 			= esc_attr( $item->vote_count );
			$number_of_votes = esc_attr( $item->total_votes );
			$tags = $item->tags;
			//grab first tag in array of tags
			$category 		= strtolower( esc_attr( $tags[0] ) );
			?>
		<div class="plugin-card cloud-search-card plugin-card-<?php echo sanitize_html_class( $item->id ); ?>">
			<?php
			echo( $this->build_column_hidden_input( 'code', $item ) );
			echo( $this->build_column_hidden_input( 'name', $item ) );
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
					<p class="authors"><cite><?php echo sprintf( __( 'Codevault:  <a href="https://codesnippets.cloud/codevault/%s">%s</a>' ), $codevault, $codevault  ) ?></cite></p>
				</div>
			</div>
			<div class="plugin-card-bottom cloud-search-card-bottom">
				<div class="vers column-rating voted-info">
					Voted 
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
					<?php
					if ( empty( $wp_tested ) ) {
						echo __( '<span class="compatibility-untested">' . __( 'Wordpress version not indicated by author' ) . '</span>' );
					} else {
						echo sprintf( __( '<span class="compatibility-compatible">Author states comptability with Wordpress  %s</span>' ), $wp_tested );
					}
					?>
				</div>
			</div>
		</div>
		<?php }
	}


	protected function display_tablenav( $which ) {

			?>
			<div class="tablenav top">
				<div class="alignleft actions">
					<?php
					/**
					 * Fires before the Plugin Install table header pagination is displayed.
					 *
					 * @since 2.7.0
					 */
					do_action( 'install_plugins_table_header' );
					?>
				</div>
				<?php $this->pagination( $which ); ?>
				<br class="clear" />
			</div>
			<?php
	}

	/**
	 * Get the action links for a Code Snippet from the cloud
	 *
	 * @param Cloud_Snippet $snippet The snippet.
	 *
	 * @return string The HTML content to display.
	 */
	protected function get_action_links( $snippet ) {
		$lang = Cloud_API::get_type_from_scope( $snippet->scope );
		$link = code_snippets()->cloud_api->get_cloud_link( $snippet->id, 'cloud' );
		$update_available = $link && $link->update_available;

		if ( $link && ! $link->update_available ) {
			return sprintf(
				'<a href="%s" class="cloud-snippet-downloaded action-button-link">%s</a>',
				esc_url( code_snippets()->get_snippet_edit_url( $link->local_id ) ),
				esc_html__( 'View', 'code-snippets' )
			);
		}

		if( $update_available ) {
			$action_link = sprintf(
				'<a class="cloud-snippet-update action-button-link" href="%s">%s</a>',
				esc_url( code_snippets()->get_snippet_edit_url( $link->local_id ) . '/#updated-code' ),
				esc_html__( 'Update Available', 'code-snippets' )
			);
		}else{
			//Get source of snippet - codevault or search use current class as snippet source is not set for every cloud snippet in cloud link
			$source = self::CLASS_NAME === $this->class_name ? 'codevault' : 'search';

			$download_url = add_query_arg(
				[
					'action'  => 'download',
					'snippet' => $snippet->id,
					'source'  => $source ,
				]
			);

			$action_link = sprintf(
				'<a class="cloud-snippet-download action-button-link" href="%s">%s</a>',
					esc_url( $download_url ),
					esc_html__( 'Download', 'code-snippets' )
			);
		}

		$thickbox_url = '#TB_inline?&width=700&height=500&inlineId=show-code-preview';

		$thickbox_link = sprintf(
			'<a href="%s" class="cloud-snippet-preview cloud-snippet-preview-style thickbox action-button-link" data-snippet="%s" data-lang="%s">%s</a>',
			esc_url( $thickbox_url ),
			esc_attr( $snippet->id ),
			esc_attr( $lang ),
			esc_html__( 'Preview', 'code-snippets' )
		);

		return $action_link . $thickbox_link;
	}

	/**
	 * Define the url for the nam anchor tag
	 *
	 * @param Cloud_Snippet $snippet The snippet to get URL.
	 *
	 * @return string The URL to be used.
	 */
	protected function get_link_for_name( $snippet ) {
		$link = code_snippets()->cloud_api->get_cloud_link( $snippet->id, 'cloud' );
		
		if ( $link ) {
	
			return [
				'cloud-snippet-link' 		=>	esc_url( code_snippets()->get_snippet_edit_url( $link->local_id ) ), 
				'cloud-snippet-downloaded' 	=> true,
			];
		}
		
		return [
			'cloud-snippet-link' 		=>	esc_url( '#TB_inline?&width=700&height=500&inlineId=show-code-preview' ),
			'cloud-snippet-downloaded' 	=> false,
		];
	}

	/**
	 * Build a hidden input field for any hidden columns.
	 *
	 * @param string        $column_name Column name - Name, Code.
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
		// Create an empty results object if there's no search query.
		//TODO: Get featured snippets from cloud server API.
		if ( empty( $_REQUEST['cloud_search'] ) ) {
			return new Cloud_Snippets();
		}

		// If we have a search query, then send a search request to cloud server API search endpoint.
		$search_query = sanitize_text_field( wp_unslash( $_REQUEST['cloud_search'] ) );
		$search_by = sanitize_text_field( wp_unslash( $_REQUEST['cloud_select'] ) );
		return $this->cloud_api->fetch_search_results( $search_by, $search_query, $this->get_pagenum() - 1 );
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
}
