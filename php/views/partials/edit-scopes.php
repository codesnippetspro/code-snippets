<?php
/**
 * HTML code for the snippet scope selector
 *
 * @package    Code_Snippets
 * @subpackage Views
 *
 * @var Edit_Menu $this
 */

namespace Code_Snippets;

/** @var Snippet $snippet */
$snippet = $this->snippet;

?>

	<h2 class="screen-reader-text"><?php esc_html_e( 'Scope', 'code-snippets' ); ?></h2>

	<?php if ( ! $snippet->id || 'php' === $snippet->type ) { ?>
	<p class="snippet-scope php-scopes-list"><?php
		$this->print_scopes_list( array(
			'global'     => __( 'Run snippet everywhere', 'code-snippets' ),
			'admin'      => __( 'Only run in administration area', 'code-snippets' ),
			'front-end'  => __( 'Only run on site front-end', 'code-snippets' ),
			'single-use' => __( 'Only run once', 'code-snippets' ),
		) );
		?></p>
<?php }

if ( ! $snippet->id || 'css' === $snippet->type ) { ?>
	<p class="snippet-scope css-scopes-list"><?php
		$this->print_scopes_list( array(
			'site-css'  => __( 'Site front-end styles', 'code-snippets' ),
			'admin-css' => __( 'Administration area styles', 'code-snippets' ),
		) );
		?></p>
<?php }

if ( ! $snippet->id || 'js' === $snippet->type ) { ?>
	<p class="snippet-scope js-scopes-list"><?php
		$this->print_scopes_list( array(
			'site-footer-js' => __( 'Load JS at the end of the <body> section', 'code-snippets' ),
			'site-head-js'   => __( 'Load JS in the <head> section', 'code-snippets' ),
		) );
		?></p>
<?php }

if ( ! $snippet->id || 'html' === $snippet->type ) { ?>
	<div class="snippet-scope html-scopes-list">
		<p><?php
			$this->print_scopes_list( array(
				'content'        => __( 'Only display when inserted into a post or page.', 'code-snippets' ),
				'head-content'   => __( 'Display in site <head> section.', 'code-snippets' ),
				'footer-content' => __( 'Display at the end of the <body> section, in the footer.', 'code-snippets' ),
			) );
			?></p>

		<?php if ( ! $snippet->id || 'content' === $snippet->scope ) {
			$block_editor = has_action( 'enqueue_block_assets' );
			$elementor = defined( 'ELEMENTOR_VERSION' );

			echo '<p>';
			if ( $elementor && $block_editor ) {
				esc_html_e( 'You can use the Code Snippets editor blocks or Elementor widgets to insert the snippet content into a post or page.', 'code-snippets' );
			} else if ( $elementor ) {
				esc_html_e( 'You can use the Code Snippets Elementor widgets to insert the snippet content into a post or page.', 'code-snippets' );
			} else if ( $block_editor ) {
				esc_html_e( 'You can use the Code Snippets editor blocks to insert the snippet content into a post or page.', 'code-snippets' );
			} else {
				/* translators: %s: snippet shortcode tag */
				esc_html_e( 'You can use the %s shortcode to insert the snippet content into a post or page.', 'code-snippets' );

				$shortcode_atts =
					( $snippet->id ? ' id=' . $snippet->id : '' ) .
					( $snippet->network ? ' network=true' : '' ) .
					( false !== stripos( $snippet->code, '<?' ) ? ' php=true' : '' );
			}
			echo '</p>';

			if ( isset( $shortcode_atts ) ) {
				echo '<code class="shortcode-tag">[code_snippet', esc_html( $shortcode_atts ), ']</code>';
			}

			if ( $snippet->id ) { ?>
				<p class="html-shortcode-options">
					<strong><?php esc_html_e( 'Shortcode Options: ', 'code-snippets' ); ?></strong>
					<label><input type="checkbox" value="php"<?php checked( false !== stripos( $snippet->code, '<?' ) ); ?>><?php
						esc_html_e( 'Evaluate PHP code', 'code-snippets' ); ?>
					</label>
					<label><input type="checkbox" value="format"><?php
						esc_html_e( 'Add paragraphs and formatting', 'code-snippets' ); ?>
					</label>
					<label><input type="checkbox" value="shortcodes"><?php
						esc_html_e( 'Evaluate additional shortcode tags', 'code-snippets' ); ?>
					</label>
				</p>
			<?php }
		} ?>
	</div>
<?php }
