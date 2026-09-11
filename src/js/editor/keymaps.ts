import { closeBracketsKeymap, completionKeymap } from '@codemirror/autocomplete'
import { defaultKeymap, historyKeymap, indentWithTab } from '@codemirror/commands'
import { foldKeymap } from '@codemirror/language'
import { lintKeymap } from '@codemirror/lint'
import { openSearchPanel, searchKeymap } from '@codemirror/search'
import { Prec } from '@codemirror/state'
import { keymap } from '@codemirror/view'
import { emacs } from '@replit/codemirror-emacs'
import type { Extension } from '@codemirror/state'
import type { KeyBinding } from '@codemirror/view'

/**
 * Keyboard shortcuts listed in the editor's help tooltip, in CodeMirror key
 * notation. `Mod` is Cmd on macOS and Ctrl elsewhere.
 */
export const EDITOR_SHORTCUTS = <const> {
	saveChanges: 'Mod-S',
	selectAll: 'Mod-A',
	find: 'Mod-F',
	findNext: 'Mod-G',
	findPrev: 'Shift-Mod-G',
	gotoLine: 'Mod-Alt-G',
	selectNextOccurrence: 'Mod-D',
	toggleComment: 'Mod-/',
	swapLineUp: 'Alt-ArrowUp',
	swapLineDown: 'Alt-ArrowDown',
	autoIndent: 'Mod-Alt-\\',
	indentLess: 'Shift-Tab',
	autocomplete: 'Ctrl-Space'
}

export type EditorShortcutAction = keyof typeof EDITOR_SHORTCUTS

const commonKeymaps = (editable: boolean): readonly KeyBinding[] => [
	...closeBracketsKeymap,
	...defaultKeymap,
	...searchKeymap,
	...historyKeymap,
	...foldKeymap,
	...completionKeymap,
	...lintKeymap,
	...editable ? [indentWithTab] : []
]

/**
 * Key bindings for a keymap setting. Vim is loaded separately by
 * `loadVimKeymap()`, as it is large and rarely chosen; until it arrives the
 * default bindings apply.
 */
export const keymapExtension = (keyMap: string, editable: boolean, vim?: Extension): Extension => {
	switch (keyMap) {
		case 'emacs':
			return [emacs(), keymap.of(commonKeymaps(editable))]
		case 'vim':
			return vim
				? [vim, keymap.of(commonKeymaps(editable))]
				: keymap.of(commonKeymaps(editable))
		default:
			return keymap.of([{ key: 'Alt-f', run: openSearchPanel }, ...commonKeymaps(editable)])
	}
}

export const loadVimKeymap = (): Promise<Extension> =>
	import(/* webpackChunkName: "editor-vim" */ '@replit/codemirror-vim')
		.then(({ vim }) => vim())

/**
 * Save shortcuts take precedence over every keymap, including Emacs, which
 * would otherwise claim Ctrl-S for incremental search.
 */
export const saveKeymap = (onSave: VoidFunction): Extension => {
	const run = () => {
		onSave()
		return true
	}

	return Prec.highest(keymap.of([
		{ key: 'Mod-s', run, preventDefault: true },
		{ key: 'Mod-Enter', run, preventDefault: true }
	]))
}
