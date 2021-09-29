import './php-lint';
import {EditorConfiguration, ModeSpec} from 'codemirror';
import './globals';

type ModeSpecOptions = {
	startOpen: boolean
}

(CodeMirror => {

	/** Define a new mode which starts the phpmixed mode in php mode instead of html mode */
	CodeMirror.defineMode('php-snippet', (config: EditorConfiguration) =>
		CodeMirror.getMode(config, {
			name: 'application/x-httpd-php',
			startOpen: true
		} as ModeSpec<ModeSpecOptions>));

})(window.wp.CodeMirror);
