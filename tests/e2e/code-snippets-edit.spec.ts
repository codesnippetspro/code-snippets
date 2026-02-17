import { test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { MESSAGES, SELECTORS } from './helpers/constants'

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

  test('Can activate and deactivate a snippet', async ({ page }) => {
    await helper.openSnippet(TEST_SNIPPET_NAME)

    // Check the current state by seeing which buttons are visible
    const saveAndDeactivateButton = page.locator('text=Save and Deactivate')

    const isAlreadyActive = await saveAndDeactivateButton.isVisible().catch(() => false)

    if (isAlreadyActive) {
      // If already active, deactivate first
      await helper.saveSnippet('save_and_deactivate')
      await helper.expectSuccessMessage(MESSAGES.SNIPPET_UPDATED_AND_DEACTIVATED)
    }

    // Now the snippet should be inactive — activate it
    await helper.saveSnippet('save_and_activate')
    await helper.expectSuccessMessage(MESSAGES.SNIPPET_UPDATED_AND_ACTIVATED)

    // Now deactivate it
    await helper.saveSnippet('save_and_deactivate')
    await helper.expectSuccessMessage(MESSAGES.SNIPPET_UPDATED_AND_DEACTIVATED)
  })

  test('Can delete a snippet', async () => {
    await helper.openSnippet(TEST_SNIPPET_NAME)
    await helper.deleteSnippet()
    await helper.expectElementCount(`${SELECTORS.SNIPPETS_TABLE} tbody tr:has-text("${TEST_SNIPPET_NAME}")`, 0)
  })
})
