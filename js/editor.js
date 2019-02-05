import CodeMirror from 'codemirror';

import 'codemirror/mode/php/php';
import 'codemirror/mode/css/css';
import 'codemirror/mode/javascript/javascript';

import 'codemirror/addon/edit/matchbrackets';
import 'codemirror/addon/edit/closebrackets';

import 'codemirror/addon/search/search';
import 'codemirror/addon/search/match-highlighter';

import 'codemirror/addon/lint/css-lint';
import './php-lint';
import csslint from 'csslint';

if (!window.CSSLint) window.CSSLint = csslint.CSSLint;
window.Code_Snippets_CodeMirror = CodeMirror;
