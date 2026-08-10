import { expect, test } from '@playwright/test'

const SETTINGS_URL = '/wp-admin/admin.php?page=snippets-settings&section=general'
const TABS = '#settings-sections-tabs'

test.describe('Settings tabs', () => {
	test('switch between rendered sections in place', async ({ page }) => {
		await page.goto(SETTINGS_URL)

		const wrap = page.locator('.wrap[data-active-tab]')
		await expect(wrap).toHaveAttribute('data-active-tab', 'general')

		// Mark the document so a full navigation would be detectable below.
		await page.evaluate(() => {
			(<Record<string, boolean>> <unknown> window).csSameDocument = true
		})

		await page.locator(`${TABS} [data-section="editor"]`).click()

		await expect(wrap).toHaveAttribute('data-active-tab', 'editor')
		await expect(page.locator(`${TABS} [data-section="editor"]`)).toHaveClass(/active-type/)
		await expect(page).toHaveURL(/section=editor/)

		// Redirections after saving must lead back to the selected tab.
		await expect(page.locator('input[name=_wp_http_referer]')).toHaveValue(/section=editor/)

		// The swap happens without reloading the page.
		expect(await page.evaluate(() =>
			(<Record<string, boolean>> <unknown> window).csSameDocument)).toBe(true)

		await page.locator(`${TABS} [data-section="general"]`).click()
		await expect(wrap).toHaveAttribute('data-active-tab', 'general')
		await expect(page.locator(`${TABS} [data-section="general"]`)).toHaveClass(/active-type/)
	})
})
