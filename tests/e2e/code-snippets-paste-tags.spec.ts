import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import type { Page } from '@playwright/test'

interface CodeMirrorHost {
	CodeMirror: {
		setValue: (value: string) => void
		getValue: () => string
		replaceRange: (
			text: string,
			from: { line: number, ch: number },
			to: { line: number, ch: number },
			origin: string
		) => void
	}
}

/**
 * Put code into the editor with the origin CodeMirror reports for a real paste.
 * Typing produces a different origin and must not be treated the same way.
 */
const enterCode = async (page: Page, code: string, origin: 'paste' | '+input'): Promise<void> => {
	await page.locator('.CodeMirror').first().waitFor({ state: 'visible' })

	await page.evaluate(([text, changeOrigin]) => {
		const cm = (<CodeMirrorHost><unknown>document.querySelector('.CodeMirror')).CodeMirror
		cm.setValue('')
		cm.replaceRange(text, { line: 0, ch: 0 }, { line: 0, ch: 0 }, changeOrigin)
	}, [code, origin])

	await page.waitForTimeout(400)
}

const editorValue = (page: Page): Promise<string> =>
	page.evaluate(() =>
		(<CodeMirrorHost><unknown>document.querySelector('.CodeMirror')).CodeMirror.getValue())

test.describe('Wrapper tags in pasted code', () => {
	let helper: SnippetsTestHelper

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page)
		await helper.navigateToSnippetsAdmin()
		await helper.clickAddNewSnippet()
	})

	test('removes an opening PHP tag and says what it removed', async ({ page }) => {
		await enterCode(page, '<?php\nreturn 1;', 'paste')

		expect(await editorValue(page)).toBe('return 1;')
		await expect(page.locator('.code-snippets-notice')).toContainText('opening PHP tag')
	})

	test('removes a markdown code fence along with the tag', async ({ page }) => {
		await enterCode(page, '```php\n<?php\nreturn 1;\n```', 'paste')

		expect(await editorValue(page)).toBe('return 1;')
		await expect(page.locator('.code-snippets-notice')).toContainText('code fence')
	})

	test('leaves code alone when there is nothing to remove', async ({ page }) => {
		await enterCode(page, 'return 42;', 'paste')

		expect(await editorValue(page)).toBe('return 42;')
		await expect(page.locator('.code-snippets-notice')).toHaveCount(0)
	})

	test('leaves tags that appear partway through the code', async ({ page }) => {
		const code = 'if ( true ) { ?>\n<p>markup</p>\n<?php }'
		await enterCode(page, code, 'paste')

		expect(await editorValue(page)).toBe(code)
		await expect(page.locator('.code-snippets-notice')).toHaveCount(0)
	})

	test('does not interfere with a tag that is typed rather than pasted', async ({ page }) => {
		await enterCode(page, '<?php\nreturn 1;', '+input')

		expect(await editorValue(page)).toContain('<?php')
		await expect(page.locator('.code-snippets-notice')).toHaveCount(0)
	})
})
