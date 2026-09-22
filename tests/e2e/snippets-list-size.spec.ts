import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { TIMEOUTS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'

/**
 * The manage screen embeds a capped list of snippets in the page so the table
 * paints immediately, then replaces it with the complete list from the REST API.
 * A library larger than that cap is therefore only shown correctly once the
 * request lands, and must never be presented as complete before it does.
 */

const PREFIX = 'E2E ListSize'

// Comfortably past the 100-snippet cap the page embeds.
const CREATE_COUNT = 120

const ALL_COUNT = '.all-type-link .subnav-count'

/** Matches the snippets collection request, on pretty and plain permalinks alike, but not a single snippet. */
const isListRequest = (url: string): boolean =>
	/rest_route=%2Fcode-snippets%2Fv1%2Fsnippets(?:&|$)/.test(url) ||
	/\/wp-json\/code-snippets\/v1\/snippets(?:\?|$)/.test(url)

const createManySnippets = async (count: number): Promise<void> => {
	const php = `
		$body = str_repeat( "// padding so each snippet carries a realistic code body\\n", 20 );
		for ( $i = 1; $i <= ${count}; $i++ ) {
			$snippet = new \\Code_Snippets\\Model\\Snippet();
			$snippet->name = sprintf( ${JSON.stringify(`${PREFIX} %03d`)}, $i );
			$snippet->code = $body . sprintf( 'add_action( "init", function () { /* %03d */ } );', $i );
			$snippet->scope = 'global';
			$snippet->active = false;
			\\Code_Snippets\\save_snippet( $snippet );
		}
	`
	await wpCli(['eval', php])
}

/** How many snippets the screen should report, which excludes trashed ones. */
const untrashedCount = async (): Promise<number> =>
	Number(await wpCli([
		'eval',
		'global $wpdb; echo (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}snippets WHERE active <> -1" );'
	]))

test.describe('A library larger than the embedded list', () => {
	let expectedTotal = 0

	test.beforeAll(async () => {
		await createManySnippets(CREATE_COUNT)
		expectedTotal = await untrashedCount()
	})

	test.afterAll(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(PREFIX)
	})

	test('shows every snippet, not only the set embedded in the page', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=snippets')

		const embedded = await page.evaluate(() =>
			(<{ CODE_SNIPPETS_MANAGE?: { snippetsList?: unknown[] } }><unknown>window).CODE_SNIPPETS_MANAGE?.snippetsList?.length ?? 0)

		expect(embedded, 'the page should embed only a partial list, or this test proves nothing')
			.toBeLessThan(expectedTotal)

		await expect(page.locator(ALL_COUNT))
			.toHaveText(String(expectedTotal), { timeout: TIMEOUTS.DEFAULT })
	})

	test('never asks for snippet code when it only needs to list them', async ({ page }) => {
		const listUrls: string[] = []
		page.on('request', request => {
			if (isListRequest(request.url())) {
				listUrls.push(request.url())
			}
		})

		await page.goto('/wp-admin/admin.php?page=snippets')
		await expect(page.locator(ALL_COUNT)).toHaveText(String(expectedTotal), { timeout: TIMEOUTS.DEFAULT })

		expect(listUrls.length, 'the list should have been requested').toBeGreaterThan(0)
		for (const url of listUrls) {
			expect(decodeURIComponent(url), 'the list request should name the fields it wants').toContain('_fields=')
			expect(decodeURIComponent(url), 'the list request should not ask for snippet code').not.toMatch(/(?:^|,)code(?:,|&|$)/)
		}
	})

	test('still shows a snippet\'s code when it is previewed from the list', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=snippets')
		await expect(page.locator(ALL_COUNT)).toHaveText(String(expectedTotal), { timeout: TIMEOUTS.DEFAULT })

		// The list omits code, so the modal has to fetch the body it displays.
		const row = page.locator('tbody tr').filter({ hasText: PREFIX }).first()
		await row.getByRole('button', { name: 'Preview', exact: true }).click()

		const editor = page.locator('.code-snippets-preview-modal .CodeMirror')
		await expect(editor).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		await expect
			.poll(
				() => editor.evaluate(el =>
					(<{ CodeMirror?: { getValue: () => string } }><unknown>el).CodeMirror?.getValue() ?? ''),
				{ timeout: TIMEOUTS.DEFAULT }
			)
			.toContain('add_action')
	})

	test('keeps the count honest and says so when the list cannot be loaded', async ({ page }) => {
		await page.route(url => isListRequest(url.toString()), route => route.abort('failed'))

		await page.goto('/wp-admin/admin.php?page=snippets')

		// The count comes from the server, so it stays true even though the table below it is short.
		await expect(page.locator(ALL_COUNT))
			.toHaveText(String(expectedTotal), { timeout: TIMEOUTS.DEFAULT })

		await expect(page.locator('.notice-error'))
			.toContainText('Could not load the complete list of snippets', { timeout: TIMEOUTS.DEFAULT })
	})
})
