import path from 'path'
import libsass from 'sass'
import cssnano from 'cssnano'
import autoprefixer from 'autoprefixer'
import hexrgba from 'postcss-hexrgba'
import MiniCssExtractPlugin from 'mini-css-extract-plugin'
import RemoveEmptyScriptsPlugin from 'webpack-remove-empty-scripts'
import WebpackRTLPlugin from 'webpack-rtl-plugin'
import { glob } from 'glob'
import type { Config as PostCssConfig } from 'postcss-load-config'
import type { Configuration, EntryObject } from 'webpack'

const postcssOptions: PostCssConfig = {
	plugins: [
		hexrgba(),
		autoprefixer(),
		cssnano({
			preset: ['default', { discardComments: { removeAll: true } }]
		})
	]
}

const entriesFromFiles = (patterns: string | string[], entry: (filename: string) => string): EntryObject =>
	Object.fromEntries(
		glob.sync(patterns)
			.map(filename => [entry(filename), `./${filename}`])
	)

export const cssWebpackConfig: Configuration = {
	entry: {
		...entriesFromFiles(
			['src/css/*.scss', '!src/css/**/_*.scss'],
			filename => `${path.parse(filename).name}-css`
		),
		...entriesFromFiles(
			'node_modules/codemirror/theme/*.css',
			filename => `codemirror-theme-${path.parse(filename).name}`
		)
	},
	module: {
		rules: [
			{
				test: /\.scss$/,
				exclude: /node_modules/,
				use: [
					MiniCssExtractPlugin.loader,
					{
						loader: 'css-loader',
						options: {
							sourceMap: true,
							importLoaders: 2
						}
					},
					{
						loader: 'postcss-loader',
						options: {
							postcssOptions
						}
					},
					{
						loader: 'sass-loader',
						options: {
							implementation: libsass,
							sourceMap: true
						}
					}
				]
			},
			{
				test: /\.css$/,
				use: [
					MiniCssExtractPlugin.loader,
					{
						loader: 'css-loader',
						options: {
							sourceMap: false,
							importLoaders: 1
						}
					},
					{
						loader: 'postcss-loader',
						options: {
							postcssOptions: {
								plugins: [cssnano()]
							}
						}
					}
				]
			}
		]
	},
	plugins: [
		new RemoveEmptyScriptsPlugin(),
		new MiniCssExtractPlugin({
			filename: ({ chunk }) =>
				chunk?.name ?
					`${chunk.name}.css`
						.replace(/^codemirror-theme-/, 'editor-themes/')
						.replace(/-css\.css$/, '.css') :
					'[name].css'
		}),
		new WebpackRTLPlugin({
			test: /^(?<filename>edit|manage)\.css$/,
			filename: [/(?<ext>\.css)/i, '-rtl$1']
		})
	]
}
