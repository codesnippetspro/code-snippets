<?php
/**
 * HTML for the editor shortcuts tooltip.
 *
 * @package    Code_Snippets
 * @subpackage Views
 */

namespace Code_Snippets;

/* @var Edit_Menu $this */

$keys = array(
	'Cmd'    => _x( 'Cmd', 'keyboard key', 'code-snippets' ),
	'Ctrl'   => _x( 'Ctrl', 'keyboard key', 'code-snippets' ),
	'Shift'  => _x( 'Shift', 'keyboard key', 'code-snippets' ),
	'Option' => _x( 'Option', 'keyboard key', 'code-snippets' ),
	'Alt'    => _x( 'Alt', 'keyboard key', 'code-snippets' ),
	'Tab'    => _x( 'Tab', 'keyboard key', 'code-snippets' ),
	'Up'     => _x( 'Up', 'keyboard key', 'code-snippets' ),
	'Down'   => _x( 'Down', 'keyboard key', 'code-snippets' ),
	'A'      => _x( 'A', 'keyboard key', 'code-snippets' ),
	'D'      => _x( 'D', 'keyboard key', 'code-snippets' ),
	'F'      => _x( 'F', 'keyboard key', 'code-snippets' ),
	'G'      => _x( 'G', 'keyboard key', 'code-snippets' ),
	'R'      => _x( 'R', 'keyboard key', 'code-snippets' ),
	'S'      => _x( 'S', 'keyboard key', 'code-snippets' ),
	'Y'      => _x( 'Y', 'keyboard key', 'code-snippets' ),
	'Z'      => _x( 'Z', 'keyboard key', 'code-snippets' ),
	'/'      => _x( '/', 'keyboard key', 'code-snippets' ),
	'['      => _x( ']', 'keyboard key', 'code-snippets' ),
	']'      => _x( ']', 'keyboard key', 'code-snippets' ),
);

$shortcuts = [
	[
		'label' => __( 'Save changes', 'code-snippets' ),
		'mod'   => 'Cmd',
		'key'   => 'S',
	],
	[
		'label' => __( 'Select all', 'code-snippets' ),
		'mod'   => 'Cmd',
		'key'   => 'A',
	],
	[
		'label' => __( 'Begin searching', 'code-snippets' ),
		'mod'   => 'Cmd',
		'key'   => 'F',
	],
	[
		'label' => __( 'Find next', 'code-snippets' ),
		'mod'   => 'Cmd',
		'key'   => 'G',
	],
	[
		'label' => __( 'Find previous', 'code-snippets' ),
		'mod'   => [ 'Shift', 'Cmd' ],
		'key'   => 'G',
	],
	[
		'label' => __( 'Replace', 'code-snippets' ),
		'mod'   => [ 'Shift', 'Cmd' ],
		'key'   => 'F',
	],
	[
		'label' => __( 'Replace all', 'code-snippets' ),
		'mod'   => [ 'Shift', 'Cmd', 'Option' ],
		'key'   => 'R',
	],
	[
		'label' => __( 'Persistent search', 'code-snippets' ),
		'mod'   => 'Alt',
		'key'   => 'F',
	],
	[
		'label' => __( 'Toggle comment', 'code-snippets' ),
		'mod'   => 'Cmd',
		'key'   => '/',
	],
	[
		'label' => __( 'Swap line up', 'code-snippets' ),
		'mod'   => 'Option',
		'key'   => 'Up',
	],
	[
		'label' => __( 'Swap line down', 'code-snippets' ),
		'mod'   => 'Option',
		'key'   => 'Down',
	],
	[
		'label' => __( 'Auto-indent current line or selection', 'code-snippets' ),
		'mod'   => 'Shift',
		'key'   => 'Tab',
	],
];

?>

<div class="snippet-editor-help">

	<div class="editor-help-tooltip cm-s-<?php
	echo esc_attr( Settings\get_setting( 'editor', 'theme' ) ); ?>"><?php
		echo esc_html_x( '?', 'help tooltip', 'code-snippets' ); ?></div>

	<div class="editor-help-text">
		<table>
			<?php foreach ( $shortcuts as $shortcut ) { ?>
				<tr>
					<td><?php echo esc_html( $shortcut['label'] ); ?></td>
					<td>
						<?php

						foreach ( (array) $shortcut['mod'] as $modifier ) {
							if ( 'Ctrl' === $modifier || 'Cmd' === $modifier ) {
								echo '<kbd class="pc-key">', esc_html( $keys['Ctrl'] ), '</kbd>';
								echo '<kbd class="mac-key">', esc_html( $keys['Cmd'] ), '</kbd>&hyphen;';
							} elseif ( 'Option' === $modifier ) {
								echo '<span class="mac-key"><kbd class="mac-key">', esc_html( $keys['Option'] ), '</kbd>&hyphen;</span>';
							} else {
								echo '<kbd>', esc_html( $keys[ $modifier ] ), '</kbd>&hyphen;';
							}
						}

						echo '<kbd>', esc_html( $keys[ $shortcut['key'] ] ), '</kbd>';
						?>
					</td>
				</tr>
			<?php } ?>
		</table>
	</div>
</div>
