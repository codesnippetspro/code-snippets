import CodeMirror from 'codemirror';

import 'codemirror/mode/php/php';
import 'codemirror/mode/css/css';
import 'codemirror/mode/javascript/javascript';

import 'codemirror/addon/edit/matchbrackets';
import 'codemirror/addon/edit/closebrackets';

import 'codemirror/addon/search/search';
import 'codemirror/addon/search/match-highlighter';

import 'codemirror/addon/lint/css-lint';
import 'codemirror/addon/lint/javascript-lint';
import './php-lint';

import csslint from 'csslint';
import jshint from 'jshint';

if (!window.CSSLint) window.CSSLint = csslint.CSSLint;
if (!window.JSHINT) window.JSHINT = jshint.JSHINT;
window.Code_Snippets_CodeMirror = CodeMirror;
