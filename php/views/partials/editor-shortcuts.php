<?php
/**
 * HTML for the editor shortcuts tooltip.
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;

/* @var Edit_Menu $this */

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
				<td><?php esc_html_e( 'Select all', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Cmd', 'A' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Undo last change', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Cmd', 'Z' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Redo last undone change', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Cmd', 'Y' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Delete current line', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Cmd', 'D' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Indent current line', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Cmd', ']' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Dedent current line', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Cmd', '[' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Auto-indent the current line or selection', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Shift', 'Tab' ); ?></td>
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
			<tr>
				<td><?php esc_html_e( 'Toggle comment', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Cmd', '/' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Swap line up', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Option', 'Up' ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Swap line down', 'code-snippets' ); ?></td>
				<td><?php $this->render_keyboard_shortcut( 'Option', 'Down' ); ?></td>
			</tr>
		</table>
	</div>
</div>
