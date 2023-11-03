const selectTab = (tabsWrapper: Element, tab: Element, section: string) => {
	// Swap the active tab class from the previously active tab to the current one.
	tabsWrapper.querySelector('.nav-tab-active')?.classList.remove('nav-tab-active')
	tab.classList.add('nav-tab-active')

	// Update the current active tab attribute so that only the active tab is displayed.
	tabsWrapper.closest('.wrap')?.setAttribute('data-active-tab', section)
}

// Refresh the editor preview if we're viewing the editor section.
const refreshEditorPreview = (section: string) => {
	if ('editor' === section) {
		window.code_snippets_editor_preview?.codemirror.refresh()
	}
}

// Update the http referer value so that any redirections lead back to this tab.
const updateHttpReferer = (section: string) => {
	const httpReferer = document.querySelector<HTMLInputElement>('input[name=_wp_http_referer]')
	if (!httpReferer) {
		console.error('could not find http referer')
		return
	}

	const newReferer = httpReferer.value.replace(/(?<base>[&?]section=)[^&]+/, `$1${section}`)
	httpReferer.value = newReferer + (newReferer === httpReferer.value ? `&section=${section}` : '')
}

export const handleSettingsTabs = () => {
	const tabsWrapper = document.getElementById('settings-sections-tabs')
	if (!tabsWrapper) {
		console.error('Could not find snippets tabs')
		return
	}

	const tabs = tabsWrapper.querySelectorAll('.nav-tab') ?? []

	for (const tab of tabs) {
		tab.addEventListener('click', event => {
			event.preventDefault()
			const section = tab.getAttribute('data-section')

			if (section) {
				selectTab(tabsWrapper, tab, section)
				refreshEditorPreview(section)
				updateHttpReferer(section)
			}
		})
	}
}
