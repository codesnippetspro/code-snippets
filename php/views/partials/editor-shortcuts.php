<?php
/**
 * HTML code for the editor shortcuts tooltip
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

	<?php

	$keys = array(
		'Cmd'    => _x( 'Cmd', 'keyboard key', 'code-snippets' ),
		'Ctrl'   => _x( 'Ctrl', 'keyboard key', 'code-snippets' ),
		'Shift'  => _x( 'Shift', 'keyboard key', 'code-snippets' ),
		'Option' => _x( 'Option', 'keyboard key', 'code-snippets' ),
		'Alt'    => _x( 'Alt', 'keyboard key', 'code-snippets' ),
		'F'      => _x( 'F', 'keyboard key', 'code-snippets' ),
		'G'      => _x( 'G', 'keyboard key', 'code-snippets' ),
		'R'      => _x( 'R', 'keyboard key', 'code-snippets' ),
		'S'      => _x( 'S', 'keyboard key', 'code-snippets' ),
	);

	?>

	<div class="editor-help-text">
		<table>
			<tr>
				<td><?php esc_html_e( 'Save changes', 'code-snippets' ); ?></td>
				<td>
					<kbd class="pc-key"><?php echo esc_html( $keys['Ctrl'] ); ?></kbd><kbd class="mac-key"><?php
						echo esc_html( $keys['Cmd'] ); ?></kbd>&hyphen;<kbd><?php echo esc_html( $keys['S'] ); ?></kbd>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Begin searching', 'code-snippets' ); ?></td>
				<td>
					<kbd class="pc-key"><?php echo esc_html( $keys['Ctrl'] ); ?></kbd><kbd class="mac-key"><?php
						echo esc_html( $keys['Cmd'] ); ?></kbd>&hyphen;<kbd><?php echo esc_html( $keys['F'] ); ?></kbd>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Find next', 'code-snippets' ); ?></td>
				<td>
					<kbd class="pc-key"><?php echo esc_html( $keys['Ctrl'] ); ?></kbd><kbd class="mac-key"><?php
						echo esc_html( $keys['Cmd'] ); ?></kbd>&hyphen;<kbd><?php echo esc_html( $keys['G'] ); ?></kbd>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Find previous', 'code-snippets' ); ?></td>
				<td>
					<kbd><?php echo esc_html( $keys['Shift'] ); ?></kbd>-<kbd class="pc-key"><?php echo esc_html( $keys['Ctrl'] );
						?></kbd><kbd class="mac-key"><?php echo esc_html( $keys['Cmd'] ); ?></kbd>&hyphen;<kbd><?php echo esc_html( $keys['G'] ); ?></kbd>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Replace', 'code-snippets' ); ?></td>
				<td>
					<kbd><?php echo esc_html( $keys['Shift'] ); ?></kbd>&hyphen;<kbd class="pc-key"><?php echo esc_html( $keys['Ctrl'] ); ?></kbd><kbd class="mac-key"><?php echo esc_html( $keys['Cmd'] ); ?></kbd>&hyphen;<kbd><?php echo esc_html( $keys['F'] ); ?></kbd>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Replace all', 'code-snippets' ); ?></td>
				<td>
					<kbd><?php echo esc_html( $keys['Shift'] ); ?></kbd>&hyphen;<kbd class="pc-key"><?php echo esc_html( $keys['Ctrl'] );
						?></kbd><kbd class="mac-key"><?php echo esc_html( $keys['Cmd'] ); ?></kbd><span class="mac-key">&hyphen;</span><kbd class="mac-key"><?php echo esc_html( $keys['Option'] ); ?></kbd>&hyphen;<kbd><?php echo esc_html( $keys['R'] ); ?></kbd>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Persistent search', 'code-snippets' ); ?></td>
				<td>
					<kbd><?php echo esc_html( $keys['Alt'] ); ?></kbd>&hyphen;<kbd><?php echo esc_html( $keys['F'] ); ?></kbd>
				</td>
			</tr>
		</table>
	</div>
</div>

