import * as path from 'path';
import { DefinePlugin, Configuration } from 'webpack';

export const config: Configuration = {
	entry: {
		manage: './js/manage.ts',
		edit: {
			import: './js/edit/edit.ts',
			dependOn: 'editor'
		},
		tags: './js/edit/tags.ts',
		settings: {
			import: './js/settings/settings.ts',
			dependOn: 'editor'
		},
		mce: './js/mce.ts',
		prism: './js/prism.ts',
		blocks: './js/blocks/blocks.ts',
		elementor: './js/elementor.ts',
		editor: './js/editor-lib.ts'
	},
	output: {
		path: path.join(path.resolve(__dirname), 'js/min'),
		filename: '[name].js',
	},
	externalsType: 'window',
	externals: {
		'react': 'React',
		'react-dom': 'ReactDOM',
		'jquery': 'jQuery',
		'tinymce': 'tinymce',
		'codemirror': ['wp', 'CodeMirror'],
		...Object.fromEntries(
			['api-fetch', 'block-editor', 'blocks', 'components', 'data', 'i18n', 'server-side-render', 'icons']
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
					presets: ['@babel/preset-env'],
					plugins: [
						['prismjs', {
							languages: ['php', 'php-extras'],
							plugins: ['line-highlight', 'line-numbers'],
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
};

export default config;
