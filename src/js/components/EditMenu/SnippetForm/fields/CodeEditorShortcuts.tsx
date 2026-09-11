import { __, _x } from '@wordpress/i18n'
import React, { Fragment } from 'react'
import { EDITOR_SHORTCUTS } from '../../../../editor/keymaps'
import { isMacOS } from '../../../../utils/screen'
import type { EditorShortcutAction } from '../../../../editor/keymaps'

const KEYBOARD_KEYS = {
	'Fn': _x('Fn', 'keyboard key', 'code-snippets'),
	'Cmd': _x('Ctrl', 'keyboard key', 'code-snippets'),
	'Ctrl': _x('Ctrl', 'keyboard key', 'code-snippets'),
	'Shift': _x('Shift', 'keyboard key', 'code-snippets'),
	'Alt': _x('Alt', 'keyboard key', 'code-snippets'),
	'Tab': _x('Tab', 'keyboard key', 'code-snippets'),
	'Up': _x('Up', 'keyboard key', 'code-snippets'),
	'Down': _x('Down', 'keyboard key', 'code-snippets'),
	'Left': _x('Left', 'keyboard key', 'code-snippets'),
	'Right': _x('Right', 'keyboard key', 'code-snippets'),
	'A': _x('A', 'keyboard key', 'code-snippets'),
	'B': _x('B', 'keyboard key', 'code-snippets'),
	'C': _x('C', 'keyboard key', 'code-snippets'),
	'D': _x('D', 'keyboard key', 'code-snippets'),
	'E': _x('E', 'keyboard key', 'code-snippets'),
	'F': _x('F', 'keyboard key', 'code-snippets'),
	'G': _x('G', 'keyboard key', 'code-snippets'),
	'H': _x('H', 'keyboard key', 'code-snippets'),
	'I': _x('I', 'keyboard key', 'code-snippets'),
	'J': _x('J', 'keyboard key', 'code-snippets'),
	'K': _x('K', 'keyboard key', 'code-snippets'),
	'L': _x('L', 'keyboard key', 'code-snippets'),
	'M': _x('M', 'keyboard key', 'code-snippets'),
	'N': _x('N', 'keyboard key', 'code-snippets'),
	'O': _x('O', 'keyboard key', 'code-snippets'),
	'P': _x('P', 'keyboard key', 'code-snippets'),
	'Q': _x('Q', 'keyboard key', 'code-snippets'),
	'R': _x('R', 'keyboard key', 'code-snippets'),
	'S': _x('S', 'keyboard key', 'code-snippets'),
	'T': _x('T', 'keyboard key', 'code-snippets'),
	'U': _x('U', 'keyboard key', 'code-snippets'),
	'V': _x('V', 'keyboard key', 'code-snippets'),
	'W': _x('W', 'keyboard key', 'code-snippets'),
	'X': _x('X', 'keyboard key', 'code-snippets'),
	'Y': _x('Y', 'keyboard key', 'code-snippets'),
	'Z': _x('Z', 'keyboard key', 'code-snippets'),
	'/': _x('/', 'keyboard key', 'code-snippets'),
	'\\': _x('\\', 'keyboard key', 'code-snippets'),
	'Space': _x('Space', 'keyboard key', 'code-snippets')
}

export const KEYBOARD_SYMBOLS: Partial<typeof KEYBOARD_KEYS> = {
	Cmd: '⌘',
	Ctrl: '⌃',
	Alt: '⌥',
	Shift: '⇧',
	Tab: '⇥',
	Up: '↑',
	Down: '↓',
	Left: '←',
	Right: '→'
}

const SHORTCUT_LABELS: Record<EditorShortcutAction, string> = {
	saveChanges: __('Save changes', 'code-snippets'),
	selectAll: __('Select all', 'code-snippets'),
	find: __('Find and replace', 'code-snippets'),
	findNext: __('Find next', 'code-snippets'),
	findPrev: __('Find previous', 'code-snippets'),
	gotoLine: __('Go to line', 'code-snippets'),
	selectNextOccurrence: __('Select next occurrence', 'code-snippets'),
	toggleComment: __('Toggle comment', 'code-snippets'),
	swapLineUp: __('Swap line up', 'code-snippets'),
	swapLineDown: __('Swap line down', 'code-snippets'),
	autoIndent: __('Auto-indent current line or selection', 'code-snippets'),
	indentLess: __('Outdent current line or selection', 'code-snippets'),
	autocomplete: __('Show completions', 'code-snippets')
}

const KEY_NAMES: Partial<Record<string, string>> = {
	Mod: isMacOS() ? 'Cmd' : 'Ctrl',
	ArrowUp: 'Up',
	ArrowDown: 'Down',
	ArrowLeft: 'Left',
	ArrowRight: 'Right'
}

const KEY_ORDER: readonly (keyof typeof KEYBOARD_KEYS)[] = isMacOS()
	? ['Fn', 'Ctrl', 'Alt', 'Shift', 'Cmd']
	: ['Cmd', 'Ctrl', 'Shift', 'Alt', 'Fn']

const getKeyComparisonValue = (key: string): string =>
	KEY_ORDER.includes(key as keyof typeof KEYBOARD_KEYS)
		? String(KEY_ORDER.indexOf(key as keyof typeof KEYBOARD_KEYS))
		: key

/**
 * Split a CodeMirror key name, such as `Shift-Mod-G`, into the keys to press
 * in the order the platform conventionally lists them.
 */
const parseShortcut = (shortcut: string): string[] =>
	shortcut
		.split(/-(?!$)/)
		.map(key => KEY_NAMES[key] ?? key)
		.sort((a, b) => getKeyComparisonValue(a).localeCompare(getKeyComparisonValue(b)))

interface KeyboardShortcutMacProps {
	keys: string[]
	keyLabels: Partial<Record<string, string>>
	keySymbols: Partial<Record<string, string>>
}

const KeyboardShortcutMac: React.FC<KeyboardShortcutMacProps> = ({ keys, keyLabels, keySymbols }) =>
	<span className="keyboard-shortcut mac-keyboard-shortcut">
		{keys.map(key =>
			<kbd key={key}>{keySymbols[key] ?? keyLabels[key] ?? key}</kbd>)}
	</span>

const SEP = _x('+', 'keyboard shortcut separator', 'code-snippets')

interface KeyboardShortcutPCProps {
	keys: string[]
	keyLabels: Partial<Record<string, string>>
}

const KeyboardShortcutPC: React.FC<KeyboardShortcutPCProps> = ({ keys, keyLabels }) =>
	<span className="keyboard-shortcut pc-keyboard-shortcot">
		{keys.map((key, index) =>
			<Fragment key={key}>
				<kbd>{keyLabels[key] ?? key}</kbd>
				{index < keys.length - 1 && <span className="shortcut-separator">{SEP}</span>}
			</Fragment>)}
	</span>

export interface CodeEditorShortcutsProps {
	editorTheme: string
}

export const CodeEditorShortcuts: React.FC<CodeEditorShortcutsProps> = ({ editorTheme }) =>
	<div className="snippet-editor-help tooltip tooltip-inline tooltip-start">
		<span className={`dashicons dashicons-editor-help cm-s-${editorTheme}`} aria-hidden="true"></span>

		<div className="tooltip-content">
			<table>
				<tbody>
					{Object.entries(EDITOR_SHORTCUTS).map(([action, shortcut]) => {
						const keys = parseShortcut(shortcut)

						return (
							<tr key={action}>
								<td>{SHORTCUT_LABELS[action as EditorShortcutAction]}</td>
								<td>
									{isMacOS()
										? <KeyboardShortcutMac keys={keys} keyLabels={KEYBOARD_KEYS} keySymbols={KEYBOARD_SYMBOLS} />
										: <KeyboardShortcutPC keys={keys} keyLabels={KEYBOARD_KEYS} />}
								</td>
							</tr>
						)
					})}
				</tbody>
			</table>
		</div>
	</div>
