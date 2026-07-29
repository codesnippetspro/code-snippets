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

	test('Table checkboxes share the cloud selection state', async ({ page }) => {
		await page.route(
			url =>
				url.pathname.includes('/cloud/snippets/featured') ||
				true === url.searchParams.get('rest_route')?.includes('/cloud/snippets/featured'),
			route => route.fulfill({
				contentType: 'application/json',
				body: JSON.stringify({
					snippets: [{
						id: 501,
						slug: 'mock-cloud-snippet',
						name: 'Mock Cloud Snippet',
						description: 'Mock description',
						code: '<?php echo "mock";',
						tags: [],
						scope: 'global',
						codevault: 'MockVault',
						total_votes: 0,
						vote_count: 0,
						wp_tested: '',
						status: 4,
						created: '2026-01-01 00:00:00',
						updated: '2026-01-01 00:00:00',
						revision: 1,
						is_owner: false,
						local_id: null,
						update_available: false
					}],
					page: 1,
					total_pages: 1,
					total_snippets: 1,
					available_filters: {}
				})
			})
		)
		await page.reload()

		const table = page.locator('.cloud-snippets-table')
		await expect(table).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		const headerCheckbox = table.locator('thead').getByRole('checkbox', { name: 'Select all snippets' })
		const rowCheckbox = table.locator('tbody').getByRole('checkbox', { name: 'Select Mock Cloud Snippet' })
		const toolbarCheckbox = page.getByRole('checkbox', { name: 'Select all items' })

		await rowCheckbox.check()
		await expect(rowCheckbox).toBeChecked()
		await expect(headerCheckbox).toBeChecked()
		await expect(toolbarCheckbox).toBeChecked()

		await headerCheckbox.uncheck()
		await expect(rowCheckbox).not.toBeChecked()
		await expect(toolbarCheckbox).not.toBeChecked()
	})
})
