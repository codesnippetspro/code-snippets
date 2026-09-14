import { expect, test } from '@playwright/test'
import { TIMEOUTS, URLS } from './helpers/constants'
import type { Page } from '@playwright/test'

// Community bundles are a capability-only feature: every cloud request is
// mocked at the browser level, so these tests cover the unconnected flow
// without a cloud account, token, or reachable cloud API.

const TOTAL_BUNDLES = 12
const PER_PAGE = 10

const makeBundle = (id: number) => ({
	id,
	name: `Bundle ${id}`,
	description: `Description for bundle ${id}`,
	share_code: `bundle-${id}`,
	snippets_count: 2,
	is_public: true
})

const makeSnippet = (id: number) => ({
	id,
	slug: `snippet-${id}`,
	name: `Snippet ${id}`,
	description: `Fish &amp; chips ${id}`,
	code: '',
	tags: ['community'],
	scope: 'global',
	codevault: 'community',
	total_votes: 0,
	vote_count: 0,
	wp_tested: '6.7',
	status: 4,
	created: '2026-01-01 00:00:00',
	updated: '2026-01-01 00:00:00',
	revision: 1,
	is_owner: false
})

const pageParam = (url: string): number =>
	Number(new URL(url).searchParams.get('page') ?? '1')

// Match both pretty-permalink and `?rest_route=` (URL-encoded) REST requests.
const isShareCodeRequest = (url: URL): boolean =>
	decodeURIComponent(url.href).includes('cloud/community-bundles/share_code/')

const isBundleListRequest = (url: URL): boolean =>
	decodeURIComponent(url.href).includes('cloud/community-bundles') && !isShareCodeRequest(url)

const mockBundleEndpoints = async (page: Page) => {
	await page.route(isBundleListRequest, async route => {
		const current = pageParam(route.request().url())
		const bundleIds = Array.from({ length: TOTAL_BUNDLES }, (_, index) => index + 1)
			.slice((current - 1) * PER_PAGE, current * PER_PAGE)

		await route.fulfill({
			json: {
				bundles: bundleIds.map(makeBundle),
				meta: {
					total: TOTAL_BUNDLES,
					total_pages: Math.ceil(TOTAL_BUNDLES / PER_PAGE),
					page: current,
					per_page: PER_PAGE
				}
			}
		})
	})

	await page.route(isShareCodeRequest, async route => {
		const current = pageParam(route.request().url())
		const snippetIds = Array.from({ length: TOTAL_BUNDLES }, (_, index) => index + 1)
			.slice((current - 1) * PER_PAGE, current * PER_PAGE)

		await route.fulfill({
			json: {
				snippets: snippetIds.map(makeSnippet),
				page: current,
				total_pages: Math.ceil(TOTAL_BUNDLES / PER_PAGE),
				total_snippets: TOTAL_BUNDLES,
				available_filters: []
			}
		})
	})
}

test.describe('Community Bundles', () => {
	const jsErrors: string[] = []
	const privateEndpointRequests: string[] = []

	test.beforeEach(async ({ page }) => {
		jsErrors.length = 0
		privateEndpointRequests.length = 0

		page.on('pageerror', error => {
			jsErrors.push(error.message)
		})

		page.on('request', request => {
			if (request.url().includes('/cloud/snippets/bundle/')) {
				privateEndpointRequests.push(request.url())
			}
		})

		await mockBundleEndpoints(page)
		await page.goto(`${URLS.CLOUD_COMMUNITY_ADMIN}&tab=bundles`)
		await page.waitForLoadState('domcontentloaded')
	})

	test('lists public bundles without a cloud connection', async ({ page }) => {
		const cards = page.locator('.cloud-bundle')
		await expect(cards).toHaveCount(PER_PAGE, { timeout: TIMEOUTS.DEFAULT })

		await expect(cards.first()).toContainText('Bundle 1')

		await expect(cards.first().locator('.public-badge')).toHaveText('Public')
		await expect(page.locator('.private-badge')).toHaveCount(0)

		expect(jsErrors).toHaveLength(0)
	})

	test('navigates to the second page of bundles', async ({ page }) => {
		await expect(page.locator('.cloud-bundle')).toHaveCount(PER_PAGE, { timeout: TIMEOUTS.DEFAULT })

		await page.locator('.tablenav .next-page').click()

		await expect(page.locator('.cloud-bundle')).toHaveCount(TOTAL_BUNDLES - PER_PAGE, { timeout: TIMEOUTS.DEFAULT })
		await expect(page.locator('.cloud-bundle').first()).toContainText(`Bundle ${PER_PAGE + 1}`)
	})

	test('opens bundle snippets through the public endpoint with pagination', async ({ page }) => {
		await expect(page.locator('.cloud-bundle')).toHaveCount(PER_PAGE, { timeout: TIMEOUTS.DEFAULT })

		await page.locator('.cloud-bundle')
			.first()
			.getByRole('button', { name: 'View Snippets' })
			.click()

		const results = page.locator('.cloud-bundle-snippets-results')
		await expect(results).toContainText('All snippets in bundle: Bundle 1', { timeout: TIMEOUTS.DEFAULT })
		await expect(results.locator('.cloud-snippets-table')).toContainText('Snippet 1')

		await expect(results).toContainText('Fish & chips 1')

		await results.locator('.tablenav .next-page').click()
		await expect(results).toContainText(`Snippet ${PER_PAGE + 1}`, { timeout: TIMEOUTS.DEFAULT })

		expect(privateEndpointRequests).toHaveLength(0)
		expect(jsErrors).toHaveLength(0)
	})

	test('entering a share code opens the bundle without a cloud connection', async ({ page }) => {
		await page.locator('.bundle-share-code-form input').fill('bundle-3')
		await page.locator('.bundle-share-code-form')
			.getByRole('button', { name: 'Show Snippets' })
			.click()

		const results = page.locator('.cloud-bundle-snippets-results')
		await expect(results).toContainText('All snippets in bundle: bundle-3', { timeout: TIMEOUTS.DEFAULT })
		await expect(results.locator('.cloud-snippets-table')).toContainText('Snippet 1')

		expect(privateEndpointRequests).toHaveLength(0)
		expect(jsErrors).toHaveLength(0)
	})
})
