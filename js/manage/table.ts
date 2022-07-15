import { Snippet } from '../types'

export type SuccessCallback = (response: { success: boolean, data?: unknown }) => void

const network_admin = '-network' === window.pagenow.substring(window.pagenow.length - '-network'.length)

const sendSnippetRequest = (query: string, onSuccess?: SuccessCallback) => {
	const request = new XMLHttpRequest()
	request.open('POST', window.ajaxurl, true)
	request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded charset=UTF-8')

	request.onload = () => {
		const success = 200
		const errorStart = 400
		if (success > request.status || errorStart <= request.status) return
		// eslint-disable-next-line no-console
		console.log(request.responseText)

		onSuccess?.(JSON.parse(request.responseText))
	}

	request.send(query)
}

/**
 * Update the data of a given snippet using AJAX
 * @param field
 * @param row_element
 * @param snippet
 * @param success_callback
 */
const updateSnippet = (field: string, row_element: Element, snippet: Partial<Snippet>, success_callback?: SuccessCallback) => {
	const { value: nonce } = document.getElementById('code_snippets_ajax_nonce') as HTMLInputElement
	const columnId = row_element.querySelector('.column-id')

	if (!columnId?.textContent || !parseInt(columnId.textContent, 10)) {
		return
	}

	snippet.id = parseInt(columnId.textContent, 10)
	snippet.shared_network = Boolean(row_element.className.match(/\bshared-network-snippet\b/))
	snippet.network = snippet.shared_network || network_admin
	snippet.scope = row_element.getAttribute('data-snippet-scope') ?? snippet.scope

	const queryString = `action=update_code_snippet&_ajax_nonce=${nonce}&field=${field}&snippet=${JSON.stringify(snippet)}`
	sendSnippetRequest(queryString, success_callback)
}

/**
 * Update the priority of a snippet
 */
export const updateSnippetPriority = (element: HTMLInputElement) => {
	const row = element.parentElement?.parentElement
	const snippet: Partial<Snippet> = { priority: parseFloat(element.value) }
	if (row) {
		updateSnippet('priority', row, snippet)
	} else {
		console.error('Could not update snippet information.', snippet, row)
	}
}

/**
 * Update the snippet count of a specific view
 * @param element
 * @param increment
 */
const updateViewCount = (element: HTMLElement, increment: boolean) => {
	if (element?.textContent) {
		let count = parseInt(element.textContent.replace(/\((?<count>\d+)\)/, '$1'), 10)
		count += increment ? 1 : -1
		element.textContent = `(${count.toString()})`
	} else {
		console.error('Could not update view count.', element)
	}
}

/**
 * Activate an inactive snippet, or deactivate an active snippet
 * @param link
 * @param event
 */
export const toggleSnippetActive = (link: HTMLAnchorElement, event: Event) => {
	const strings = window.code_snippets_manage_i18n

	const row = link?.parentElement?.parentElement // Switch < cell < row
	if (!row) {
		console.error('Could not toggle snippet active status.', row)
		return
	}

	const match = row.className.match(/\b(?:in)?active-snippet\b/)
	if (!match) return

	event.preventDefault()

	const activating = 'inactive-snippet' === match[0]
	const snippet: Partial<Snippet> = { active: activating }

	updateSnippet('active', row, snippet, response => {
		const button = row.querySelector('.snippet-activation-switch') as HTMLAnchorElement

		if (response.success) {
			row.className = activating ?
				row.className.replace(/\binactive-snippet\b/, 'active-snippet') :
				row.className.replace(/\bactive-snippet\b/, 'inactive-snippet')

			const views = document.querySelector('.subsubsub')
			const activeCount = views?.querySelector<HTMLElement>('.active .count')
			const inactiveCount = views?.querySelector<HTMLElement>('.inactive .count')

			activeCount ? updateViewCount(activeCount, activating) : null
			inactiveCount ? updateViewCount(inactiveCount, activating) : null

			button.title = activating ? strings.deactivate : strings.activate
		} else {
			row.className += ' erroneous-snippet'
			button.title = strings.activation_error
		}
	})
}
