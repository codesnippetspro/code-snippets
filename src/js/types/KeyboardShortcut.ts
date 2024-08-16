import { _x } from '@wordpress/i18n'

export const KEYBOARD_KEYS = <const> {
	'Cmd': _x('Cmd', 'keyboard key', 'code-snippets'),
	'Ctrl': _x('Ctrl', 'keyboard key', 'code-snippets'),
	'Shift': _x('Shift', 'keyboard key', 'code-snippets'),
	'Option': _x('Option', 'keyboard key', 'code-snippets'),
	'Alt': _x('Alt', 'keyboard key', 'code-snippets'),
	'Tab': _x('Tab', 'keyboard key', 'code-snippets'),
	'Up': _x('Up', 'keyboard key', 'code-snippets'),
	'Down': _x('Down', 'keyboard key', 'code-snippets'),
	'A': _x('A', 'keyboard key', 'code-snippets'),
	'D': _x('D', 'keyboard key', 'code-snippets'),
	'F': _x('F', 'keyboard key', 'code-snippets'),
	'G': _x('G', 'keyboard key', 'code-snippets'),
	'R': _x('R', 'keyboard key', 'code-snippets'),
	'S': _x('S', 'keyboard key', 'code-snippets'),
	'Y': _x('Y', 'keyboard key', 'code-snippets'),
	'Z': _x('Z', 'keyboard key', 'code-snippets'),
	'/': _x('/', 'keyboard key', 'code-snippets'),
	'[': _x(']', 'keyboard key', 'code-snippets'),
	']': _x(']', 'keyboard key', 'code-snippets')
}

export type KeyboardKey = keyof typeof KEYBOARD_KEYS

export interface KeyboardShortcut {
	label: string
	mod: KeyboardKey | KeyboardKey[]
	key: KeyboardKey
}
