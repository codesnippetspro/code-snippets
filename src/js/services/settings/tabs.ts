import { updateQueryParams } from '../../utils/urls'

const selectTab = (tabsWrapper: Element, tab: Element, section: string) => {
	// Swap the active tab class from the previously active tab to the current one.
	tabsWrapper.querySelector('.active-type')?.classList.remove('active-type')
	tab.classList.add('active-type')
	updateQueryParams({ section })

	// Update the current active tab attribute so that only the active tab is displayed.
	tabsWrapper.closest('.wrap')?.setAttribute('data-active-tab', section)

	//Hide all cloud messages - this is a bit of a hack, but it works make better **TODO**
	document.querySelectorAll('.cloud-message').forEach(element => {
		element.classList.add('hidden')
	})
}

// Refresh the editor preview if we're viewing the editor section.
const refreshEditorPreview = (section: string) => {
	if ('editor' === section) {
		window.code_snippets_editor_preview?.codemirror.refresh()
	}
}

// Update the http referer value so that any redirections lead back to this tab.
const updateHttpReferer = (section: string) => {
	const httpReferer: HTMLInputElement | null = document.querySelector('input[name=_wp_http_referer]')
	if (!httpReferer) {
		console.error('could not find http referer')
		return
	}

	const newReferer = httpReferer.value.replace(/(?<base>[&?]section=)[^&]+/, `$1${section}`)
	httpReferer.value = newReferer + (newReferer === httpReferer.value ? `&section=${section}` : '')
}

const setupHorizontalOverflow = (tabs: HTMLElement) => {
	const wrapper = tabs.closest<HTMLElement>('.snippet-type-nav-wrapper')
	if (!wrapper) {
		return
	}

	const updateFades = () => {
		const remainingScroll = tabs.scrollWidth - tabs.clientWidth - tabs.scrollLeft
		wrapper.classList.toggle('has-scroll-start', 1 < tabs.scrollLeft)
		wrapper.classList.toggle('has-scroll-end', 1 < remainingScroll)
	}

	tabs.addEventListener('scroll', updateFades, { passive: true })

	const observer = new ResizeObserver(updateFades)
	observer.observe(tabs)
	observer.observe(tabs.firstElementChild ?? tabs)
	updateFades()
}

export const handleSettingsTabs = () => {
	const tabsWrapper = document.getElementById('settings-sections-tabs')
	if (!tabsWrapper) {
		console.error('Could not find snippets tabs')
		return
	}

	setupHorizontalOverflow(tabsWrapper)

	const tabs = tabsWrapper.querySelectorAll('.snippet-type-link')

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
