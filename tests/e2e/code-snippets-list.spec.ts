import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS } from './helpers/constants'

const TEST_SNIPPET_NAME = 'E2E List Test Snippet'

test.describe('Code Snippets List Page Actions', () => {
  let helper: SnippetsTestHelper

  test.beforeEach(async ({ page }) => {
    helper = new SnippetsTestHelper(page)
    await helper.navigateToSnippetsAdmin()

    await helper.createAndActivateSnippet({
      name: TEST_SNIPPET_NAME,
      code: "add_filter('show_admin_bar', '__return_false');"
    })
    await helper.navigateToSnippetsAdmin()
  })

  test.afterEach(async () => {
    await helper.cleanupSnippet(TEST_SNIPPET_NAME)
  })

  test('Can toggle snippet activation from list page', async ({ page }) => {
    const snippetRow = page
      .locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${TEST_SNIPPET_NAME}"))`)
      .first()
    const toggleSwitch = snippetRow.locator(SELECTORS.SNIPPET_TOGGLE).first()

    await expect(toggleSwitch).toHaveAttribute('title', 'Deactivate')

    await toggleSwitch.click()

    const updatedRow = page
      .locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${TEST_SNIPPET_NAME}"))`)
      .first()
    const updatedToggle = updatedRow.locator(SELECTORS.SNIPPET_TOGGLE).first()
    await expect(updatedToggle).toHaveAttribute('title', 'Activate')

    await updatedToggle.click()

    const reactivatedRow = page
      .locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${TEST_SNIPPET_NAME}"))`)
      .first()
    const reactivatedToggle = reactivatedRow.locator(SELECTORS.SNIPPET_TOGGLE).first()
    await expect(reactivatedToggle).toHaveAttribute('title', 'Deactivate')
  })

  test('Can access edit from list page', async ({ page }) => {
    const snippetRow = page
      .locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${TEST_SNIPPET_NAME}"))`)
      .first()

    await snippetRow.locator(SELECTORS.SNIPPET_NAME_LINK).first().click()

    await expect(page).toHaveURL(/page=edit-snippet/)
    await expect(page.locator('#title')).toHaveValue(TEST_SNIPPET_NAME)
  })

  test('Can clone snippet from list page', async ({ page }) => {
    const snippetRow = page
      .locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${TEST_SNIPPET_NAME}"))`)
      .first()

    await snippetRow.locator(SELECTORS.CLONE_ACTION).click()

    await expect(page).toHaveURL(/page=snippets/)
    await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()

    await helper.expectTextVisible(`${TEST_SNIPPET_NAME} [CLONE]`)

    const clonedRow = page.locator(`tr:has-text("${TEST_SNIPPET_NAME} [CLONE]")`)
    await clonedRow.locator(SELECTORS.DELETE_ACTION).click()
    await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()
  })

  test('Can delete snippet from list page', async ({ page }) => {
    const snippetRow = page
      .locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${TEST_SNIPPET_NAME}"))`)
      .first()

    await snippetRow.locator(SELECTORS.DELETE_ACTION).click()

    await expect(page).toHaveURL(/page=snippets/)
    await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()

    const trashedLink = page.locator('ul.subsubsub li.trashed a').first()
    await expect(trashedLink).toBeVisible()
    await trashedLink.click()

    await expect(page).toHaveURL(/status=trashed/)
    await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()

    const trashedRow = page
      .locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${TEST_SNIPPET_NAME}"))`)
      .first()
    await expect(trashedRow).toBeVisible()

    page.once('dialog', async dialog => {
      expect(dialog.type()).toBe('confirm')
      expect(dialog.message()).toContain('permanently delete')
      await dialog.accept()
    })

    await trashedRow.locator('.delete_permanently a.delete').click()
    await expect(page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()

    const remainingCount = await page.locator(`tr:has-text("${TEST_SNIPPET_NAME}")`).count()
    expect(remainingCount).toBe(0)
  })

  test('Can export snippet from list page', async ({ page }) => {
    const snippetRow = page
      .locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${TEST_SNIPPET_NAME}"))`)
      .first()

    const downloadPromise = page.waitForEvent('download')

    await snippetRow.locator(SELECTORS.EXPORT_ACTION).click()

    const download = await downloadPromise
    expect(download.suggestedFilename()).toMatch(/\.json$/)
  })
})
