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
			$this->print_scopes_list(
				array(
					'content' => __( 'Only display when inserted into a post or page.', 'code-snippets' ),
				)
			);
			?>
		</p>

		<?php
		if ( ! $snippet->id || 'content' === $snippet->scope ) {
			/* translators: %s: snippet shortcode tag */
			$text = $snippet->id ? __( 'You can use the %s shortcode to insert your content into a post or page.', 'code-snippets' ) : __( 'After saving, you will be able to use the %s shortcode to insert your content into a post or page.', 'code-snippets' );

			$shortcode_atts = ( $snippet->id ? ' id=' . $snippet->id : '' ) . ( $snippet->network ? ' network=true' : '' );

			if ( false !== stripos( $snippet->code, '<?' ) ) {
				$shortcode_atts .= ' php=true';
			}

			printf(
				'<p>' . esc_html( $text ) . '</p>',
				'<code class="shortcode-tag">[code_snippet' . esc_html( $shortcode_atts ) . ']</code>'
			);

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
