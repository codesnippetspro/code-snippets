declare module 'gulp-rtlcss' {
	import { Transform } from 'stream'
	import { ConfigOptions } from 'rtlcss'
	export default function (config?: ConfigOptions): Transform
}

declare module 'gulp-remove-sourcemaps' {
	import { Transform } from 'stream'
	export default function (): Transform
}

declare module 'postcss-easy-import' {
	import { Plugin } from 'postcss'
	export default function (opts: {
		prefix: string | boolean
		extensions: string | string[]
	}): Plugin
}

declare module 'postcss-prefix-selector' {
	import { Plugin } from 'postcss'
	export default function (options: {
		prefix: string
		exclude?: string
		transform?: (prefix: string, selector: string, prefixedSelector: string, filePath: string, rule: string) => string
		ignoreFiles?: string[]
		includeFiles?: string[]
	}): Plugin
}

declare module 'postcss-hexrgba' {
	import { Plugin } from 'postcss'
	export default function (): Plugin
}

declare module 'gulp-phpcs' {
	import { Transform } from 'stream'
	const phpcs: {
		(options?: {
			bin?: string
			severity?: number
			warningSeverity?: number
			errorSeverity?: number
			standard?: string
			encoding?: string
			report?: string
			showSniffCode?: boolean
			sniffs?: string[]
			ignore?: string[]
			cwd?: string
			colors?: boolean
		}): Transform
		reporter(name: 'fail' | 'log' | 'file', options?: {
			failOnFirst?: boolean
			path?: string
		}): Transform
	}
	export default phpcs
}

declare module 'gulp-composer' {
	import { Transform } from 'stream'
	export default function (cmd?: string, opts?: {
		'bin'?: string
		'self-install'?: boolean
		'async'?: boolean
		'ansi'?: boolean
		'working-dir'?: string
	} & Record<string, unknown>): Transform
}

declare module 'gulp-flatmap' {
	import { Readable, Stream, Transform } from 'stream'
	import Vinyl from 'vinyl'
	export default function (func: (readStream: Readable, data: Vinyl) => Stream): Transform
}

declare module '@wordpress/server-side-render' {
	interface Props {
		attributes: Record<string, unknown>
		block: string
		className?: string
		httpMethod?: 'GET' | 'POST'
		urlQueryArgs?: Record<string, unknown>
		EmptyResponsePlaceholder?: React.FC<Props>
		ErrorResponsePlaceholder?: React.FC<Props>
		LoadingResponsePlaceholder?: React.FC<Props>
	}

	const ServerSideRender: React.FC<Props>
	export default ServerSideRender
}
