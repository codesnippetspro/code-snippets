import * as path from 'path';
import * as webpack from 'webpack';

const entries = [
	'./manage.ts',
	'edit/edit',
	'edit/tags',
	'settings/settings',
	'mce',
	'prism'
];

const config: webpack.Configuration = {
	mode: 'production',
	entry: Object.fromEntries(entries.map(entry => [
		entry.replace(/.+\/(?<file>\w+)$/, '$file'),
		`./js/${entry}.ts`
	])),
	output: {
		path: path.resolve(__dirname),
		filename: '[name].js',
	},
	externalsType: 'window',
	externals: {
		codemirror: 'wp.CodeMirror',
		tinymce: 'tinymce',
	},
	resolve: {
		extensions: ['.ts', '.js', '.json'],
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
