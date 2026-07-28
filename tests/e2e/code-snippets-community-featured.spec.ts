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

const closePreviewIfOpen = async (page: Page) => {
	const closeButton = page.getByRole('button', { name: 'Close' })

	if (await closeButton.isVisible()) {
		await closeButton.click()
	}
}

const openCommunityCloud = async (page: Page) => {
	await page.goto(URLS.COMMUNITY_CLOUD)
	await page.waitForLoadState('domcontentloaded')
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

	test.beforeEach(({ page }) => {
		jsErrors.length = 0

		page.on('pageerror', error => {
			jsErrors.push(error.message)
		})
	})

	test('Page loads without JavaScript errors', async ({ page }) => {
		await openCommunityCloud(page)

		// Wait for the cloud search form to render, confirming the React app mounted.
		await expect(page.locator('.cloud-search')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		expect(jsErrors).toHaveLength(0)
	})

	test('Featured heading appears or graceful empty state', async ({ page }) => {
		await openCommunityCloud(page)

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
		await openCommunityCloud(page)

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

	test('Announces loading and error states while featured snippets resolve', async ({ page }) => {
		let releaseRequest: () => void = () => undefined
		const requestPending = new Promise<void>(resolve => {
			releaseRequest = () => resolve()
		})

		await page.route(isFeaturedRequest, async route => {
			await requestPending
			return route.fulfill({
				status: 500,
				contentType: 'application/json',
				body: JSON.stringify({ message: 'Cloud unavailable' })
			})
		})
		await openCommunityCloud(page)

		const loadingNotice = page.getByRole('status', { name: 'Community snippets status' })
		await expect(loadingNotice).toHaveClass(/code-snippets-notice/)
		await expect(loadingNotice).toContainText('Loading community snippets…')

		releaseRequest()

		const errorNotice = page.getByRole('alert', { name: 'Community snippets status' })
		await expect(errorNotice).toHaveClass(/code-snippets-notice/)
		await expect(errorNotice)
			.toContainText('An error occurred while fetching search results. Please try again.')
	})

	test('Shares download state between the card and its preview', async ({ page }) => {
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

		// The refresh that follows a download fails, so the shared state is the only
		// thing that can keep both mounts showing the snippet as downloaded.
		await page.route(isFeaturedRequest, async route => {
			featuredRequests += 1

			if (1 < featuredRequests) {
				await refreshPending
				return route.fulfill({
					status: 500,
					contentType: 'application/json',
					body: JSON.stringify({ message: 'Cloud unavailable' })
				})
			}

			return route.fulfill({
				contentType: 'application/json',
				body: JSON.stringify(makeFeaturedResponse([
					makeCloudSnippet(501, 'Downloadable Cloud Snippet'),
					makeCloudSnippet(502, 'Installed Cloud Snippet', 42)
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
		await openCommunityCloud(page)

		await switchSnippetView(page, 'Card view')

		try {
			const preview = page.locator('.code-snippets-preview-modal')

			// A snippet that is already installed offers editing rather than downloading.
			await page.getByRole('button', { name: 'Installed Cloud Snippet' }).click()
			await expect(preview.getByRole('link', { name: 'Edit' })).toBeVisible()
			await page.getByRole('button', { name: 'Close' }).click()

			const card = page.locator('.cloud-search-result', { hasText: 'Downloadable Cloud Snippet' })
			const cardActions = card.locator('.snippet-card-footer-actions')
			await card.getByRole('button', { name: 'Downloadable Cloud Snippet' }).click()
			await preview.getByRole('button', { name: 'Download' }).click()

			// Both mounts show the download as pending before the request resolves.
			await expect(preview.getByRole('button', { name: 'Download' })).toBeDisabled()
			await expect(cardActions.getByRole(
				'button',
				{ name: 'Download', exact: true, includeHidden: true }
			)).toBeDisabled()

			releaseDownload()

			// The preview updates as soon as the download resolves, without waiting for
			// the refresh, which then fails.
			await expect(preview.getByRole('link', { name: 'Edit' })).toBeVisible()
			releaseRefresh()
			await expect(page.getByRole('alert', { name: 'Community snippets status' }))
				.toContainText('An error occurred while fetching search results. Please try again.')

			await page.getByRole('button', { name: 'Close' }).click()

			await expect(cardActions.getByRole('link', { name: 'Edit', exact: true })).toBeVisible()
			await expect(cardActions.getByRole('button', { name: 'Download', exact: true })).toHaveCount(0)
			expect(downloadRequests).toBe(1)
		} finally {
			releaseDownload()
			releaseRefresh()
			await closePreviewIfOpen(page)
			await switchSnippetView(page, 'Table view').catch(() => undefined)
		}
	})

	test('Table checkboxes share the cloud selection state', async ({ page }) => {
		await page.route(isFeaturedRequest, route => route.fulfill({
			contentType: 'application/json',
			body: JSON.stringify(makeFeaturedResponse())
		}))
		await openCommunityCloud(page)
		await switchSnippetView(page, 'Table view')

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
