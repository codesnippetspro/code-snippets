declare module 'webpack-rtl-plugin' {
	import { ConfigOptions, Plugin } from 'rtlcss'
	import cssnano from 'cssnano'
	import webpack, { WebpackPluginInstance } from 'webpack'

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
