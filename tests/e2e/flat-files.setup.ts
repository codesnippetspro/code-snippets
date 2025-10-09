import { expect, test as setup } from '@playwright/test'

setup('enable flat files', async ({ page }) => {
	await page.goto('/wp-admin/admin.php?page=snippets-settings')
	await page.waitForSelector('#wpbody-content')

	await page.waitForSelector('form')

	const flatFilesCheckbox = page.locator('input[name="code_snippets_settings[general][enable_flat_files]"]')
	await expect(flatFilesCheckbox).toBeVisible()
	
	const isChecked = await flatFilesCheckbox.isChecked()
	if (!isChecked) {
		await flatFilesCheckbox.check()
	}

	await page.click('input[type="submit"][name="submit"]')
	
	await page.waitForSelector('.notice-success', { timeout: 10000 })
	await expect(page.locator('.notice-success')).toContainText('Settings saved')

	await page.reload()
	await page.waitForSelector('input[name="code_snippets_settings[general][enable_flat_files]"]')
	await expect(page.locator('input[name="code_snippets_settings[general][enable_flat_files]"]')).toBeChecked()
})
