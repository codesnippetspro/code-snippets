import './php-lint';

(function (CodeMirror) {
	'use strict';

	/** Define a new mode which starts the phpmixed mode in php mode instead of html mode */
	CodeMirror.defineMode('php-snippet', function (config) {
		return CodeMirror.getMode(config, {name: 'application/x-httpd-php', startOpen: true});
	});

})(wp.CodeMirror);
