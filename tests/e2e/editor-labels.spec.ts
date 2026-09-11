import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

// The code editor's editable region is not a form control, so the name a
// screen reader hears comes from the attribute the plugin gives it. One check
// per editor the plugin creates.
test.describe('Code editor labels', () => {
	test('the snippet editor input is named', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForSelector('.cm-editor')

		await expect(page.locator('.snippet-editor .cm-content')).toHaveAttribute('aria-label', 'Snippet code')
	})

	test('the settings preview input is named', async ({ page }) => {
		await page.goto(`${URLS.SETTINGS_ADMIN}&section=editing`)
		await page.waitForSelector('.cm-editor')

		await expect(page.locator('.cm-content').first()).toHaveAttribute('aria-label', 'Code editor preview')
	})
})
