import { readFileSync } from 'fs'
import { expect, test } from '@playwright/test'
import { DEFAULT_E2E_SNIPPET_BASE_NAME, SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS } from './helpers/constants'
import type { Page } from '@playwright/test'

test.describe('Code Snippets List Page Actions', () => {
	let helper: SnippetsTestHelper
	let snippetName: string
	const EXPORT_TEST_TIMEOUT_MS = 60000

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page)
		snippetName = SnippetsTestHelper.makeUniqueSnippetName()
		await SnippetsTestHelper.cleanupSnippetsByPrefix(DEFAULT_E2E_SNIPPET_BASE_NAME)
		await helper.navigateToSnippetsAdmin()

		await helper.createAndActivateSnippet({
			name: snippetName,
			code: "add_filter('show_admin_bar', '__return_false');"
		})
		await helper.navigateToSnippetsAdmin()
	})

	test.afterEach(async () => {
		await helper.cleanupSnippet(snippetName)
	})

	test('Filters snippets as the search query changes without a submit control', async ({ page }) => {
		const search = page.getByRole('search')
		const searchInput = search.getByRole('searchbox', { name: 'Search Snippets:' })
		const snippetRow = page.getByRole('row', { name: new RegExp(snippetName) })

		await searchInput.fill(snippetName)

		await expect(snippetRow).toBeVisible()
		await searchInput.fill(`${snippetName}-does-not-exist`)
		await expect(snippetRow).toBeHidden()
		await expect(search.getByRole('button', { name: 'Search' })).toHaveCount(0)
	})

	test('Can toggle snippet activation from list page', async ({ page }) => {
		const snippetRow = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()

		const toggleCell = snippetRow.locator('td').first()
		const toggleSwitch = toggleCell.getByRole('switch').first()
		await expect(toggleSwitch).toBeVisible()

		const initialChecked = await toggleSwitch.isChecked()
		await expect(toggleSwitch).toHaveAccessibleName(initialChecked ? /Deactivate/i : /Activate/i)

		await toggleSwitch.click({ force: true })
		if (initialChecked) {
			await expect(toggleSwitch).not.toBeChecked()
		} else {
			await expect(toggleSwitch).toBeChecked()
		}
		await expect(toggleSwitch).toHaveAccessibleName(!initialChecked ? /Deactivate/i : /Activate/i)

		await toggleSwitch.click({ force: true })
		if (initialChecked) {
			await expect(toggleSwitch).toBeChecked()
		} else {
			await expect(toggleSwitch).not.toBeChecked()
		}
		await expect(toggleSwitch).toHaveAccessibleName(initialChecked ? /Deactivate/i : /Activate/i)
	})

	test('Can access edit from list page', async ({ page }) => {
		const snippetRow = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()

		await snippetRow.locator(SELECTORS.SNIPPET_NAME_LINK).first().click()

		await expect(page).toHaveURL(/page=edit-snippet/)
		await expect(page.locator('#title')).toHaveValue(snippetName)
	})

	test('Can clone snippet from list page', async ({ page }) => {
		const snippetRow = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()

		await snippetRow.locator(SELECTORS.CLONE_ACTION).click()

		await expect(page).toHaveURL(/page=snippets/)
		await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()

		// Verify that a cloned snippet exists in the table (use table-scoped check to avoid admin bar matches)
		const clonedRow = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName} [CLONE]"))`)
			.first()
		await expect(clonedRow).toBeVisible()

		// Clean up the clone by trashing it
		await clonedRow.locator(SELECTORS.DELETE_ACTION).click()
		await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()
	})

	test('Can delete snippet from list page', async ({ page }) => {
		const snippetRow = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()

		// Click "Trash" in row actions — in the new React UI, this moves to trash immediately (no dialog)
		await snippetRow.locator(SELECTORS.DELETE_ACTION).click()

		// Some implementations show a confirmation modal that must be dismissed.
		const confirmDialog = page.locator('[role="dialog"]').filter({ hasText: /Are you sure\\?/i })
		const dialogVisible = await confirmDialog
			.waitFor({ state: 'visible', timeout: 2000 })
			.then(() => true)
			.catch(() => false)

		if (dialogVisible) {
			await confirmDialog.locator('button:has-text("Trash"), button:has-text("Delete")').first().click()
			await confirmDialog.waitFor({ state: 'hidden', timeout: 30000 }).catch(() => undefined)
		}

		await expect(page).toHaveURL(/page=snippets/)
		await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()

		// Navigate to the trash view using the new filter link format
		const trashedLink = page.locator('a[href*="status=trashed"]').first()
		await expect(trashedLink).toBeVisible()
		await trashedLink.click()

		await expect(page).toHaveURL(/status=trashed/, { timeout: 30000 })
		await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()

		const trashedRow = page.locator(`${SELECTORS.SNIPPET_ROW}:has-text("${snippetName}")`).first()
		await expect(trashedRow).toBeVisible({ timeout: 30000 })
		await expect(trashedRow).toContainText(/Restore/i)
	})

	test('Can export snippet from list page', async ({ page }) => {
		test.setTimeout(EXPORT_TEST_TIMEOUT_MS)
		const snippetRow = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()

		const download = await Promise.all([
			page.waitForEvent('download'),
			snippetRow.locator(SELECTORS.EXPORT_ACTION).click()
		]).then(([downloadEvent]) => downloadEvent)

		expect(download.suggestedFilename()).toMatch(/\.json$/)
	})

	test('Can export multiple snippets from bulk actions', async ({ page }) => {
		test.setTimeout(EXPORT_TEST_TIMEOUT_MS)
		const secondSnippetName = SnippetsTestHelper.makeUniqueSnippetName()

		await helper.createAndActivateSnippet({
			name: secondSnippetName,
			code: "add_filter('show_admin_bar', '__return_false');"
		})
		await helper.navigateToSnippetsAdmin()

		const firstRow = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()
		const secondRow = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${secondSnippetName}"))`)
			.first()

		await firstRow.locator('input[name="checked[]"]').check({ force: true })
		await secondRow.locator('input[name="checked[]"]').check({ force: true })
		await page.locator('select[name="action"]').first().selectOption({ label: 'Export' })

		const download = await Promise.all([
			page.waitForEvent('download'),
			page.locator('#doaction').click()
		]).then(([downloadEvent]) => downloadEvent)

		expect(download.suggestedFilename()).toBe('snippets.code-snippets.json')

		await helper.cleanupSnippet(secondSnippetName)
	})

	test('Can download a single snippet from bulk actions', async ({ page }) => {
		test.setTimeout(EXPORT_TEST_TIMEOUT_MS)
		const snippetRow = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()

		await snippetRow.locator('input[name="checked[]"]').check({ force: true })
		await page.locator('select[name="action"]').first().selectOption({ label: 'Download' })

		const download = await Promise.all([
			page.waitForEvent('download'),
			page.locator('#doaction').click()
		]).then(([downloadEvent]) => downloadEvent)

		expect(download.suggestedFilename()).toMatch(/\.code-snippets\.php$/)
	})

	test('Can download multiple snippets from bulk actions as a zip archive', async ({ page }) => {
		test.setTimeout(EXPORT_TEST_TIMEOUT_MS)
		const secondSnippetName = SnippetsTestHelper.makeUniqueSnippetName('E2E Download CSS')

		await SnippetsTestHelper.createSnippetViaCli({
			name: secondSnippetName,
			active: false,
			type: 'css'
		})
		await helper.navigateToSnippetsAdmin()

		const firstRow = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()
		const secondRow = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${secondSnippetName}"))`)
			.first()

		await firstRow.locator('input[name="checked[]"]').check({ force: true })
		await secondRow.locator('input[name="checked[]"]').check({ force: true })
		await page.locator('select[name="action"]').first().selectOption({ label: 'Download' })

		const download = await Promise.all([
			page.waitForEvent('download'),
			page.locator('#doaction').click()
		]).then(([downloadEvent]) => downloadEvent)

		expect(download.suggestedFilename()).toMatch(/^code-snippets-\d+\.zip$/)

		await helper.cleanupSnippet(secondSnippetName)
	})

	test('Bulk download stays scoped to the current page selection', async ({ page }) => {
		test.setTimeout(EXPORT_TEST_TIMEOUT_MS)
		const bulkScopeBaseName = 'E2E Bulk Scope'
		const firstScopedSnippetName = SnippetsTestHelper.makeUniqueSnippetName(bulkScopeBaseName)
		const secondScopedSnippetName = SnippetsTestHelper.makeUniqueSnippetName(bulkScopeBaseName)

		await SnippetsTestHelper.setSnippetsPerPage(1)

		try {
			await helper.createAndActivateSnippet({
				name: firstScopedSnippetName,
				code: "add_filter('show_admin_bar', '__return_false');"
			})
			await helper.createAndActivateSnippet({
				name: secondScopedSnippetName,
				code: "add_filter('show_admin_bar', '__return_false');"
			})
			await helper.navigateToSnippetsAdmin()

			await page.locator('#snippets_search').fill(bulkScopeBaseName)

			const firstPageRow = page.locator(SELECTORS.SNIPPET_ROW).first()
			await expect(firstPageRow).toBeVisible()
			await firstPageRow.locator('input[name="checked[]"]').check({ force: true })

			await page.locator('.next-page').first().click()

			const secondPageRow = page.locator(SELECTORS.SNIPPET_ROW).first()
			await expect(secondPageRow).toBeVisible()
			await secondPageRow.locator('input[name="checked[]"]').check({ force: true })
			await page.locator('select[name="action"]').first().selectOption({ label: 'Download' })

			const download = await Promise.all([
				page.waitForEvent('download'),
				page.locator('#doaction').click()
			]).then(([downloadEvent]) => downloadEvent)

			expect(download.suggestedFilename()).toMatch(/\.code-snippets\.php$/)
		} finally {
			await SnippetsTestHelper.resetSnippetsPerPage()
			await helper.cleanupSnippet(firstScopedSnippetName)
			await helper.cleanupSnippet(secondScopedSnippetName)
		}
	})

	test('Bulk export stays scoped to the current page selection', async ({ page }) => {
		test.setTimeout(EXPORT_TEST_TIMEOUT_MS)
		const bulkScopeBaseName = 'E2E Bulk Scope Export'
		const firstScopedSnippetName = SnippetsTestHelper.makeUniqueSnippetName(bulkScopeBaseName)
		const secondScopedSnippetName = SnippetsTestHelper.makeUniqueSnippetName(bulkScopeBaseName)

		await SnippetsTestHelper.setSnippetsPerPage(1)

		try {
			await helper.createAndActivateSnippet({
				name: firstScopedSnippetName,
				code: "add_filter('show_admin_bar', '__return_false');"
			})
			await helper.createAndActivateSnippet({
				name: secondScopedSnippetName,
				code: "add_filter('show_admin_bar', '__return_false');"
			})
			await helper.navigateToSnippetsAdmin()

			await page.locator('#snippets_search').fill(bulkScopeBaseName)

			// Select a row on page 1.
			const firstPageRow = page.locator(SELECTORS.SNIPPET_ROW).first()
			await expect(firstPageRow).toBeVisible()
			await firstPageRow.locator('input[name="checked[]"]').check({ force: true })

			// Navigate to page 2 - the page-1 selection should be cleared.
			await page.locator('.next-page').first().click()

			// Select the row on page 2 and export.
			const secondPageRow = page.locator(SELECTORS.SNIPPET_ROW).first()
			await expect(secondPageRow).toBeVisible()
			await secondPageRow.locator('input[name="checked[]"]').check({ force: true })
			await page.locator('select[name="action"]').first().selectOption({ label: 'Export' })

			const download = await Promise.all([
				page.waitForEvent('download'),
				page.locator('#doaction').click()
			]).then(([downloadEvent]) => downloadEvent)

			// A single-snippet export (not a multi-snippet archive) confirms only the page-2
			// snippet — not both — was included in the selection.
			expect(download.suggestedFilename()).toMatch(/\.code-snippets\.json$/)
			const downloadPath = await download.path()
			if (!downloadPath) {
				throw new Error('Download did not produce a local file path')
			}
			const parsed = <{ snippets: { name: string }[] }><unknown>JSON.parse(readFileSync(downloadPath, 'utf-8'))
			expect(parsed.snippets).toHaveLength(1)
		} finally {
			await SnippetsTestHelper.resetSnippetsPerPage()
			await helper.cleanupSnippet(firstScopedSnippetName)
			await helper.cleanupSnippet(secondScopedSnippetName)
		}
	})
})

test.describe('Manage table Screen Options', () => {
	let helper: SnippetsTestHelper
	let snippetName: string

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page)
		snippetName = SnippetsTestHelper.makeUniqueSnippetName('E2E Screen Options')
		await SnippetsTestHelper.cleanupSnippetsByPrefix(DEFAULT_E2E_SNIPPET_BASE_NAME)
		await helper.createAndActivateSnippet({
			name: snippetName,
			code: "add_filter('show_admin_bar', '__return_false');"
		})
		await helper.navigateToSnippetsAdmin()
	})

	test.afterEach(async () => {
		await helper.cleanupSnippet(snippetName)
	})

	const openScreenOptions = async (page: Page) => {
		const panel = page.locator('#adv-settings')
		const isVisible = await panel.isVisible().catch(() => false)

		if (!isVisible) {
			await page.locator('#show-settings-link').click()
			await expect(panel).toBeVisible()
		}
	}

	test('Column visibility toggle hides and shows columns in real time', async ({ page }) => {
		await openScreenOptions(page)

		const descToggle = page.locator('#adv-settings input.hide-column-tog[value="desc"]')
		await expect(descToggle).toBeVisible()

		// Ensure Description column is initially visible.
		await descToggle.check()
		await expect(page.locator('.wp-list-table th.column-desc').first()).not.toHaveClass(/\bhidden\b/)

		// Uncheck — column should disappear in real time.
		await descToggle.uncheck()
		await expect(page.locator('.wp-list-table th.column-desc').first()).toHaveClass(/\bhidden\b/)

		// Re-check — column should reappear in real time.
		await descToggle.check()
		await expect(page.locator('.wp-list-table th.column-desc').first()).not.toHaveClass(/\bhidden\b/)
	})

	test('Truncation toggle applies and removes the truncation class in real time', async ({ page }) => {
		await openScreenOptions(page)

		const truncationToggle = page.locator('#snippets-table-truncate-row-values')
		await expect(truncationToggle).toBeVisible()

		// Enable truncation and verify the CSS class is applied.
		await truncationToggle.check()
		await expect(page.locator('.wp-list-table.truncate-row-values')).toBeVisible()

		// Disable truncation and verify the CSS class is removed.
		await truncationToggle.uncheck()
		await expect(page.locator('.wp-list-table.truncate-row-values')).toBeHidden()

		// Re-enable and verify restoration.
		await truncationToggle.check()
		await expect(page.locator('.wp-list-table.truncate-row-values')).toBeVisible()
	})
})
