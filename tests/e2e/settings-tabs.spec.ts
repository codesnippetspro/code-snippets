import { expect, test } from '@playwright/test'
import { wpCli } from './helpers/wpCli'
import { URLS } from './helpers/constants'

const TABS = '#settings-sections-tabs'

test.describe('Settings tabs', () => {
	test('shows a success notice after saving the current tab', async ({ page }) => {
		await page.goto(`${URLS.SETTINGS_ADMIN}&section=editing`)
		const editorRows = page.getByRole('spinbutton').first()
		const originalRows = await editorRows.inputValue()

		try {
			await editorRows.fill(String(Number(originalRows) + 1))
			await page.getByRole('button', { name: 'Save Changes' }).click()

			await expect(page).toHaveURL(/section=editing/)
			await expect(page.locator('#setting-error-settings-saved')).toContainText('Settings saved.')
		} finally {
			await wpCli([
				'eval',
				`\\Code_Snippets\\Settings\\update_setting('general', 'visual_editor_rows', ${originalRows});`
			])
		}
	})

	test('switch between rendered sections in place', async ({ page }) => {
		await page.goto(`${URLS.SETTINGS_ADMIN}&section=editing`)

		const wrap = page.locator('.wrap[data-active-tab]')
		await expect(wrap).toHaveAttribute('data-active-tab', 'editing')

		// Mark the document so a full navigation would be detectable below.
		await page.evaluate(() => {
			(<Record<string, boolean>> <unknown> window).csSameDocument = true
		})

		await page.locator(`${TABS} [data-section="running"]`).click()

		await expect(wrap).toHaveAttribute('data-active-tab', 'running')
		await expect(page.locator(`${TABS} [data-section="running"]`)).toHaveClass(/active-type/)
		await expect(page).toHaveURL(/section=running/)

		// Redirections after saving must lead back to the selected tab.
		await expect(page.locator('input[name=_wp_http_referer]')).toHaveValue(/section=running/)

		// The swap happens without reloading the page.
		expect(await page.evaluate(() =>
			(<Record<string, boolean>> <unknown> window).csSameDocument)).toBe(true)

		await page.locator(`${TABS} [data-section="editing"]`).click()
		await expect(wrap).toHaveAttribute('data-active-tab', 'editing')
		await expect(page.locator(`${TABS} [data-section="editing"]`)).toHaveClass(/active-type/)
	})

	test('confirms cache reset from Advanced settings', async ({ page }) => {
		await page.goto(`${URLS.SETTINGS_ADMIN}&section=advanced`)
		await page.getByRole('button', { name: 'Reset Caches' }).click()

		await expect(page.locator('#setting-error-snippet_caches_reset')).toContainText('Successfully reset snippets caches.')
	})

	test('confirms database table upgrade from Advanced settings', async ({ page }) => {
		await page.goto(`${URLS.SETTINGS_ADMIN}&section=advanced`)
		await page.getByRole('button', { name: 'Upgrade Database Table' }).click()

		await expect(page.locator('#setting-error-database_update_done')).toContainText('Successfully performed database table upgrade.')
	})

	test('changes the default snippet save action when Activate by Default is toggled', async ({ page }) => {
		await page.goto(`${URLS.SETTINGS_ADMIN}&section=running`)
		const activateByDefault = page.getByRole('checkbox', { name: /Make the 'Save and Activate' button/ })
		const originalValue = await activateByDefault.isChecked()

		try {
			await activateByDefault.uncheck()
			await page.getByRole('button', { name: 'Save Changes' }).click()
			await page.goto(URLS.ADD_SNIPPET_ADMIN)
			await expect(page.getByRole('button', { name: 'Save Snippet' })).toHaveClass(/button-primary/)

			await page.goto(`${URLS.SETTINGS_ADMIN}&section=running`)
			await page.getByRole('checkbox', { name: /Make the 'Save and Activate' button/ }).check()
			await page.getByRole('button', { name: 'Save Changes' }).click()
			await page.goto(URLS.ADD_SNIPPET_ADMIN)
			await expect(page.getByRole('button', { name: 'Save and Activate' })).toHaveClass(/button-primary/)
		} finally {
			await wpCli([
				'eval',
				`\\Code_Snippets\\Settings\\update_setting('general', 'activate_by_default', ${originalValue ? 'true' : 'false'});`
			])
		}
	})

	test('shows Insights controls for performance tracking and security scanning', async ({ page }) => {
		await page.goto(`${URLS.SETTINGS_ADMIN}&section=insights`)

		await expect(page.getByRole('checkbox', { name: /Track Snippet Performance/ })).toBeVisible({ timeout: 5000 })
		await expect(page.getByRole('checkbox', { name: /Scan Snippets for Security Issues/ })).toBeVisible({ timeout: 5000 })
	})

	test('resets settings to their defaults', async ({ page }) => {
		const originalSettings = (await wpCli(['eval', "echo wp_json_encode(get_option('code_snippets_settings')); "])).trim()

		try {
			await page.goto(`${URLS.SETTINGS_ADMIN}&section=running`)
			await page.getByRole('checkbox', { name: /Make the 'Save and Activate' button/ }).uncheck()
			await page.getByRole('button', { name: 'Save Changes' }).click()
			await page.locator('input[name="code_snippets_settings[reset_settings]"]').click()

			await expect(page.locator('#setting-error-settings_reset')).toContainText(
				'All settings have been reset to their defaults.',
				{ timeout: 5000 }
			)
			await page.goto(URLS.ADD_SNIPPET_ADMIN)
			await expect(page.getByRole('button', { name: 'Save and Activate' })).toHaveClass(/button-primary/)
		} finally {
			await wpCli([
				'eval',
				`update_option('code_snippets_settings', json_decode(${JSON.stringify(originalSettings)}, true));`
			])
		}
	})

	test('stores the Complete Uninstall setting', async ({ page }) => {
		await page.goto(`${URLS.SETTINGS_ADMIN}&section=advanced`)
		const completeUninstall = page.getByRole('checkbox', { name: /also delete all snippets and plugin settings/ })
		const originalValue = await completeUninstall.isChecked()

		try {
			await completeUninstall.uncheck()
			await page.getByRole('button', { name: 'Save Changes' }).click()
			await page.reload()
			await expect(page.getByRole('checkbox', { name: /also delete all snippets and plugin settings/ })).not.toBeChecked()

			await page.getByRole('checkbox', { name: /also delete all snippets and plugin settings/ }).check()
			await page.getByRole('button', { name: 'Save Changes' }).click()
			await page.reload()
			await expect(
				page.getByRole('checkbox', { name: /also delete all snippets and plugin settings/ })
			).toBeChecked({ timeout: 5000 })
		} finally {
			await wpCli([
				'eval',
				`\\Code_Snippets\\Settings\\update_setting('general', 'complete_uninstall', ${originalValue ? 'true' : 'false'});`
			])
		}
	})

	test('shows and hides the admin bar menu when its setting changes', async ({ page }) => {
		await page.goto(`${URLS.SETTINGS_ADMIN}&section=interface`)
		const enableAdminBar = page.getByRole('checkbox', { name: /Show a Snippets menu in the admin bar/ })
		const originalValue = await enableAdminBar.isChecked()

		try {
			await enableAdminBar.uncheck()
			await page.getByRole('button', { name: 'Save Changes' }).click()
			await page.goto(URLS.SNIPPETS_ADMIN)
			await expect(page.locator('#wp-admin-bar-code-snippets')).toHaveCount(0)

			await page.goto(`${URLS.SETTINGS_ADMIN}&section=interface`)
			await page.getByRole('checkbox', { name: /Show a Snippets menu in the admin bar/ }).check()
			await page.getByRole('button', { name: 'Save Changes' }).click()
			await page.goto(URLS.SNIPPETS_ADMIN)
			await expect(page.locator('#wp-admin-bar-code-snippets')).toBeVisible()
		} finally {
			await wpCli([
				'eval',
				`\\Code_Snippets\\Settings\\update_setting('general', 'enable_admin_bar', ${originalValue ? 'true' : 'false'});`
			])
		}
	})
})
