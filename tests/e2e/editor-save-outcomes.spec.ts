import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { TIMEOUTS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'

/**
 * A save whose response never reaches the browser proves nothing about whether
 * the snippet was written. The editor used to report it as a definite failure
 * that never reached the site, which sent people back to re-save work that had
 * in fact been saved.
 */

const PREFIX = 'E2E Save Outcome'

/** Matches the snippets collection and single-snippet routes, on pretty and plain permalinks alike. */
const isSnippetWriteRequest = (url: string): boolean =>
	url.includes('rest_route=%2Fcode-snippets%2Fv1%2Fsnippets') ||
	url.includes('/wp-json/code-snippets/v1/snippets')

test.describe('Reporting a save whose outcome is unknown', () => {
	let helper: SnippetsTestHelper
	let snippetName: string

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page)
		snippetName = SnippetsTestHelper.makeUniqueSnippetName(PREFIX)

		await wpCli(['eval', `
			$snippet = new \\Code_Snippets\\Model\\Snippet([
				'name' => ${JSON.stringify(snippetName)},
				'code' => ${JSON.stringify('// original\n')},
				'scope' => 'global',
				'active' => false,
			]);
			echo \\Code_Snippets\\save_snippet($snippet)->id;
		`])
	})

	test.afterEach(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(PREFIX)
	})

	test('does not claim the request never arrived, or that nothing was saved', async ({ page }) => {
		await helper.openSnippet(snippetName)

		// Drop the save on its way back, leaving the browser without a response.
		await page.route(url => isSnippetWriteRequest(url.toString()), route => route.abort('failed'))

		await helper.saveSnippet()

		const notice = page.locator('.code-snippets-notice.error, .snippet-editor-sidebar .notice.error').first()
		await expect(notice).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		await expect(notice, 'the reader should be told the outcome is unconfirmed')
			.toContainText('could not be confirmed')

		const text = await notice.textContent() ?? ''

		expect(text, 'nothing establishes that the request failed to arrive')
			.not.toContain('did not reach your site')
		expect(text, 'nothing establishes that the snippet was left unsaved')
			.not.toContain('Could not update snippet')
	})
})
