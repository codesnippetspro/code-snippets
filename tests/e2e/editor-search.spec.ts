import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'
import type { Page } from '@playwright/test'

const SEARCH_DIALOG = '.cs-editor-search-form'
const SEARCH_INPUT = '.cs-editor-search-field'
const SEARCH_STATUS = '.cs-editor-search-status'
const CLOSE_BUTTON = '.cs-editor-search-close'

// CodeMirror replaces the labelled textarea with an input of its own, so key
// presses must land on the editor itself for its key map to see them. The
// plugin picks the search shortcut from the browser's user agent, so the test
// does the same instead of looking at the machine running the suite.
const getFinderKey = async (page: Page): Promise<string> =>
	page.evaluate(() => /mac/i.test(window.navigator.userAgent) ? 'Meta+F' : 'Control+F')

const SEED_CODE = [
	'<?php',
	'// first marker',
	'function first_one() {}',
	'function second_one() {}'
].join('\n')

type EditorCommand =
	| { action: 'setValue', value: string }
	| { action: 'setKeyMap', value: string }
	| { action: 'focus' }
	| { action: 'getCursorColumn' }

type EditorCommandResult = boolean | number

interface CodeMirrorLike {
	setValue?: (value: string) => void
	setOption?: (option: string, value: unknown) => void
	focus?: () => void
	getCursor?: (where?: string) => { ch: number }
}

// Runs one command against the page's CodeMirror instance. The callback is
// self-contained and receives everything through a serializable argument,
// because Playwright cannot pass functions or closures across the page
// boundary.
const runEditorCommand = (page: Page, command: EditorCommand): Promise<EditorCommandResult> =>
	page.evaluate(request => {
		const wrapper = document.querySelector<HTMLElement>('.CodeMirror')
		const instance = <{ CodeMirror?: unknown } | null><unknown>wrapper
		const editor = <CodeMirrorLike | undefined>instance?.CodeMirror

		if (!editor) {
			return 'getCursorColumn' === request.action ? -1 : false
		}

		switch (request.action) {
			case 'setValue':
				if ('function' !== typeof editor.setValue) {
					return false
				}
				editor.setValue.call(editor, request.value)
				return true
			case 'setKeyMap':
				if ('function' !== typeof editor.setOption) {
					return false
				}
				editor.setOption.call(editor, 'keyMap', request.value)
				return true
			case 'focus':
				if ('function' !== typeof editor.focus) {
					return false
				}
				editor.focus.call(editor)
				return true
			case 'getCursorColumn':
				if ('function' !== typeof editor.getCursor) {
					return -1
				}
				return editor.getCursor.call(editor, 'head').ch
		}
	}, command)

const seedEditor = async (page: Page): Promise<void> => {
	await expect.poll(() => runEditorCommand(page, { action: 'setValue', value: SEED_CODE })).toBe(true)
}

const setEditorKeyMap = async (page: Page, keyMap: string): Promise<void> => {
	await expect.poll(() => runEditorCommand(page, { action: 'setKeyMap', value: keyMap })).toBe(true)
}

const focusEditor = async (page: Page): Promise<void> => {
	await expect.poll(() => runEditorCommand(page, { action: 'focus' })).toBe(true)
}

const getCursorColumn = (page: Page): Promise<number> =>
	<Promise<number>>runEditorCommand(page, { action: 'getCursorColumn' })

const openSearchDialog = async (page: Page): Promise<void> => {
	await page.locator('.CodeMirror').first().click()
	await page.keyboard.press(await getFinderKey(page))
}

const expectEditorFocused = async (page: Page): Promise<void> => {
	const focused = await page.evaluate(() =>
		Boolean(document.activeElement?.closest('.CodeMirror'))
	)
	expect(focused).toBe(true)
}

test.describe('Persistent editor search', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForSelector('.CodeMirror')

		await seedEditor(page)
	})

	test('Ctrl or Cmd F opens the dialog and focuses its input', async ({ page }) => {
		await openSearchDialog(page)

		await expect(page.locator(SEARCH_DIALOG)).toBeVisible()
		await expect(page.locator(SEARCH_INPUT)).toBeFocused()
	})

	test('Enter keeps focus in the input and steps through matches', async ({ page }) => {
		await openSearchDialog(page)
		await page.keyboard.type('first')

		// The first Enter commits the query and lands on the first match.
		await page.keyboard.press('Enter')
		await expect(page.locator(SEARCH_STATUS)).toContainText('Match 1 of 2')
		await expect(page.locator(SEARCH_INPUT)).toBeFocused()

		// A second Enter moves on but must not close the dialog.
		await page.keyboard.press('Enter')
		await expect(page.locator(SEARCH_STATUS)).toContainText('Match 2 of 2')
		await expect(page.locator(SEARCH_INPUT)).toBeFocused()

		// At the end the search wraps back to the first match.
		await page.keyboard.press('Enter')
		await expect(page.locator(SEARCH_STATUS)).toContainText('Match 1 of 2')
		await expect(page.locator(SEARCH_DIALOG)).toBeVisible()
	})

	test('Shift Enter walks backwards through the matches', async ({ page }) => {
		await openSearchDialog(page)
		await page.keyboard.type('first')
		await page.keyboard.press('Enter')
		await expect(page.locator(SEARCH_STATUS)).toContainText('Match 1 of 2')

		await page.keyboard.press('Shift+Enter')
		await expect(page.locator(SEARCH_STATUS)).toContainText('Match 2 of 2')
		await expect(page.locator(SEARCH_INPUT)).toBeFocused()
	})

	test('A query without matches shows an accessible no results state', async ({ page }) => {
		await openSearchDialog(page)
		await page.keyboard.type('nothing-matches-this')
		await page.keyboard.press('Enter')

		const status = page.locator(SEARCH_STATUS)
		await expect(status).toContainText('No results found')
		await expect(status).toBeVisible()
		await expect(status).toHaveAttribute('aria-live', 'polite')

		// A failed search must not hand focus back to the code editor, where
		// the next keypress would type into the snippet.
		await expect(page.locator(SEARCH_DIALOG)).toBeVisible()
		await expect(page.locator(SEARCH_INPUT)).toBeFocused()
	})

	test('Escape closes the dialog and returns focus to the editor', async ({ page }) => {
		await openSearchDialog(page)
		await expect(page.locator(SEARCH_INPUT)).toBeFocused()

		await page.keyboard.press('Escape')
		await expect(page.locator(SEARCH_DIALOG)).toBeHidden()

		await expectEditorFocused(page)
	})

	test('The close button closes the dialog and refocuses the editor', async ({ page }) => {
		await openSearchDialog(page)
		await expect(page.locator(SEARCH_DIALOG)).toBeVisible()

		await page.locator(CLOSE_BUTTON).click()
		await expect(page.locator(SEARCH_DIALOG)).toBeHidden()

		await expectEditorFocused(page)
	})

	test('Tab still inserts a tab under the Default keymap', async ({ page }) => {
		await focusEditor(page)
		await expect(page.locator('.CodeMirror-focused')).toBeVisible()

		const before = await getCursorColumn(page)
		await page.keyboard.press('Tab')
		const after = await getCursorColumn(page)

		expect(after).toBeGreaterThan(before)
	})

	for (const keyMap of ['default', 'vim', 'emacs', 'sublime']) {
		test(`The dialog still opens with the ${keyMap} keymap active`, async ({ page }) => {
			await setEditorKeyMap(page, keyMap)

			await openSearchDialog(page)

			await expect(page.locator(SEARCH_DIALOG)).toBeVisible()
			await expect(page.locator(SEARCH_INPUT)).toBeFocused()
		})
	}
})
