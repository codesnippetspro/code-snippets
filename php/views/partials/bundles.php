<?php
/**
 * HTML for the snippet bundles tab
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;

$bundle_id = sanitize_text_field( wp_unslash( $_REQUEST['cloud_bundles'] ?? '' ) );
$bundle_name = sanitize_text_field( wp_unslash( $_REQUEST['bundle_share_name'] ?? '' ) );

?>

<p class="text-justify">
	<?php esc_html_e( 'A snippet bundle is a set of snippets grouped together to be downloaded from the cloud together.', 'code-snippets' ); ?>
	<?php esc_html_e( 'Please visit your code snippets cloud account to create and manage your bundles.', 'code-snippets' ); ?>
	<?php esc_html_e( 'You can also enter a bundle share code from someone else who has shared their bundle publicly.', 'code-snippets' ); ?>
</p>
<form method="get" action="" id="cloud-search-form">
	<?php List_Table::required_form_fields( 'search_box' ); ?>
	<label class="screen-reader-text" for="cloud-bundles">
		<?php esc_html_e( 'Find and Get Snippet Bundles', 'code-snippets' ); ?>
	</label>
	<?php
	if ( isset( $_REQUEST['type'] ) ) {
		printf( '<input type="hidden" name="type" value="%s">', esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['type'] ) ) ) );
	}
	?>
	<div class="heading-box">
		<p class="cloud-search-heading"><?php esc_html_e( 'Snippet Bundles', 'code-snippets' ); ?></p>
		<p class="text-justify">
			<?php esc_html_e( 'Enter a bundle share code below to see all snippets from a publicly viewable bundle or you can select one of your saved bundles from the dropdown list below.', 'code-snippets' ); ?>
		</p>
	</div>
	<div class="input-group bundle-group">
		<input type="text" id="bundle_share_name" name="bundle_share_name" class="bundle_share_name"
		       placeholder="<?php esc_html_e( 'Enter bundle share code…', 'code-snippets' ); ?> "
		       value="<?php echo esc_html( $bundle_name ); ?>">
		<p class="bundle-share-text">OR</p>
		<select id="cloud-bundles" class="select-bundle" name="cloud_bundles">
			<option value="0"><?php esc_html_e( 'Please choose one of your bundles', 'code-snippets' ); ?></option>
			<?php
			$bundles = Cloud\Cloud_API::get_bundles();
			foreach ( $bundles['bundles'][0] as $bundle ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $bundle['id'] ),
					selected( $bundle['id'], $bundle_id, false ),
					esc_html( $bundle['name'] )
				);
			}
			?>
		</select>
		<button type="submit"
		        id="cloud-bundle-show"
		        class="button"
		        name="cloud-bundle-show"
		        value="true"><?php esc_html_e( 'Show', 'code-snippets' ); ?></button>
		<button type="submit"
		        id="cloud-bundle-run"
		        class="button"
		        name="cloud-bundle-run"
		        value="true"><?php esc_html_e( 'Get Snippets', 'code-snippets' ); ?></button>
	</div>
</form>
<form method="post" action="" id="cloud-search-results">
	<input type="hidden"
	       id="code_snippets_ajax_nonce"
	       value="<?php echo esc_attr( wp_create_nonce( 'code_snippets_manage_ajax' ) ); ?>">

	<?php

	List_Table::required_form_fields();

	// Check if url has a search query called cloud_search.
	if ( isset( $_REQUEST['cloud_bundles'] ) || isset( $_REQUEST['bundle_share_name'] ) ) {
		// If it does, then we want to display the cloud search table.
		$this->cloud_bundles->display();
	}

	?>
</form>
