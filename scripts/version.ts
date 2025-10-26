import plugin from '../package.json'
import { replaceInFile } from './utils/files'

replaceInFile(
	'src/code-snippets.php',
	contents => contents
		.replace(/(?<prefix>Version:\s+|@version\s+)\d+\.\d+\.\d+[\w-.]*$/mg, `$1${plugin.version}`)
		.replace(/(?<prefix>'CODE_SNIPPETS_VERSION',\s+)'[\w-.]+'/, `$1'${plugin.version}'`)
)

if (!/beta/i.test(plugin.version)) {
	replaceInFile(
		'src/readme.txt',
		contents => contents
			.replace(/(?<prefix>Stable tag:\s+|@version\s+)\d+\.\d+[\w-.]+$/mg, `$1${plugin.version}`)
	)
}
