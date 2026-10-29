import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS, URLS } from './helpers/constants'
import type { Page } from '@playwright/test'

const SNIPPET_PREFIX = 'E2E Universal Search'
const SNIPPET_NAME = `${SNIPPET_PREFIX} Marigold`

// More snippets than the dropdown lists in a group.
const LONE_GROUP_WORD = 'Quillonzest'
const LONE_GROUP_SIZE = 5
const SNIPPETS_GROUP_LIMIT = 3

const searchField = (page: Page) =>
	page.getByRole('combobox', { name: 'Search snippets, settings, docs and cloud' })

const resultsList = (page: Page) =>
	page.getByRole('listbox', { name: 'Search results' })

// The upgrade prompts only show without a licence, which a Pro test site has.
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

const isCommunitySearch = (url: URL): boolean =>
	decodeURIComponent(url.href).includes('code-snippets/v1/search') && 'community' === url.searchParams.get('sources')

const AI_RESULT_NAME = 'Hide the toolbar from clients'

/**
 * Answer the community searches in the browser, so the tests never wait on Code Snippets Cloud.
 * An AI search finds one snippet when asked about the toolbar, and a keyword search finds none.
 */
const mockCommunitySearch = (page: Page, methods: (string | null)[] = []) =>
	page.route(isCommunitySearch, route => {
		const url = new URL(route.request().url())
		const method = url.searchParams.get('method')
		methods.push(method)

		return route.fulfill({
			contentType: 'application/json',
			body: JSON.stringify({
				query: url.searchParams.get('q'),
				groups: 'ai' === method && url.searchParams.get('q')?.includes('toolbar')
					? [{
						source: 'community',
						total: 1,
						items: [{
							id: 'community-601',
							source: 'community',
							type: 'php',
							title: AI_RESULT_NAME,
							description: 'Removes the admin bar for every role except administrators.',
							url: '',
							path: 'Community · by jlewis',
							matched: []
						}]
					}]
					: []
			})
		})
	})

test.describe('Universal search', () => {
	let snippetId = 0

	test.beforeAll(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(SNIPPET_PREFIX)
		snippetId = await SnippetsTestHelper.createSnippetViaCli({ name: SNIPPET_NAME, active: false })

		for (let index = 1; index <= LONE_GROUP_SIZE; index++) {
			await SnippetsTestHelper.createSnippetViaCli({
				name: `${SNIPPET_PREFIX} ${LONE_GROUP_WORD} ${index}`,
				active: false
			})
		}
	})

	test.afterAll(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(SNIPPET_PREFIX)
	})

	test.beforeEach(async ({ page }) => {
		await mockCommunitySearch(page)
	})

	for (const [screen, url] of Object.entries({
		'the snippets list': URLS.SNIPPETS_ADMIN,
		'the settings screen': URLS.SETTINGS_ADMIN,
		'the Stats screen': URLS.STATS_ADMIN,
		'the welcome screen': URLS.WELCOME_ADMIN,
		'the import screen': URLS.IMPORT_ADMIN,
		'the snippet editor': URLS.ADD_SNIPPET_ADMIN
	})) {
		test(`the search field is in the header of ${screen}`, async ({ page }) => {
			await page.goto(url)

			await expect(searchField(page)).toBeVisible()
			await expect(searchField(page)).toHaveAttribute('aria-expanded', 'false')
		})
	}

	test('pressing "/" moves focus to the search field', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await expect(searchField(page)).toBeVisible()

		await page.locator('body').press('/')

		await expect(searchField(page)).toBeFocused()
		await expect(searchField(page)).toHaveValue('')
	})

	test('lists matching snippets and settings as they are typed', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)

		await searchField(page).fill('Marigold')

		await expect(searchField(page)).toHaveAttribute('aria-expanded', 'true')
		await expect(resultsList(page).getByRole('group', { name: /^Snippets/ }).getByRole('option')).toContainText(SNIPPET_NAME)
		await expect(resultsList(page).locator('mark').first()).toHaveText('Marigold')

		await searchField(page).fill('admin bar')

		await expect(resultsList(page).getByRole('group', { name: /^Settings/ }).getByRole('option').first())
			.toContainText('Admin Bar')
	})

	test('a group lists only its top matches, with a link to the rest', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)

		await searchField(page).fill(LONE_GROUP_WORD)

		const snippets = resultsList(page).getByRole('group', { name: /^Snippets/ })
		await expect(snippets.getByRole('option')).toHaveCount(SNIPPETS_GROUP_LIMIT)

		await snippets.getByRole('link', { name: `View all ${LONE_GROUP_SIZE}` }).click()

		await expect(page).toHaveURL(/subpage=search&s=Quillonzest&source=snippets/)
		await expect(page.getByRole('region', { name: /^Snippets/ }).getByRole('listitem')).toHaveCount(LONE_GROUP_SIZE)
	})

	test('without Pro, searching the Cloud Library is offered as an upgrade', async ({ page }) => {
		await forceLicenseState(page, false)
		await page.goto(URLS.SNIPPETS_ADMIN)

		await searchField(page).fill('Marigold')

		await expect(page.getByText('Search your own Cloud Library too, along with your synced and private snippets.'))
			.toBeVisible()
		await expect(page.locator('.universal-search-dropdown').getByRole('link', { name: /^Upgrade to Pro/ }))
			.toHaveAttribute('href', 'https://codesnippets.pro/pricing/')
		await expect(resultsList(page).getByRole('group', { name: /^Cloud Library/ })).toHaveCount(0)
	})

	test('AI results are listed last without being asked for', async ({ page }) => {
		const methods: (string | null)[] = []
		await page.unrouteAll()
		await mockCommunitySearch(page, methods)
		await page.goto(URLS.SNIPPETS_ADMIN)

		await searchField(page).fill('hide the toolbar')
		const aiGroup = resultsList(page).getByRole('group', { name: /^AI\s*AI results/ })
		const aiOption = aiGroup.getByRole('option', { name: new RegExp(AI_RESULT_NAME) })
		await expect(aiOption).toBeVisible()
		expect(methods).toContain('ai')

		await searchField(page).press('ArrowUp')
		await expect(aiOption).toHaveAttribute('aria-selected', 'true')
	})

	test('an AI result not on this site opens the AI tab of the results page', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)

		await searchField(page).fill('hide the toolbar')
		const aiOption = resultsList(page)
			.getByRole('group', { name: /^AI\s*AI results/ })
			.getByRole('option', { name: new RegExp(AI_RESULT_NAME) })
		await expect(aiOption).toHaveAttribute('href', /[?&]source=ai(?:&|$)/)

		await searchField(page).press('ArrowUp')
		await expect(aiOption).toHaveAttribute('aria-selected', 'true')
		await searchField(page).press('Enter')

		await expect(page).toHaveURL(/subpage=search.*[?&]source=ai(?:&|$)/)
		await expect(page.getByRole('region', { name: /^Cloud Community/ }).getByRole('heading', { name: AI_RESULT_NAME }))
			.toBeVisible()
	})

	test('the AI tab of the results page lists what the AI search found', async ({ page }) => {
		const methods: (string | null)[] = []
		await page.unrouteAll()
		await mockCommunitySearch(page, methods)

		await page.goto(`${URLS.SNIPPETS_ADMIN}&subpage=search&s=zzqxjv+hide+the+toolbar`)
		await expect(page.getByText('Nothing matched. Try a different term or another source.')).toBeVisible()
		expect(methods).not.toContain('ai')

		await page.getByRole('navigation', { name: 'Search result sources' }).getByRole('button', { name: /^Search with AI/ }).click()

		await expect(page).toHaveURL(/source=ai/)
		await expect(page.getByRole('region', { name: /^Cloud Community/ }).getByRole('heading', { name: AI_RESULT_NAME }))
			.toBeVisible()
		await expect(page.getByRole('checkbox', { name: 'Title' })).toHaveCount(0)
		expect(methods).toContain('ai')
	})

	test('without Pro, the Cloud Library tab of the results page offers an upgrade', async ({ page }) => {
		await forceLicenseState(page, false)
		await page.goto(`${URLS.SNIPPETS_ADMIN}&subpage=search&s=Marigold&source=cloud`)

		const tab = page.getByRole('navigation', { name: 'Search result sources' }).getByRole('button', { name: /^Cloud Library/ })
		await expect(tab).toContainText('Pro')
		await expect(tab).toHaveAttribute('aria-current', 'page')

		await expect(page.getByRole('heading', { name: 'Search your own Cloud Library for “Marigold”' })).toBeVisible()
		const upsell = page.locator('.search-results-upsell')
		await expect(upsell.getByRole('link', { name: /^Upgrade to Pro/ })).toHaveAttribute('href', 'https://codesnippets.pro/pricing/')
		await expect(upsell.getByRole('link', { name: 'See the Cloud Library' })).toHaveAttribute('href', /subpage=cloud-library/)
	})

	test('the active result shows the line of code it matched on', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)

		await searchField(page).fill(`// ${SNIPPET_NAME}`)

		const option = resultsList(page).getByRole('group', { name: /^Snippets/ }).getByRole('option', { name: new RegExp(SNIPPET_NAME) })
		const preview = page.locator('.universal-search-code-preview')
		await expect(option).toBeVisible()
		await expect(preview).toHaveCount(0)

		await option.hover()
		await expect(preview).toContainText(`1// ${SNIPPET_NAME}`)
		await expect(preview.locator('mark').first()).toBeVisible()

		await page.mouse.move(0, 0)
		await expect(preview).toHaveCount(0)

		await searchField(page).press('ArrowDown')
		await expect(option).toHaveAttribute('aria-selected', 'true')
		await expect(preview).toBeVisible()
	})

	test('the arrow keys and Enter open a result', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)

		await searchField(page).fill('Marigold')
		await expect(resultsList(page).getByRole('option').first()).toBeVisible()

		await searchField(page).press('ArrowDown')
		await expect(resultsList(page).getByRole('option').first()).toHaveAttribute('aria-selected', 'true')

		await searchField(page).press('Enter')

		await expect(page).toHaveURL(new RegExp(`page=edit-snippet&id=${snippetId}`))
	})

	test('Escape closes the results and keeps the query', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)

		await searchField(page).fill('Marigold')
		await expect(resultsList(page)).toBeVisible()

		await searchField(page).press('Escape')

		await expect(resultsList(page)).toBeHidden()
		await expect(searchField(page)).toHaveAttribute('aria-expanded', 'false')

		await searchField(page).press('Escape')
		await expect(searchField(page)).toHaveValue('Marigold')
	})

	test('pressing "/" while typing elsewhere types the character', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)

		const title = page.locator(SELECTORS.TITLE_INPUT)
		await title.click()
		await title.press('/')

		await expect(title).toBeFocused()
		await expect(title).toHaveValue('/')
	})

	test('reports when nothing matches', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)

		await searchField(page).fill('zzqxjv-no-such-thing')

		await expect(page.getByText('No results for “zzqxjv-no-such-thing”.')).toBeVisible()
		await expect(resultsList(page).getByRole('option')).toHaveCount(0)
	})

	test('Enter without a selection opens the full results page', async ({ page }) => {
		await page.goto(URLS.SETTINGS_ADMIN)

		await searchField(page).fill('Marigold')
		await expect(resultsList(page).getByRole('option').first()).toBeVisible()
		await searchField(page).press('Enter')

		await expect(page).toHaveURL(/page=snippets&subpage=search&s=Marigold/)
		await expect(page.getByRole('heading', { level: 1, name: 'Search results for “Marigold”' })).toBeVisible()
		await expect(searchField(page)).toHaveValue('Marigold')

		const snippets = page.getByRole('region', { name: /^Snippets/ })
		await expect(snippets.getByRole('link', { name: SNIPPET_NAME })).toBeVisible()
		await expect(snippets.getByRole('switch', { name: `Activate ${SNIPPET_NAME}` })).not.toBeChecked()
	})

	test('a snippet can be activated from the results page', async ({ page }) => {
		await page.goto(`${URLS.SNIPPETS_ADMIN}&subpage=search&s=Marigold`)

		const snippets = page.getByRole('region', { name: /^Snippets/ })
		await snippets.getByRole('switch', { name: `Activate ${SNIPPET_NAME}` }).click()

		const deactivate = snippets.getByRole('switch', { name: `Deactivate ${SNIPPET_NAME}` })
		await expect(deactivate).toBeChecked()

		await deactivate.click()
		await expect(snippets.getByRole('switch', { name: `Activate ${SNIPPET_NAME}` })).not.toBeChecked()
	})

	test('a code match can be shown from the results page', async ({ page }) => {
		await page.goto(`${URLS.SNIPPETS_ADMIN}&subpage=search&s=${encodeURIComponent(`// ${SNIPPET_NAME}`)}`)

		const button = page.getByRole('region', { name: /^Snippets/ }).getByRole('button', { name: /^Found in – Line 1/ })
		await button.click()

		await expect(button).toHaveAttribute('aria-expanded', 'true')
		await expect(page.locator('.universal-search-code-preview')).toContainText(`1// ${SNIPPET_NAME}`)

		await page.keyboard.press('Escape')
		await expect(page.locator('.universal-search-code-preview')).toHaveCount(0)
	})

	test('the results page narrows to one source and to chosen fields', async ({ page }) => {
		await page.goto(`${URLS.SNIPPETS_ADMIN}&subpage=search&s=admin+bar`)

		await expect(page.getByRole('region', { name: /^Settings/ })).toBeVisible()

		await page.getByRole('navigation', { name: 'Search result sources' }).getByRole('button', { name: /^Settings/ }).click()
		await expect(page).toHaveURL(/source=settings/)

		await expect(page.getByRole('region', { name: /^Settings/ })).toBeVisible()
		await expect(page.getByRole('region', { name: /^Stats/ })).toHaveCount(0)
		await expect(page.getByRole('region', { name: /^Welcome/ })).toHaveCount(0)

		await page.getByRole('checkbox', { name: 'Title' }).uncheck()
		await page.getByRole('checkbox', { name: 'Description' }).uncheck()
		await page.getByRole('checkbox', { name: 'Code' }).uncheck()

		await expect(page.getByText('Choose at least one place to match in.')).toBeVisible()
	})

	test('a Stats result opens the Stats screen at its report', async ({ page }) => {
		await page.goto(`${URLS.SNIPPETS_ADMIN}&subpage=search&s=Activation+status`)

		await page.getByRole('region', { name: /^Stats/ }).getByRole('link', { name: 'View stats' }).first().click()

		await expect(page).toHaveURL(/page=code-snippets-stats#stats-chart-activation/)
		await expect(page.locator('#stats-chart-activation')).toBeInViewport()
	})

	test('a setting result opens its tab with the field picked out', async ({ page }) => {
		await page.goto(`${URLS.SNIPPETS_ADMIN}&subpage=search&s=Enable+Admin+Bar+Menu`)

		await page.getByRole('region', { name: /^Settings/ }).getByRole('link', { name: 'Open setting' }).first().click()

		await expect(page).toHaveURL(/page=snippets-settings&section=interface#setting-enable_admin_bar/)
		await expect(page.locator('#setting-enable_admin_bar')).toBeInViewport()
		await expect(page.locator('#setting-enable_admin_bar')).toContainText('Enable Admin Bar Menu')
	})

	test('the field collapses to a button on a narrow screen', async ({ page }) => {
		await page.setViewportSize({ width: 1000, height: 800 })
		await page.goto(URLS.SNIPPETS_ADMIN)

		await expect(searchField(page)).toBeHidden()

		await page.getByRole('button', { name: 'Search', exact: true }).click()

		await expect(searchField(page)).toBeFocused()

		await page.getByRole('button', { name: 'Close search' }).click()
		await expect(searchField(page)).toBeHidden()
	})
})
