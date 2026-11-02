import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

const MARKUP_MESSAGE = '<img id="injected-markup" src="x"> Could not switch versions.'

test.describe('Version switcher', () => {
	test('shows the message returned for a switch as text', async ({ page }) => {
		await page.route('**/wp-admin/admin-ajax.php', route =>
			route.request().postData()?.includes('action=code_snippets_switch_version')
				? route.fulfill({
					contentType: 'application/json',
					body: JSON.stringify({ success: false, data: { message: MARKUP_MESSAGE } })
				})
				: route.continue())

		await page.goto(`${URLS.SETTINGS_ADMIN}&section=advanced`)

		const versions = page.getByLabel('Switch to Version:')
		const oldestVersion = await versions.locator('option').last().getAttribute('value')
		await versions.selectOption(oldestVersion ?? '')
		await page.getByRole('button', { name: 'Switch Version' }).click()

		await expect(page.getByText(MARKUP_MESSAGE)).toBeVisible()
		await expect(page.locator('#injected-markup')).toHaveCount(0)
	})
})
