import { expect, test } from '@playwright/test'
import { TIMEOUTS, URLS } from './helpers/constants'
import type { Page } from '@playwright/test'

const switchSnippetView = async (page: Page, view: 'Card view' | 'Table view') => {
	const saved = page
		.waitForResponse(
			response => response.url().includes('/snippet-view') && 'GET' !== response.request().method(),
			{ timeout: TIMEOUTS.SHORT }
		)
		.catch(() => undefined)
	await page.getByRole('button', { name: view }).click()
	await saved
}

const isFeaturedRequest = (url: URL): boolean =>
	url.pathname.includes('/cloud/snippets/featured') ||
	true === url.searchParams.get('rest_route')?.includes('/cloud/snippets/featured')

const isSnippetDownloadRequest = (url: URL): boolean =>
	url.pathname.includes('/cloud/snippets/501/download') ||
	true === url.searchParams.get('rest_route')?.includes('/cloud/snippets/501/download')

const makeCloudSnippet = (id: number, name: string, localId: number | null = null) => ({
	id,
	slug: `mock-cloud-snippet-${id}`,
	name,
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
	local_id: localId,
	update_available: false
})

const makeFeaturedResponse = (snippets = [makeCloudSnippet(501, 'Mock Cloud Snippet')]) => ({
	snippets,
	page: 1,
	total_pages: 1,
	total_snippets: snippets.length,
	available_filters: {}
})

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

	test('Shows a page-level notice while featured snippets load', async ({ page }) => {
		let releaseRequest: () => void = () => undefined
		const requestPending = new Promise<void>(resolve => {
			releaseRequest = () => resolve()
		})

		await page.route(isFeaturedRequest, async route => {
			await requestPending
			await route.fulfill({
				contentType: 'application/json',
				body: JSON.stringify(makeFeaturedResponse())
			})
		})

		await page.reload()

		try {
			await expect(page.getByRole('status', { name: 'Community snippets status' }))
				.toContainText('Loading community snippets…')
		} finally {
			releaseRequest()
		}

		await expect(page.locator('.cloud-search .notice')).toBeHidden()
	})

	test('Shows a page-level notice when featured snippets fail to load', async ({ page }) => {
		await page.route(isFeaturedRequest, route => route.fulfill({
			status: 500,
			contentType: 'application/json',
			body: JSON.stringify({ message: 'Cloud unavailable' })
		}))
		await page.reload()

		await expect(page.getByRole('alert', { name: 'Community snippets status' }))
			.toContainText('An error occurred while fetching search results. Please try again.')
	})

	test('Preview offers the same download or edit action as its card', async ({ page }) => {
		await page.route(isFeaturedRequest, route => route.fulfill({
			contentType: 'application/json',
			body: JSON.stringify(makeFeaturedResponse([
				makeCloudSnippet(501, 'Downloadable Cloud Snippet'),
				makeCloudSnippet(502, 'Installed Cloud Snippet', 42)
			]))
		}))
		await page.reload()

		const openPreview = async (snippetName: string) => {
			await page.getByRole('button', { name: snippetName }).click()
			await expect(page.locator('.code-snippets-preview-modal')).toBeVisible()
		}

		await openPreview('Downloadable Cloud Snippet')
		await expect(page.locator('.code-snippets-preview-modal').getByRole('button', { name: 'Download' }))
			.toBeVisible()
		await page.getByRole('button', { name: 'Close' }).click()

		await openPreview('Installed Cloud Snippet')
		await expect(page.locator('.code-snippets-preview-modal').getByRole('link', { name: 'Edit' }))
			.toBeVisible()
	})

	test('Shares pending and downloaded state before refresh completes', async ({ page }) => {
		let releaseDownload: () => void = () => undefined
		let releaseRefresh: () => void = () => undefined
		const downloadPending = new Promise<void>(resolve => {
			releaseDownload = () => resolve()
		})
		const refreshPending = new Promise<void>(resolve => {
			releaseRefresh = () => resolve()
		})
		let downloadRequests = 0
		let featuredRequests = 0

		await page.route(isFeaturedRequest, async route => {
			featuredRequests += 1

			if (1 < featuredRequests) {
				await refreshPending
			}

			return route.fulfill({
				contentType: 'application/json',
				body: JSON.stringify(makeFeaturedResponse([
					makeCloudSnippet(501, 'Downloadable Cloud Snippet')
				]))
			})
		})
		await page.route(isSnippetDownloadRequest, async route => {
			downloadRequests += 1
			await downloadPending
			return route.fulfill({
				contentType: 'application/json',
				body: JSON.stringify({ success: true, snippet_id: 42, link_id: 501 })
			})
		})
		await page.reload()

		await switchSnippetView(page, 'Card view')

		try {
			const card = page.locator('.cloud-search-result', { hasText: 'Downloadable Cloud Snippet' })
			await card.getByRole('button', { name: 'Downloadable Cloud Snippet' }).click()

			const preview = page.locator('.code-snippets-preview-modal')
			await preview.getByRole('button', { name: 'Download' }).click()

			await expect(preview.getByRole('button', { name: 'Download' })).toBeDisabled()
			await expect(card.getByRole('button', { name: 'Download' })).toBeDisabled()

			releaseDownload()
			await expect(preview.getByRole('link', { name: 'Edit' })).toBeVisible()
			await expect.poll(() => featuredRequests).toBe(2)
			await page.getByRole('button', { name: 'Close' }).click()

			await expect(card.getByRole('link', { name: 'Edit' })).toBeVisible()
			await expect(card.getByRole('button', { name: 'Download' })).toHaveCount(0)
			expect(downloadRequests).toBe(1)
		} finally {
			releaseDownload()
			releaseRefresh()
			await page.getByRole('button', { name: 'Close' }).click().catch(() => undefined)
			await switchSnippetView(page, 'Table view').catch(() => undefined)
		}
	})

	test('Keeps downloaded state when refresh fails', async ({ page }) => {
		let downloadRequests = 0
		let featuredRequests = 0

		await page.route(isFeaturedRequest, route => {
			featuredRequests += 1

			return 2 === featuredRequests
				? route.fulfill({
					status: 500,
					contentType: 'application/json',
					body: JSON.stringify({ message: 'Cloud unavailable' })
				})
				: route.fulfill({
					contentType: 'application/json',
					body: JSON.stringify(makeFeaturedResponse([
						makeCloudSnippet(501, 'Downloadable Cloud Snippet')
					]))
				})
		})
		await page.route(isSnippetDownloadRequest, route => {
			downloadRequests += 1
			return route.fulfill({
				contentType: 'application/json',
				body: JSON.stringify({ success: true, snippet_id: 42, link_id: 501 })
			})
		})
		await page.reload()

		await switchSnippetView(page, 'Card view')

		try {
			const card = page.locator('.cloud-search-result', { hasText: 'Downloadable Cloud Snippet' })
			await card.getByRole('button', { name: 'Downloadable Cloud Snippet' }).click()

			const preview = page.locator('.code-snippets-preview-modal')
			await preview.getByRole('button', { name: 'Download' }).click()

			await expect(page.getByRole('alert', { name: 'Community snippets status' }))
				.toContainText('An error occurred while fetching search results. Please try again.')

			await page.locator('.cloud-search-form')
				.getByRole('button', { name: 'Search Cloud Library' })
				.click()

			await expect(card.getByRole('link', { name: 'Edit' })).toBeVisible()
			await expect(card.getByRole('button', { name: 'Download' })).toHaveCount(0)
			expect(downloadRequests).toBe(1)
		} finally {
			await page.getByRole('button', { name: 'Close' }).click().catch(() => undefined)
			await switchSnippetView(page, 'Table view').catch(() => undefined)
		}
	})

	test('Table checkboxes share the cloud selection state', async ({ page }) => {
		await page.route(isFeaturedRequest, route => route.fulfill({
			contentType: 'application/json',
			body: JSON.stringify(makeFeaturedResponse())
		}))
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
