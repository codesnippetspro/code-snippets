import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS } from './helpers/constants'

test.describe('Code Snippets List Page Actions', () => {
	let helper: SnippetsTestHelper
	let snippetName: string
	const EXPORT_TEST_TIMEOUT_MS = 60000

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page)
		snippetName = SnippetsTestHelper.makeUniqueSnippetName()
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

	test('Can toggle snippet activation from list page', async ({ page }) => {
		const snippetRow = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()

		const toggleCell = snippetRow.locator('td').first()
		const toggleCheckbox = toggleCell.getByRole('checkbox').first()

		const initialChecked = await toggleCheckbox.isChecked()
		await expect(toggleCell).toContainText(initialChecked ? 'Deactivate' : 'Activate')

		await toggleCheckbox.click({ force: true })
		if (initialChecked) {
			await expect(toggleCheckbox).not.toBeChecked()
		} else {
			await expect(toggleCheckbox).toBeChecked()
		}
		await expect(toggleCell).toContainText(!initialChecked ? 'Deactivate' : 'Activate')

		await toggleCheckbox.click({ force: true })
		if (initialChecked) {
			await expect(toggleCheckbox).toBeChecked()
		} else {
			await expect(toggleCheckbox).not.toBeChecked()
		}
		await expect(toggleCell).toContainText(initialChecked ? 'Deactivate' : 'Activate')
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
})
