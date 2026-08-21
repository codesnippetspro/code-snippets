// Handles version switching UI on the settings screen.
// Exported init function so callers can opt in like other settings modules.
// Uses vanilla DOM APIs and the global `code_snippets_version_switch` config
// injected by PHP via wp_add_inline_script.

interface VersionConfig {
	ajaxurl?: string
	nonce_switch?: string
	nonce_refresh?: string
}

interface AjaxResponse {
	success?: boolean
	data?: {
		message?: string
	}
}

declare global {
	interface Window {
		code_snippets_version_switch?: VersionConfig
		__code_snippets_i18n?: {
			selectDifferent: string
			switching: string
			processing: string
			error: string
			errorSwitch: string
			refreshing: string
			refreshed: string
		}
	}
}

const i18n = window.__code_snippets_i18n

const getCurrentVersion = (): string =>
	(document.querySelector('.current-version')?.textContent ?? '').trim()

const bindDropdown = (
	dropdown: HTMLSelectElement,
	button: HTMLButtonElement | null,
	currentVersion: string
): void => {
	const warningNotice = document.getElementById('version-switch-warning')

	dropdown.addEventListener('change', () => {
		const selectedVersion = dropdown.value
		if (!button) {
			return
		}

		if (!selectedVersion || selectedVersion === currentVersion) {
			button.disabled = true
			warningNotice?.classList.add('hidden')
		} else {
			button.disabled = false
			warningNotice?.classList.remove('hidden')
		}
	})
}

const SUCCESS_RELOAD_MS = 3000

const postForm = async (data: Record<string, string>, config: VersionConfig): Promise<AjaxResponse> => {
	const body = new URLSearchParams()
	Object.keys(data).forEach(k => body.append(k, data[k]))

	if (!config.ajaxurl) {
		throw new Error('ajaxurl not defined in config')
	}

	const resp = await fetch(config.ajaxurl, {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
		body: body.toString(),
		credentials: 'same-origin'
	})

	return <AjaxResponse> await resp.json()
}

const bindSwitch = (
	button: HTMLButtonElement,
	dropdown: HTMLSelectElement,
	result: HTMLDivElement,
	cfg: VersionConfig,
	currentVersion: string
): void => {
	button.addEventListener('click', (): void => {
		void (async (): Promise<void> => {
			const targetVersion = dropdown.value
			if (!targetVersion || targetVersion === currentVersion) {
				result.className = 'notice notice-warning'
				result.innerHTML = `<p>${i18n?.selectDifferent}</p>`
				result.style.display = ''
				return
			}

			button.disabled = true
			const originalText = button.textContent
			button.textContent = i18n?.switching ?? ''

			result.className = 'notice notice-info'
			result.innerHTML = `<p>${i18n?.processing}</p>`
			result.style.display = ''

			try {
				const response = await postForm({
					action: 'code_snippets_switch_version',
					target_version: targetVersion,
					nonce: cfg.nonce_switch ?? ''
				}, cfg)

				if (response.success) {
					result.className = 'notice notice-success'
					result.innerHTML = `<p>${response.data?.message ?? ''}</p>`
					setTimeout(() => window.location.reload(), SUCCESS_RELOAD_MS)
					return
				}

				result.className = 'notice notice-error'
				result.innerHTML = `<p>${response.data?.message ?? i18n?.error}</p>`
				button.disabled = false
				button.textContent = originalText
			} catch (_err) {
				result.className = 'notice notice-error'
				result.innerHTML = `<p>${i18n?.errorSwitch}</p>`
				button.disabled = false
				button.textContent = originalText
			}
		})()
	})
}

const REFRESH_RELOAD_MS = 1000

const bindRefresh = (
	btn: HTMLButtonElement,
	cfg: VersionConfig
): void => {
	btn.addEventListener('click', (): void => {
		void (async (): Promise<void> => {
			const original = btn.textContent
			btn.disabled = true
			btn.textContent = i18n?.error ?? ''

			try {
				await postForm({
					action: 'code_snippets_refresh_versions',
					nonce: cfg.nonce_refresh ?? ''
				}, cfg)

				btn.textContent = i18n?.refreshed ?? ''
				setTimeout(() => {
					btn.disabled = false
					btn.textContent = original
					window.location.reload()
				}, REFRESH_RELOAD_MS)
			} catch {
				btn.disabled = false
				btn.textContent = original
			}
		})()
	})
}

export const initVersionSwitch = (): void => {
	const currentVersion = getCurrentVersion()
	const config = window.code_snippets_version_switch

	if (!config) {
		throw Error('version switch config missing')
	}

	const button = <HTMLButtonElement | null> document.getElementById('switch-version-btn')
	const dropdown = <HTMLSelectElement | null> document.getElementById('target_version')
	const result = <HTMLDivElement | null> document.getElementById('version-switch-result')
	const refreshBtn = <HTMLButtonElement | null> document.getElementById('refresh-versions-btn')

	if (dropdown) {
		bindDropdown(dropdown, button, currentVersion)
	}

	if (button && dropdown && result) {
		bindSwitch(button, dropdown, result, config, currentVersion)
	}

	if (refreshBtn) {
		bindRefresh(refreshBtn, config)
	}
}
