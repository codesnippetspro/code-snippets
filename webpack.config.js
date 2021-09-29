import path from 'path';

module.exports = {
	mode: 'development',
	entry: {
		'manage': './js/manage.ts',
		'edit': './js/edit/edit.ts',
		'tags': './js/edit/tags.ts',
		'settings': './js/settings/settings.ts',
		'mce': './js/mce.ts',
		'prism': './js/prism.ts',
	},
	output: {
		path: path.resolve(__dirname),
		filename: '[name].js',
	},
	externals: {
		'codemirror': 'wp.CodeMirror'
	},
	resolve: {
		extensions: ['.ts', '.js', '.json']
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
