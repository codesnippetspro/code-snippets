import util from 'util'
import { execFile } from 'child_process'

export interface WpCliOptions {
	url?: string
}

const execFileAsync = util.promisify(execFile)

const hasArg = (args: string[], prefix: string): boolean =>
	args.some(arg => arg === prefix || arg.startsWith(`${prefix}=`))

export const wpCli = async (args: string[], options: WpCliOptions = {}): Promise<string> => {
	const mode = (process.env.WP_E2E_WPCLI_MODE ?? '').toLowerCase()
	const dockerContainer = process.env.WP_E2E_WP_CONTAINER

	const url = options.url ?? process.env.WP_E2E_WPCLI_URL
	const urlArgs = url && !hasArg(args, '--url') ? [`--url=${url}`] : []

	if (dockerContainer || 'gh-actions-ci' === mode) {
		if (!dockerContainer) {
			throw new Error('WP_E2E_WP_CONTAINER must be set when WP_E2E_WPCLI_MODE is gh-actions-ci.')
		}

		const pharPath = process.env.WP_E2E_WPCLI_PHAR ?? '/tmp/wp-cli.phar'
		const allowRootArgs = hasArg(args, '--allow-root') ? [] : ['--allow-root']

		const { stdout } = await execFileAsync('docker', [
			'exec',
			'-u',
			'root',
			'-w',
			'/var/www/html',
			dockerContainer,
			'php',
			pharPath,
			...urlArgs,
			...allowRootArgs,
			...args
		])

		return stdout
	}

	// Default to wp-env (local dev) for backwards compatibility.
	const { stdout } = await execFileAsync('npx', ['wp-env', 'run', 'cli', 'wp', ...urlArgs, ...args])

	return stdout
}
