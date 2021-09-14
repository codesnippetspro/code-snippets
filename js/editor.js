import CodeMirror from 'codemirror/lib/codemirror';
import 'codemirror/mode/php/php';
import 'codemirror/addon/edit/matchbrackets';
import 'codemirror/addon/edit/closebrackets';
import 'codemirror/addon/search/search';
import 'codemirror/addon/search/match-highlighter';
import '../node_modules/codemirror-colorpicker/dist/codemirror-colorpicker';
import './php-lint';

window.Code_Snippets_CodeMirror = CodeMirror;

/** Define a new mode which starts the phpmixed mode in php mode instead of html mode */
CodeMirror.defineMode('php-snippet', function (config) {
	return CodeMirror.getMode(config, {name: 'application/x-httpd-php', startOpen: true});
});
