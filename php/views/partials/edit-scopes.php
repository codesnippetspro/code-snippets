<?php
/**
 * HTML for the snippet scope selector.
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;

/* @var Edit_Menu $this */

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

			echo '<p>';
			esc_html_e( 'There are multiple options for inserting this snippet into a post, page or other content. You can copy the below shortcode, or use the Classic Editor button, Block Editor block (Pro) or Elementor widget (Pro).', 'code-snippets' );
			echo '</p>';

			$shortcode_atts =
				( $snippet->id ? ' id=' . $snippet->id : '' ) .
				( $snippet->network ? ' network=true' : '' ) .
				( false !== stripos( $snippet->code, '<?' ) ? ' php=true' : '' );

			$shortcode_tag = "[code_snippet$shortcode_atts]";

			echo '<code class="shortcode-tag">', esc_html( $shortcode_tag ), '</code>';

			printf(
				'<a class="code-snippets-copy-text dashicons dashicons-clipboard" title="%s" data-text="%s" href="#"></a>',
				esc_attr__( 'Copy shortcode to clipboard', 'code-snippets' ),
				esc_attr( $shortcode_tag )
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
						<input type="checkbox" value="format" checked="checked">
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
