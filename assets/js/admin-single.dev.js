/**
 * Loads CodeMirror on the snippet editor
 */
(function() {

	// import 'codemirror/lib/codemirror.js'
	// import 'codemirror/mode/clike/clike.js'
	// import 'codemirror/mode/php/php.js'
	// import 'codemirror/addon/search/searchcursor.js'
	// import 'codemirror/addon/search/search.js'
	// import 'codemirror/addon/edit/matchbrackets.js'

	var atts = {
		lineNumbers: true,
		matchBrackets: true,
		lineWrapping: true,
		mode: "text/x-php",
		indentUnit: 4,
		indentWithTabs: true,
		enterMode: "keep",
		tabMode: "shift"
	};

	var editor = CodeMirror.fromTextArea(document.getElementById("snippet_code"), atts);

})();
