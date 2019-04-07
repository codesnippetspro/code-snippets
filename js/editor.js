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

import 'codemirror/addon/hint/show-hint';
import 'codemirror/addon/hint/css-hint';

if (!window.CSSLint) window.CSSLint = csslint.CSSLint;

window.init_code_snippet_editor = (function () {

	const configure_autocomplete = (codemirror) => {
		if (!codemirror.showHint) return;
		// code taken from wp-admin/js/code-editor.js?ver=5.1.1
		codemirror.on('keyup', (editor, event) => { // eslint-disable-line complexity
			let shouldAutocomplete, isAlphaKey = /^[a-zA-Z]$/.test(event.key), lineBeforeCursor, innerMode, token;
			if (codemirror.state.completionActive && isAlphaKey) {
				return;
			}

			// Prevent autocompletion in string literals or comments.
			token = codemirror.getTokenAt(codemirror.getCursor());
			console.log(token);
			if (!token || 'string' === token.type || 'comment' === token.type) {
				return;
			}

			innerMode = CodeMirror.innerMode(codemirror.getMode(), token.state).mode.name;
			lineBeforeCursor = codemirror.doc.getLine(codemirror.doc.getCursor().line).substr(0, codemirror.doc.getCursor().ch);
			if ('html' === innerMode || 'xml' === innerMode) {
				shouldAutocomplete =
					'<' === event.key ||
					'/' === event.key && 'tag' === token.type ||
					isAlphaKey && 'tag' === token.type ||
					isAlphaKey && 'attribute' === token.type ||
					'=' === token.string && token.state.htmlState && token.state.htmlState.tagName;
			} else if ('css' === innerMode) {
				shouldAutocomplete =
					isAlphaKey ||
					':' === event.key ||
					' ' === event.key && /:\s+$/.test(lineBeforeCursor);
			} else if ('javascript' === innerMode) {
				shouldAutocomplete = isAlphaKey || '.' === event.key;
			} else if ('clike' === innerMode && 'application/x-httpd-php' === codemirror.options.mode) {
				shouldAutocomplete = 'keyword' === token.type || 'variable' === token.type;
			}
			if (shouldAutocomplete) {
				codemirror.showHint({completeSingle: false});
			}
		});
	};

	const init_editor = (textarea, settings) => {
		const codemirror = CodeMirror.fromTextArea(textarea, settings);

		configure_autocomplete(codemirror);

		return codemirror;
	};

	return init_editor;
})();
