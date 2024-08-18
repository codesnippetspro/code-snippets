import { updateSnippet } from './requests'
import type { Snippet } from '../../types/Snippet'

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

export const handleSnippetPriorityChanges = () => {
	for (const field of <HTMLCollectionOf<HTMLInputElement>> document.getElementsByClassName('snippet-priority')) {
		field.addEventListener('input', () => updateSnippetPriority(field))
		field.disabled = false
	}
}
