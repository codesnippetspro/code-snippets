<?php
/**
 * HTML for the Manage Snippets page.
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;

/**
 * Loaded from the Manage_Menu class.
 *
 * @var Manage_Menu $this
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$types = array_merge( [ 'all' => __( 'All Snippets', 'code-snippets' ) ], Plugin::get_types() );

$current_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'all';
$current_type = isset( $types[ $current_type ] ) ? $current_type : 'all';
$version = code_snippets()->version;

if ( false !== strpos( $version, 'beta' ) ) {
	echo '<div class="beta-test-notice"><p id="beta-testing">';
	echo wp_kses(
		__( 'Thank you for testing this <span class="highlight-yellow">Beta version of Code Snippets</span>. We would love to hear your feedback.', 'code-snippets' ),
		[ 'span' => [ 'class' => [ 'highlight-yellow' ] ] ]
	);

	$feedback_url = __( 'mailto:team@codesnippets.pro?subject=Code Snippet Beta Test Feedback', 'code-snippets' );
	printf( '<a href="%s">%s</a>', esc_url( $feedback_url ), esc_html__( 'Click here to submit your feedback', 'code-snippets' ) );
	echo '</p></div>';
}

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

		?>
		<a class="button button-large nav-tab-button nav-tab-inactive go-pro-button"
		   href="https://codesnippets.pro/pricing/" target="_blank"
		   title="Find more about Pro (opens in external tab)">
			<?php echo wp_kses( __( 'Upgrade to <span class="badge">Pro</span> ', 'code-snippets' ), [ 'span' => [ 'class' => 'badge' ] ] ); ?>
			<span class="dashicons dashicons-external"></span>
		</a>
	</h2>

	<?php
	$desc = code_snippets()->get_type_description( $current_type );
	if ( $desc ) {
		echo '<p class="snippet-type-description">', esc_html( $desc );

		$type_names = [
			'php'          => __( 'function snippets', 'code-snippets' ),
			'html'         => __( 'content snippets', 'code-snippets' ),
			'css'          => __( 'style snippets', 'code-snippets' ),
			'js'           => __( 'javascript snippets', 'code-snippets' ),
			'cloud'        => __( 'cloud snippets', 'code-snippets' ),
			'cloud_search' => __( 'Cloud Search', 'code-snippets' ),
			'bundles'      => __( 'Bundles', 'code-snippets' ),
		];

		$type_names = apply_filters( 'code_snippets/admin/manage/type_names', $type_names );

		/* translators: %s: snippet type name */
		$learn_more_text = sprintf( __( 'Learn more about %s &rarr;', 'code-snippets' ), $type_names[ $current_type ] );

		printf(
			' <a href="%s" target="_blank">%s</a></p>',
			esc_url( "https://codesnippets.pro/learn-$current_type/" ),
			esc_html( $learn_more_text )
		);
	}

	do_action( 'code_snippets/admin/manage/before_list_table' );
	$this->list_table->views();

	switch ( $current_type ) {
		case 'cloud_search':
			include_once 'partials/cloud-search.php';
			break;

		default:
			include_once 'partials/list-table.php';
			break;
	}

	do_action( 'code_snippets/admin/manage' );
	?>
</div>
