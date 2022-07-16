import { SnippetType } from '../types/snippet'
import { EditorConfiguration } from 'codemirror'

const EDITOR_MODES: Record<SnippetType, string> = {
	css: 'text/css',
	js: 'javascript',
	php: 'text/x-php',
	html: 'application/x-httpd-php'
}

const selectScope = (type: SnippetType, snippetForm: HTMLElement | null) => {
	const editor = window.code_snippets_editor?.codemirror
	const scope = snippetForm?.querySelector<HTMLInputElement>(`.${type}-scopes-list input:first-child`)

	if (scope) {
		scope.checked = true
	}

	editor?.setOption('lint' as keyof EditorConfiguration, 'php' === type || 'css' === type)
	if (type in EDITOR_MODES) {
		editor?.setOption('mode', EDITOR_MODES[type])
	}
}

const switchTab = (tabsContainer: HTMLElement, tab: Element) => {
	const prevActive = tabsContainer.querySelector('.nav-tab-active')
	prevActive?.setAttribute('href', '#')
	prevActive?.classList.remove('nav-tab-active')

	tab.classList.add('nav-tab-active')
	tab.removeAttribute('href')
}


export const handleSnippetTypeTabs = () => {
	const tabsContainer = document.getElementById('snippet-type-tabs')

	if (!tabsContainer) {
		return
	}

	const snippetForm = document.getElementById('snippet-form')
	const tabs = tabsContainer.querySelectorAll('.nav-tab')

	for (const tab of tabs) {
		tab.addEventListener('click', event => {
			if (tab.classList.contains('nav-tab-active') || tab.classList.contains('nav-tab-inactive')) return
			const type = tab.getAttribute('data-type') as SnippetType
			event.preventDefault()

			// Update the form styles to match the new type.
			snippetForm?.setAttribute('data-snippet-type', type)

			// Switch the active tab and change the snippet scope.
			switchTab(tabsContainer, tab)
			selectScope(type, snippetForm)
		})
	}

}
