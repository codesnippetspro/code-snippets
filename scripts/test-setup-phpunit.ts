#!/usr/bin/env ts-node

import { execFileSync } from 'node:child_process'
import { existsSync } from 'node:fs'
import { resolve } from 'node:path'
import process, { loadEnvFile } from 'node:process'

interface DatabaseOptions {
	binary: string
	schema: string
	user: string
	password: string
	host: string
}

const run = (cmd: string, args: readonly string[], options?: { env?: NodeJS.ProcessEnv }) => {
	execFileSync(cmd, args, { stdio: 'inherit', env: { ...process.env, ...options?.env } })
}

const buildMysqlArgs = (options: DatabaseOptions): string[] => [
	...['-u', options.user],
	...options.password ? [`--password=${options.password}`] : [],
	...options.host ? ['-h', options.host] : []
]

const assertSafeIdentifier = (text: string, name: string) => {
	if (!/^[A-Za-z0-9_]+$/.test(text)) {
		throw new Error(`Invalid ${name} "${text}". Use only letters, numbers, and underscore.`)
	}
}

const assertSimpleString = (text: string, name: string) => {
	if (!/^[A-Za-z0-9_.-]+$/.test(text)) {
		throw new Error(`Invalid ${name} "${text}". Use only letters, numbers, underscore, dot, and hyphen.`)
	}
}

const initialiseDatabase = (): DatabaseOptions => {
	const db: DatabaseOptions = {
		binary: process.env.WP_PHPUNIT_DB_BINARY ?? 'mysql',
		schema: process.env.WP_PHPUNIT_DB_NAME ?? 'code_snippets_phpunit',
		user: process.env.WP_PHPUNIT_DB_USER ?? 'root',
		password: process.env.WP_PHPUNIT_DB_PASS ?? '',
		host: process.env.WP_PHPUNIT_DB_HOST ?? '127.0.0.1',
	}

	assertSafeIdentifier(db.schema, 'WP_PHPUNIT_DB_NAME')
	assertSafeIdentifier(db.user, 'WP_PHPUNIT_DB_USER')
	assertSimpleString(db.host, 'WP_PHPUNIT_DB_HOST')
	assertSimpleString(db.password, 'WP_PHPUNIT_DB_PASS')

	const useDbSocket = 'true' === (process.env.WP_PHPUNIT_DB_USE_SOCKET ?? 'false').toLowerCase()

	// Create the database if needed (avoid install-wp-tests.sh prompt / destructive behavior).
	const mysqlArgs = useDbSocket ? [] : buildMysqlArgs(db)
	run(db.binary, [...mysqlArgs, '-e', `CREATE DATABASE IF NOT EXISTS \`${db.schema}\`;`])

	if (useDbSocket && 'root' !== db.user) {
		run(db.binary, ['-e', `CREATE USER IF NOT EXISTS '${db.user}'@'${db.host}' IDENTIFIED BY '${db.password}';`])
		run(db.binary, ['-e', `GRANT ALL PRIVILEGES ON \`${db.schema}\`.* TO '${db.user}'@'${db.host}';`])
		run(db.binary, ['-e', 'FLUSH PRIVILEGES;'])
	}

	// Ensure a clean test schema before WordPress bootstraps installation.
	run(db.binary, [
		...mysqlArgs,
		db.schema,
		'-e',
		[
			'SET FOREIGN_KEY_CHECKS = 0',
			'SET @tables = (SELECT GROUP_CONCAT(table_name)' +
			` FROM information_schema.tables WHERE table_schema = '${db.schema}' AND table_name LIKE 'wptests\\_%')`,
			"SET @drop = IF(@tables IS NULL, 'SELECT 1', CONCAT('DROP TABLE ', @tables))",
			'PREPARE stmt FROM @drop',
			'EXECUTE stmt',
			'DEALLOCATE PREPARE stmt',
			'SET FOREIGN_KEY_CHECKS = 1'
		].join('; ')
	])

	return db
}

const main = () => {
	const envFile = resolve(process.cwd(), '.env')

	if (existsSync(envFile)) {
		loadEnvFile(envFile)
	}

	const wpVersion = process.env.WP_PHPUNIT_WP_VERSION ?? 'latest'
	const db = initialiseDatabase()

	const wpTestsDir = resolve(process.cwd(), '.wp-tests-lib')
	const wpCoreDir = resolve(process.cwd(), '.wp-core')
	const wpTestsConfig = resolve(wpTestsDir, 'wp-tests-config.php')
	const installScript = resolve(process.cwd(), 'scripts', 'install-wp-tests.sh')

	// Ensure config is regenerated with current DB settings.
	run('rm', ['-f', wpTestsConfig, `${wpTestsConfig}.bak`])

	run(
		'bash',
		[installScript, db.schema, db.user, db.password, db.host, wpVersion, 'true'],
		{ env: { WP_TESTS_DIR: wpTestsDir, WP_CORE_DIR: wpCoreDir } }
	)

	// Initialize WordPress test tables so `npm run test:php` works on a fresh DB.
	run('php', [resolve(wpTestsDir, 'includes', 'install.php'), wpTestsConfig])
}

try {
	main()
} catch (error: unknown) {
	console.error(error)
	process.exitCode = 1
}
