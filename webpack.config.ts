import * as path from 'path';
import * as webpack from 'webpack';

const config: webpack.Configuration = {
	mode: 'production',
	entry: {
		manage: './js/manage.ts',
		edit: './js/edit/edit.ts',
		tags: './js/edit/tags.ts',
		settings: './js/settings/settings.ts',
		mce: './js/mce.ts',
		prism: './js/prism.ts',
		blocks: './js/blocks/blocks.tsx',
		elementor: './js/elementor.ts',
	},
	output: {
		path: path.resolve(__dirname),
		filename: '[name].js',
	},
	externalsType: 'window',
	externals: {
		'react': 'React',
		'react-dom': 'ReactDOM',
		'codemirror': 'wp.CodeMirror',
		'tinymce': 'tinymce',
		'jquery': 'jQuery'
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
		new webpack.DefinePlugin({
			'process.arch': JSON.stringify('x64')
		})
	]
};

export default config;
