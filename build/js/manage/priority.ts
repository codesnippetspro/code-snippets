import { Snippet } from '../types/snippet'
import { updateSnippet } from './requests'

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
	for (const field of document.getElementsByClassName('snippet-priority') as HTMLCollectionOf<HTMLInputElement>) {
		field.addEventListener('input', () => updateSnippetPriority(field))
		field.disabled = false
	}
}
