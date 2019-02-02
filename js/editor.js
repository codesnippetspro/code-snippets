import CodeMirror from 'codemirror/lib/codemirror';

import 'codemirror/mode/php/php';
import 'codemirror/mode/css/css';

import 'codemirror/addon/edit/matchbrackets';
import 'codemirror/addon/edit/closebrackets';

import 'codemirror/addon/search/search';
import 'codemirror/addon/search/match-highlighter';

import {CSSLint} from 'csslint/dist/csslint-node'
import './php-lint';
import 'codemirror/addon/lint/css-lint';

if (!window.CSSLint) window.CSSLint = CSSLint;
window.Code_Snippets_CodeMirror = CodeMirror;
