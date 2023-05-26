<?php
/**
 * HTML for the Manage Snippets page.
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;

use Code_Snippets\Cloud\Cloud_API;

/* @var Manage_Menu $this */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$licensed = code_snippets()->licensing->is_licensed();
$types = array_merge( [ 'all' => __( 'All Snippets', 'code-snippets' ) ], Plugin::get_types() );
$current_type = $this->get_current_type();

?>

<div class="wrap">
	<h1>
		<?php
		esc_html_e( 'Snippets', 'code-snippets' );

		$this->render_page_title_actions( code_snippets()->is_compact_menu() ? [ 'add', 'import', 'settings' ] : [ 'add', 'import' ] );

		$this->list_table->search_notice();
		

		?>
	</h1>

	<?php $this->print_messages(); ?>

	<h2 class="nav-tab-wrapper" id="snippet-type-tabs">
		<?php

		foreach ( $types as $type_name => $label ) {
			Admin::render_snippet_type_tab( $type_name, $label, $current_type );
		}

		if ( ! $licensed ) {
			?>
			<a class="button button-large nav-tab-button nav-tab-inactive go-pro-button"
			   href="https://codesnippets.pro/pricing/" target="_blank"
			   title="Find more about Pro (opens in external tab)">
				<?php echo wp_kses( __( 'Upgrade to <span class="badge">Pro</span>', 'code-snippets' ), [ 'span' => [ 'class' => 'badge' ] ] ); ?>
				<span class="dashicons dashicons-external"></span>
			</a>
			<?php
		}
		?>
	</h2>

	<?php
	$desc = code_snippets()->get_type_description( $current_type );
	if ( $desc ) {
		echo '<p class="snippet-type-description">', esc_html( $desc );

		$type_names = [
			'php'   => __( 'function snippets', 'code-snippets' ),
			'html'  => __( 'content snippets', 'code-snippets' ),
			'css'   => __( 'style snippets', 'code-snippets' ),
			'js'    => __( 'javascript snippets', 'code-snippets' ),
			'cloud' => __( 'cloud snippets', 'code-snippets' ),
			'cloud_search' => __( 'Cloud Search', 'code-snippets' ),
			'bundles' => __( 'Bundles', 'code-snippets' ),
		];

		$type_names = apply_filters( 'code_snippets/admin/manage/type_names', $type_names );

		/* translators: %s: snippet type name */
		$learn_more_text = sprintf( __( 'Learn more about %s &rarr;', 'code-snippets' ), $type_names[ $current_type ] );

		$learn_url = 'cloud' === $current_type ?
			Cloud_API::CLOUD_URL :
			"https://codesnippets.pro/learn-$current_type/";

		printf(
			' <a href="%s" target="_blank">%s</a></p>',
			esc_url( $learn_url ),
			esc_html( $learn_more_text )
		);
	}
	?>

	<?php
	do_action( 'code_snippets/admin/manage/before_list_table' );
	$this->list_table->views();

	if ( 'cloud_search' === $current_type ) {

		$search_query = isset( $_REQUEST['cloud_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['cloud_search'] ) ) : '';
	?>
		
		<p class="text-justify">
			<?php echo __('Use the search bar below to search cloud snippets by entering either the name of a codevault 
			(Important : codevault name is case and spelling sensitive and only public snippets will be shown) or by keyword(s).'); ?>
		</p>

		<form method="get" action="" id="cloud-search-form">
			<?php List_Table::required_form_fields( 'search_box' ); ?>
			<label class="screen-reader-text" for="cloud_search">
				<?php esc_html_e( 'Search cloud snippets', 'code-snippets' ); ?>
			</label>
			<?php 
				if( isset($_REQUEST['type'] ) ){
					echo '<input type="hidden" name="type" value="' . sanitize_text_field( esc_attr( $_REQUEST['type' ] ) ) . '">';
				}
			?>
			<div class="heading-box"> 
				<p class="cloud-search-heading"><?php echo __('Search Cloud'); ?></p>
			</div>
			<div class="input-group">
				<select id="cloud-select-prepend" class="select-prepend" name="cloud_select">
					<option value="term"><?php echo __('Search by Keyword(s)'); ?></option>
					<option value="codevault"><?php echo __(' Name of Codevault'); ?> </option>
				</select>
				<input type="text" id="cloud_search" name="cloud_search" class="cloud_search"
					value="<?php echo esc_html( $search_query ); ?>"
					placeholder="<?php esc_html_e( 'e.g. Remove unused JavaScript…', 'code-snippets' ); ?>">
				<button type="submit" id="cloud-search-submit" class="button"><?php echo __('Search Cloud'); ?><span class="dashicons dashicons-search cloud-search"></span></button>
			</div>
		</form>
		<form method="post" action="" id="cloud-search-results">
			<input type="hidden" id="code_snippets_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'code_snippets_manage_ajax' ) ); ?>">
			<?php
				List_Table::required_form_fields();
				//Check if url has a search query called cloud_search
				if( isset( $_REQUEST['cloud_search'] ) ){
					//If it does, then we want to display the cloud search table
					$this->cloud_search_list_table->display();
				}			
		echo '</form>';
	
	}elseif('bundles' === $current_type ){

		$bundle_id = isset( $_REQUEST['cloud_bundles'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['cloud_bundles'] ) ) : '';
		$bundle_name = isset( $_REQUEST['bundle_share_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bundle_share_name'] ) ) : '';
	?>
		<p class="text-justify"><?php echo __('A Cloud bundle is a set of snippets grouped together to be downloaded from the cloud together.
			Please visit your code snippets cloud account to create and manage your bundles. You can also enter a bundle share code from someone else who 
			has shared their bundle publicly.'); ?>
		</p>
		<form method="get" action="" id="cloud-search-form">
			<?php List_Table::required_form_fields( 'search_box' ); ?>
			<label class="screen-reader-text" for="cloud-bundles">
				<?php esc_html_e( 'Find and Get Cloud Bundles', 'code-snippets' ); ?>
			</label>
			<?php 
				if( isset($_REQUEST['type'] ) ){
					echo '<input type="hidden" name="type" value="' . sanitize_text_field( esc_attr( $_REQUEST['type' ] ) ) . '">';
				}
			?>			
			<div class="heading-box"> 
				<p class="cloud-search-heading"><?php echo __('Cloud Bundles'); ?></p>
				<p class="text-justify"><?php echo __('Enter a Bundle Share Code below to see all snippets from a publicly viewable bundle or
					you can select one of your saved bundles from the dropdown list below.'); ?>
				</p>
			</div>
			<div class="input-group bundle-group">
				<input type="text" id="bundle_share_name" name="bundle_share_name" class="bundle_share_name"
					placeholder="<?php esc_html_e( 'Enter Bundle Share Code..', 'code-snippets' ); ?> " 
					value="<?php echo esc_html( $bundle_name ); ?>">
				<p class="bundle-share-text">OR</p>
				<select id="cloud-bundles" class="select-bundle" name="cloud_bundles">
					<option value="0"><?php echo __('Please choose one of your bundles'); ?></option>
					<?php
						$bundles = Cloud_API::get_bundles();
						$selected = '';
						foreach( $bundles['bundles'][0] as $bundle ){
							if( $bundle['id'] == $bundle_id ){
								//echo '<option value="' . $bundle['id'] . '" selected>' . $bundle['name'] . '</option>';
								$selected = ' selected';
							}
							echo '<option value="' . $bundle['id'] . '"'. $selected .'>' . $bundle['name'] . '</option>';
							$selected = '';
						}
					?>
				</select>
				<button type="submit" id="cloud-bundle-show" class="button" name="cloud-bundle-show" value="true"><?php esc_html_e( 'Show', 'code-snippets' ); ?></button>
				<button type="submit" id="cloud-bundle-run" class="button" name="cloud-bundle-run" value="true"><?php esc_html_e( 'Get Snippets', 'code-snippets' ); ?></button>
			</div>
		</form>
		<form method="post" action="" id="cloud-search-results">
			<input type="hidden" id="code_snippets_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'code_snippets_manage_ajax' ) ); ?>">
			<?php
				List_Table::required_form_fields();
				//Check if url has a search query called cloud_search
				if( isset( $_REQUEST['cloud_bundles'] ) || isset( $_REQUEST['bundle_share_name'] ) ){
					//If it does, then we want to display the cloud search table
					$this->cloud_bundles->display();
				}			
		echo '</form>';

	}else{

	?>
		<form method="get" action="">
			<?php
			List_Table::required_form_fields( 'search_box' );

			if ( 'cloud' === $current_type ) {
				$this->cloud_list_table->search_box( __( 'Search Snippets', 'code-snippets' ), 'cloud_search_id' );
			} else {
				$this->list_table->search_box( __( 'Search Snippets', 'code-snippets' ), 'search_id' );
			}
			?>
		</form>

		<form method="post" action="">
			<input type="hidden" id="code_snippets_ajax_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'code_snippets_manage_ajax' ) ); ?>">
			<?php
			List_Table::required_form_fields();

			if ( 'cloud' === $current_type ) {
				$this->cloud_list_table->display();
			} else {
				$this->list_table->display();
			}
			?>
		</form>
		<div class="cloud-key">
			<p><b><u>Cloud Sync Guide</u></b></p>
			<p><span class="dashicons dashicons-cloud cloud-icon cloud-synced"></span>Snippet downloaded and in sync with Codevault</p>
			<p><span class="dashicons dashicons-cloud cloud-icon cloud-downloaded"></span>Snippet Downloaded from Cloud but not synced with Codevault</p>
			<p><span class="dashicons dashicons-cloud cloud-icon cloud-not-downloaded"></span>Snippet in Codevault but not downloaded to local site</p>
			<p><span class="dashicons dashicons-cloud cloud-icon cloud-update"></span>Snippet Update available</p>
		</div>
	<?php } do_action( 'code_snippets/admin/manage', $current_type ); ?>
</div>
