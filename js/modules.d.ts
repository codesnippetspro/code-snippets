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

declare module 'gulp-archiver' {
	import { ThroughStream } from 'through';
	import { ArchiverOptions } from 'archiver';
	export default function (file: string, opts?: ArchiverOptions): ThroughStream
}

declare module 'gulp-rtlcss' {
	import { ThroughStream } from 'through';
	import { ConfigOptions } from 'rtlcss';
	export default function (config?: ConfigOptions): ThroughStream
}

declare module 'postcss-easy-import' {
	import { Plugin } from 'postcss';
	export default function (opts: {
		prefix: string | boolean
		extensions: string | string[]
	}): Plugin
}

declare module 'postcss-hexrgba' {
	import { Plugin } from 'postcss';
	export default function (): Plugin
}

declare module 'gulp-eslint' {
	import { Transform } from 'stream';
	export default gulpEslint

	const gulpEslint: {
		(): Transform
		format(formatter?: string, output?: WritableStream): Transform
		formatEach(formatter?: string, output?: WritableStream): Transform
		failAfterError(): Transform
	}
}

declare module 'gulp-wp-pot' {
	import { Transform } from 'stream';
	export default function (options?: {
		bugReport?: string
		commentKeyword?: string
		domain?: string
		destFile?: string
		headers?: boolean | Record<string, string>
		getTextFunctions?: Record<string, string>
		includePOTCreationDate?: boolean
		lastTranslator?: string
		metadataFile?: string
		noFilePaths?: boolean
		package?: string
		parser?: 'php' | 'js'
		parserOptions?: Record<string, unknown>
		relativeTo?: string
		src?: string | string[]
		globOpts?: Record<string, unknown>
		team?: string
		writeFile?: boolean
		ignoreTemplateNameHeader?: boolean
	}): Transform
}

declare module 'gulp-gettext' {
	import { Transform } from 'stream';
	export default function (): Transform
}

declare module 'gulp-phpcs' {
	import { Transform } from 'stream';
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
	import { Transform } from 'stream';
	export default function (cmd?: string, opts?: {
		'bin'?: string
		'self-install'?: boolean
		'async'?: boolean
		'ansi'?: boolean
		'working-dir'?: string
	} & Record<string, unknown>): Transform
}
