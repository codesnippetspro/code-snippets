// The package's export map has no types condition, so its declarations are
// not found when it is resolved as CommonJS.
declare module '@replit/codemirror-emacs' {
	import type { Extension } from '@codemirror/state'

	export const emacs: () => Extension
}
