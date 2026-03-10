import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { MESSAGES, SELECTORS, TIMEOUTS } from './helpers/constants'

test.describe('Code Snippets Admin', () => {
	let helper: SnippetsTestHelper

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page)
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
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()

		await expect(snippetRow).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(snippetRow.locator(SELECTORS.SNIPPET_TOGGLE).first()).toBeChecked({ timeout: TIMEOUTS.DEFAULT })

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
		await helper.expectElementCount(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`, 0)
	})
})
