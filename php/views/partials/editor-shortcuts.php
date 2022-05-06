<?php
/**
 * HTML for the editor shortcuts tooltip.
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;

?>

<div class="snippet-editor-help">

	<div class="editor-help-tooltip cm-s-<?php
	echo esc_attr( Settings\get_setting( 'editor', 'theme' ) ); ?>"><?php
		echo esc_html_x( '?', 'help tooltip', 'code-snippets' ); ?></div>

	<div class="editor-help-text">
		<table>
			<tr>
				<td><?php esc_html_e( 'Save changes', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Cmd', 'S' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Begin searching', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Cmd', 'F' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Find next', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Cmd', 'G' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Find previous', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( array( 'Shift', 'Cmd' ), 'G' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Replace', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( array( 'Shift', 'Cmd' ), 'F' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Replace all', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( array( 'Shift', 'Cmd', 'Option' ), 'R' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Persistent search', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Alt', 'F' ); ?></td>
			</tr>
		</table>
	</div>
</div>
