import { expect, test } from '@playwright/test'
import { SELECTORS, TIMEOUTS, URLS } from './helpers/constants'
import type { Page } from '@playwright/test'

interface LintResult {
	message: string
	severity: string
}

const lintCss = async (page: Page, css: string): Promise<LintResult[]> =>
	page.evaluate(text => {
		const { wp } = <{ wp: {
			CodeMirror: { helpers: { lint: { css: (text: string, options: unknown) => LintResult[] } } }
			codeEditor: { defaultSettings: { csslint: unknown } }
		} }><unknown>window

		// The same CSSLint ruleset WordPress passes to the editor.
		return wp.CodeMirror.helpers.lint.css(text, wp.codeEditor.defaultSettings.csslint)
			.map(({ message, severity }) => ({ message, severity }))
	}, css)

test.describe('CSS snippet linting', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForSelector(SELECTORS.CODE_MIRROR_TEXTAREA, { timeout: TIMEOUTS.DEFAULT })
	})

	test('The fr unit is not reported as an error', async ({ page }) => {
		expect(await lintCss(page, '.grid { grid-template-columns: minmax(0, 1fr) 2fr; }')).toEqual([])
	})

	test('The fr unit is not reported in upper case either', async ({ page }) => {
		expect(await lintCss(page, '.grid { grid-template-columns: 1FR 2Fr; }')).toEqual([])
	})

	test('Custom properties starting with a digit or a hyphen are not reported', async ({ page }) => {
		const css = ':root { --0: 1px; ---x: 2px; }\n.box { margin: var(--0) var(---x); }'
		expect(await lintCss(page, css)).toEqual([])
	})

	test('Custom properties and var() are not reported', async ({ page }) => {
		const css = ':root { --main-color: #fff; --gap: 4px; }\n.box { color: var(--main-color); margin: var(--gap, 2px); }'
		expect(await lintCss(page, css)).toEqual([])
	})

	test('Real errors are still reported', async ({ page }) => {
		expect(await lintCss(page, '.box { colr: red; }'))
			.toContainEqual({ message: "Unknown property 'colr'.", severity: 'warning' })

		const unclosed = await lintCss(page, '.open { color: red;')
		expect(unclosed.some(({ severity }) => 'error' === severity)).toBe(true)
	})
})
