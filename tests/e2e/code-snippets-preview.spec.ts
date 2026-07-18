import { expect, test } from '@playwright/test'
import { DEFAULT_E2E_SNIPPET_BASE_NAME, SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS, TIMEOUTS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'

const MAXIMUM_FOCUS_ATTEMPTS = 10

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
		await wpCli(['eval', "delete_option( 'code_snippets_snippet_view' );"])
		await helper.cleanupSnippet(snippetName)
	})

	test('Preview falls back to a readable textarea when the code editor is unavailable',
		async ({ page }) => {
			const pageErrors: string[] = []
			page.on('pageerror', error => pageErrors.push(error.message))

			await wpCli(['eval', "update_option( 'code_snippets_snippet_view', 'table' );"])
			await helper.navigateToSnippetsAdmin()
			await helper.filterSnippetsByName(snippetName)

			const row = page
				.locator(
					`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`
				)
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

	test('Preview code can be selected with the keyboard without being changed', async ({ page }) => {
		await setSyntaxHighlighting(true)
		await helper.navigateToSnippetsAdmin()
		await helper.filterSnippetsByName(snippetName)

		const row = page
			.locator(
				`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`
			)
			.first()
		await expect(row).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		await row.hover()
		await row.locator(SELECTORS.PREVIEW_ACTION).first().click()

		const preview = page.locator('.code-snippets-preview-modal')
		const editor = preview.locator('.CodeMirror')
		const codeArea = preview.getByLabel('Snippet code preview')
		const initialValue = await codeArea.inputValue()
		await expect(editor).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		for (let attempt = 0; attempt < MAXIMUM_FOCUS_ATTEMPTS; attempt++) {
			if (await editor.evaluate(element => element.classList.contains('CodeMirror-focused'))) {
				break
			}

			await page.keyboard.press('Tab')
		}

		await expect(editor).toHaveClass(/CodeMirror-focused/)
		await page.keyboard.press('Shift+ArrowRight')

		await expect.poll(() => editor.evaluate(element => {
			const codeMirror = (<HTMLElement & {
				CodeMirror?: { getSelection?: () => string }
			}>element).CodeMirror

			return codeMirror?.getSelection?.() ?? ''
		})).not.toBe('')
		await expect(codeArea).toHaveValue(initialValue)
	})
})
