import { test as setup } from '@playwright/test'
import { wpCli } from './helpers/wpCli'

// Put the test user on a right-to-left locale. The language pack is fetched
// from wordpress.org when missing; if that is impossible (offline), the RTL
// specs notice the page is still left-to-right and skip themselves.
const RTL_LOCALE = 'he_IL'
const SETUP_TIMEOUT_MS = 180000

setup('switch the test user to a right-to-left locale', async ({ page }) => {
	setup.setTimeout(SETUP_TIMEOUT_MS)

	try {
		await wpCli(['language', 'core', 'install', RTL_LOCALE])
	} catch (error) {
		console.warn(`Could not install the ${RTL_LOCALE} language pack; RTL specs will skip.`, error)
	}

	await wpCli(['user', 'update', 'admin', `--locale=${RTL_LOCALE}`])

	await page.goto('/wp-admin/')
	await page.waitForSelector('#wpbody-content')
	const dir = await page.evaluate(() => document.documentElement.getAttribute('dir'))
	console.log(`RTL setup: admin renders with dir="${dir ?? 'ltr'}"`)
})
