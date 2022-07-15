import { toggleSnippetActive, updateSnippetPriority } from './table'

for (const link of document.getElementsByClassName('snippet-activation-switch')) {
	link.addEventListener('click', event => toggleSnippetActive(link as HTMLAnchorElement, event))
}

for (const field of document.getElementsByClassName('snippet-priority') as HTMLCollectionOf<HTMLInputElement>) {
	field.addEventListener('input', () => updateSnippetPriority(field))
	field.disabled = false
}
