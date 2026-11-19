import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS, TIMEOUTS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'
import type { Page } from '@playwright/test'

/**
 * Safari and Firefox on macOS do not focus a button when it is clicked, so the editor
 * has to tell which submit button was pressed without relying on focus.
 */

const PREFIX = 'E2E Submit Action'

const getActiveState = async (name: string): Promise<string> =>
	(await wpCli(['eval', `
		foreach (\\Code_Snippets\\get_snippets() as $snippet) {
			if ($snippet->name === ${JSON.stringify(name)}) {
				echo $snippet->active ? 'active' : 'inactive';
				exit;
			}
		}
		echo 'missing';
	`])).trim()

const createSnippet = async (name: string, active: boolean): Promise<void> => {
	await wpCli(['eval', `
		$snippet = new \\Code_Snippets\\Model\\Snippet([
			'name' => ${JSON.stringify(name)},
			'code' => ${JSON.stringify('// submit action\n')},
			'scope' => 'global',
			'active' => ${active ? 'true' : 'false'},
		]);
		echo \\Code_Snippets\\save_snippet($snippet)->id;
	`])
}

const clickWithoutFocus = async (page: Page, name: RegExp): Promise<void> => {
	const button = page.getByRole('button', { name }).first()
	await button.evaluate(element => element.addEventListener('mousedown', event => event.preventDefault()))
	await button.click()
}

const waitForSave = async (page: Page): Promise<void> => {
	await page.locator(SELECTORS.SAVE_SETTLED_NOTICE).first().waitFor({ state: 'visible', timeout: TIMEOUTS.DEFAULT })
}

test.describe('Snippet editor submit actions', () => {
	let helper: SnippetsTestHelper
	let snippetName: string

	test.beforeEach(({ page }) => {
		helper = new SnippetsTestHelper(page)
		snippetName = SnippetsTestHelper.makeUniqueSnippetName(PREFIX)
	})

	test.afterEach(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(PREFIX)
	})

	test('Save and Activate activates a new snippet when the click does not move focus', async ({ page }) => {
		await helper.clickAddNewSnippet()
		await helper.fillSnippetForm({ name: snippetName, code: '// submit action\n' })

		await clickWithoutFocus(page, /^Save and Activate$/)
		await waitForSave(page)

		expect(await getActiveState(snippetName)).toBe('active')
	})

	test('Save and Deactivate deactivates a snippet when the click does not move focus', async ({ page }) => {
		await createSnippet(snippetName, true)
		await helper.openSnippet(snippetName)

		await clickWithoutFocus(page, /^Save and Deactivate$/)
		await waitForSave(page)

		expect(await getActiveState(snippetName)).toBe('inactive')
	})

	test('pressing Enter in the title field saves without deactivating an active snippet', async ({ page }) => {
		await createSnippet(snippetName, true)
		await helper.openSnippet(snippetName)

		await page.focus(SELECTORS.TITLE_INPUT)
		await page.keyboard.press('Enter')
		await waitForSave(page)

		expect(await getActiveState(snippetName)).toBe('active')
	})
})
