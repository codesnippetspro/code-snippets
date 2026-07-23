import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'

const WHATS_NEW_SEEN_META_KEY = 'code_snippets_whats_new_seen_version'

test.describe("What's New unseen release indicator", () => {
	test.afterEach(async () => {
		await wpCli(['user', 'meta', 'delete', 'admin', WHATS_NEW_SEEN_META_KEY])
	})

	test('Dot clears after opening the page and stays cleared', async ({ page }) => {
		await wpCli(['user', 'meta', 'update', 'admin', WHATS_NEW_SEEN_META_KEY, '1.0.0'])
		await page.goto(URLS.SNIPPETS_ADMIN)

		const upperNav = page.locator('.code-snippets-toolbar-upper')
		const whatsNewLink = upperNav.getByRole('link', { name: /What's New/ })
		await expect(whatsNewLink.locator('.nav-dot')).toBeVisible()
		await expect(whatsNewLink).toContainText('New content available')

		await whatsNewLink.click()
		await expect(page).toHaveURL(URLS.WELCOME_SCREEN_ADMIN)
		await expect(upperNav.getByRole('link', { name: /What's New/ }).locator('.nav-dot')).toBeHidden()

		const currentVersion = (await wpCli(['eval', 'echo CODE_SNIPPETS_VERSION;'])).trim()
		const seenVersion =
			(await wpCli(['user', 'meta', 'get', 'admin', WHATS_NEW_SEEN_META_KEY])).trim()
		expect(seenVersion).toBe(currentVersion)

		await page.goto(URLS.SNIPPETS_ADMIN)
		await expect(upperNav.getByRole('link', { name: /What's New/ }).locator('.nav-dot')).toBeHidden()
	})
})
