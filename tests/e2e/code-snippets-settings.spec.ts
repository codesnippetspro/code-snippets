import { expect, test } from '@playwright/test'
import { expectCanonicalCheckbox } from './helpers/checkbox'
import { URLS } from './helpers/constants'

test.describe('Code Snippets settings', () => {
	test('Uses the canonical checkbox in settings fields', async ({ page }) => {
		await page.goto(URLS.SETTINGS_ADMIN)

		const checkbox = page.locator('.settings-section:visible input[type="checkbox"]:not(.switch)').first()

		await expect(checkbox).toBeVisible()
		await expectCanonicalCheckbox(checkbox)
		await expect(checkbox).toHaveCSS('margin-inline-end', '8px')
	})
})
