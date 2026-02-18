import { join } from 'path'
import { expect, test as setup } from '@playwright/test'
import { wpCli } from './helpers/wpCli'

const authFile = join(__dirname, '.auth/user.json')
const AUTH_SETUP_TIMEOUT_MS = 120000

setup('authenticate', async ({ page }) => {
	setup.setTimeout(AUTH_SETUP_TIMEOUT_MS)

	// Ensure a clean environment across local runs / retries.
	// If Safe Mode is enabled via `wp-config.php` it disables snippet execution and can
	// break unrelated tests (e.g., those expecting snippets to run).
	try {
		await wpCli(['config', 'delete', 'CODE_SNIPPETS_SAFE_MODE'])
	} catch {
		// Ignore if the constant isn't present.
	}

	// CI sometimes boots with WordPress already installed (so the workflow's
	// `wp core install --admin_password=...` step is skipped). Ensure the admin
	// credentials are set to the expected values before logging in via UI.
	try {
		await wpCli(['user', 'update', 'admin', '--user_pass=password'])
	} catch {
		// If the user doesn't exist, create it (local/wp-env + CI both support this).
		await wpCli([
			'user',
			'create',
			'admin',
			'admin@example.org',
			'--user_pass=password',
			'--role=administrator'
		])
	}

	await page.goto('/wp-login.php')
	await page.waitForSelector('#user_login')

	await page.fill('#user_login', 'admin')
	await page.fill('#user_pass', 'password')

	await Promise.all([
		page.waitForLoadState('domcontentloaded'),
		page.click('#wp-submit')
	])

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
