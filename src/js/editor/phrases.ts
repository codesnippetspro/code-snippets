import { __ } from '@wordpress/i18n'

/**
 * Translations for the phrases CodeMirror shows in its own interface.
 *
 * Kept apart from the editor itself so that the strings are bundled with the
 * page script that WordPress loads translations for, rather than with editor
 * code that some pages load on demand.
 */
export const getEditorPhrases = (): Record<string, string> => ({
	'Find': __('Find', 'code-snippets'),
	'Replace': __('Replace', 'code-snippets'),
	'next': __('next', 'code-snippets'),
	'previous': __('previous', 'code-snippets'),
	'all': __('all', 'code-snippets'),
	'match case': __('match case', 'code-snippets'),
	'regexp': __('regexp', 'code-snippets'),
	'by word': __('by word', 'code-snippets'),
	'replace': __('replace', 'code-snippets'),
	'replace all': __('replace all', 'code-snippets'),
	'close': __('close', 'code-snippets'),
	'current match': __('current match', 'code-snippets'),
	'on line': __('on line', 'code-snippets'),
	// translators: $ is replaced with the number of matches.
	'replaced $ matches': __('replaced $ matches', 'code-snippets'),
	// translators: $ is replaced with a line number.
	'replaced match on line $': __('replaced match on line $', 'code-snippets'),
	'Go to line': __('Go to line', 'code-snippets'),
	'go': __('go', 'code-snippets'),
	'Folded lines': __('Folded lines', 'code-snippets'),
	'Unfolded lines': __('Unfolded lines', 'code-snippets'),
	'to': __('to', 'code-snippets'),
	'folded code': __('folded code', 'code-snippets'),
	'unfold': __('unfold', 'code-snippets'),
	'Fold line': __('Fold line', 'code-snippets'),
	'Unfold line': __('Unfold line', 'code-snippets'),
	'Diagnostics': __('Diagnostics', 'code-snippets'),
	'No diagnostics': __('No diagnostics', 'code-snippets'),
	'Completions': __('Completions', 'code-snippets'),
	'Documentation': __('Documentation', 'code-snippets'),
	'Control character': __('Control character', 'code-snippets'),
	'Selection deleted': __('Selection deleted', 'code-snippets'),
	'Syntax error': __('Syntax error', 'code-snippets')
})
