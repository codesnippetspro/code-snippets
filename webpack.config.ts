import { Configuration } from 'webpack'
import { merge } from 'webpack-merge'
import { cssWebpackConfig } from './config/webpack-css'
import { jsWebpackConfig } from './config/webpack-js'

const devConfig: Configuration = {}

const prodConfig: Configuration = {}

const config = (_env: unknown, argv: Record<string, string>): Configuration =>
	merge(
		cssWebpackConfig,
		jsWebpackConfig,
		'development' === argv.mode ? devConfig : prodConfig
	)

export default config
