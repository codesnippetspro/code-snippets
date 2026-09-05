const MAX_ERRORS = 24

window.codeSnippetsErrors = window.codeSnippetsErrors ?? []

const record = (entry: string): void => {
	if (window.codeSnippetsErrors && window.codeSnippetsErrors.length < MAX_ERRORS) {
		window.codeSnippetsErrors.push(entry)
	}
}

window.addEventListener('error', event => {
	record(`${event.message || 'Error'} — ${event.filename || 'unknown'}:${event.lineno}`)
}, true)

window.addEventListener('unhandledrejection', event => {
	const reason: unknown = event.reason
	record(`Unhandled rejection — ${reason instanceof Error ? reason.message : String(reason)}`)
})

export {}
