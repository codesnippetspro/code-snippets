import { expect, test } from '@playwright/test'
import { DEFAULT_E2E_SNIPPET_BASE_NAME, SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS, TIMEOUTS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'

// Disabling the admin's "Syntax Highlighting" preference makes
// wp_enqueue_code_editor() a no-op, so window.wp.codeEditor is undefined when
// the preview modal opens. The modal must fall back to the read-only textarea
// instead of throwing.
const setSyntaxHighlighting = (enabled: boolean): Promise<string> => {
	const value = enabled ? "'true'" : "'false'"
	const php = `
		$user = get_user_by('login', 'admin');
		if ($user) {
			update_user_meta($user->ID, 'syntax_highlighting', ${value});
		}
	`

	return wpCli(['eval', php])
}

test.describe('Code Snippets Preview Modal', () => {
	let helper: SnippetsTestHelper
	let snippetName: string

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page)
		snippetName = SnippetsTestHelper.makeUniqueSnippetName()
		await SnippetsTestHelper.cleanupSnippetsByPrefix(DEFAULT_E2E_SNIPPET_BASE_NAME)
		await setSyntaxHighlighting(false)
		await SnippetsTestHelper.createSnippetViaCli({ name: snippetName, type: 'php', active: false })
	})

	test.afterEach(async () => {
		await setSyntaxHighlighting(true)
		await helper.cleanupSnippet(snippetName)
	})

	test('Preview falls back to a readable textarea when the code editor is unavailable', async ({ page }) => {
		const pageErrors: string[] = []
		page.on('pageerror', error => pageErrors.push(error.message))

		await helper.navigateToSnippetsAdmin()
		await helper.filterSnippetsByName(snippetName)

		const row = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()
		await expect(row).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		await row.hover()
		await row.locator(SELECTORS.PREVIEW_ACTION).first().click()

		const preview = page.locator('.code-snippets-preview-modal')
		await expect(preview).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		const codeArea = preview.getByLabel('Snippet code preview')
		await expect(codeArea).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(codeArea).toHaveValue(new RegExp(snippetName))

		expect(pageErrors).toEqual([])
	})
})
