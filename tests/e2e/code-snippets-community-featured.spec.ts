import { expect, test } from '@playwright/test'
import { TIMEOUTS, URLS } from './helpers/constants'

test.describe('Community Cloud Featured Snippets', () => {
	const jsErrors: string[] = []

	test.beforeEach(async ({ page }) => {
		jsErrors.length = 0

		page.on('pageerror', error => {
			jsErrors.push(error.message)
		})

		await page.goto(URLS.COMMUNITY_CLOUD)
		await page.waitForLoadState('domcontentloaded')
	})

	test('Page loads without JavaScript errors', async ({ page }) => {
		// Wait for the cloud search form to render, confirming the React app mounted.
		await expect(page.locator('.cloud-search')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		expect(jsErrors).toHaveLength(0)
	})

	test('Featured heading appears or graceful empty state', async ({ page }) => {
		// Wait for the search form — this confirms the page rendered.
		await expect(page.locator('.cloud-search')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		// Wait for the loading spinner to disappear, indicating the featured request completed.
		await page.locator('.cloud-search .components-spinner')
			.waitFor({ state: 'hidden', timeout: TIMEOUTS.DEFAULT })
			.catch(() => undefined)

		const featuredHeading = page.locator('.cloud-snippets-heading', { hasText: 'Featured Snippets' })
		const headingVisible = await featuredHeading
			.waitFor({ state: 'visible', timeout: TIMEOUTS.SHORT })
			.then(() => true)
			.catch(() => false)

		if (headingVisible) {
			await expect(featuredHeading).toContainText('Featured Snippets')
		} else {
			// Cloud API unreachable — verify no crash: the search form is still functional.
			await expect(page.locator('.cloud-search')).toBeVisible()
			await expect(page.locator('#cloud-search-query')).toBeVisible()
		}
	})

	test('Search overrides featured heading', async ({ page }) => {
		// Wait for the page to be ready.
		await expect(page.locator('.cloud-search')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		// Type a search term.
		const searchInput = page.locator('#cloud-search-query')
		await expect(searchInput).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await searchInput.fill('disable comments')

		// Submit the search form.
		await page.locator('.cloud-search-form').getByRole('button', { name: /Search Cloud Library/i }).click()

		// Wait for the search to complete (spinner appears then disappears).
		await page.locator('.cloud-search .components-spinner')
			.waitFor({ state: 'hidden', timeout: TIMEOUTS.DEFAULT })
			.catch(() => undefined)

		// The "Featured Snippets" heading should no longer be visible (search-mode heading replaces it).
		await expect(page.locator('.cloud-snippets-heading', { hasText: 'Featured Snippets' })).not.toBeVisible()
	})
})
