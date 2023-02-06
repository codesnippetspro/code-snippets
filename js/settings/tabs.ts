const selectTab = (tabsWrapper: Element, tab: Element, section: string) => {
	// Swap the active tab class from the previously active tab to the current one.
	tabsWrapper.querySelector('.nav-tab-active')?.classList.remove('nav-tab-active')
	tab.classList.add('nav-tab-active')

	// Update the current active tab attribute so that only the active tab is displayed.
	tabsWrapper.closest('.wrap')?.setAttribute('data-active-tab', section)

	//Hide all cloud messages - this is a bit of a hack, but it works make better **TODO**
	document.querySelectorAll('.cloud-message')?.forEach(element => {
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
	const httpReferer = document.querySelector<HTMLInputElement>('input[name=_wp_http_referer]')
	if (!httpReferer) {
		console.error('could not find http referer')
		return
	}

	const newReferer = httpReferer.value.replace(/(?<base>[&?]section=)[^&]+/, `$1${section}`)
	httpReferer.value = newReferer + (newReferer === httpReferer.value ? `&section=${section}` : '')
}

//Verify API Token by seding a HTTP Request to the API and checking the response
const verifyToken = () => {
	const verifyTokenButton = document.getElementById('verify_token')
	verifyTokenButton?.addEventListener('click', event => {
		//Hide all messages
		document.querySelectorAll('.cloud-message')?.forEach(element => {
			element.classList.add('hidden')
		})
		event.preventDefault()
		//Get the token value
		const tokenInput = document.getElementById('cloud_token') as HTMLInputElement
		const tokenValue = tokenInput.value
		console.log(tokenValue)
		//Send a HTTP Request to the API - update this to verify URL **TODO**
		cs(tokenValue).then(response => {
			if(response?.ok) {
				document.querySelector('.cloud-success')?.classList.remove('hidden')
				const hiddenInput = document.getElementById('cloud_token_verified') as HTMLInputElement
				hiddenInput.value = 'true'
			} 
		})
	})
}

const cs  = async function cloudAPIVerify(tokenValue: string) {
	try {
		const response = await fetch('https://codesnippets.cloud/api/v1/private/publicsnippets', {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': `Bearer ${tokenValue}`,
			},
		})
		if (!response.ok) throw await response.json()
		return response
	} catch (e) {
		console.log(e)
		document.querySelector('.cloud-error')?.classList.remove('hidden')
	}
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

	verifyToken()
}
