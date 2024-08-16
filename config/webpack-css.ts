import path from 'path'
import { Configuration } from 'webpack'
import { Config as PostCssConfig } from 'postcss-load-config'
import libsass from 'sass'
import cssnano from 'cssnano'
import autoprefixer from 'autoprefixer'
import hexrgba from 'postcss-hexrgba'
import MiniCssExtractPlugin from 'mini-css-extract-plugin'
import RemoveEmptyScriptsPlugin from 'webpack-remove-empty-scripts'
import WebpackRTLPlugin from 'webpack-rtl-plugin'
import { glob } from 'glob'

const postcssOptions: PostCssConfig = {
	plugins: [
		hexrgba(),
		autoprefixer(),
		cssnano({
			preset: ['default', { discardComments: { removeAll: true } }]
		})
	]
}

export const cssWebpackConfig: Configuration = {
	entry: {
		...Object.fromEntries(
			glob.sync(['src/css/*.scss', '!src/css/**/_*.scss'])
				.map(filename => {
					const name = path.parse(filename).name
					return [`${name}-style`, `./${filename}`]
				})
		),
		...Object.fromEntries(
			glob.sync('node_modules/codemirror/theme/*.css')
				.map(filename => {
					const name = path.parse(filename).name
					return [`codemirror-theme-${name}`, `./${filename}`]
				})
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
						.replace(/-style\.css$/, '.css') :
					'[name].css'
		}),
		new WebpackRTLPlugin({
			test: /^(?<filename>edit|manage)\.css$/,
			filename: [/(?<ext>\.css)/i, '-rtl$1']
		})
	]
}
