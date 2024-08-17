declare module 'webpack-rtl-plugin' {
	import type { ConfigOptions, Plugin } from 'rtlcss'
	import type cssnano from 'cssnano'
	import type { WebpackPluginInstance } from 'webpack'
	import type webpack from 'webpack'

	class WebpackRtlPlugin implements WebpackPluginInstance {
		constructor(options?: {
			test?: RegExp
			filename?: string | [string | RegExp, string]
			options?: ConfigOptions
			plugins?: Plugin[],
			diffOnly?: boolean
			minify?: boolean | cssnano.Options
		})

		apply: (compiler: webpack.Compiler) => void
	}

	export default WebpackRtlPlugin
}
