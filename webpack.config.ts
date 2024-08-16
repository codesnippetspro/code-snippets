import { Configuration } from 'webpack'
import { merge } from 'webpack-merge'
import { cssWebpackConfig } from './config/webpack-css'
import { jsWebpackConfig } from './config/webpack-js'

const config: Configuration = {
	mode: 'development'
}

export default merge(cssWebpackConfig, jsWebpackConfig, config)
