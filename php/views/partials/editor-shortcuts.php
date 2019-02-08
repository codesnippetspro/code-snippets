<?php

namespace Code_Snippets;

/**
 * HTML code for the editor shortcuts tooltip
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

?>

<div class="snippet-editor-help">

	<div class="editor-help-tooltip cm-s-<?php
	echo esc_attr( Settings\get_setting( 'editor', 'theme' ) ); ?>"><?php
		echo esc_html_x( '?', 'help tooltip', 'code-snippets' ); ?></div>

	<?php

	$keys = array(
		'Cmd'    => esc_html_x( 'Cmd', 'keyboard key', 'code-snippets' ),
		'Ctrl'   => esc_html_x( 'Ctrl', 'keyboard key', 'code-snippets' ),
		'Shift'  => esc_html_x( 'Shift', 'keyboard key', 'code-snippets' ),
		'Option' => esc_html_x( 'Option', 'keyboard key', 'code-snippets' ),
		'Alt'    => esc_html_x( 'Alt', 'keyboard key', 'code-snippets' ),
		'F'      => esc_html_x( 'F', 'keyboard key', 'code-snippets' ),
		'G'      => esc_html_x( 'G', 'keyboard key', 'code-snippets' ),
		'R'      => esc_html_x( 'R', 'keyboard key', 'code-snippets' ),
		'S'      => esc_html_x( 'S', 'keyboard key', 'code-snippets' ),
	);

	?>

	<div class="editor-help-text">
		<table>
			<tr>
				<td><?php esc_html_e( 'Save changes', 'code-snippets' ); ?></td>
				<td>
					<kbd class="pc-key"><?php echo $keys['Ctrl']; ?></kbd><kbd class="mac-key"><?php
						echo $keys['Cmd']; ?></kbd>&hyphen;<kbd><?php echo $keys['S']; ?></kbd>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Begin searching', 'code-snippets' ); ?></td>
				<td>
					<kbd class="pc-key"><?php echo $keys['Ctrl']; ?></kbd><kbd class="mac-key"><?php
						echo $keys['Cmd']; ?></kbd>&hyphen;<kbd><?php echo $keys['F']; ?></kbd>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Find next', 'code-snippets' ); ?></td>
				<td>
					<kbd class="pc-key"><?php echo $keys['Ctrl']; ?></kbd><kbd class="mac-key"><?php echo $keys['Cmd']; ?></kbd>&hyphen;<kbd><?php echo $keys['G']; ?></kbd>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Find previous', 'code-snippets' ); ?></td>
				<td>
					<kbd><?php echo $keys['Shift']; ?></kbd>-<kbd class="pc-key"><?php echo $keys['Ctrl']; ?></kbd><kbd class="mac-key"><?php echo $keys['Cmd']; ?></kbd>&hyphen;<kbd><?php echo $keys['G']; ?></kbd>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Replace', 'code-snippets' ); ?></td>
				<td>
					<kbd><?php echo $keys['Shift']; ?></kbd>&hyphen;<kbd class="pc-key"><?php echo $keys['Ctrl']; ?></kbd><kbd class="mac-key"><?php echo $keys['Cmd']; ?></kbd>&hyphen;<kbd><?php echo $keys['F']; ?></kbd>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Replace all', 'code-snippets' ); ?></td>
				<td>
					<kbd><?php echo $keys['Shift']; ?></kbd>&hyphen;<kbd class="pc-key"><?php echo $keys['Ctrl']; ?></kbd><kbd class="mac-key"><?php echo $keys['Cmd']; ?></kbd><span class="mac-key">&hyphen;</span><kbd class="mac-key"><?php echo $keys['Option']; ?></kbd>&hyphen;<kbd><?php echo $keys['R']; ?></kbd>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Persistent search', 'code-snippets' ); ?></td>
				<td>
					<kbd><?php echo $keys['Alt']; ?></kbd>&hyphen;<kbd><?php echo $keys['F']; ?></kbd>
				</td>
			</tr>
		</table>
	</div>
</div>

