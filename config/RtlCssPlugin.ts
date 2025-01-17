import path from 'path'
import rtlcss from 'rtlcss'
import type { Compiler } from 'webpack'
import type { ConfigureOptions } from 'rtlcss'

export interface WebpackRTLPluginOptions {
	entries: Set<string>
	rtlcssConfig: ConfigureOptions
	transformFilename: (sourceFilename: string) => string
}

export class RtlCssPlugin {
	private readonly options: WebpackRTLPluginOptions

	private static readonly defaultOptions: WebpackRTLPluginOptions = {
		entries: new Set(),
		rtlcssConfig: {},
		transformFilename: sourceFilename =>
			`${path.parse(sourceFilename).name}-rtl.css`
	}

	constructor(options: Partial<WebpackRTLPluginOptions>) {
		this.options = { ...RtlCssPlugin.defaultOptions, ...options }
	}

	apply(compiler: Compiler) {
		const { Compilation, sources: { ConcatSource } } = compiler.webpack
		const rtlcssProcessor = rtlcss.configure(this.options.rtlcssConfig)

		compiler.hooks.compilation.tap(RtlCssPlugin.name, compilation => {
			compilation.hooks.processAssets.tap({
				name: RtlCssPlugin.name,
				stage: Compilation.PROCESS_ASSETS_STAGE_ADDITIONAL
			}, assets => {
				for (const chunk of compilation.chunks) {
					if (chunk.name && this.options.entries.has(chunk.name)) {
						for (const sourceFilename of chunk.files) {
							if ('.css' === path.extname(sourceFilename)) {
								const rtlFilename = this.options.transformFilename(sourceFilename)
								const ltrCss = assets[sourceFilename].source()
								const rtlCss = rtlcssProcessor.process(ltrCss).css
								compilation.emitAsset(rtlFilename, new ConcatSource(rtlCss), { sourceFilename })
							}
						}
					}
				}
			})
		})
	}
}
