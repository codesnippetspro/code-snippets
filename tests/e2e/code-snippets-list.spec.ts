import { readFileSync } from 'fs'
import { expect, test } from '@playwright/test'
import { DEFAULT_E2E_SNIPPET_BASE_NAME, SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS, TIMEOUTS } from './helpers/constants'
import type { Page, Route } from '@playwright/test'

// The view preference saves through an optimistic background request, so wait
// for it to persist before navigating or ending the test.
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

const MAXIMUM_COLUMN_ALIGNMENT_OFFSET = 0.5

const snippetRowByName = (page: Page, snippetName: string) =>
	page.locator(SELECTORS.SNIPPET_ROW).filter({ hasText: snippetName }).first()

const clickRowAction = async (row: ReturnType<typeof snippetRowByName>, selector: string) => {
	await expect(row).toBeVisible()
	await row.hover()

	const action = row.locator(selector).first()
	await expect(action).toBeVisible()
	await action.click()
}

test.describe('Code Snippets List Page Actions', () => {
	let helper: SnippetsTestHelper
	let snippetName: string
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

	test('Shows an empty state when no snippets match the search', async ({ page }) => {
		const searchInput = page.getByRole('searchbox', { name: 'Search Snippets:' })
		await searchInput.fill(`${snippetName}-does-not-exist`)

		await expect(page.getByText('No snippets were found matching the current search query.')).toBeVisible()
	})

	test('Filters snippets by the selected tag', async ({ page }) => {
		const taggedSnippetName = SnippetsTestHelper.makeUniqueSnippetName('Tag filter')
		const tag = `e2e-tag-${Date.now()}`

		try {
			await SnippetsTestHelper.createSnippetViaCli({
				name: taggedSnippetName,
				active: false,
				tags: [tag]
			})
			await helper.navigateToSnippetsAdmin()

			await page.getByRole('combobox', { name: 'Filter snippets by tag' }).selectOption({ label: tag })
			await expect(snippetRowByName(page, taggedSnippetName)).toBeVisible()
			await expect(snippetRowByName(page, snippetName)).toBeHidden()
		} finally {
			await helper.cleanupSnippet(taggedSnippetName)
		}
	})

	test('Clears search, tag, and status filters together', async ({ page }) => {
		const taggedSnippetName = SnippetsTestHelper.makeUniqueSnippetName('Clear filters')
		const tag = `e2e-clear-${Date.now()}`

		try {
			await SnippetsTestHelper.createSnippetViaCli({
				name: taggedSnippetName,
				active: false,
				tags: [tag]
			})
			await helper.navigateToSnippetsAdmin()

			await page.locator('.subsubsub .inactive a').click()
			await page.getByRole('combobox', { name: 'Filter snippets by tag' }).selectOption({ label: tag })
			await page.getByRole('searchbox', { name: 'Search Snippets:' }).fill(taggedSnippetName)
			await expect(snippetRowByName(page, taggedSnippetName)).toBeVisible()

			await page.getByRole('button', { name: 'Clear Filters' }).click()
			await expect.poll(async () => ({
				search: await page.getByRole('searchbox', { name: 'Search Snippets:' }).inputValue(),
				tag: await page.getByRole('combobox', { name: 'Filter snippets by tag' }).inputValue(),
				activeSnippetVisible: await snippetRowByName(page, snippetName).isVisible()
			}), { timeout: TIMEOUTS.SHORT }).toEqual({
				search: '',
				tag: '',
				activeSnippetVisible: true
			})
		} finally {
			await helper.cleanupSnippet(taggedSnippetName)
		}
	})

	test('Searches snippets by name, description, and code', async ({ page }) => {
		const nameQuery = SnippetsTestHelper.makeUniqueSnippetName('Search name')
		const descriptionQuery = SnippetsTestHelper.makeUniqueSnippetName('Search-description-query')
		const codeQuery = SnippetsTestHelper.makeUniqueSnippetName('Search-code-query')
		const fixtures = [
			{
				name: nameQuery,
				query: nameQuery
			},
			{
				name: SnippetsTestHelper.makeUniqueSnippetName('Search description'),
				description: descriptionQuery,
				query: descriptionQuery
			},
			{
				name: SnippetsTestHelper.makeUniqueSnippetName('Search code'),
				code: `// ${codeQuery}`,
				query: codeQuery
			}
		]

		try {
			for (const fixture of fixtures) {
				await SnippetsTestHelper.createSnippetViaCli({ ...fixture, active: false })
			}

			await helper.navigateToSnippetsAdmin()
			const searchInput = page.getByRole('searchbox', { name: 'Search Snippets:' })

			for (const fixture of fixtures) {
				await searchInput.fill(fixture.query)
				await expect(snippetRowByName(page, fixture.name)).toBeVisible()

				for (const otherFixture of fixtures.filter(other => other !== fixture)) {
					await expect(snippetRowByName(page, otherFixture.name)).toBeHidden()
				}
			}
		} finally {
			for (const fixture of fixtures) {
				await helper.cleanupSnippet(fixture.name)
			}
		}
	})

	test('Lists active and inactive snippets in the table', async ({ page }) => {
		const inactiveSnippetName = SnippetsTestHelper.makeUniqueSnippetName('Inactive snippet')

		try {
			await SnippetsTestHelper.createSnippetViaCli({ name: inactiveSnippetName, active: false })
			await helper.navigateToSnippetsAdmin()

			await expect(snippetRowByName(page, snippetName)).toBeVisible()
			await expect(snippetRowByName(page, inactiveSnippetName)).toBeVisible()
		} finally {
			await helper.cleanupSnippet(inactiveSnippetName)
		}
	})

	test('Filters snippets by each status link', async ({ page }) => {
		const inactiveSnippetName = SnippetsTestHelper.makeUniqueSnippetName('Inactive snippet')

		try {
			await SnippetsTestHelper.createSnippetViaCli({ name: inactiveSnippetName, active: false })
			await helper.navigateToSnippetsAdmin()

			const activeRow = snippetRowByName(page, snippetName)
			const inactiveRow = snippetRowByName(page, inactiveSnippetName)
			await expect(activeRow).toBeVisible()
			await expect(inactiveRow).toBeVisible()

			await page.locator('.subsubsub .active a').click()
			await expect(activeRow).toBeVisible()
			await expect(inactiveRow).toBeHidden()

			await page.locator('.subsubsub .inactive a').click()
			await expect(activeRow).toBeHidden()
			await expect(inactiveRow).toBeVisible()

			await helper.navigateToSnippetsAdmin()
			await activeRow.getByRole('switch').click({ force: true })
			await expect(activeRow.getByRole('switch')).not.toBeChecked()
			await page.locator('.subsubsub .recently_active a').click()
			await expect(activeRow).toBeVisible()
			await expect(inactiveRow).toBeHidden()
		} finally {
			await helper.cleanupSnippet(inactiveSnippetName)
		}
	})

	test('Card action popovers let keyboard focus continue through the document', async ({ page }) => {
		await switchSnippetView(page, 'Card view')

		try {
			const card = page.locator('.snippets-card-grid .code-snippets-card').filter({ hasText: snippetName })
			const trigger = card.getByRole('button', { name: `Actions for ${snippetName}` })
			const popover = card.locator('.kebab-menu-popover')

			await expect(card).toBeVisible()
			await trigger.click()
			await expect(popover).toBeVisible()
			await popover.getByRole('button').last().focus()
			await page.keyboard.press('Tab')

			await expect(page.locator('#bulk-action-selector-bottom')).toBeFocused()
			await expect(popover).toHaveCount(0)

			await trigger.click()
			await expect(popover).toBeVisible()
			await popover.getByRole('button').first().focus()
			await page.keyboard.press('Shift+Tab')

			await expect(trigger).toBeFocused()
			await expect(popover).toBeVisible()
			await page.keyboard.press('Shift+Tab')

			await expect(card.getByRole('link', { name: 'Edit' })).toBeFocused()
			await expect(popover).toHaveCount(0)
		} finally {
			await switchSnippetView(page, 'Table view').catch(() => undefined)
		}
	})

	test('Can toggle snippet activation from list page', async ({ page }) => {
		const snippetRow = snippetRowByName(page, snippetName)

		const toggleCell = snippetRow.locator('td').first()
		const toggleSwitch = toggleCell.getByRole('switch').first()
		await expect(toggleSwitch).toBeVisible()

		// Active rows draw an accent border on the checkbox cell, so the same width
		// is reserved on every other row: without it, rows jump as they are toggled.
		const rowCheckbox = snippetRow.locator('.check-column input[type="checkbox"]')
		const checkboxInlineStart = async () => (await rowCheckbox.boundingBox())?.x ?? 0
		const initialInlineStart = await checkboxInlineStart()

		const initialChecked = await toggleSwitch.isChecked()
		await expect(toggleSwitch).toHaveAccessibleName(initialChecked ? /Deactivate/i : /Activate/i)

		await toggleSwitch.click({ force: true })
		if (initialChecked) {
			await expect(toggleSwitch).not.toBeChecked()
		} else {
			await expect(toggleSwitch).toBeChecked()
		}
		await expect(toggleSwitch).toHaveAccessibleName(!initialChecked ? /Deactivate/i : /Activate/i)
		expect(Math.abs(await checkboxInlineStart() - initialInlineStart))
			.toBeLessThanOrEqual(MAXIMUM_COLUMN_ALIGNMENT_OFFSET)

		await toggleSwitch.click({ force: true })
		if (initialChecked) {
			await expect(toggleSwitch).toBeChecked()
		} else {
			await expect(toggleSwitch).not.toBeChecked()
		}
		await expect(toggleSwitch).toHaveAccessibleName(initialChecked ? /Deactivate/i : /Activate/i)
	})

	test('Runs and stops a snippet when its row toggle changes', async ({ page }) => {
		const executionSnippetName = SnippetsTestHelper.makeUniqueSnippetName('Row toggle execution')
		const bodyClass = `row-toggle-${Date.now()}`

		try {
			await SnippetsTestHelper.createSnippetViaCli({
				name: executionSnippetName,
				active: false,
				code: `add_filter('body_class', function() { return ['${bodyClass}']; });`
			})
			await helper.navigateToSnippetsAdmin()

			let executionRow = snippetRowByName(page, executionSnippetName)
			await executionRow.getByRole('switch').click({ force: true })
			await expect(executionRow.getByRole('switch')).toBeChecked()

			await helper.navigateToFrontend()
			await expect(page.locator('body')).toHaveClass(new RegExp(bodyClass))

			await helper.navigateToSnippetsAdmin()
			executionRow = snippetRowByName(page, executionSnippetName)
			await executionRow.getByRole('switch').click({ force: true })
			await expect(executionRow.getByRole('switch')).not.toBeChecked()

			await helper.navigateToFrontend()
			await expect(page.locator('body')).not.toHaveClass(new RegExp(bodyClass))
		} finally {
			await helper.cleanupSnippet(executionSnippetName)
		}
	})

	test('Can access edit from list page', async ({ page }) => {
		const snippetRow = snippetRowByName(page, snippetName)

		await expect(snippetRow).toBeVisible()
		await snippetRow.locator(SELECTORS.SNIPPET_NAME_LINK).first().click()

		await expect(page).toHaveURL(/page=edit-snippet/)
		await expect(page.locator('#title')).toHaveValue(snippetName)
	})

	test('Shows View and omits Trash for a locked snippet', async ({ page }) => {
		const lockedSnippetName = SnippetsTestHelper.makeUniqueSnippetName('Locked snippet')

		try {
			await SnippetsTestHelper.createSnippetViaCli({
				name: lockedSnippetName,
				active: false,
				locked: true
			})
			await helper.navigateToSnippetsAdmin()

			const lockedRow = snippetRowByName(page, lockedSnippetName)
			await expect(lockedRow).toBeVisible()
			await lockedRow.hover()
			const actions = lockedRow.locator('.row-actions')

			await expect(actions.getByRole('link', { name: 'View' })).toBeVisible()
			await expect(actions.getByRole('link', { name: 'Edit' })).toHaveCount(0)
			await expect(actions.getByRole('button', { name: 'Trash' })).toHaveCount(0)
		} finally {
			await helper.cleanupSnippet(lockedSnippetName)
		}
	})

	test('Can clone snippet from list page', async ({ page }) => {
		const snippetRow = snippetRowByName(page, snippetName)

		await clickRowAction(snippetRow, SELECTORS.CLONE_ACTION)

		await expect(page).toHaveURL(/page=snippets/)
		await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()

		// Verify that a cloned snippet exists in the table (use table-scoped check to avoid admin bar matches)
		const clonedRow = snippetRowByName(page, `${snippetName} [CLONE]`)
		await expect(clonedRow).toBeVisible()
		await expect(clonedRow.getByRole('switch')).not.toBeChecked()

		// Clean up the clone by trashing it
		await clickRowAction(clonedRow, SELECTORS.DELETE_ACTION)
		await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()
	})

	test('Can clone a snippet once from the preview modal', async ({ page }) => {
		let createRequests = 0

		const trackCreateRequest = async (route: Route) => {
			const request = route.request()
			const requestUrl = new URL(request.url())
			const restRoute = requestUrl.searchParams.get('rest_route')
			const isCreateRequest = 'POST' === request.method() && (
				requestUrl.pathname.endsWith('/code-snippets/v1/snippets') ||
				'/code-snippets/v1/snippets' === restRoute
			)

			if (isCreateRequest) {
				createRequests += 1
				await new Promise(resolve => setTimeout(resolve, TIMEOUTS.VERY_SHORT))
			}

			await route.continue()
		}

		await page.route('**/wp-json/code-snippets/v1/snippets*', trackCreateRequest)
		await page.route(/\/index\.php\?rest_route=/, trackCreateRequest)

		const snippetRow = snippetRowByName(page, snippetName)
		await clickRowAction(snippetRow, SELECTORS.PREVIEW_ACTION)

		const previewModal = page.getByRole('dialog', { name: snippetName })
		const cloneButton = previewModal.getByRole('button', { name: 'Clone' })
		await cloneButton.click()
		await expect(cloneButton).toBeDisabled()
		await cloneButton.click({ force: true })

		await expect(previewModal).toBeHidden()
		expect(createRequests).toBe(1)
		await helper.cleanupSnippet(`${snippetName} [CLONE]`)
	})

	test('Selects rows with the header checkbox and individual checkboxes', async ({ page }) => {
		const row = snippetRowByName(page, snippetName)
		const rowCheckbox = row.locator('input[name="checked[]"]')
		const selectAll = page.locator(SELECTORS.SNIPPETS_TABLE)
			.locator('thead')
			.getByRole('checkbox', { name: 'Select All', exact: true })

		await selectAll.check()
		await expect(rowCheckbox).toBeChecked()

		await rowCheckbox.uncheck()
		await expect(selectAll).not.toBeChecked()
	})

	test('labels table controls and row actions for assistive technology', async ({ page }) => {
		const tableHead = page.locator(SELECTORS.SNIPPETS_TABLE).locator('thead')
		const row = snippetRowByName(page, snippetName)

		await expect(tableHead.getByRole('checkbox', { name: 'Select All', exact: true }))
			.toHaveAccessibleName('Select All')

		for (const column of ['Name', 'Type', 'Modified', 'Priority']) {
			await expect(tableHead.getByRole('button', { name: new RegExp(`^${column}`) }))
				.toHaveAccessibleName(new RegExp(column))
		}

		await row.hover()
		await expect(row.getByRole('link', { name: 'Edit' })).toHaveAccessibleName('Edit')
		for (const action of ['Preview', 'Clone', 'Export', 'Trash']) {
			await expect(row.getByRole('button', { name: action })).toHaveAccessibleName(action)
		}
	})

	test('reduces switch animation when the user prefers less motion', async ({ page }) => {
		await page.emulateMedia({ reducedMotion: 'reduce' })
		const toggleSwitch = snippetRowByName(page, snippetName).getByRole('switch')

		expect(await toggleSwitch.evaluate(element =>
			getComputedStyle(element, '::before').transitionDuration)).toBe('0.01s')
	})

	test('Can trash a snippet from the preview modal', async ({ page }) => {
		const snippetRow = snippetRowByName(page, snippetName)
		await clickRowAction(snippetRow, SELECTORS.PREVIEW_ACTION)

		const previewModal = page.getByRole('dialog', { name: snippetName })
		await previewModal.getByRole('button', { name: 'Trash' }).click()

		const confirmDialog = page.getByRole('dialog', { name: 'Are you sure?' })
		await confirmDialog.getByRole('button', { name: 'Trash' }).click()
		await expect(previewModal).toBeHidden()

		await page.locator('a[href*="status=trashed"]').first().click()
		await expect(page).toHaveURL(/status=trashed/)
		await expect(page.locator(`${SELECTORS.SNIPPET_ROW}:has-text("${snippetName}")`).first()).toBeVisible()
	})

	test('Can delete snippet from list page', async ({ page }) => {
		const snippetRow = snippetRowByName(page, snippetName)

		// Click "Trash" in row actions — in the new React UI, this moves to trash immediately (no dialog)
		await clickRowAction(snippetRow, SELECTORS.DELETE_ACTION)

		// Some implementations show a confirmation modal that must be dismissed.
		const confirmDialog = page.locator('[role="dialog"]').filter({ hasText: /Are you sure\\?/i })
		const dialogVisible = await confirmDialog
			.waitFor({ state: 'visible', timeout: TIMEOUTS.VERY_SHORT })
			.then(() => true)
			.catch(() => false)

		if (dialogVisible) {
			await confirmDialog.locator('button:has-text("Trash"), button:has-text("Delete")').first().click()
			await confirmDialog.waitFor({ state: 'hidden', timeout: TIMEOUTS.DEFAULT }).catch(() => undefined)
		}

		await expect(page).toHaveURL(/page=snippets/)
		await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()

		// Navigate to the trash view using the new filter link format
		const trashedLink = page.locator('a[href*="status=trashed"]').first()
		await expect(trashedLink).toBeVisible()
		await trashedLink.click()

		await expect(page).toHaveURL(/status=trashed/, { timeout: TIMEOUTS.DEFAULT })
		await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()

		const trashedRow = page.locator(`${SELECTORS.SNIPPET_ROW}:has-text("${snippetName}")`).first()
		await expect(trashedRow).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(trashedRow).toContainText(/Restore/i)
	})

	test('Can restore a trashed snippet from list page', async ({ page }) => {
		const snippetRow = snippetRowByName(page, snippetName)
		await clickRowAction(snippetRow, SELECTORS.DELETE_ACTION)

		const confirmDialog = page.locator('[role="dialog"]').filter({ hasText: /Are you sure\?/i })
		if (await confirmDialog.isVisible()) {
			await confirmDialog.getByRole('button', { name: 'Trash' }).click()
		}

		await page.locator('.subsubsub .trashed a').click()
		await expect(snippetRow).toBeVisible()
		await clickRowAction(snippetRow, 'button:has-text("Restore")')

		await expect(snippetRow).toBeVisible()
		await expect(snippetRow).not.toHaveClass(/trashed-snippet/)
		await expect(snippetRow.getByRole('switch')).not.toBeChecked()
	})

	test('Confirms permanent deletion from the Trash view', async ({ page }) => {
		const snippetRow = snippetRowByName(page, snippetName)
		await clickRowAction(snippetRow, SELECTORS.DELETE_ACTION)

		const trashDialog = page.locator('[role="dialog"]').filter({ hasText: /Are you sure\?/i })
		if (await trashDialog.isVisible()) {
			await trashDialog.getByRole('button', { name: 'Trash' }).click()
		}

		await page.locator('.subsubsub .trashed a').click()
		await expect(snippetRow).toBeVisible()
		await clickRowAction(snippetRow, 'button:has-text("Delete Permanently")')

		const deleteDialog = page.locator('[role="dialog"]').filter({ hasText: /Are you sure\?/i })
		await expect(deleteDialog).toBeVisible()
		await deleteDialog.getByRole('button', { name: 'Delete' }).click()
		await expect(snippetRow).toHaveCount(0)
	})

	test('Can export snippet from list page', async ({ page }) => {
		test.setTimeout(TIMEOUTS.LONG)
		const snippetRow = snippetRowByName(page, snippetName)
		await expect(snippetRow).toBeVisible()
		await snippetRow.hover()
		await expect(snippetRow.locator(SELECTORS.EXPORT_ACTION).first()).toBeVisible()

		const download = await Promise.all([
			page.waitForEvent('download'),
			snippetRow.locator(SELECTORS.EXPORT_ACTION).first().click()
		]).then(([downloadEvent]) => downloadEvent)

		expect(download.suggestedFilename()).toMatch(/\.json$/)
	})

	test('Exports the metadata needed to re-import a snippet', async ({ page }) => {
		test.setTimeout(TIMEOUTS.LONG)
		const exportName = SnippetsTestHelper.makeUniqueSnippetName('Export metadata')

		try {
			await SnippetsTestHelper.createSnippetViaCli({
				name: exportName,
				description: 'An E2E export description.',
				priority: 7,
				scope: 'front-end',
				tags: ['e2e-export'],
				active: false
			})
			await helper.navigateToSnippetsAdmin()

			const exportRow = snippetRowByName(page, exportName)
			await exportRow.hover()
			const download = await Promise.all([
				page.waitForEvent('download'),
				exportRow.locator(SELECTORS.EXPORT_ACTION).first().click()
			]).then(([downloadEvent]) => downloadEvent)
			const downloadPath = await download.path()

			if (!downloadPath) {
				throw new Error('Export did not produce a local file path')
			}

			const exported = <{ snippets: Record<string, unknown>[] }><unknown>JSON.parse(readFileSync(downloadPath, 'utf-8'))
			expect(exported.snippets).toEqual([expect.objectContaining({
				name: exportName,
				desc: 'An E2E export description.',
				priority: 7,
				scope: 'front-end',
				tags: ['e2e-export']
			})])
		} finally {
			await helper.cleanupSnippet(exportName)
		}
	})

	test('Can activate, deactivate, trash, and permanently delete snippets from bulk actions', async ({ page }) => {
		const bulkSnippetNames = [
			SnippetsTestHelper.makeUniqueSnippetName('Bulk action snippet'),
			SnippetsTestHelper.makeUniqueSnippetName('Bulk action snippet')
		]
		const rows = () => bulkSnippetNames.map(name => snippetRowByName(page, name))
		const selectRows = async () => {
			for (const row of rows()) {
				await row.locator('input[name="checked[]"]').check({ force: true })
			}
		}
		const applyAction = async (action: string) => {
			await page.locator('select[name="action"]').first().selectOption({ label: action })
			await page.locator('#doaction').click()
		}

		try {
			for (const name of bulkSnippetNames) {
				await SnippetsTestHelper.createSnippetViaCli({ name, active: false })
			}
			await helper.navigateToSnippetsAdmin()
			await selectRows()
			await applyAction('Activate')

			for (const row of rows()) {
				await expect(row.getByRole('switch')).toBeChecked()
			}

			await selectRows()
			await applyAction('Deactivate')
			for (const row of rows()) {
				await expect(row.getByRole('switch')).not.toBeChecked()
			}

			await selectRows()
			await applyAction('Trash')
			for (const row of rows()) {
				await expect(row).toHaveCount(0)
			}

			await page.locator('.subsubsub .trashed a').click()
			await selectRows()
			await applyAction('Delete Permanently')
			for (const row of rows()) {
				await expect(row).toHaveCount(0)
			}
		} finally {
			for (const name of bulkSnippetNames) {
				await helper.cleanupSnippet(name)
			}
		}
	})

	test('Does not apply a bulk action when no rows are selected', async ({ page }) => {
		const row = snippetRowByName(page, snippetName)

		await page.locator('select[name="action"]').first().selectOption({ label: 'Trash' })
		await page.locator('#doaction').click()

		await expect(row).toBeVisible()
		await expect(row).not.toHaveClass(/trashed-snippet/)
	})

	test('Restores selected snippets from the Trash with a bulk action', async ({ page }) => {
		const row = snippetRowByName(page, snippetName)

		await row.locator('input[name="checked[]"]').check({ force: true })
		await page.locator('select[name="action"]').first().selectOption({ label: 'Trash' })
		await page.locator('#doaction').click()
		await expect(row).toHaveCount(0)

		await page.locator('.subsubsub .trashed a').click()
		const trashedRow = snippetRowByName(page, snippetName)
		await trashedRow.locator('input[name="checked[]"]').check({ force: true })
		await page.locator('select[name="action"]').first().selectOption({ label: 'Restore' })
		await page.locator('#doaction').click()
		await expect(trashedRow).toHaveCount(0)

		await page.locator('.subsubsub .all a').click()
		await expect(snippetRowByName(page, snippetName)).toBeVisible()
	})

	test('Can export multiple snippets from bulk actions', async ({ page }) => {
		test.setTimeout(TIMEOUTS.LONG)
		const secondSnippetName = SnippetsTestHelper.makeUniqueSnippetName()

		await helper.createAndActivateSnippet({
			name: secondSnippetName,
			code: "add_filter('show_admin_bar', '__return_false');"
		})
		await helper.navigateToSnippetsAdmin()

		const firstRow = snippetRowByName(page, snippetName)
		const secondRow = snippetRowByName(page, secondSnippetName)

		await expect(firstRow).toBeVisible()
		await expect(secondRow).toBeVisible()

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
		test.setTimeout(TIMEOUTS.LONG)
		await helper.filterSnippetsByName(snippetName)
		const snippetRow = snippetRowByName(page, snippetName)
		await expect(snippetRow).toBeVisible()

		await snippetRow.locator('input[name="checked[]"]').check({ force: true })
		await page.locator('select[name="action"]').first().selectOption({ label: 'Download' })

		const download = await Promise.all([
			page.waitForEvent('download'),
			page.locator('#doaction').click()
		]).then(([downloadEvent]) => downloadEvent)

		expect(download.suggestedFilename()).toMatch(/\.code-snippets\.php$/)
	})

	test('Can download multiple snippets from bulk actions as a zip archive', async ({ page }) => {
		test.setTimeout(TIMEOUTS.LONG)
		const secondSnippetName = SnippetsTestHelper.makeUniqueSnippetName('E2E Download CSS')

		await SnippetsTestHelper.createSnippetViaCli({
			name: secondSnippetName,
			active: false,
			type: 'css'
		})
		await helper.navigateToSnippetsAdmin()

		const firstRow = snippetRowByName(page, snippetName)
		const secondRow = snippetRowByName(page, secondSnippetName)

		await expect(firstRow).toBeVisible()
		await expect(secondRow).toBeVisible()

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
		test.setTimeout(TIMEOUTS.LONG)
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
		test.setTimeout(TIMEOUTS.LONG)
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
	let unrelatedSnippetName: string | undefined

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page)
		snippetName = SnippetsTestHelper.makeUniqueSnippetName('E2E Screen Options')
		unrelatedSnippetName = undefined
		await SnippetsTestHelper.cleanupSnippetsByPrefix(DEFAULT_E2E_SNIPPET_BASE_NAME)
		await helper.createAndActivateSnippet({
			name: snippetName,
			code: "add_filter('show_admin_bar', '__return_false');"
		})
		await helper.navigateToSnippetsAdmin()
	})

	test.afterEach(async () => {
		await helper.cleanupSnippet(snippetName)
		if (unrelatedSnippetName) {
			await helper.cleanupSnippet(unrelatedSnippetName)
		}
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

	test('ID column visibility persists when enabled and supports ID search', async ({ page }) => {
		const snippetRow = snippetRowByName(page, snippetName)
		const editUrl = await snippetRow.locator('.snippet-name').getAttribute('href')
		const snippetId = new URL(editUrl ?? '', page.url()).searchParams.get('id')

		if (!snippetId) {
			throw new Error('Created snippet does not have an edit URL with an ID')
		}

		unrelatedSnippetName = SnippetsTestHelper.makeUniqueSnippetName('E2E ID Search')
		const unrelatedSnippetId = await SnippetsTestHelper.createSnippetViaCli({
			name: unrelatedSnippetName,
			active: false
		})
		expect(unrelatedSnippetId).not.toBe(Number(snippetId))

		await openScreenOptions(page)

		const idToggle = page.locator('#adv-settings input.hide-column-tog[value="id"]')
		await expect(idToggle).toBeVisible()
		await idToggle.uncheck()
		await expect(page.locator('.wp-list-table th.column-id').first()).toHaveClass(/\bhidden\b/)

		await idToggle.check()
		await page.locator('#screen-options-apply').click()
		await page.waitForLoadState('networkidle')

		await helper.navigateToSnippetsAdmin()
		await openScreenOptions(page)
		await expect(idToggle).toBeChecked()
		await expect(page.locator('.wp-list-table th.column-id').first()).not.toHaveClass(/\bhidden\b/)
		await expect(snippetRowByName(page, snippetName).locator('.column-id')).toHaveText(snippetId)
		await expect(snippetRowByName(page, unrelatedSnippetName)).toBeVisible()

		await page.getByRole('searchbox', { name: 'Search Snippets:' }).fill(snippetId)
		await expect(snippetRowByName(page, snippetName)).toBeVisible()
		await expect(snippetRowByName(page, unrelatedSnippetName)).toBeHidden()

		await idToggle.uncheck()
		await page.locator('#screen-options-apply').click()
		await page.waitForLoadState('networkidle')
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

	test('Truncation preference survives Apply and a page reload', async ({ page }) => {
		await openScreenOptions(page)
		await page.locator('#snippets-table-truncate-row-values').uncheck()

		await page.locator('#screen-options-apply').click()
		await page.waitForLoadState('networkidle')

		await helper.navigateToSnippetsAdmin()
		await openScreenOptions(page)

		await expect(page.locator('#snippets-table-truncate-row-values')).not.toBeChecked()
		await expect(page.locator('.wp-list-table.truncate-row-values')).toBeHidden()

		// Restore the default so later tests and the dev site are unaffected.
		await page.locator('#snippets-table-truncate-row-values').check()
		await page.locator('#screen-options-apply').click()
		await page.waitForLoadState('networkidle')
	})
})

test.describe('Snippets list order setting', () => {
	const PREFIX = 'E2E Order Test'
	const names = ['E2E Order Test Alpha', 'E2E Order Test Zulu']

	test.beforeAll(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(PREFIX)
		for (const name of names) {
			await SnippetsTestHelper.createSnippetViaCli({ name, active: false, type: 'php' })
		}
	})

	test.afterAll(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(PREFIX)
		await SnippetsTestHelper.setListOrder('priority-asc')
	})

	const firstMatchingName = async (page: Page): Promise<string> => {
		const rows = page.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK})`)
		await rows.first().waitFor()
		const all = await rows.locator(`a${SELECTORS.SNIPPET_NAME_LINK}`).allInnerTexts()
		return all.find(name => name.startsWith(PREFIX)) ?? ''
	}

	// The setting describes itself as the default order for this screen, so it
	// decides how the table opens. Sorting moved to the column headings during
	// the admin rewrite and the setting was left reading nothing at all.
	test('Snippets List Order decides the order the table opens in', async ({ page }) => {
		const helper = new SnippetsTestHelper(page)

		await SnippetsTestHelper.setListOrder('name-asc')
		await helper.navigateToSnippetsAdmin()
		expect(await firstMatchingName(page)).toBe('E2E Order Test Alpha')

		await SnippetsTestHelper.setListOrder('name-desc')
		await helper.navigateToSnippetsAdmin()
		expect(await firstMatchingName(page)).toBe('E2E Order Test Zulu')
	})
})
