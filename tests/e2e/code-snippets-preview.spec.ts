import { expect, test } from '@playwright/test'
import { DEFAULT_E2E_SNIPPET_BASE_NAME, SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS, TIMEOUTS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'
import type { Locator, Page } from '@playwright/test'

const MAXIMUM_FOCUS_ATTEMPTS = 10
const MAXIMUM_BADGE_ALIGNMENT_OFFSET = 4
const PREVIEW_VIEWPORT_WIDTHS = [1280, 640]

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
	const openPreviewEditor = async (page: Page): Promise<Locator> => {
		await setSyntaxHighlighting(true)
		await helper.navigateToSnippetsAdmin()
		await helper.filterSnippetsByName(snippetName)

		const row = page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()
		await expect(row).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		await row.hover()
		await row.locator(SELECTORS.PREVIEW_ACTION).first().click()

		const editor = page.locator('.code-snippets-preview-modal .CodeMirror')
		await expect(editor).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		return editor
	}
	const focusPreviewEditor = async (page: Page, editor: Locator): Promise<void> => {
		for (let attempt = 0; attempt < MAXIMUM_FOCUS_ATTEMPTS; attempt++) {
			if (await editor.evaluate(element => element.classList.contains('CodeMirror-focused'))) {
				break
			}

			await page.keyboard.press('Tab')
		}

		await expect(editor).toHaveClass(/CodeMirror-focused/)
	}
	// The CodeMirror input differs by inputStyle ('textarea' or 'contenteditable'
	// depending on the WordPress version), so read the document and selection
	// through the editor instance instead of locating the input element.
	const readPreviewEditor = (editor: Locator, method: 'getSelection' | 'getValue'): Promise<string> =>
		editor.evaluate((element, editorMethod) => {
			const codeMirror = (<HTMLElement & {
				CodeMirror?: Partial<Record<'getSelection' | 'getValue', () => string>>
			}>element).CodeMirror

			return codeMirror?.[editorMethod]?.() ?? ''
		}, method)

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

			// The type badge renders in the modal content (the minimum-supported
			// WordPress Modal has no headerActions prop).
			await expect(preview.locator('.code-snippets-preview-modal__badge .badge'))
				.toBeVisible()

			expect(pageErrors).toEqual([])
		})

	test('Preview code can be selected with the keyboard without being changed', async ({ page }) => {
		const editor = await openPreviewEditor(page)
		const initialValue = await readPreviewEditor(editor, 'getValue')
		expect(initialValue).not.toBe('')
		await focusPreviewEditor(page, editor)
		await page.keyboard.press('Shift+ArrowRight')

		await expect.poll(() => readPreviewEditor(editor, 'getSelection')).not.toBe('')
		await expect.poll(() => readPreviewEditor(editor, 'getValue')).toBe(initialValue)
	})

	test('Preview editor exposes its accessible label', async ({ page }) => {
		const editor = await openPreviewEditor(page)

		await expect(editor.locator('[aria-label="Snippet code preview"]')).toBeAttached()
	})

	test('Preview type badge stays aligned with the title', async ({ page }) => {
		await openPreviewEditor(page)

		const modal = page.locator('.code-snippets-preview-modal')
		const title = modal.locator('.components-modal__header-heading')
		const badge = modal.locator('.code-snippets-preview-modal__badge')
		const closeButton = modal.locator('.components-modal__header').getByRole('button', { name: 'Close' })

		for (const width of PREVIEW_VIEWPORT_WIDTHS) {
			await page.setViewportSize({ width, height: 800 })
			const [titleBox, badgeBox, closeBox] =
				await Promise.all([title.boundingBox(), badge.boundingBox(), closeButton.boundingBox()])

			expect(titleBox).not.toBeNull()
			expect(badgeBox).not.toBeNull()
			expect(closeBox).not.toBeNull()

			if (titleBox && badgeBox && closeBox) {
				const titleCenter = titleBox.y + titleBox.height / 2
				const badgeCenter = badgeBox.y + badgeBox.height / 2

				expect(Math.abs(titleCenter - badgeCenter)).toBeLessThanOrEqual(MAXIMUM_BADGE_ALIGNMENT_OFFSET)
				expect(titleBox.x + titleBox.width).toBeLessThan(badgeBox.x)
				expect(badgeBox.x + badgeBox.width).toBeLessThanOrEqual(closeBox.x)
			}
		}
	})

	for (const keypress of <const> ['Tab', 'Shift+Tab']) {
		test(`${keypress} leaves the preview editor`, async ({ page }) => {
			const editor = await openPreviewEditor(page)
			await focusPreviewEditor(page, editor)

			await page.keyboard.press(keypress)

			await expect.poll(
				() => editor.evaluate(element => element.contains(document.activeElement))
			).toBe(false)
		})
	}
})
