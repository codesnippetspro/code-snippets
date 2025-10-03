<?php
/**
 * HTML for the Manage Snippets page.
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;

use Code_Snippets\Cloud\Cloud_API;
use function Code_Snippets\Settings\get_setting;

/**
 * Loaded from the manage menu class.
 *
 * @var Manage_Menu $this
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}


$licensed = code_snippets()->licensing->is_licensed();
$cloud_enabled = code_snippets()->cloud_api->is_cloud_key_verified();
$types = array_merge( [ 'all' => __( 'All Snippets', 'code-snippets' ) ], Plugin::get_types() );
$current_type = $this->get_current_type();

if ( false !== strpos( code_snippets()->version, 'beta' ) ) {
	echo '<div class="notice beta-test-notice"><p id="beta-testing">';
	echo wp_kses(
		__( 'Thank you for testing this <span class="highlight-yellow">beta version of Code Snippets</span>. We would love to hear your thoughts.', 'code-snippets' ),
		[ 'span' => [ 'class' => [ 'highlight-yellow' ] ] ]
	);

	printf(
		' <a href="%s" class="button button-secondary" target="_blank">%s</a>',
		esc_url( __( 'https://codesnippets.pro/beta-testing/feedback/', 'code-snippets' ) ),
		esc_html__( 'Share feedback', 'code-snippets' )
	);
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

		Admin::render_snippet_type_tabs( $types, $current_type );

		if ( ! $licensed && ! get_setting( 'general', 'hide_upgrade_menu' ) ) {
			?>
			<a class="button button-large nav-tab-button nav-tab-inactive go-pro-button"
			   href="https://codesnippets.pro/pricing/" target="_blank"
			   aria-label="<?php esc_attr_e( 'Find more about Pro (opens in external tab)', 'code-snippets' ); ?>">
				<?php echo wp_kses( __( 'Upgrade to <span class="badge">Pro</span>', 'code-snippets' ), [ 'span' => [ 'class' => 'badge' ] ] ); ?>
				<span class="dashicons dashicons-external"></span>
			</a>
			<?php
		}
		?>
		<div class="cloud-connect-wrap <?php echo $cloud_enabled ? 'cloud-connect-active' : ''; ?>">
			<span class="cloud-status-dot"></span>
			<p>
				<?php esc_html_e( 'Cloud sync:', 'code-snippets' ); ?>
				<span class="cloud-connect-text">
					<?php
					echo $cloud_enabled ?
						esc_html__( 'Connected', 'code-snippets' ) :
						esc_html__( 'Disconnected', 'code-snippets' );
					?>
				</span>
			</p>
			<?php

			if ( $cloud_enabled ) {
				$this->render_view( 'partials/cloud-sync-guide' );
			} else {
				printf(
					'<a href="%s" class="button button-secondary button-small" target="_blank" title="%s">%s</a>',
					esc_url( Cloud_API::get_connect_cloud_url() ),
					esc_attr__( 'Authorize a connection between this site and Code Snippets Cloud.', 'code-snippets' ),
					esc_html__( 'Connect', 'code-snippets' )
				);
			}

			?>
		</div>
	</h2>

	<?php

	$type_info = [
		'php'   => [
			__( 'Function snippets are run on your site as if there were in a plugin or theme functions.php file.', 'code-snippets' ),
			__( 'Learn more about function snippets &rarr;', 'code-snippets' ),
			'https://codesnippets.pro/learn-php/',
		],
		'html'  => [
			__( 'Content snippets are bits of reusable PHP and HTML content that can be inserted into posts and pages.', 'code-snippets' ),
			__( 'Learn more about content snippets &rarr;', 'code-snippets' ),
			'https://codesnippets.pro/learn-html/',
		],
		'css'   => [
			__( 'Style snippets are written in CSS and loaded in the admin area or on the site front-end, just like the theme style.css.', 'code-snippets' ),
			__( 'Learn more about style snippets &rarr;', 'code-snippets' ),
			'https://codesnippets.pro/learn-css/',
		],
		'js'    => [
			__( 'Script snippets are loaded on the site front-end in a JavaScript file, either in the head or body sections.', 'code-snippets' ),
			__( 'Learn more about javascript snippets &rarr;', 'code-snippets' ),
			'https://codesnippets.pro/learn-js/',
		],
		'cloud' => [
			__( 'See all your public and private snippets that are stored in your Code Snippet Cloud codevault.', 'code-snippets' ),
			__( 'Learn more about Code Snippets Cloud &rarr;', 'code-snippets' ),
			'https://codesnippets.cloud/getstarted/',
		],
	];


	if ( isset( $type_info[ $current_type ] ) ) {
		$info = $type_info[ $current_type ];

		printf(
			'<p class="snippet-type-description">%s <a href="%s" target="_blank">%s</a></p>',
			esc_html( $info[0] ),
			esc_url( $info[2] ),
			esc_html( $info[1] )
		);
	}

	do_action( 'code_snippets/admin/manage/before_list_table' );
	$this->list_table->views();

	switch ( $current_type ) {
		case 'cloud_search':
			include_once 'partials/cloud-search.php';
			break;

		case 'bundles':
			include_once 'partials/bundles.php';
			break;

		default:
			include_once 'partials/list-table.php';
			break;
	}

	do_action( 'code_snippets/admin/manage', $current_type );

	?>
</div>
