import * as path from 'path'
import { DefinePlugin, Configuration } from 'webpack'

const SOURCE_DIR = './js'
const DEST_DIR = './dist'

export const config: Configuration = {
	entry: {
		manage: `${SOURCE_DIR}/manage/index.ts`,
		edit: {
			import: `${SOURCE_DIR}/Edit/index.tsx`,
			dependOn: 'editor'
		},
		settings: {
			import: `${SOURCE_DIR}/settings/index.ts`,
			dependOn: 'editor'
		},
		mce: `${SOURCE_DIR}/mce.ts`,
		prism: `${SOURCE_DIR}/prism.ts`,
		editor: `${SOURCE_DIR}/editor.ts`,
	},
	output: {
		path: path.join(path.resolve(__dirname), DEST_DIR),
		filename: '[name].js'
	},
	externalsType: 'window',
	externals: {
		'react': 'React',
		'react-dom': 'ReactDOM',
		'jquery': 'jQuery',
		'tinymce': 'tinymce',
		'codemirror': ['wp', 'CodeMirror'],
		...Object.fromEntries(
			['api-fetch', 'block-editor', 'blocks', 'components', 'data', 'i18n', 'server-side-render']
				.map(p => [
					`@wordpress/${p}`,
					['wp', p.replace(/-(?<letter>[a-z])/g, (_, letter) => letter.toUpperCase())]
				])
		)
	},
	resolve: {
		extensions: ['.ts', '.tsx', '.js', '.json'],
		alias: {
			'php-parser': path.resolve(__dirname, 'node_modules/php-parser/src/index.js')
		}
	},
	module: {
		rules: [{
			test: /\.[jt]sx?$/,
			exclude: /node_modules/,
			use: {
				loader: 'babel-loader',
				options: {
					presets: [
						'@babel/preset-env',
						'@wordpress/babel-preset-default'
					],
					plugins: [
						['prismjs', {
							languages: ['php', 'php-extras'],
							plugins: ['line-highlight', 'line-numbers']
						}]
					]
				}
			}
		}]
	},
	plugins: [
		new DefinePlugin({
			'process.arch': JSON.stringify('x64')
		})
	]
}

export default config
