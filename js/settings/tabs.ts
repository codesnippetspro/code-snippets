const page = document.querySelector('.wrap')
const tabs_wrapper = document.getElementById('settings-sections-tabs')
const http_referer = document.querySelector('input[name=_wp_http_referer]') as HTMLInputElement

const selectTab = (tab: Element, section: string) => {
	// Swap the active tab class from the previously active tab to the current one.
	const active_tab = tabs_wrapper?.querySelector('.nav-tab-active')
	if (active_tab) active_tab.classList.remove('nav-tab-active')
	tab.classList.add('nav-tab-active')

	// Update the current active tab attribute so that only the active tab is displayed.
	page?.setAttribute('data-active-tab', section)
}

// Refresh the editor preview if we're viewing the editor section.
const refreshEditorPreview = (section: string) => {
	if ('editor' === section) {
		const editor = window.code_snippets_editor_preview
		if (editor && editor.codemirror) editor.codemirror.refresh()
	}
}

// Update the http referer value so that any redirections lead back to this tab.
const updateHttpReferer = (section: string) => {
	let new_referer = http_referer.value.replace(/(?<base>[&?]section=)[^&]+/, `$1${section}`)
	if (new_referer === http_referer.value) {
		new_referer += `&section=${section}`
	}
	http_referer.value = new_referer
}

export const handleSettingsTabs = () => {
	const tabs = tabs_wrapper?.querySelectorAll('.nav-tab') ?? []

	for (const tab of tabs) {
		tab.addEventListener('click', event => {
			event.preventDefault()
			const section = tab.getAttribute('data-section')

			if (section) {
				selectTab(tab, section)
				refreshEditorPreview(section)
				updateHttpReferer(section)
			}
		})
	}
}
