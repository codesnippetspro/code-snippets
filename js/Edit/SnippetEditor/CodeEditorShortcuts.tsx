import { __, _x } from '@wordpress/i18n'
import classnames from 'classnames'
import React from 'react'
import { isMacOS } from '../../utils/general'

const SEP = _x('-', 'keyboard shortcut separator', 'code-snippets')

const keys = {
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
} as const

type Key = keyof typeof keys

interface Shortcut {
	label: string
	mod: Key | Key[]
	key: Key
}

const shortcuts: Shortcut[] = [
	{
		label: __('Save changes', 'code-snippets'),
		mod: 'Cmd',
		key: 'S'
	},
	{
		label: __('Select all', 'code-snippets'),
		mod: 'Cmd',
		key: 'A'
	},
	{
		label: __('Begin searching', 'code-snippets'),
		mod: 'Cmd',
		key: 'F'
	},
	{
		label: __('Find next', 'code-snippets'),
		mod: 'Cmd',
		key: 'G'
	},
	{
		label: __('Find previous', 'code-snippets'),
		mod: ['Shift', 'Cmd'],
		key: 'G'
	},
	{
		label: __('Replace', 'code-snippets'),
		mod: ['Shift', 'Cmd'],
		key: 'F'
	},
	{
		label: __('Replace all', 'code-snippets'),
		mod: ['Shift', 'Cmd', 'Option'],
		key: 'R'
	},
	{
		label: __('Persistent search', 'code-snippets'),
		mod: 'Alt',
		key: 'F'
	},
	{
		label: __('Toggle comment', 'code-snippets'),
		mod: 'Cmd',
		key: '/'
	},
	{
		label: __('Swap line up', 'code-snippets'),
		mod: 'Option',
		key: 'Up'
	},
	{
		label: __('Swap line down', 'code-snippets'),
		mod: 'Option',
		key: 'Down'
	},
	{
		label: __('Auto-indent current line or selection', 'code-snippets'),
		mod: 'Shift',
		key: 'Tab'
	}
]

export interface CodeEditorShortcutsProps {
	editorTheme: string
}

export const CodeEditorShortcuts: React.FC<CodeEditorShortcutsProps> = ({ editorTheme }) =>
	<div className="snippet-editor-help">
		<div className={`editor-help-tooltip cm-s-${editorTheme}`}>
			{_x('?', 'help tooltip', 'code-snippets')}
		</div>

		<div className={classnames('editor-help-text', { 'platform-mac': isMacOS() })}>
			<table>
				{shortcuts.map(({ label, mod, key }) =>
					<tr key={label}>
						<td>{label}</td>
						<td>
							{(Array.isArray(mod) ? mod : [mod]).map(modifier =>
								'Ctrl' === modifier || 'Cmd' === modifier ?
									<>
										<kbd className="pc-key">{keys.Ctrl}</kbd>
										<kbd className="mac-key">{keys.Cmd}</kbd>
										{SEP}
									</> :
									'Option' === mod ?
										<span className="mac-key">
											<kbd className="mac-key">{keys.Option}</kbd>{SEP}
										</span> :
										<><kbd>{keys[modifier]}</kbd>{SEP}</>
							)}
							<kbd>{keys[key]}</kbd>
						</td>
					</tr>
				)}
			</table>
		</div>
	</div>
