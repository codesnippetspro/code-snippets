import * as path from 'path';
import * as webpack from 'webpack';

const config: webpack.Configuration = {
	mode: 'production',
	entry: {
		manage: './js/manage.js',
		edit: './js/edit/edit.js',
		tags: './js/edit/tags.js',
		settings: './js/settings/settings.js',
		mce: './js/mce.js',
		prism: './js/prism.js',
		blocks: './js/blocks/blocks.js',
		elementor: './js/elementor.js',
	},
	output: {
		path: path.resolve(__dirname),
		filename: '[name].js',
	},
	externals: {
		'react': 'React',
		'react-dom': 'ReactDOM',
		'codemirror': 'wp.CodeMirror'
	},
	resolve: {
		extensions: ['.ts', '.js', '.json'],
		alias: {
			'php-parser': path.resolve(__dirname, 'node_modules/php-parser/src/index.js'),
			'tinymce': false
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
