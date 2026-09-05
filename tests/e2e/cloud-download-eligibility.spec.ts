import { expect, test } from '@playwright/test'
import { CloudStatus } from '../../src/js/types/schema/CloudSnippetSchema'
import { URLS } from './helpers/constants'
import type { CloudSnippetSchema } from '../../src/js/types/schema/CloudSnippetSchema'
import type { Page } from '@playwright/test'

const cloudSnippet = (fields: Partial<CloudSnippetSchema> & Pick<CloudSnippetSchema, 'id' | 'name'>): CloudSnippetSchema => ({
	slug: `snippet-${fields.id}`,
	description: '',
	code: 'phpinfo();',
	tags: [],
	scope: 'global',
	codevault: 'testvault',
	total_votes: 0,
	vote_count: 0,
	wp_tested: '6.7',
	status: CloudStatus.Public,
	created: '2026-01-01 00:00:00',
	updated: '2026-01-01 00:00:00',
	revision: 1,
	is_owner: false,
	local_id: null,
	update_available: false,
	...fields
})

const ELIGIBLE = cloudSnippet({ id: 101, name: 'Eligible Alpha' })
const LINKED = cloudSnippet({ id: 102, name: 'Linked Beta', local_id: 42 })
const PRO_LOCKED = cloudSnippet({ id: 103, name: 'Pro Gamma', scope: 'site-css' })
const ELIGIBLE_OTHER = cloudSnippet({ id: 104, name: 'Eligible Delta' })

interface CloudRoutesState {
	snippets: CloudSnippetSchema[]
	downloads: number[]
}

const forceLicenseState = (page: Page, isLicensed: boolean) =>
	page.addInitScript(licensed => {
		let value: { isLicensed?: boolean } | undefined

		Object.defineProperty(window, 'CODE_SNIPPETS', {
			configurable: true,
			get: () => value,
			set: (incoming: { isLicensed?: boolean } | undefined) => {
				value = incoming ? { ...incoming, isLicensed: licensed } : incoming
			}
		})
	}, isLicensed)

const routeCloudSnippets = (page: Page, state: CloudRoutesState) =>
	page.route(url => decodeURIComponent(url.href).includes('cloud/snippets'), async route => {
		const url = decodeURIComponent(route.request().url())
		const download = /cloud\/snippets\/(?<id>\d+)\/download/.exec(url)

		if (download && 'POST' === route.request().method()) {
			state.downloads.push(Number(download.groups?.id))
			await route.fulfill({ json: { snippet: null } })
		} else {
			await route.fulfill({
				json: {
					snippets: state.snippets,
					page: 1,
					total_pages: 1,
					total_snippets: state.snippets.length
				}
			})
		}
	})

const openCommunityCloud = async (page: Page, view: 'table' | 'card') => {
	await page.goto(URLS.COMMUNITY_CLOUD)
	await expect(page.locator('.cloud-search')).toBeVisible()
	await page.getByTitle(`Switch to ${view} view`).click()
}

const applyBulkDownload = async (page: Page) => {
	await page.locator('#bulk-action-selector-top').selectOption('download')
	await page.locator('#doaction').click()
}

test.describe('Cloud bulk download eligibility', () => {
	test('unlicensed table view only offers and downloads eligible snippets', async ({ page }) => {
		const state: CloudRoutesState = { snippets: [ELIGIBLE, LINKED, PRO_LOCKED], downloads: [] }
		await forceLicenseState(page, false)
		await routeCloudSnippets(page, state)
		await openCommunityCloud(page, 'table')

		const table = page.locator('.cloud-snippets-table')
		await expect(table.getByRole('checkbox', { name: 'Select Eligible Alpha' })).toBeVisible()
		// Rows that cannot be downloaded keep a box, disabled, that says why.
		await expect(table.getByRole('checkbox', { name: 'Linked Beta cannot be selected: Already in your library.' })).toBeDisabled()
		await expect(table.getByRole('checkbox', { name: 'Pro Gamma cannot be selected: Requires Code Snippets Pro.' })).toBeDisabled()

		const selectAll = table.getByRole('checkbox', { name: 'Select all downloadable snippets' })
		await expect(selectAll).not.toBeChecked()
		await selectAll.check()
		await expect(table.getByRole('checkbox', { name: 'Select Eligible Alpha' })).toBeChecked()
		await expect(table.getByRole('checkbox', { name: 'Linked Beta cannot be selected: Already in your library.' })).not.toBeChecked()

		await applyBulkDownload(page)
		await expect.poll(() => state.downloads).toEqual([ELIGIBLE.id])
	})

	test('unlicensed card view select-all only downloads eligible snippets', async ({ page }) => {
		const state: CloudRoutesState = { snippets: [ELIGIBLE, LINKED, PRO_LOCKED], downloads: [] }
		await forceLicenseState(page, false)
		await routeCloudSnippets(page, state)
		await openCommunityCloud(page, 'card')

		const cards = page.locator('.cloud-search-results')
		await expect(cards.getByRole('checkbox', { name: 'Select Eligible Alpha' })).toBeVisible()
		await expect(cards.getByRole('checkbox', { name: 'Select Linked Beta' })).toHaveCount(0)
		await expect(cards.getByRole('checkbox', { name: 'Select Pro Gamma' })).toHaveCount(0)

		await page.getByRole('checkbox', { name: 'Select all items' }).check()
		await expect(cards.getByRole('checkbox', { name: 'Select Eligible Alpha' })).toBeChecked()

		await applyBulkDownload(page)
		await expect.poll(() => state.downloads).toEqual([ELIGIBLE.id])
	})

	test('licensed table view select-all includes Pro snippets but not linked ones', async ({ page }) => {
		const state: CloudRoutesState = { snippets: [ELIGIBLE, LINKED, PRO_LOCKED], downloads: [] }
		await forceLicenseState(page, true)
		await routeCloudSnippets(page, state)
		await openCommunityCloud(page, 'table')

		const table = page.locator('.cloud-snippets-table')
		await expect(table.getByRole('checkbox', { name: 'Select Pro Gamma' })).toBeVisible()
		await expect(table.getByRole('checkbox', { name: 'Linked Beta cannot be selected: Already in your library.' })).toBeDisabled()

		await table.getByRole('checkbox', { name: 'Select all downloadable snippets' }).check()
		await applyBulkDownload(page)
		await expect.poll(() => [...state.downloads].sort((a, b) => a - b)).toEqual([ELIGIBLE.id, PRO_LOCKED.id])
	})

	test('a page with nothing downloadable offers no selection at all', async ({ page }) => {
		const state: CloudRoutesState = {
			snippets: [
				cloudSnippet({ id: 201, name: 'Owned One', local_id: 11 }),
				cloudSnippet({ id: 202, name: 'Owned Two', local_id: 12 })
			],
			downloads: []
		}
		await forceLicenseState(page, true)
		await routeCloudSnippets(page, state)
		await openCommunityCloud(page, 'table')

		const table = page.locator('.cloud-snippets-table')
		const selectAll = table.getByRole('checkbox', { name: 'Select all downloadable snippets' })
		// "All of nothing" must not read as a selection: the header box is unticked and disabled.
		await expect(selectAll).toBeDisabled()
		await expect(selectAll).not.toBeChecked()
		await expect(table.getByRole('checkbox', { name: 'Owned One cannot be selected: Already in your library.' })).toBeDisabled()
		await expect(table.getByRole('checkbox', { name: 'Owned Two cannot be selected: Already in your library.' })).toBeDisabled()
	})

	test('selections hidden by a new search are not downloaded', async ({ page }) => {
		const state: CloudRoutesState = { snippets: [ELIGIBLE, LINKED, PRO_LOCKED], downloads: [] }
		await forceLicenseState(page, false)
		await routeCloudSnippets(page, state)
		await openCommunityCloud(page, 'table')

		const table = page.locator('.cloud-snippets-table')
		await table.getByRole('checkbox', { name: 'Select Eligible Alpha' }).check()

		state.snippets = [ELIGIBLE_OTHER]
		await page.locator('#cloud-search-query').fill('delta')
		await page.locator('.cloud-search-form').getByRole('button', { name: /Search Cloud Library/i }).click()

		const otherCheckbox = table.getByRole('checkbox', { name: 'Select Eligible Delta' })
		await expect(otherCheckbox).toBeVisible()
		await otherCheckbox.check()

		await applyBulkDownload(page)
		await expect.poll(() => state.downloads).toEqual([ELIGIBLE_OTHER.id])
	})
})
