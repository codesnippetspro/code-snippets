declare module 'gulp-rtlcss' {
	import { ThroughStream } from 'through'
	import { ConfigOptions } from 'rtlcss'
	export default function (config?: ConfigOptions): ThroughStream
}

declare module 'postcss-easy-import' {
	import { Plugin } from 'postcss'
	export default function (opts: {
		prefix: string | boolean
		extensions: string | string[]
	}): Plugin
}

declare module 'postcss-hexrgba' {
	import { Plugin } from 'postcss'
	export default function (): Plugin
}

declare module 'gulp-eslint' {
	import { Transform } from 'stream'
	export default gulpEslint

	const gulpEslint: {
		(): Transform
		format(formatter?: string, output?: WritableStream): Transform
		formatEach(formatter?: string, output?: WritableStream): Transform
		failAfterError(): Transform
	}
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
