import { writeFileSync } from 'fs'
import { expect, test as setup } from '@playwright/test'
import { RTL_LOCALE, RTL_USER, rtlAuthFile, rtlCreatedMarker } from './helpers/rtlUser'
import { wpCli } from './helpers/wpCli'

// The RTL specs sign in as a user of their own whose locale is right-to-left,
// so the rest of the suite, which signs in as the usual admin, never sees the
// site mirrored, whatever order the projects run in. The language pack is
// fetched from wordpress.org when missing; if that is impossible (offline),
// the specs notice the page is still left-to-right and skip themselves.
const SETUP_TIMEOUT_MS = 180000

setup('sign in as a right-to-left user', async ({ page }) => {
	setup.setTimeout(SETUP_TIMEOUT_MS)

	try {
		await wpCli(['language', 'core', 'install', RTL_LOCALE])
	} catch (error) {
		console.warn(`Could not install the ${RTL_LOCALE} language pack; RTL specs will skip.`, error)
	}

	// `user create` takes no locale flag, so the locale is set by a second command.
	let created = false

	try {
		await wpCli(['user', 'get', RTL_USER, '--field=ID'])
	} catch {
		await wpCli(['user', 'create', RTL_USER, `${RTL_USER}@example.org`, '--role=administrator'])
		created = true
	}

	writeFileSync(rtlCreatedMarker, created ? 'created' : 'existing')
	await wpCli(['user', 'update', RTL_USER, '--user_pass=password', `--locale=${RTL_LOCALE}`])

	await page.goto('/wp-login.php')
	await page.waitForSelector('#user_login')
	await page.fill('#user_login', RTL_USER)
	await page.fill('#user_pass', 'password')
	await Promise.all([
		page.waitForLoadState('domcontentloaded'),
		page.click('#wp-submit')
	])
	await page.waitForSelector('#wpbody-content, #adminmenu', { timeout: 60000 })
	await expect(page.locator('#adminmenu')).toBeVisible()

	const dir = await page.evaluate(() => document.documentElement.getAttribute('dir'))
	console.log(`RTL setup: ${RTL_USER} renders the admin with dir="${dir ?? 'ltr'}"`)

	await page.context().storageState({ path: rtlAuthFile })
})
