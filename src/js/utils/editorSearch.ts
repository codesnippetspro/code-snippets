import { __, sprintf } from '@wordpress/i18n'
import CodeMirror from 'codemirror'
import type { DialogCloseFunction } from 'codemirror/addon/dialog/dialog'
// Brings the getSearchCursor type augmentation into the program.
import type {} from 'codemirror/addon/search/searchcursor'

/**
 * Per-editor search state, kept in cm.state.search in the same shape the stock
 * search addon uses, so CodeMirror's own findNext and findPrev commands work on
 * the same active query our dialog tracks.
 */
interface SearchState {
	query: RegExp | string | null
	queryText: string | null
	lastQuery: string | null
	posFrom: CodeMirror.Position | null
	posTo: CodeMirror.Position | null
	overlay: SearchOverlay | null
	annotate: SearchAnnotation | null
	closeDialog: DialogCloseFunction | null
	status: HTMLSpanElement | null
	input: HTMLInputElement | null
}

interface SearchOverlay {
	token: (stream: CodeMirror.StringStream) => string | null
}

// Scrollbar annotations created by the stock addon when its find or replace
// dialog runs. Clearing them on close removes any leftover marks.
interface SearchAnnotation {
	clear: VoidFunction
}

interface SearchDialogElements {
	form: HTMLLabelElement
	input: HTMLInputElement
	status: HTMLSpanElement
	closeButton: HTMLButtonElement
}

// CodeMirror ships these members on its runtime object, but the @types package
// does not declare them, so the addon keymaps and the command registry stay
// reachable.
interface RuntimeCodeMirror {
	keyMap: Record<string, CodeMirror.KeyMap | undefined>
	keyName: (event: KeyboardEvent, noShift?: boolean) => string
	commands: Record<string, (instance: CodeMirror.Editor) => void>
}

const runtime = <RuntimeCodeMirror><unknown>CodeMirror

const SCROLL_MARGIN = 20

// Commands the dialog forwards to the search itself instead of letting the
// browser or the dialog default handling process the key.
const SEARCH_COMMANDS = ['find', 'findNext', 'findPrev', 'findPersistent', 'findPersistentNext', 'findPersistentPrev']

/**
 * Turn a dialog string into a query. Text wrapped in slashes is treated as a
 * regular expression, anything else is searched literally. A pattern that
 * could match empty text is swapped for /x^/, which never matches, so the
 * dialog reports no results instead of stalling on one spot.
 */
const buildQuery = (rawQuery: string): RegExp | string => {
	const regexpMatch = /\/(?<body>.*)\/(?<flags>[a-z]*)$/.exec(rawQuery)

	if (regexpMatch?.groups) {
		try {
			return new RegExp(regexpMatch.groups.body, -1 === regexpMatch.groups.flags.indexOf('i') ? '' : 'i')
		} catch {
			// Not a valid regular expression after all, do a string search.
		}
	}

	const parsed = rawQuery.replace(/\\(?<char>[nrt\\])/g, (_match: string, char: string) => {
		if ('n' === char) {
			return '\n'
		}
		if ('r' === char) {
			return '\r'
		}
		if ('t' === char) {
			return '\t'
		}

		return '\\'
	})

	return '' === parsed ? /x^/ : parsed
}

const queryCaseInsensitive = (query: RegExp | string | null): boolean =>
	'string' === typeof query && query.toLowerCase() === query

// The overlay and the cursor search the same text the dialog shows. Strings
// become escaped patterns; an existing regexp is reused when it can scan a
// whole line in one pass.
const buildMatcher = (query: RegExp | string): RegExp => {
	if ('string' === typeof query) {
		return new RegExp(query.replace(/[-[\]/{}()*+?.\\^$|]/g, '\\$&'), queryCaseInsensitive(query) ? 'gi' : 'g')
	}

	return query.global ? query : new RegExp(query.source, query.ignoreCase ? 'gi' : 'g')
}

// Same token function as the stock search overlay: matching text gets the
// "searching" style that ships with WordPress' CodeMirror CSS.
const buildOverlay = (query: RegExp | string): SearchOverlay => {
	const matcher = buildMatcher(query)

	return {
		token: (stream: CodeMirror.StringStream): string | null => {
			matcher.lastIndex = stream.pos
			const match = matcher.exec(stream.string)

			if (match?.index === stream.pos) {
				stream.pos += match[0].length || 1
				return 'searching'
			}

			if (match) {
				stream.pos = match.index
			} else {
				stream.skipToEnd()
			}

			return null
		}
	}
}

const getSearchState = (cm: CodeMirror.Editor): SearchState => {
	// The stock search addon keeps its state here; extending it keeps the
	// editor's own findNext and findPrev commands working alongside ours.
	const state = <Record<string, unknown>>cm.state

	state.search ??= {
		query: null,
		queryText: null,
		lastQuery: null,
		posFrom: null,
		posTo: null,
		overlay: null,
		annotate: null,
		closeDialog: null,
		status: null,
		input: null
	}

	return <SearchState>state.search
}

// Lowercase queries search case insensitively, and queries unescaped with \n
// match across lines, both like the stock addon does.
const getSearchCursor = (cm: CodeMirror.Editor, query: RegExp | string, position: CodeMirror.Position) =>
	cm.getSearchCursor(query, position, { caseFold: queryCaseInsensitive(query), multiline: true })

const applyQuery = (cm: CodeMirror.Editor, rawQuery: string): void => {
	const state = getSearchState(cm)

	if (state.overlay) {
		cm.removeOverlay(state.overlay)
	}

	state.queryText = rawQuery
	state.query = buildQuery(rawQuery)
	state.overlay = buildOverlay(state.query)
	cm.addOverlay(state.overlay)
}

/**
 * Select the next or previous match, wrapping around both document ends, and
 * refresh the positions CodeMirror's own search commands read.
 */
const jumpToMatch = (cm: CodeMirror.Editor, reverse: boolean): void => {
	const state = getSearchState(cm)
	const { query, posFrom, posTo } = state

	if (!query || !posFrom || !posTo) {
		return
	}

	cm.operation(() => {
		let cursor = getSearchCursor(cm, query, reverse ? posFrom : posTo)

		if (!cursor.find(reverse)) {
			cursor = getSearchCursor(cm, query, reverse ? CodeMirror.Pos(cm.lastLine()) : CodeMirror.Pos(cm.firstLine(), 0))

			if (!cursor.find(reverse)) {
				return
			}
		}

		const from = cursor.from()
		const to = cursor.to()

		state.posFrom = from
		state.posTo = to
		cm.setSelection(from, to)
		cm.scrollIntoView({ from, to }, SCROLL_MARGIN)
	})
}

// Which match the selection sits on and how many matches the document holds,
// found in a single sweep. The cursor steps past zero-width matches on its
// own, so the loop always terminates.
const countMatches = (cm: CodeMirror.Editor): { index: number, total: number } => {
	const { query, posFrom } = getSearchState(cm)

	if (!query) {
		return { index: 0, total: 0 }
	}

	const cursor = getSearchCursor(cm, query, { line: 0, ch: 0 })

	let total = 0
	let index = 0

	while (cursor.findNext()) {
		total += 1

		if (cursor.from().line === posFrom?.line && cursor.from().ch === posFrom.ch) {
			index = total
		}
	}

	return { index, total }
}

const updateStatus = (cm: CodeMirror.Editor): void => {
	const status = getSearchState(cm).status

	if (!status) {
		return
	}

	const { index, total } = countMatches(cm)

	if (0 === total) {
		status.classList.add('is-no-results')
		status.textContent = __('No results found', 'code-snippets')
		return
	}

	status.classList.remove('is-no-results')

	// Translators: %1$s is the position of the current match, %2$s is the total number of matches.
	status.textContent = sprintf(__('Match %1$s of %2$s', 'code-snippets'), index, total)
}

/**
 * Search for the dialog input value and jump to the next or previous match.
 * Committing an unchanged query just moves to the next match.
 */
const commitSearch = (cm: CodeMirror.Editor, rawQuery: string, reverse: boolean): void => {
	if (!rawQuery) {
		return
	}

	const state = getSearchState(cm)

	if (rawQuery !== state.queryText) {
		applyQuery(cm, rawQuery)

		const start = cm.getCursor()
		state.posFrom = start
		state.posTo = start
	}

	jumpToMatch(cm, reverse)
	updateStatus(cm)

	// Selecting the match swaps CodeMirror's hidden input, which blurs the
	// search field. Focus only ever moves within the editor here, so pull it
	// straight back into the dialog.
	if (state.input && !elementsContain(state.input, document.activeElement)) {
		state.input.focus()
	}
}

// The stock dialog dispatches to the command bound to the pressed key, extra
// keys before the active key map. Tab must reach the input itself, so only
// search commands are forwarded.
const lookupDialogCommand = (cm: CodeMirror.Editor, event: KeyboardEvent): string | undefined => {
	const keyName = runtime.keyName(event)
	const extraKeys = cm.getOption('extraKeys')
	const keyMap = runtime.keyMap[cm.getOption('keyMap') ?? 'default']
	const bindings = 'object' === typeof extraKeys ? { ...keyMap, ...extraKeys } : keyMap
	const mapped = bindings?.[keyName]

	return 'string' === typeof mapped ? mapped : undefined
}

const handleDialogKeyDown = (cm: CodeMirror.Editor, input: HTMLInputElement) => (event: KeyboardEvent): boolean => {
	// Enter submits the surrounding snippet form through the browser's
	// implicit submission, so it is always treated as a search step here.
	// Shift-Enter walks backwards, as does any key mapped to a previous-match
	// command.
	const isEnter = 'Enter' === event.key
	let reverse = false

	if (isEnter) {
		reverse = event.shiftKey
	} else {
		const command = lookupDialogCommand(cm, event)

		if (!command || !SEARCH_COMMANDS.includes(command)) {
			return false
		}

		reverse = 'findPrev' === command || 'findPersistentPrev' === command
	}

	// Stopping the event keeps the browser from scrolling or submitting and
	// keeps the dialog open while bound keys navigate matches.
	event.preventDefault()
	event.stopPropagation()
	commitSearch(cm, input.value, reverse)

	return true
}

// Whether the element or one of its descendants currently holds focus.
const elementsContain = (element: HTMLElement, candidate: Element | null): boolean =>
	candidate instanceof Node && element.contains(candidate)

const buildDialog = (): SearchDialogElements => {
	const form = document.createElement('label')
	form.className = 'cs-editor-search-form'

	const input = document.createElement('input')
	input.className = 'cs-editor-search-field'
	input.type = 'text'
	input.setAttribute('aria-label', __('Search', 'code-snippets'))

	const status = document.createElement('span')
	status.className = 'cs-editor-search-status'

	// Screen readers announce result changes without interrupting typing.
	status.setAttribute('role', 'status')
	status.setAttribute('aria-live', 'polite')

	const closeButton = document.createElement('button')
	closeButton.className = 'cs-editor-search-close'
	closeButton.type = 'button'
	closeButton.textContent = __('Close', 'code-snippets')
	closeButton.setAttribute('aria-label', __('Close search', 'code-snippets'))

	form.append(__('Search', 'code-snippets'), input, status, closeButton)

	return { form, input, status, closeButton }
}

/**
 * Close the dialog when focus settles outside it, as the stock addon does.
 * Focus moving inside the dialog, like tabbing to the close button, does not
 * close it. The check runs one task later because jumping to a match
 * momentarily blurs the input while CodeMirror swaps its hidden textarea, and
 * the page's focus scripts may flicker focus across the React tree before it
 * settles back in the dialog. Returns the cancel function, which the close
 * handler also uses to drop a pending check when the dialog closes first.
 */
const watchDialogFocus = (form: HTMLLabelElement, close: () => void): (() => void) => {
	let timer: number | undefined

	const cancel = (): void => {
		if (undefined !== timer) {
			window.clearTimeout(timer)
			timer = undefined
		}
	}

	form.addEventListener('focusin', cancel)

	form.addEventListener('focusout', (event: FocusEvent) => {
		const target = event.relatedTarget

		if (target instanceof Node && form.contains(target)) {
			return
		}

		cancel()
		timer = window.setTimeout(() => {
			timer = undefined

			if (!elementsContain(form, document.activeElement)) {
				close()
			}
		}, 0)
	})

	return cancel
}

/**
 * Open the persistent search dialog for an editor.
 *
 * The dialog keeps focus in its input while the user steps through matches,
 * stays open on Enter, and reports for every query whether it has matches.
 * Escape and the close button return focus to the editor.
 */
const openSearchDialog = (cm: CodeMirror.Editor): void => {
	const state = getSearchState(cm)

	if (state.closeDialog) {
		// Opening a new search replaces an active one, as the stock command does.
		state.closeDialog()
	}

	const elements = buildDialog()
	state.status = elements.status
	state.input = elements.input

	const onKeyDown = handleDialogKeyDown(cm, elements.input)

	// Assigned a real watcher right after the dialog opens; the close handler
	// can only ever run after that assignment.
	let cancelWatch: VoidFunction = (): void => undefined

	const closeDialog: DialogCloseFunction = cm.openDialog(
		elements.form,
		(rawQuery, event) => commitSearch(cm, rawQuery, event instanceof KeyboardEvent && event.shiftKey),
		{
			// Enter must not close the dialog: navigation happens here and
			// the input keeps focus between jumps.
			closeOnEnter: false,
			closeOnBlur: false,
			onKeyDown,
			onClose: () => {
				cancelWatch()
				state.closeDialog = null
				state.status = null
				state.input = null
				clearSearch(cm)
			}
		}
	)

	state.closeDialog = closeDialog
	cancelWatch = watchDialogFocus(elements.form, closeDialog)

	const selection = cm.getSelection()

	elements.input.value = selection || (state.lastQuery ?? '')
	elements.input.select()

	elements.closeButton.addEventListener('click', closeDialog)

	if (elements.input.value) {
		commitSearch(cm, elements.input.value, false)
	}
}

const clearSearch = (cm: CodeMirror.Editor): void => {
	const state = getSearchState(cm)
	state.lastQuery = state.queryText

	if (!state.query) {
		return
	}

	state.queryText = null
	state.query = null

	if (state.annotate) {
		state.annotate.clear()
		state.annotate = null
	}

	if (state.overlay) {
		cm.removeOverlay(state.overlay)
		state.overlay = null
	}
}

export const registerPersistentSearch = (): void => {
	runtime.commands.findPersistent = cm => openSearchDialog(cm)
}
