#!/usr/bin/env ts-node

import { execFileSync } from 'node:child_process'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

const run = (cmd: string, args: readonly string[]) => {
	execFileSync(cmd, args, { stdio: 'inherit' })
}

const runWpEnvCli = (args: readonly string[]) => run('npx', ['wp-env', 'run', 'cli', ...args])

const getPluginSlug = (): string => {
	const prefix = 'wp-content/plugins/'
	const config = <{ mappings?: Record<string, string> }>JSON.parse(readFileSync(resolve(process.cwd(), '.wp-env.json'), 'utf8'))
	const mapping = Object.keys(config.mappings ?? {}).find(key => key.startsWith(prefix))

	if (!mapping) {
		throw new Error('No plugin mapping found in .wp-env.json')
	}

	return mapping.slice(prefix.length)
}

const main = () => {
	// Ensure a clean slate for file-based execution tests:
	// - remove flat-file execution directory (stale indexes can break the WP site)
	// - ensure plugin is active
	// - force enable_flat_files=false so the Playwright setup test can flip it to true
	// - delete all DB snippets with an E2E prefix (keeps list clean across runs)

	runWpEnvCli(['sh', '-lc', 'rm -rf wp-content/code-snippets'])
	runWpEnvCli(['wp', 'plugin', 'activate', getPluginSlug()])

	runWpEnvCli([
		'wp',
		'eval',
		`
			$settings = get_option('code_snippets_settings', []);
			$settings['general']['enable_flat_files'] = false;
			update_option('code_snippets_settings', $settings);
		`
	])

	runWpEnvCli([
		'wp',
		'eval',
		`
			global $wpdb;
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}snippets WHERE name LIKE %s",
					"E2E%"
				)
			);
		`
	])
}

try {
	main()
} catch (error: unknown) {
	console.error(error)
	process.exitCode = 1
}
