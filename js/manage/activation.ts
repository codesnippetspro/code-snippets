import { Snippet } from '../types/snippet'
import { updateSnippet } from './requests'

/**
 * Update the snippet count of a specific view
 * @param element
 * @param increment
 */
const updateViewCount = (element: HTMLElement, increment: boolean) => {
	if (element?.textContent) {
		let count = parseInt(element.textContent.replace(/\((?<count>\d+)\)/, '$1'), 10)
		count += increment ? 1 : -1
		element.textContent = `(${count})`
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

export const handleSnippetActivationSwitches = () => {
	for (const link of document.getElementsByClassName('snippet-activation-switch')) {
		link.addEventListener('click', event => toggleSnippetActive(link as HTMLAnchorElement, event))
	}
}
