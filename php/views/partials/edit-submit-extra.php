<?php

namespace Code_Snippets;

/**
 * @package    Code_Snippets
 * @subpackage Views
 *
 * @var Edit_Menu $this
 */

$snippet = $this->snippet;

?>

<p class="submit-inline">
	<?php

	$actions['save_snippet'] = array(
		__( 'Save Changes', 'code-snippets' ),
		__( 'Save Snippet', 'code-snippets' ),
	);

	if ( 'html' !== $snippet->type ) {

		if ( 'single-use' === $snippet->scope ) {
			$actions['save_snippet_execute'] = array(
				__( 'Execute Once', 'code-snippets' ),
				__( 'Save Snippet and Execute Once', 'code-snippets' ),
			);

		} elseif ( ! $snippet->shared_network || ! is_network_admin() ) {

			if ( $snippet->active ) {
				$actions['save_snippet_deactivate'] = array(
					__( 'Deactivate', 'code-snippets' ),
					__( 'Save Snippet and Deactivate', 'code-snippets' ),
				);

			} else {
				$actions['save_snippet_activate'] = array(
					__( 'Activate', 'code-snippets' ),
					__( 'Save Snippet and Activate', 'code-snippets' ),
				);
			}
		}
	}

	foreach ( $actions as $action => $labels ) {
		submit_button( $labels[0], 'secondary small', $action, false, array( 'title' => $labels[1] ) );
	}

	?>
</p>
