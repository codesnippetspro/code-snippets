import { __, _x } from '@wordpress/i18n'
import classnames from 'classnames'
import React from 'react'
import { KEYBOARD_KEYS } from '../../../types/KeyboardShortcut'
import { isMacOS } from '../../../utils/general'
import type { KeyboardShortcut } from '../../../types/KeyboardShortcut'

const shortcuts: KeyboardShortcut[] = [
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

const SEP = _x('-', 'keyboard shortcut separator', 'code-snippets')

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
										<kbd className="pc-key">{KEYBOARD_KEYS.Ctrl}</kbd>
										<kbd className="mac-key">{KEYBOARD_KEYS.Cmd}</kbd>
										{SEP}
									</> :
									'Option' === mod ?
										<span className="mac-key">
											<kbd className="mac-key">{KEYBOARD_KEYS.Option}</kbd>{SEP}
										</span> :
										<><kbd>{KEYBOARD_KEYS[modifier]}</kbd>{SEP}</>
							)}
							<kbd>{KEYBOARD_KEYS[key]}</kbd>
						</td>
					</tr>
				)}
			</table>
		</div>
	</div>
