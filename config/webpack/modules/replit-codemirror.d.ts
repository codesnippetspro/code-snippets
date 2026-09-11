// These packages' export maps have no types condition, so their declarations
// are not found when they are resolved as CommonJS.
declare module '@replit/codemirror-emacs' {
	import type { Extension } from '@codemirror/state'

	export const emacs: () => Extension
}

declare module '@replit/codemirror-indentation-markers' {
	import type { Extension } from '@codemirror/state'

	export interface IndentationMarkerConfiguration {
		hideFirstIndent?: boolean
		markerType?: 'fullScope' | 'codeOnly'
		thickness?: number
		activeThickness?: number
		colors?: {
			light?: string
			dark?: string
			activeLight?: string
			activeDark?: string
		}
	}

	export const indentationMarkers: (config?: IndentationMarkerConfiguration) => Extension
}
