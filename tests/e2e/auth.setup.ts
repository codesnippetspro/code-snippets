import { join } from 'path'
import { expect, test as setup } from '@playwright/test'

const authFile = join(__dirname, '.auth/user.json')

setup('authenticate', async ({ page }) => {
	await page.goto('/wp-login.php')
	await page.waitForSelector('#user_login')

	await page.fill('#user_login', 'admin')
	await page.fill('#user_pass', 'password')

	await page.click('#wp-submit')

	// If WordPress shows the DB upgrade interstitial it includes a link to
	// `upgrade.php`. In that case navigate back to `/wp-admin` (the upgrade
	// process is handled by the environment) and then wait for the admin UI.
	const upgradeLink = page.locator('a[href*="upgrade.php"]')
	if (0 < await upgradeLink.count()) {
		// Click the upgrade link to reach the upgrade interstitial page.
		await upgradeLink.first().click()

		// If the interstitial shows an "Update WordPress Database" action, click it.
		const updateBtn = page.locator('a:has-text("Update WordPress Database")')
		if (0 < await updateBtn.count()) {
			await updateBtn.first().click()
		}
		// Give the upgrade process more time to complete and the admin UI to load.
		await page.waitForSelector('#wpbody-content, #adminmenu', { timeout: 120000 })
	} else {
		// Normal path: wait for admin UI.
		await page.waitForSelector('#wpbody-content, #adminmenu', { timeout: 60000 })
	}

	await expect(page.locator('#adminmenu')).toBeVisible()

	await page.context().storageState({ path: authFile })
})
