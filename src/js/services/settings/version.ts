// Handles version switching UI on the settings screen.
// Exported init function so callers can opt-in like other settings modules.
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
		__code_snippets_i18n?: Record<string, string>
	}
}

const el = (id: string): HTMLElement | null => document.getElementById(id)

const getConfig = (): VersionConfig => {
	const w = <{ code_snippets_version_switch?: VersionConfig }><unknown>window
	return w.code_snippets_version_switch ?? {}
}

const getCurrentVersion = (): string => (document.querySelector('.current-version')?.textContent ?? '').trim()

const getI18n = (key: string, fallback: string): string => window.__code_snippets_i18n?.[key] ?? fallback

const bindDropdown = (
	dropdown: HTMLSelectElement,
	button: HTMLButtonElement | null,
	currentVersion: string,
): void => {
	dropdown.addEventListener('change', (): void => {
		const selectedVersion = dropdown.value
		if (!button) {
			return
		}
		if (!selectedVersion || selectedVersion === currentVersion) {
			button.disabled = true
			const warn = el('version-switch-warning')
			if (warn) { warn.setAttribute('style', 'display: none;') }
		} else {
			button.disabled = false
			const warn = el('version-switch-warning')
			if (warn) { warn.setAttribute('style', '') }
		}
	})
}

const SUCCESS_RELOAD_MS = 3000

const postForm = async (data: Record<string, string>, cfg: VersionConfig): Promise<AjaxResponse> => {
	const body = new URLSearchParams()
	Object.keys(data).forEach(k => body.append(k, data[k]))
	const resp = await fetch(cfg.ajaxurl ?? '/wp-admin/admin-ajax.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
		body: body.toString(),
		credentials: 'same-origin',
	})
	const json = <AjaxResponse> await resp.json()
	return json
}

const bindSwitch = (
	button: HTMLButtonElement,
	dropdown: HTMLSelectElement,
	result: HTMLDivElement,
	cfg: VersionConfig,
	currentVersion: string,
): void => {
	button.addEventListener('click', (): void => {
		void (async (): Promise<void> => {
			const targetVersion = dropdown.value
			if (!targetVersion || targetVersion === currentVersion) {
				result.className = 'notice notice-warning'
				result.innerHTML = `<p>${getI18n('selectDifferent', 'Please select a different version to switch to.')}</p>`
				result.style.display = ''
				return
			}

			button.disabled = true
			const originalText = button.textContent ?? ''
			button.textContent = getI18n('switching', 'Switching...')

			result.className = 'notice notice-info'
			result.innerHTML = `<p>${getI18n('processing', 'Processing version switch. Please wait...')}</p>`
			result.style.display = ''

			try {
				const response = await postForm({
					action: 'code_snippets_switch_version',
					target_version: targetVersion,
					nonce: cfg.nonce_switch ?? '',
				}, cfg)

				if (response.success) {
					result.className = 'notice notice-success'
					result.innerHTML = `<p>${response.data?.message ?? ''}</p>`
					setTimeout(() => window.location.reload(), SUCCESS_RELOAD_MS)
					return
				}

				result.className = 'notice notice-error'
				result.innerHTML = `<p>${response.data?.message ?? getI18n('error', 'An error occurred.')}</p>`
				button.disabled = false
				button.textContent = originalText
			} catch (_err) {
				result.className = 'notice notice-error'
				result.innerHTML = `<p>${getI18n('errorSwitch', 'An error occurred while switching versions. Please try again.')}</p>`
				button.disabled = false
				button.textContent = originalText
			}
		})()
	})
}

const REFRESH_RELOAD_MS = 1000

const bindRefresh = (
	btn: HTMLButtonElement,
	cfg: VersionConfig,
): void => {
	btn.addEventListener('click', (): void => {
		void (async (): Promise<void> => {
			const original = btn.textContent ?? ''
			btn.disabled = true
			btn.textContent = getI18n('refreshing', 'Refreshing...')

			try {
				await postForm({
					action: 'code_snippets_refresh_versions',
					nonce: cfg.nonce_refresh ?? '',
				}, cfg)

				btn.textContent = getI18n('refreshed', 'Refreshed!')
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
	const cfg = getConfig()
	const currentVersion = getCurrentVersion()

	const button = <HTMLButtonElement | null> el('switch-version-btn')
	const dropdown = <HTMLSelectElement | null> el('target_version')
	const result = <HTMLDivElement | null> el('version-switch-result')
	const refreshBtn = <HTMLButtonElement | null> el('refresh-versions-btn')

	if (dropdown) {
		bindDropdown(dropdown, button, currentVersion)
	}

	if (button && dropdown && result) {
		bindSwitch(button, dropdown, result, cfg, currentVersion)
	}

	if (refreshBtn) {
		bindRefresh(refreshBtn, cfg)
	}
}


