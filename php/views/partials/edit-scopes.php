<?php
/**
 * HTML for the snippet scope selector.
 *
 * @package    Code_Snippets
 * @subpackage Views
 *
 * @var Edit_Menu $this
 */

namespace Code_Snippets;

/**
 * Snippet object.
 *
 * @var Snippet $snippet
 */
$snippet = $this->snippet;

echo '<h2 class="screen-reader-text">', esc_html__( 'Scope', 'code-snippets' ), '</h2>';

if ( ! $snippet->id || 'php' === $snippet->type ) {
	echo '<p class="snippet-scope php-scopes-list">';

	$this->print_scopes_list(
		array(
			'global'     => __( 'Run snippet everywhere', 'code-snippets' ),
			'admin'      => __( 'Only run in administration area', 'code-snippets' ),
			'front-end'  => __( 'Only run on site front-end', 'code-snippets' ),
			'single-use' => __( 'Only run once', 'code-snippets' ),
		)
	);

	echo '</p>';
}

if ( ! $snippet->id || 'css' === $snippet->type ) {
	echo '<p class="snippet-scope css-scopes-list">';

	$this->print_scopes_list(
		array(
			'site-css'  => __( 'Site front-end styles', 'code-snippets' ),
			'admin-css' => __( 'Administration area styles', 'code-snippets' ),
		)
	);

	echo '</p>';
}

if ( ! $snippet->id || 'js' === $snippet->type ) {
	echo '<p class="snippet-scope js-scopes-list">';

	$this->print_scopes_list(
		array(
			'site-footer-js' => __( 'Load JS at the end of the &lt;body&gt; section', 'code-snippets' ),
			'site-head-js'   => __( 'Load JS in the &lt;head&gt; section', 'code-snippets' ),
		)
	);

	echo '</p>';
}

if ( ! $snippet->id || 'html' === $snippet->type ) {
	?>
	<div class="snippet-scope html-scopes-list">
		<p>
			<?php

			$pro_scopes = code_snippets()->licensing->is_licensed() ? array(
				'head-content'   => __( 'Display in site &lt;head&gt; section.', 'code-snippets' ),
				'footer-content' => __( 'Display at the end of the &lt;body&gt; section, in the footer.', 'code-snippets' ),
			) : array();

			$this->print_scopes_list(
				array_merge(
					array(
						'content' => __( 'Only display when inserted into a post or page.', 'code-snippets' ),
					),
					$pro_scopes
				)
			);

			?>
		</p>

		<?php
		if ( ! $snippet->id || 'content' === $snippet->scope ) {
			$block_editor = has_action( 'enqueue_block_assets' );
			$elementor = defined( 'ELEMENTOR_VERSION' );

			echo '<p>';
			if ( $elementor && $block_editor ) {
				esc_html_e( 'You can use the Code Snippets editor blocks or Elementor widgets to insert the snippet content into a post or page.', 'code-snippets' );
			} elseif ( $elementor ) {
				esc_html_e( 'You can use the Code Snippets Elementor widgets to insert the snippet content into a post or page.', 'code-snippets' );
			} elseif ( $block_editor ) {
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
					<label>
						<input type="checkbox"
						       value="php"<?php checked( false !== stripos( $snippet->code, '<?' ) ); ?>>
						<?php esc_html_e( 'Evaluate PHP code', 'code-snippets' ); ?>
					</label>
					<label>
						<input type="checkbox" value="format">
						<?php esc_html_e( 'Add paragraphs and formatting', 'code-snippets' ); ?>
					</label>
					<label>
						<input type="checkbox" value="shortcodes">
						<?php esc_html_e( 'Evaluate additional shortcode tags', 'code-snippets' ); ?>
					</label>
				</p>
				<?php
			}
		}
		?>
	</div>
	<?php
}
