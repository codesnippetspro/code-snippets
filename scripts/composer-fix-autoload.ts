import { replaceInFile } from './utils/files'

replaceInFile(
	'src/vendor/matthiasmullie/minify/src/JS.php',
	contents => contents
		.replace('\\\\MatthiasMullie\\Minify\\\\', '\\\\Code_Snippets\\Vendor\\MatthiasMullie\\Minify\\\\')
)
