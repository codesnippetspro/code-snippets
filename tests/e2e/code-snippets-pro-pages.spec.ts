import { expect, test } from '@playwright/test'
import { CloudStatus } from '../../src/js/types/schema/CloudSnippetSchema'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { wpCli } from './helpers/wpCli'
import { URLS } from './helpers/constants'
import type { Page, Route } from '@playwright/test'
import type { CloudSnippetSchema } from '../../src/js/types/schema/CloudSnippetSchema'
import type { CloudSnippetsSchema } from '../../src/js/types/schema/CloudSnippetsSchema'

const CLOUD_SNIPPETS_PATH = '/code-snippets/v1/cloud/snippets'
const CLOUD_BUNDLES_PATH = '/code-snippets/v1/cloud/bundles'

const cloudSnippet = (id: number, name: string): CloudSnippetSchema => ({
	id,
	slug: `snippet-${id}`,
	name,
	description: `${name} description`,
	code: `// ${name}`,
	tags: ['Testing'],
	scope: 'global',
	codevault: 'E2E Library',
	total_votes: 0,
	vote_count: 0,
	wp_tested: '6.8',
	status: CloudStatus.Public,
	created: '2026-01-01 00:00:00',
	updated: '2026-01-01 00:00:00',
	revision: 1,
	is_owner: false,
	local_id: null,
	update_available: false
})

const cloudSnippets = (
	snippets: CloudSnippetSchema[],
	props?: { page?: number, totalPages?: number, totalSnippets?: number }
): CloudSnippetsSchema => ({
	snippets,
	page: props?.page ?? 1,
	total_pages: props?.totalPages ?? 1,
	total_snippets: props?.totalSnippets ?? snippets.length,
	available_filters: {}
})

const fulfillJson = (route: Route, json: unknown) => route.fulfill({ json })

const restPath = (requestUrl: string): string => {
	const url = new URL(requestUrl)

	return url.searchParams.get('rest_route') ?? url.pathname
}

const selectSnippetView = async (page: Page, view: 'Card' | 'Table') => {
	const preferenceSaved = page.waitForResponse(response =>
		'POST' === response.request().method() &&
		restPath(response.url()).endsWith('/preferences/snippet-view') &&
		response.ok())

	await page.getByRole('button', { name: `${view} view` }).click()
	await preferenceSaved
}

test.describe('Pro manage page interactions', () => {
	test.beforeAll(async () => {
		await wpCli([
			'option',
			'update',
			'code_snippets_cloud_settings',
			JSON.stringify({
				cloud_token: 'e2e-cloud-token',
				local_token: 'e2e-local-token',
				token_verified: true,
				code_verifier: '',
				code_challenge: '',
				state: ''
			}),
			'--format=json'
		])
	})

	test.afterAll(async () => {
		await wpCli(['option', 'delete', 'code_snippets_cloud_settings'])
	})

	test('cloud sync status keeps its accessible name at tablet widths', async ({ page }) => {
		const snippetName = SnippetsTestHelper.makeUniqueSnippetName('E2E Cloud status')
		await SnippetsTestHelper.createSnippetViaCli({ name: snippetName, active: false })

		try {
			await page.route('**/*', async route => {
				const request = route.request()

				if ('GET' !== request.method() || !restPath(request.url()).endsWith('/snippets')) {
					return route.fallback()
				}

				const response = await route.fetch()
				const snippets = <Record<string, unknown>[]> await response.json()

				return fulfillJson(route, snippets.map(snippet => snippetName === snippet.name
					? { ...snippet, cloud_id: 901, cloud_update_available: false }
					: snippet))
			})

			await page.setViewportSize({ width: 1200, height: 800 })
			await page.goto(URLS.SNIPPETS_ADMIN)
			await page.getByRole('button', { name: 'Card view' }).click()

			const card = page.locator('.code-snippets-card').filter({ hasText: snippetName })
			const status = card.getByRole('img', { name: 'Synced' })

			await expect(status).toHaveAccessibleName('Synced')
			await expect(status.locator('.cloud-sync-status-label')).toBeVisible()
			await expect(status.locator('svg')).toHaveAttribute('aria-hidden', 'true')
			await expect(status.locator('svg')).toHaveAttribute('focusable', 'false')
		} finally {
			await SnippetsTestHelper.cleanupSnippetsByPrefix(snippetName)
		}
	})

	test('new search results discard the previous cloud selection', async ({ page }) => {
		const selectedResult = cloudSnippet(101, 'Selected result')
		const replacementResult = cloudSnippet(202, 'Replacement result')
		const downloadedIds: number[] = []

		await page.route('**/*', route => {
			const request = route.request()
			const path = restPath(request.url())
			const downloadMatch = /\/cloud\/snippets\/(?<id>\d+)\/download$/u.exec(path)

			if (!path.startsWith(CLOUD_SNIPPETS_PATH)) {
				return route.fallback()
			}

			if ('POST' === request.method() && downloadMatch?.groups) {
				downloadedIds.push(Number(downloadMatch.groups.id))
				return fulfillJson(route, {})
			}

			if (path.endsWith('/cloud/snippets/featured')) {
				return fulfillJson(route, cloudSnippets([selectedResult]))
			}

			if (path.endsWith('/cloud/snippets')) {
				return fulfillJson(route, cloudSnippets([replacementResult]))
			}

			return route.fallback()
		})

		await page.goto(URLS.CLOUD_COMMUNITY_ADMIN)
		await page.getByRole('button', { name: 'Card view' }).click()
		await page.getByRole('checkbox', { name: `Select ${selectedResult.name}` }).check()

		const bulkAction = page.getByLabel('Select bulk action').first()
		await expect(bulkAction.locator('option:checked')).toHaveText('Bulk actions')
		await bulkAction.selectOption('download')

		await page.locator('#cloud-search-query').fill('replacement')
		await page.getByRole('button', { name: 'Search Cloud Library' }).click()
		await expect(page.getByText(replacementResult.name, { exact: true })).toBeVisible()
		await expect(page.getByText(selectedResult.name, { exact: true })).toHaveCount(0)

		await expect(page.getByRole('checkbox', { name: `Select ${replacementResult.name}` })).not.toBeChecked()
		await expect(page.locator('#doaction')).toBeDisabled()
		expect(downloadedIds).toEqual([])
	})

	test('each cloud result view owns exactly one Select All control', async ({ page }) => {
		await page.route('**/*', route =>
			restPath(route.request().url()).startsWith(CLOUD_SNIPPETS_PATH)
				? fulfillJson(route, cloudSnippets([cloudSnippet(301, 'Select All result')]))
				: route.fallback())

		await page.goto(URLS.CLOUD_COMMUNITY_ADMIN)

		try {
			await selectSnippetView(page, 'Table')
			await expect(page.getByRole('checkbox', { name: 'Select all downloadable snippets' })).toHaveCount(1)
			await expect(page.getByRole('checkbox', { name: 'Select all items' })).toHaveCount(0)

			await selectSnippetView(page, 'Card')
			await expect(page.getByRole('checkbox', { name: 'Select all downloadable snippets' })).toHaveCount(0)
			await expect(page.getByRole('checkbox', { name: 'Select all items' })).toHaveCount(1)
		} finally {
			await selectSnippetView(page, 'Table')
		}
	})

	test('Cloud Library renders without context errors', async ({ page }) => {
		const pageErrors: string[] = []
		page.on('pageerror', error => pageErrors.push(error.message))

		await page.route('**/*', route => {
			const path = restPath(route.request().url())

			if (path.endsWith('/cloud/snippets/codevault')) {
				return fulfillJson(route, cloudSnippets([cloudSnippet(400, 'Library snippet')]))
			}

			return path.endsWith(CLOUD_BUNDLES_PATH) ? fulfillJson(route, []) : route.fallback()
		})

		await page.goto(URLS.CLOUD_LIBRARY_ADMIN)
		await expect(page.getByText('Library snippet', { exact: true })).toBeVisible()
		expect(pageErrors).toEqual([])
	})

	test('Cloud Library search is live without a submit control', async ({ page }) => {
		const matchingSnippet = cloudSnippet(401, 'Matching library snippet')
		const hiddenSnippet = cloudSnippet(402, 'Hidden library snippet')

		await page.route('**/*', route => {
			const url = new URL(route.request().url())
			const path = restPath(route.request().url())

			if (path.endsWith('/cloud/snippets/codevault')) {
				return fulfillJson(
					route,
					cloudSnippets(
						[matchingSnippet, hiddenSnippet],
						{ page: Number(url.searchParams.get('page') ?? 1), totalPages: 3, totalSnippets: 6 })
				)
			}

			return path.endsWith(CLOUD_BUNDLES_PATH) ? fulfillJson(route, []) : route.fallback()
		})

		await page.goto(URLS.CLOUD_LIBRARY_ADMIN)

		const search = page.getByRole('search', { name: 'Search Snippets' })
		await expect(search.locator('form')).toHaveCount(0)
		await expect(search.getByRole('button', { name: 'Search' })).toHaveCount(0)
		await page.getByRole('button', { name: 'Last page' }).first().click()
		await expect(page.getByText('3 of 3', { exact: true })).toBeVisible()

		await search.getByRole('searchbox').fill('Matching library')

		await expect(page.getByText(matchingSnippet.name, { exact: true })).toBeVisible()
		await expect(page.getByText(hiddenSnippet.name, { exact: true })).toHaveCount(0)
		await expect(page.getByText('3 of 3', { exact: true })).toBeVisible()
	})

	test('Blueprint search is live without a submit control', async ({ page }) => {
		await page.goto(URLS.BLUEPRINTS_ADMIN)

		const search = page.getByRole('search', { name: 'Search Blueprints' })
		await expect(search.locator('form')).toHaveCount(0)
		await expect(search.getByRole('button', { name: 'Search' })).toHaveCount(0)

		await search.getByRole('searchbox').fill('No Blueprint Has This Exact Name')

		await expect(page.getByRole('status')).toHaveText('0 items')
	})

	test('returning to the Blueprint gallery removes the selected Blueprint URL parameter', async ({ page }) => {
		await page.goto(`${URLS.BLUEPRINTS_ADMIN}&blueprint=taxonomy`)
		await page.getByRole('button', { name: 'Back to blueprints' }).click()

		await expect(page).not.toHaveURL(/blueprint=/)
	})

	test('changing Blueprint category removes the selected Blueprint URL parameter', async ({ page }) => {
		await page.goto(`${URLS.BLUEPRINTS_ADMIN}&blueprint=taxonomy`)
		await page.getByRole('navigation', { name: 'Blueprint categories' })
			.getByRole('button', { name: /Content/ })
			.click()

		await expect(page).not.toHaveURL(/blueprint=/)
	})
})
