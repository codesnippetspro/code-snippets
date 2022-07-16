import { Snippet } from '../types/snippet'

export type SuccessCallback = (response: { success: boolean, data?: unknown }) => void

const isNetworkAdmin = () =>
	'-network' === window.pagenow.substring(window.pagenow.length - '-network'.length)

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
export const updateSnippet = (field: string, row_element: Element, snippet: Partial<Snippet>, success_callback?: SuccessCallback) => {
	const { value: nonce } = document.getElementById('code_snippets_ajax_nonce') as HTMLInputElement
	const columnId = row_element.querySelector('.column-id')

	if (!columnId?.textContent || !parseInt(columnId.textContent, 10)) {
		return
	}

	snippet.id = parseInt(columnId.textContent, 10)
	snippet.shared_network = Boolean(row_element.className.match(/\bshared-network-snippet\b/))
	snippet.network = snippet.shared_network || isNetworkAdmin()
	snippet.scope = row_element.getAttribute('data-snippet-scope') ?? snippet.scope

	const queryString = `action=update_code_snippet&_ajax_nonce=${nonce}&field=${field}&snippet=${JSON.stringify(snippet)}`
	sendSnippetRequest(queryString, success_callback)
}
