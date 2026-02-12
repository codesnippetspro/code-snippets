declare module 'codemirror/src/input/keymap' {
	import type { KeyMap } from 'codemirror'

	export const getKeyMap: (keyMap: string) => KeyMap
}
