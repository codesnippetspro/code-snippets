import { expect, test } from '@playwright/test'
import { DEFAULT_E2E_SNIPPET_BASE_NAME, SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { MESSAGES, SELECTORS, TIMEOUTS } from './helpers/constants'

test.describe('Code Snippets Admin', () => {
	let helper: SnippetsTestHelper

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page)
		await SnippetsTestHelper.cleanupSnippetsByPrefix(DEFAULT_E2E_SNIPPET_BASE_NAME)
		await helper.navigateToSnippetsAdmin()
	})

	test('Can access snippets admin page', async () => {
		await helper.expectToBeOnSnippetsAdminPage()
	})

	test('Can add a new snippet', async () => {
		const snippetName = SnippetsTestHelper.makeUniqueSnippetName()
		await helper.createSnippet({
			name: snippetName,
			code: "add_filter('show_admin_bar', '__return_false');"
		})
	})

	test('Can activate and deactivate a snippet', async () => {
		const snippetName = SnippetsTestHelper.makeUniqueSnippetName()
		await helper.createSnippet({
			name: snippetName,
			code: "add_filter('show_admin_bar', '__return_false');"
		})

		await helper.openSnippet(snippetName)

		// Activate it.
		await helper.saveSnippet('save_and_activate')
		await helper.expectSuccessMessage(MESSAGES.SNIPPET_UPDATED_AND_ACTIVATED)

		// Deactivate it (Status toggle + save in the new UI).
		await helper.saveSnippet('save_and_deactivate')
		await helper.expectSuccessMessage(MESSAGES.SNIPPET_UPDATED_AND_DEACTIVATED)
	})

	test('Can activate a new snippet on the first save attempt', async ({ page }) => {
		const snippetName = SnippetsTestHelper.makeUniqueSnippetName()
		await helper.clickAddNewSnippet()
		await helper.fillSnippetForm({
			name: snippetName,
			code: "add_filter('show_admin_bar', '__return_false');"
		})

		await helper.saveSnippet('save_and_activate')
		await helper.expectSuccessMessage(MESSAGES.SNIPPET_CREATED_AND_ACTIVATED)

		await helper.navigateToSnippetsAdmin()

		const snippetRow = page
			.locator(SELECTORS.SNIPPET_ROW)
			.filter({ has: page.locator(SELECTORS.SNIPPET_NAME_LINK).filter({ hasText: snippetName }) })
			.first()

		await expect(snippetRow).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(snippetRow.locator(SELECTORS.SNIPPET_TOGGLE).first()).toBeChecked({ timeout: TIMEOUTS.DEFAULT })

		await helper.cleanupSnippet(snippetName)
	})

	test('Back navigation confirms before discarding unsaved changes', async ({ page }) => {
		const snippetName = SnippetsTestHelper.makeUniqueSnippetName()
		await helper.clickAddNewSnippet()
		await helper.fillSnippetForm({
			name: snippetName,
			code: "add_filter('show_admin_bar', '__return_false');"
		})
		await helper.saveSnippet()
		await expect(page).toHaveURL(/page=edit-snippet/)

		const editedName = `${snippetName} edited`
		await page.locator('#title').fill(editedName)
		// Leaving the editor is confirmed either through the unsaved-changes prompt or
		// the browser's own unload prompt, depending on how the editor was reached, so
		// the prompt is answered without asserting which of the two it is.
		page.once('dialog', dialog => dialog.dismiss())
		await page.evaluate(() => window.history.back())

		await expect(page).toHaveURL(/page=edit-snippet/)
		await expect(page.locator('#title')).toHaveValue(editedName)

		page.once('dialog', dialog => dialog.accept())
		await page.evaluate(() => window.history.back())
		await expect(page).not.toHaveURL(/page=edit-snippet/)

		await helper.cleanupSnippet(snippetName)
	})

	test('Accepted in-page Back navigation shows one confirmation', async ({ page }) => {
		const snippetName = SnippetsTestHelper.makeUniqueSnippetName()
		await helper.createSnippet({
			name: snippetName,
			code: "add_filter('show_admin_bar', '__return_false');"
		})
		await helper.openSnippet(snippetName)

		await page.locator('a.page-title-action').filter({ hasText: 'Add New' }).click()
		await expect(page).toHaveURL(/page=add-snippet/)
		await page.locator('#title').fill(`${snippetName} draft`)

		const dialogs: { message: string, type: string }[] = []
		page.on('dialog', async dialog => {
			dialogs.push({ message: dialog.message(), type: dialog.type() })
			await dialog.accept()
		})

		await page.evaluate(() => window.history.back())
		await expect(page).toHaveURL(/page=edit-snippet/)
		await expect(page.locator('#title')).toHaveValue(snippetName)

		expect(dialogs).toHaveLength(1)
		expect(dialogs[0].type).toBe('confirm')
		expect(dialogs[0].message).toContain('unsaved changes')

		await helper.cleanupSnippet(snippetName)
	})

	test('Shows an error notice when activation fails after saving', async ({ page }) => {
		const snippetName = SnippetsTestHelper.makeUniqueSnippetName()
		await helper.clickAddNewSnippet()
		await helper.fillSnippetForm({
			name: snippetName,
			code: 'missing_runtime_function_call();'
		})

		await helper.saveSnippet('save_and_activate')

		const errorNotice = page.locator('.wrap > .notice.error').first()
		await expect(errorNotice).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(errorNotice).toContainText('Snippet could not be activated.')
		await expect(errorNotice).toContainText('Call to undefined function missing_runtime_function_call()')
		await expect(errorNotice).toContainText('The snippet was saved, but remains inactive due to this error:')

		const traceDetails = errorNotice.locator('details').first()
		await expect(traceDetails).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(traceDetails.locator('summary')).toContainText('View stack trace')

		await helper.navigateToSnippetsAdmin()
		await helper.filterSnippetsByName(snippetName)

		const snippetRow = page
			.locator(SELECTORS.SNIPPET_ROW)
			.filter({ has: page.locator(SELECTORS.SNIPPET_NAME_LINK).filter({ hasText: snippetName }) })
			.first()

		await expect(snippetRow).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(snippetRow.locator(SELECTORS.SNIPPET_TOGGLE).first()).not.toBeChecked({ timeout: TIMEOUTS.DEFAULT })

		await helper.cleanupSnippet(snippetName)
	})

	test('Can delete a snippet', async () => {
		const snippetName = SnippetsTestHelper.makeUniqueSnippetName()
		await helper.createSnippet({
			name: snippetName,
			code: "add_filter('show_admin_bar', '__return_false');"
		})

		await helper.openSnippet(snippetName)
		await helper.deleteSnippet()
		await helper.deleteSnippetFromList(snippetName)
		await helper.expectElementCount(
			`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:contains("${snippetName}"))`,
			0
		)
	})
})
