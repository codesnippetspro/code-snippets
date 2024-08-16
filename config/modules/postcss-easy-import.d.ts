declare module 'postcss-easy-import' {
	import { Plugin } from 'postcss'

	export default function (opts: {
		prefix: string | boolean
		extensions: string | string[]
	}): Plugin
}
