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

/* Bail if accessed directly */
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

		$this->page_title_actions( code_snippets()->is_compact_menu() ? [ 'add', 'import', 'settings' ] : [ 'add', 'import' ] );

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
			<a class="button button-large nav-tab-button go-pro-button" href="https://codesnippets.pro/pricing/"
			   target="_blank"
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

	if ( 'cloud' === $current_type ) {
		$search_query = isset( $_REQUEST['cloud_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['cloud_search'] ) ) : '';

		?>
		<h2>Cloud Search</h2>
		<p id="search">Search the Code Snippets Cloud for snippets that you can import into your site.</p>
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

			<input type="text" id="cloud_search" name="cloud_search" class="cloud_search"
			       value="<?php echo esc_html( $search_query ); ?>"
			       placeholder="<?php esc_html_e( 'e.g. Remove unused JavaScript…', 'code-snippets' ); ?>">

			<button type="submit" id="cloud-search-submit" class="button">Search Cloud</button>
		</form>


		<form method="post" action="">
			<input type="hidden" id="code_snippets_ajax_nonce"
			       value="<?php echo esc_attr( wp_create_nonce( 'code_snippets_manage_ajax' ) ); ?>">
			<?php

			List_Table::required_form_fields();
			$this->cloud_search_list_table->display();

			?>
		</form>

		<h2>My Codevault</h2>
		<p id="cloud">See all public and private snippets in your Code Snippet Cloud Codevault.</p>
	<?php } ?>

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
		<p><span class="dashicons dashicons-cloud cloud-icon cloud-synced"></span>Snippet Synced to Codevault</p>
		<p><span class="dashicons dashicons-cloud cloud-icon cloud-downloaded"></span>Snippet Downloaded from Cloud</p>
		<p><span class="dashicons dashicons-cloud cloud-icon cloud-update"></span>Snippet Update available</p>
	</div>
	<?php do_action( 'code_snippets/admin/manage', $current_type ); ?>
</div>
