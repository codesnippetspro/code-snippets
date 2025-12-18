<?php
/**
 * HTML for the Import Snippets page.
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;

/**
 * Loaded from import menu.
 *
 * @var Import_Menu $this
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$max_size_bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
?>
<div class="wrap">
	<h1>
		<?php

		esc_html_e( 'Import Snippets', 'code-snippets' );

		if ( code_snippets()->is_compact_menu() ) {
			$this->render_page_title_actions( [ 'manage', 'add', 'settings' ] );
		}

		?>
	</h1>

	<div id="import-container"></div>
</div>
