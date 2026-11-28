import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'
import type { SnippetSchema } from '../../src/js/types/schema/SnippetSchema'
import type { Page, Request } from '@playwright/test'

const SNIPPET_ID = 9

const listedSnippet = (fields: Partial<SnippetSchema> & Pick<SnippetSchema, 'name'>): SnippetSchema => ({
	id: SNIPPET_ID,
	desc: '',
	code: '// row identity',
	tags: [],
	scope: 'global',
	condition_id: 0,
	active: false,
	locked: false,
	trashed: false,
	priority: 10,
	shared_network: null,
	network: false,
	modified: '2026-11-28 10:00:00',
	revision: '1',
	cloud_id: 0,
	is_cloud_owner: false,
	...fields
})

// A site snippet and a shared network snippet are numbered separately, so a subsite list can hold both with one ID.
const SITE_SNIPPET = listedSnippet({ name: 'Row identity site' })
const NETWORK_SNIPPET = listedSnippet({ name: 'Row identity network', network: true, shared_network: true })

const COLLECTION_PATTERN = /code-snippets\/v1\/snippets(?:[?&]|$)/
const ITEM_PATTERN = /code-snippets\/v1\/snippets\/\d+/

interface SentRequest {
	method: string
	url: string
	body: Record<string, unknown> | null
}

const describeRequest = (request: Request): SentRequest => ({
	method: request.method(),
	url: decodeURIComponent(request.url()),
	body: <Record<string, unknown> | null> request.postDataJSON()
})

const routeSnippets = async (page: Page): Promise<SentRequest[]> => {
	const sent: SentRequest[] = []

	await page.route(url => ITEM_PATTERN.test(decodeURIComponent(url.href)), async route => {
		sent.push(describeRequest(route.request()))
		await route.fulfill({ json: SITE_SNIPPET })
	})

	await page.route(url => COLLECTION_PATTERN.test(decodeURIComponent(url.href)), async route => {
		if ('GET' === route.request().method()) {
			await route.fulfill({ json: [SITE_SNIPPET, NETWORK_SNIPPET] })
			return
		}

		sent.push(describeRequest(route.request()))
		await route.fulfill({ json: { ...SITE_SNIPPET, id: SNIPPET_ID + 1 } })
	})

	return sent
}

const rowCheckbox = (page: Page, name: string) =>
	page.locator('tr', { hasText: name }).getByRole('checkbox', { name: 'Select snippet' })

const openListAndSelectSiteRow = async (page: Page) => {
	await page.goto(URLS.SNIPPETS_ADMIN)
	await expect(page.locator('tr', { hasText: NETWORK_SNIPPET.name })).toBeVisible()
	await rowCheckbox(page, SITE_SNIPPET.name).check()
}

const applyBulkAction = async (page: Page, action: string) => {
	await page.locator('#bulk-action-selector-top').selectOption(action)
	await page.locator('#doaction').click()
}

test.describe('Snippets list rows with the same ID', () => {
	test('selecting the site snippet does not select the network snippet', async ({ page }) => {
		await routeSnippets(page)
		await openListAndSelectSiteRow(page)

		await expect(rowCheckbox(page, SITE_SNIPPET.name)).toBeChecked()
		await expect(rowCheckbox(page, NETWORK_SNIPPET.name)).not.toBeChecked()
		await expect(page.locator(`[id="cb-select-${SNIPPET_ID}"]`)).toHaveCount(1)
	})

	test('trashing the site snippet sends one request, for the site snippet', async ({ page }) => {
		const sent = await routeSnippets(page)
		await openListAndSelectSiteRow(page)
		await applyBulkAction(page, 'trash')

		await expect.poll(() => sent.length).toBe(1)
		await page.waitForTimeout(500)

		expect(sent).toHaveLength(1)
		expect(sent[0].url).toMatch(new RegExp(`snippets/${SNIPPET_ID}\\b`))
		expect(sent[0].url).not.toMatch(/network=(?:1|true)/)
	})

	test('cloning the site snippet creates one site snippet', async ({ page }) => {
		const sent = await routeSnippets(page)
		await openListAndSelectSiteRow(page)
		await applyBulkAction(page, 'clone')

		await expect.poll(() => sent.length).toBe(1)
		await page.waitForTimeout(500)

		expect(sent).toHaveLength(1)
		expect(sent[0].method).toBe('POST')
		expect(sent[0].body?.network).toBeFalsy()
	})
})
