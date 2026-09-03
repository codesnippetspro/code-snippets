import { expect, test } from '@playwright/test'

// CodeMirror replaces the labelled textarea with an input of its own, so the
// name a screen reader hears comes from the attribute the plugin sets on that
// input after the editor starts. One check per editor the plugin creates.
test.describe('Code editor labels', () => {
	test('the snippet editor input is named', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=add-snippet')
		await page.waitForSelector('.CodeMirror')

		await expect(page.locator('.snippet-editor .CodeMirror textarea')).toHaveAttribute('aria-label', 'Snippet code')
	})

	test('the settings preview input is named', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=snippets-settings&section=editing')
		await page.waitForSelector('.CodeMirror')

		await expect(page.locator('.CodeMirror textarea').first()).toHaveAttribute('aria-label', 'Code editor preview')
	})
})
