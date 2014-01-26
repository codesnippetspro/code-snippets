/**
 * Loads CodeMirror on the snippet editor
 */
(function() {

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
