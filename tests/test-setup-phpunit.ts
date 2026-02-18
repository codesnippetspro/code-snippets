#!/usr/bin/env ts-node

import { execFileSync } from 'node:child_process'
import { resolve } from 'node:path'

const getEnv = (key: string, fallback: string): string => process.env[key] ?? fallback

const run = (cmd: string, args: readonly string[], options: { env?: NodeJS.ProcessEnv } = {}) => {
	const extraEnv = options.env ?? {}
	execFileSync(cmd, args, { stdio: 'inherit', env: { ...process.env, ...extraEnv } })
}

const buildMysqlArgs = (options: { user: string; password: string; host: string }) => {
	const args = ['-u', options.user]

	if (options.password) {
		args.push(`--password=${options.password}`)
	}

	if (options.host) {
		args.push('-h', options.host)
	}

	return args
}

const assertSafeDbName = (dbName: string) => {
	if (!/^[A-Za-z0-9_]+$/.test(dbName)) {
		throw new Error(`Invalid DB name "${dbName}". Use only letters, numbers, and underscore.`)
	}
}

const main = () => {
	const dbName = getEnv('WP_PHPUNIT_DB_NAME', 'code_snippets_phpunit')
	const dbUser = getEnv('WP_PHPUNIT_DB_USER', 'root')
	const dbPass = getEnv('WP_PHPUNIT_DB_PASS', '')
	const dbHost = getEnv('WP_PHPUNIT_DB_HOST', '127.0.0.1')
	const wpVersion = getEnv('WP_PHPUNIT_WP_VERSION', 'latest')

	assertSafeDbName(dbName)

	const wpTestsDir = resolve(process.cwd(), '.wp-tests-lib')
	const wpCoreDir = resolve(process.cwd(), '.wp-core')
	const wpTestsConfig = resolve(wpTestsDir, 'wp-tests-config.php')
	const installScript = resolve(process.cwd(), 'tests', 'install-wp-tests.sh')

	// Create the database if needed (avoid install-wp-tests.sh prompt / destructive behavior).
	const mysqlArgs = buildMysqlArgs({ user: dbUser, password: dbPass, host: dbHost })
	run('mysql', [...mysqlArgs, '-e', `CREATE DATABASE IF NOT EXISTS \`${dbName}\`;`])

	// Ensure config is regenerated with current DB settings.
	run('rm', ['-f', wpTestsConfig, `${wpTestsConfig}.bak`])

	run('bash', [
		installScript,
		dbName,
		dbUser,
		dbPass,
		dbHost,
		wpVersion,
		'true'
	], {
		env: {
			WP_TESTS_DIR: wpTestsDir,
			WP_CORE_DIR: wpCoreDir
		}
	})
}

try {
	main()
} catch (error: unknown) {
	console.error(error)
	process.exitCode = 1
}
