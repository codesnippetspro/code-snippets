import path from 'path';

module.exports = {
	mode: 'development',
	entry: {
		'manage': './js/manage.js',
		'edit': './js/edit/edit.js',
		'tags': './js/edit/tags.js',
		'settings': './js/settings/settings.js',
		'mce': './js/mce.js',
		'prism': './js/prism.ts',
	},
	output: {
		path: path.resolve(__dirname),
		filename: '[name].js',
	},
	externals: {
		'codemirror': 'wp.CodeMirror'
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
							'languages': ['php', 'php-extras'],
							'plugins': ['line-highlight', 'line-numbers'],
						}]
					]
				},
			}
		}]
	}
};
