import AxeBuilder from '@axe-core/playwright'
import { expect, test } from '@playwright/test'
import { SELECTORS, TIMEOUTS, URLS } from './helpers/constants'
import type { Page } from '@playwright/test'

const A11Y_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']

const runAxe = (page: Page) =>
	new AxeBuilder({ page })
		.withTags(A11Y_TAGS)
		// Skip rules that target areas owned by WordPress core admin chrome
		// rather than this plugin (skip-link target, default landmarks).
		.disableRules(['region', 'skip-link'])
		.analyze()

test.describe('Accessibility (axe-core, WCAG 2.1 AA)', () => {
	test('Manage snippets list has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Community cloud screen has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.COMMUNITY_CLOUD_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Add new snippet form has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Import snippets screen has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.IMPORT_SNIPPETS_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Snippets settings screen has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.SETTINGS_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Welcome screen has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.WELCOME_SCREEN_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})
})

test.describe('Accessibility (manual checks)', () => {
	test('Snippets table with sortable column use buttons with aria-sort', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const nameSortButton = page.locator('th.column-name .list-table-sort-button').first()
		await expect(nameSortButton).toBeVisible()

		const nameHeader = page.locator('th.column-name').first()
		const ariaSort = await nameHeader.getAttribute('aria-sort')
		expect(['ascending', 'descending', null].includes(ariaSort)).toBe(true)
	})

	test('Snippets edit screen associates Snippet Content label with the code field', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForSelector(SELECTORS.TITLE_INPUT, { timeout: TIMEOUTS.DEFAULT })

		await expect(page.locator('label[for="snippet-code"]')).toBeVisible()
		await expect(page.locator('#snippet-code')).toBeVisible()
	})

	test('Snippets import screen has a keyboard-focusable upload file control', async ({ page }) => {
		await page.goto(URLS.IMPORT_SNIPPETS_ADMIN)
		await page.waitForSelector('.import-snippets-menu', { timeout: TIMEOUTS.DEFAULT })

		const fileInput = page.locator('.upload-drop-zone-file-input')
		await fileInput.focus()

		await expect(fileInput).toBeFocused()
	})
})
