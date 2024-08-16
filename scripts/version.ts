import { readFileSync, writeFileSync } from 'fs'
import plugin from '../package.json'
import { resolve } from './utils/files'

const replaceInFile = (filename: string, transform: (contents: string) => string) => {
	const file = resolve(filename)
	const contents = readFileSync(file, 'utf8')
	writeFileSync(file, transform(contents), 'utf8')
}

replaceInFile(
	'code-snippets.php',
	contents => contents
		.replace(/(?<prefix>Version:\s+|@version\s+)\d+\.\d+[\w-.]+$/mg, `$1${plugin.version}`)
		.replace(/(?<prefix>'CODE_SNIPPETS_VERSION',\s+)'[\w-.]+'/, `$1'${plugin.version}'`)
)

replaceInFile(
	'readme.txt',
	contents => contents
		.replace(/(?<prefix>Stable tag:\s+|@version\s+)\d+\.\d+[\w-.]+$/mg, `$1${plugin.version}`)
)
