import { merge } from 'webpack-merge'
import { cssWebpackConfig } from './config/webpack-css'
import { jsWebpackConfig } from './config/webpack-js'
import type { Configuration } from 'webpack'

const config: Configuration = {
	mode: 'development'
}

export default merge(cssWebpackConfig, jsWebpackConfig, config)
