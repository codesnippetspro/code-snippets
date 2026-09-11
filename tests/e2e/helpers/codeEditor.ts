import type { Locator, Page } from '@playwright/test'

export const CODE_EDITOR_SELECTOR = '.cm-editor'

const META_OR_CONTROL_A = 'ControlOrMeta+A'

/**
 * Replace the editor's contents the way a clipboard paste does, so the editor
 * treats the change as pasted rather than typed.
 */
export const pasteIntoEditor = async (page: Page, editor: Locator, text: string): Promise<void> => {
	const content = editor.locator('.cm-content')
	await content.click()
	await page.keyboard.press(META_OR_CONTROL_A)

	await content.evaluate((element, pasted) => {
		const clipboardData = new DataTransfer()
		clipboardData.setData('text/plain', pasted)
		element.dispatchEvent(new ClipboardEvent('paste', { clipboardData, bubbles: true, cancelable: true }))
	}, text)
}

/**
 * Replace the editor's contents by typing.
 */
export const typeIntoEditor = async (page: Page, editor: Locator, text: string): Promise<void> => {
	await editor.locator('.cm-content').click()
	await page.keyboard.press(META_OR_CONTROL_A)
	await page.keyboard.press('Delete')
	await page.keyboard.insertText(text)
}

/**
 * The document currently shown in the editor.
 */
export const readEditorValue = (editor: Locator): Promise<string> =>
	editor.locator('.cm-content').evaluate(content =>
		Array.from(content.querySelectorAll(':scope > .cm-line'), line => line.textContent ?? '').join('\n'))
