import { SnippetType } from '../types'
import { EditorConfiguration } from 'codemirror'

(editor => {
	const tabsContainer = document.getElementById('snippet-type-tabs')
	if (!tabsContainer) return

	const snippetForm = document.getElementById('snippet-form')
	const tabs = tabsContainer.querySelectorAll('.nav-tab')

	const modes: Record<SnippetType, string> = {
		css: 'text/css',
		js: 'javascript',
		php: 'text/x-php',
		html: 'application/x-httpd-php'
	}

	const selectScope = (type: SnippetType) => {
		const scope = snippetForm?.querySelector(`.${type}-scopes-list input:first-child`) as HTMLInputElement
		if (scope) {
			scope.checked = true
		}

		editor?.setOption('lint' as keyof EditorConfiguration, 'php' === type || 'css' === type)
		if (type in modes) {
			editor?.setOption('mode', modes[type])
		}
	}

	const switchTab = (tab: Element) => {
		const prevActive = tabsContainer.querySelector('.nav-tab-active')
		prevActive?.setAttribute('href', '#')
		prevActive?.classList.remove('nav-tab-active')

		tab.classList.add('nav-tab-active')
		tab.removeAttribute('href')
	}

	for (const tab of tabs) {
		tab.addEventListener('click', event => {
			if (tab.classList.contains('nav-tab-active')) return
			const type = tab.getAttribute('data-type') as SnippetType
			event.preventDefault()

			// Update the form styles to match the new type.
			snippetForm?.setAttribute('data-snippet-type', type)

			// Switch the active tab and change the snippet scope.
			switchTab(tab)
			selectScope(type)
		})
	}

})(window.code_snippets_editor?.codemirror)
