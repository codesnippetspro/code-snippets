import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { URLS } from './helpers/constants'

const LINT_ERROR = '.snippet-editor .CodeMirror-lint-mark-error'

test.describe('CSS snippet linting', () => {
	test('grid fr units are not reported as errors', async ({ page }) => {
		const helper = new SnippetsTestHelper(page)
		await page.goto(URLS.ADD_SNIPPET_ADMIN)

		// A real syntax error first, so the empty check below cannot pass before the linter has run.
		await helper.fillSnippetForm({
			name: SnippetsTestHelper.makeUniqueSnippetName(),
			type: 'CSS',
			code: '.a {\n\tcolor: red;\n'
		})
		await expect(page.locator(LINT_ERROR).first()).toBeVisible()

		await helper.fillSnippetForm({
			name: SnippetsTestHelper.makeUniqueSnippetName(),
			code: '.a {\n\tgrid-template-columns: minmax(0, 1fr) repeat(2, 1fr);\n}\n'
		})
		await expect(page.locator(LINT_ERROR)).toHaveCount(0)
	})
})
