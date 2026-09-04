import { expect, test as setup } from '@playwright/test'

setup('enable flat files', async ({ page }) => {
	const isMultisite = 'true' === process.env.WP_E2E_MULTISITE_MODE || '1' === process.env.WP_E2E_MULTISITE_MODE
	const wpAdminbase = isMultisite ? '/wp-admin/network' : '/wp-admin'
  
	// The flat files switch lives on the Running tab; the other tabs are hidden.
	const settingsUrl = `${wpAdminbase}/admin.php?page=snippets-settings&section=running`

	await page.goto(settingsUrl)
	await page.waitForSelector('#wpbody-content')

	// Await page.waitForSelector('form')

	const flatFilesCheckbox = page.locator('input[name="code_snippets_settings[general][enable_flat_files]"]')
	await expect(flatFilesCheckbox).toBeVisible()
	
	const isChecked = await flatFilesCheckbox.isChecked()
	if (!isChecked) {
		await flatFilesCheckbox.check()
	}

	// Await page.click('input[type="submit"][name="submit"]')
	
	// await page.waitForSelector('.notice-success', { timeout: 10000 })
	// await expect(page.locator('.notice-success')).toContainText('Settings saved')
	const saveButton = page.getByRole('button', { name: 'Save Changes' })

	await Promise.all([
		page.waitForURL(/settings-updated=true/, { timeout: 10000 }),
		saveButton.click()
	])


	await page.goto(settingsUrl)
	await page.waitForSelector('input[name="code_snippets_settings[general][enable_flat_files]"]')
	await expect(page.locator('input[name="code_snippets_settings[general][enable_flat_files]"]')).toBeChecked()
})
