import path from 'path';

module.exports = {
	mode: 'development',
	entry: {
		'manage': './js/manage.js',
		'edit': './js/edit/edit.js',
		'tags': './js/edit/tags.js',
		'settings': './js/settings/settings.js',
		'mce': './js/mce.js',
		'blocks': './js/blocks/blocks.js',
		'front-end': './js/front-end.js',
		'elementor': './js/elementor.js'
	},
	output: {
		path: path.resolve(__dirname),
		filename: '[name].js',
	},
	externals: {
		'react': 'React',
		'react-dom': 'ReactDOM',
	},
	module: {
		rules: [{
			test: /\.js$/,
			exclude: /node_modules/,
			use: {
				loader: 'babel-loader',
				options: {
					presets: ['@babel/preset-env'],
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
