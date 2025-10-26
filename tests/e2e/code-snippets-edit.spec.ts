import { test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { MESSAGES } from './helpers/constants'

const TEST_SNIPPET_NAME = 'E2E Test Snippet'

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
		await helper.createSnippet({
			name: TEST_SNIPPET_NAME,
			code: "add_filter('show_admin_bar', '__return_false');"
		})
	})

	test('Can activate and deactivate a snippet', async () => {
		await helper.openSnippet(TEST_SNIPPET_NAME)

		await helper.saveSnippet('save_and_activate')
		await helper.expectSuccessMessageInParagraph(MESSAGES.SNIPPET_UPDATED_AND_ACTIVATED)

		await helper.saveSnippet('save_and_deactivate')
		await helper.expectSuccessMessageInParagraph(MESSAGES.SNIPPET_UPDATED_AND_DEACTIVATED)
	})

	test('Can delete a snippet', async () => {
		await helper.openSnippet(TEST_SNIPPET_NAME)
		await helper.deleteSnippet()
		await helper.expectTextNotVisible(TEST_SNIPPET_NAME)
	})
})
