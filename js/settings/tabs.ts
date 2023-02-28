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
//Declare the hidden input
const hiddenInput = document.getElementById('cloud_token_verified') as HTMLInputElement

//Show the cloud guide text and sync status
const showCloudGuide = (section: string) => {
	if ('cloud' === section) {
		const cloudGuide = document.getElementById('cloud_guide')
		const cloudSyncStatus = document.getElementById('cloud_sync_status')
		cloudGuide?.classList.remove('hidden')
		cloudSyncStatus?.classList.remove('hidden')
	}
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
		const localTokenInput = document.getElementById('local_token') as HTMLInputElement
		const tokenValue = tokenInput.value
		const localToken = generateTokenForCloud(tokenValue)
		localTokenInput.value = localToken
		//Send a HTTP Request to the API - update this to verify URL **TODO**
		cs(tokenValue, localToken).then(response => {
			if(response?.ok) {
				document.querySelector('.cloud-success')?.classList.remove('hidden')
				hiddenInput.value = 'true'
			} 
		})
	})
}

const cs  = async function cloudAPIVerify(tokenValue: string, localToken: string) {
	const formData = new FormData()
	formData.append('site_token', localToken)
	formData.append('site_host', window.location.host)
	try {
		const response = await fetch('https://codesnippets.cloud/api/v1/private/syncandverify', {
			method: 'POST',
			headers: {
				'Authorization': `Bearer ${tokenValue}`,
				'Access-Control-Allow-Origin': '*',
				'Accept': 'application/json',
			},
			body: formData
		})
		if (!response.ok) throw await response.json()
		console.log(response)
		return response
	} catch (e) {
		console.log(e)
		document.querySelector('.cloud-error')?.classList.remove('hidden')
		hiddenInput.value = 'false'
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
				showCloudGuide(section)
			}
		})
	}

	verifyToken()
}

export const generateTokenForCloud = (baseToken: string) => {
	let result = ''
	const charactersLength = baseToken.length
	for ( let i = 0; i < charactersLength; i++ ) {
		result += baseToken.charAt(Math.floor(Math.random() * charactersLength))
	}
	return result
}