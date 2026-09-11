import { expect, test } from '@playwright/test'
import { CODE_EDITOR_SELECTOR, pasteIntoEditor, readEditorValue, typeIntoEditor } from './helpers/codeEditor'
import { URLS } from './helpers/constants'
import { DEFAULT_E2E_SNIPPET_BASE_NAME, SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { wpCli } from './helpers/wpCli'
import type { Page } from '@playwright/test'

const setEditorSetting = (field: string, value: string | number | boolean): Promise<string> =>
	wpCli(['eval', `\\Code_Snippets\\Settings\\update_setting( 'editor', '${field}', ${JSON.stringify(value)} );`])

const resetEditorSettings = (): Promise<string> =>
	wpCli(['eval', `
		$settings = \\Code_Snippets\\Settings\\get_settings_values();
		$settings['editor'] = \\Code_Snippets\\Settings\\Settings_Fields::get_default_values()['editor'];
		update_option( 'code_snippets_settings', $settings );
	`])

const openNewSnippet = async (page: Page) => {
	await page.goto(URLS.ADD_SNIPPET_ADMIN)
	const editor = page.locator(`.snippet-editor ${CODE_EDITOR_SELECTOR}`)
	await expect(editor).toBeVisible()
	return editor
}

const selectType = async (page: Page, label: string): Promise<void> => {
	await page.locator('.snippet-type-container .code-snippets-select').click()
	await page.getByRole('listbox').getByRole('option', { name: new RegExp(label, 'i') }).click()
}

test.describe('Code editor', () => {
	test.afterEach(async () => {
		await resetEditorSettings()
	})

	test('loads without the editor bundled with WordPress', async ({ page }) => {
		const pageErrors: string[] = []
		page.on('pageerror', error => pageErrors.push(error.message))

		await openNewSnippet(page)

		expect(await page.locator('script[src*="wp-includes/js/codemirror"]').count()).toBe(0)
		expect(await page.evaluate(() => 'CodeMirror' in window)).toBe(false)
		expect(pageErrors).toEqual([])
	})

	test('saves edited code with the keyboard shortcut and shows it when reopened', async ({ page }) => {
		const helper = new SnippetsTestHelper(page)
		const name = SnippetsTestHelper.makeUniqueSnippetName()
		const code = 'function cs_e2e_example( $value ) {\n\treturn $value;\n}'

		const editor = await openNewSnippet(page)
		await page.locator('#title').fill(name)
		await pasteIntoEditor(page, editor, code)
		await page.keyboard.press('ControlOrMeta+S')
		await expect(page.getByRole('status').filter({ hasText: 'Snippet created' })).toBeVisible()

		await helper.navigateToSnippetsAdmin()
		await helper.openSnippet(name)
		await expect.poll(() => readEditorValue(page.locator(`.snippet-editor ${CODE_EDITOR_SELECTOR}`))).toBe(code)

		await helper.cleanupSnippet(name)
		await SnippetsTestHelper.cleanupSnippetsByPrefix(DEFAULT_E2E_SNIPPET_BASE_NAME)
	})

	test('closes brackets and indents typed code', async ({ page }) => {
		const editor = await openNewSnippet(page)

		await typeIntoEditor(page, editor, '')
		await page.keyboard.type('if ( true ) {')
		await page.keyboard.press('Enter')
		await page.keyboard.type('return;')

		await expect.poll(() => readEditorValue(editor)).toBe('if ( true ) {\n\treturn;\n}')
	})

	test('highlights PHP variables and content markup with theme token classes', async ({ page }) => {
		const editor = await openNewSnippet(page)
		await pasteIntoEditor(page, editor, '$greeting = "hello";')
		await expect(editor.locator('.cm-variable-2', { hasText: '$greeting' })).toBeVisible()
		await expect(editor.locator('.cm-string', { hasText: '"hello"' })).toBeVisible()

		await selectType(page, 'Content')
		await pasteIntoEditor(page, editor, '<p class="note"><?php echo $greeting; ?></p>')
		await expect(editor.locator('.cm-tag', { hasText: 'p' }).first()).toBeVisible()
		await expect(editor.locator('.cm-attribute', { hasText: 'class' })).toBeVisible()
		await expect(editor.locator('.cm-variable-2', { hasText: '$greeting' })).toBeVisible()
	})

	test('colours fold markers so they stay visible on dark themes', async ({ page }) => {
		await setEditorSetting('theme', 'dracula')
		const editor = await openNewSnippet(page)
		await pasteIntoEditor(page, editor, 'if ( true ) {\n\treturn;\n}')

		const foldMarker = editor.locator('.cm-foldGutter .cm-gutterElement', { hasText: /\S/ }).first()
		await expect(foldMarker).toHaveCSS('color', 'rgb(153, 153, 153)')
	})

	test('reports PHP errors in the lint gutter', async ({ page }) => {
		const editor = await openNewSnippet(page)
		await pasteIntoEditor(page, editor, 'function example() {}\nfunction example() {}')

		await expect(editor.locator('.cm-lint-marker-error')).toBeVisible()
	})

	test('applies the saved editor settings', async ({ page }) => {
		await setEditorSetting('line_numbers', false)
		await setEditorSetting('theme', 'monokai')
		await setEditorSetting('font_size', 18)

		const editor = await openNewSnippet(page)

		await expect(editor.locator('.cm-lineNumbers')).toHaveCount(0)
		await expect(editor).toHaveClass(/cm-s-monokai/)
		await expect(editor).toHaveCSS('background-color', 'rgb(39, 40, 34)')
		await expect(editor).toHaveCSS('font-size', '18px')
	})

	for (const keymap of <const> ['emacs', 'vim', 'sublime']) {
		test(`starts with the ${keymap} keymap selected`, async ({ page }) => {
			const pageErrors: string[] = []
			page.on('pageerror', error => pageErrors.push(error.message))
			await setEditorSetting('keymap', keymap)

			const editor = await openNewSnippet(page)
			await editor.locator('.cm-content').click()

			if ('vim' === keymap) {
				await expect(editor.locator('.cm-fat-cursor')).toBeVisible()
			}

			if ('sublime' === keymap) {
				await pasteIntoEditor(page, editor, 'echo 1;')
				await page.keyboard.press('Shift+ControlOrMeta+D')
				await expect.poll(() => readEditorValue(editor)).toBe('echo 1;\necho 1;')
			}

			expect(pageErrors).toEqual([])
		})
	}

	test('previews setting changes on the settings page', async ({ page }) => {
		await page.goto(`${URLS.SETTINGS_ADMIN}&section=editing`)
		const preview = page.locator(`.settings-section ${CODE_EDITOR_SELECTOR}`).first()
		await expect(preview).toBeVisible()
		await expect(preview.locator('.cm-lineNumbers')).toBeVisible()

		await page.locator('[name="code_snippets_settings[editor][line_numbers]"]').uncheck()
		await expect(preview.locator('.cm-lineNumbers')).toHaveCount(0)

		await page.locator('[name="code_snippets_settings[editor][theme]"]').selectOption('dracula')
		await expect(preview).toHaveClass(/cm-s-dracula/)
		await expect(preview).toHaveCSS('background-color', 'rgb(40, 42, 54)')
	})
})
