import { spawn } from 'child_process'
import { createWriteStream } from 'fs'
import * as process from 'node:process'
import { webpack as webpackAsync, Configuration } from 'webpack'
import plugin from '../package.json'
import webpackConfig from '../webpack.config'
import { cleanup, copy, resolve } from './utils/files'
import archiver from 'archiver'

const DEST_DIR = 'bundle/'

const BUNDLE_FILES = [
	'assets/**/*',
	'css/**/*',
	'js/**/*',
	'dist/**/*',
	'!dist/**/*.map',
	'php/**/*',
	'vendor/**/*',
	'code-snippets.php',
	'uninstall.php',
	'readme.txt',
	'license.txt',
	'CHANGELOG.md'
]

const execute = (command: string, ...args: readonly string[]): Promise<number | null> =>
	new Promise(resolve => {
		const child = spawn(command, args)

		child.stdout.on('data', (data: string) => process.stdout.write(data))
		child.stderr.on('data', (data: string) => process.stderr.write(data))
		child.on('close', code => resolve(code))
	})

const webpack = (config: Configuration): Promise<void> =>
	new Promise((resolve, reject) =>
		webpackAsync({ ...webpackConfig, ...config }, error => error ? reject(error) : resolve()))

export const createArchive = (): Promise<void> => {
	const filename = `${plugin.name}.${plugin.version}.zip`
	console.info(`creating '${filename}\n`)

	const output = createWriteStream(resolve(filename), 'utf8')
	const archive = archiver('zip', {
		zlib: { level: 9 }
	})

	archive.pipe(output)
	archive.directory(resolve(DEST_DIR), plugin.name)
	return archive.finalize()
}

const bundle = async () => {
	console.info('\ngenerating composer and webpack files\n')

	await Promise.all([
		cleanup(`${plugin.name}.*.zip`),
		execute('composer', 'install', '--no-dev'),
		webpack({ mode: 'production' })
	])

	await copy(BUNDLE_FILES, DEST_DIR)
	await createArchive()

	await execute('composer', 'install')
}

void bundle().then()
