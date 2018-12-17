import CodeMirror from 'codemirror/lib/codemirror';
import 'codemirror/mode/php/php';
import 'codemirror/addon/edit/matchbrackets';
import 'codemirror/addon/edit/closebrackets';
import 'codemirror/addon/search/search';
import 'codemirror/addon/search/match-highlighter';
import './php-lint';

window.Code_Snippets_CodeMirror = CodeMirror;
