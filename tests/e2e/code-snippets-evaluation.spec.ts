import { expect, test } from '@playwright/test'
import { DEFAULT_E2E_SNIPPET_BASE_NAME, SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'
import type { Page } from '@playwright/test'

const BODY_CLASS_TEST_CODE = `
	add_filter('admin_body_class', function($classes) {
		return $classes . ' custom-admin-class';
	});

	add_filter('body_class', function($classes) {
		$classes[] = 'custom-frontend-class';
		return $classes;
	});
`

const verifyShortcodeRendersCorrectly = async (
	helper: SnippetsTestHelper,
	page: Page,
	pageUrl: string
): Promise<void> => {
	await page.goto(pageUrl)

	await expect(page.locator('.custom-snippet-content')).toBeVisible()
	await expect(page.locator('.custom-snippet-content h3')).toContainText('Custom HTML Content')
	await expect(page.locator('.custom-snippet-content p')).toContainText('This content was inserted via shortcode!')

	await helper.expectTextVisible('Page content before shortcode.')
	await helper.expectTextVisible('Page content after shortcode.')
}

const createPageWithShortcode = async (snippetId: string, snippetName: string): Promise<string> => {
	const shortcode = `[code_snippet id=${snippetId} format name="${snippetName}"]`
	const pageContent = `<p>Page content before shortcode.</p>\n\n${shortcode}\n\n<p>Page content after shortcode.</p>`

	try {
		const pageId = (await wpCli([
			'post',
			'create',
			'--post_type=page',
			'--post_title=Test Page for Snippet Shortcode',
			`--post_content=${pageContent}`,
			'--post_status=publish',
			'--porcelain'
		])).trim()

		return (await wpCli(['post', 'url', pageId])).trim()
	} catch (error) {
		console.error('Failed to create page via WP-CLI.', error)
		// The suite depends on WP-CLI in local/wp-env mode; keep failures explicit to avoid
		// silently exercising a different creation path.
		throw error
	}
}

const createHtmlSnippetForEditor = async (
	helper: SnippetsTestHelper,
	page: Page,
	snippetName: string
): Promise<string> => {
	await helper.createAndActivateSnippet({
		name: snippetName,
		code: '<div class="custom-snippet-content">' +
			'<h3>Custom HTML Content</h3><p>This content was inserted via shortcode!</p></div>',
		type: 'HTML',
		location: 'IN_EDITOR'
	})

	// `createAndActivateSnippet` ends on the list screen; pull the ID from the edit link.
	await helper.navigateToSnippetsAdmin()
	await helper.filterSnippetsByName(snippetName)
	const row = page.locator(SELECTORS.SNIPPET_ROW).filter({ hasText: snippetName }).first()
	await expect(row).toBeVisible()

	const nameLink = row.locator(SELECTORS.SNIPPET_NAME_LINK).first()
	const editHref = await nameLink.evaluate(el => el.getAttribute('href') ?? '')

	const urlMatch = /[?&]id=(?<id>\d+)/.exec(editHref)
	expect(urlMatch).toBeTruthy()
	return urlMatch?.groups?.id ?? '0'
}

test.describe('Code Snippets Evaluation', () => {
	let helper: SnippetsTestHelper
	let snippetName: string

	test.beforeAll(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(DEFAULT_E2E_SNIPPET_BASE_NAME)
	})

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page)
		snippetName = SnippetsTestHelper.makeUniqueSnippetName()

		await helper.navigateToSnippetsAdmin()
	})

	test('PHP snippet is evaluating correctly', async () => {
		await helper.createAndActivateSnippet({
			name: snippetName,
			code: "add_filter('show_admin_bar', '__return_false');"
		})

		await helper.navigateToFrontend()
		await helper.expectElementCount(SELECTORS.ADMIN_BAR, 0)
	})

	test('PHP Snippet runs everywhere', async ({ page }) => {
		await helper.createAndActivateSnippet({
			name: snippetName,
			location: 'EVERYWHERE',
			code: BODY_CLASS_TEST_CODE
		})

		await page.goto('/wp-admin/')
		await expect(page.locator('body')).toHaveClass(/custom-admin-class/)

		await helper.navigateToFrontend()
		await expect(page.locator('body')).toHaveClass(/custom-frontend-class/)
	})

	test('PHP Snippet runs only in Admin', async ({ page }) => {
		await helper.createAndActivateSnippet({
			name: snippetName,
			location: 'ADMIN_ONLY',
			code: BODY_CLASS_TEST_CODE
		})

		await page.goto('/wp-admin/')
		await expect(page.locator('body')).toHaveClass(/custom-admin-class/)

		await helper.navigateToFrontend()
		await expect(page.locator('body')).not.toHaveClass(/custom-frontend-class/)
	})

	test('PHP Snippet runs only in Frontend', async ({ page }) => {
		await helper.createAndActivateSnippet({
			name: snippetName,
			location: 'FRONTEND_ONLY',
			code: BODY_CLASS_TEST_CODE
		})

		await page.goto('/wp-admin/')
		await expect(page.locator('body')).not.toHaveClass(/custom-admin-class/)

		await helper.navigateToFrontend()
		await expect(page.locator('body')).toHaveClass(/custom-frontend-class/)
	})

	test('HTML snippet is evaluating correctly in footer', async () => {
		await helper.createAndActivateSnippet({
			name: snippetName,
			code: '<p>Hello World HTML snippet in footer!</p>',
			type: 'HTML',
			location: 'SITE_FOOTER'
		})

		await helper.navigateToFrontend()
		await helper.expectTextVisible('Hello World HTML snippet in footer!')
		await helper.expectElementCount('text=Hello World HTML snippet in footer!', 1)
	})

	test('HTML snippet is evaluating correctly in header', async () => {
		await helper.createAndActivateSnippet({
			name: snippetName,
			code: '<p>Hello World HTML snippet in header!</p>',
			type: 'HTML',
			location: 'SITE_HEADER'
		})

		await helper.navigateToFrontend()
		await helper.expectTextVisible('Hello World HTML snippet in header!')
		await helper.expectElementCount('text=Hello World HTML snippet in header!', 1)
	})

	test('HTML snippet is evaluating correctly at body start', async () => {
		await helper.createAndActivateSnippet({
			name: snippetName,
			code: '<p>Hello World HTML snippet in body start!</p>',
			type: 'HTML',
			location: 'SITE_BODY'
		})

		await helper.navigateToFrontend()
		await helper.expectTextVisible('Hello World HTML snippet in body start!')
		await helper.expectElementCount('text=Hello World HTML snippet in body start!', 1)
		await helper.expectTextBeforeElement('Hello World HTML snippet in body start!', SELECTORS.THEME_MAIN_WRAPPER)
	})

	test('HTML snippet is evaluating correctly at body end', async () => {
		await helper.createAndActivateSnippet({
			name: snippetName,
			code: '<p>Hello World HTML snippet in body end!</p>',
			type: 'HTML',
			location: 'SITE_FOOTER'
		})

		await helper.navigateToFrontend()
		await helper.expectTextVisible('Hello World HTML snippet in body end!')
		await helper.expectElementCount('text=Hello World HTML snippet in body end!', 1)
		await helper.expectTextAfterElement('Hello World HTML snippet in body end!', SELECTORS.THEME_MAIN_WRAPPER)
	})

	test('HTML snippet works with shortcode in editor', async ({ page }) => {
		const snippetId = await createHtmlSnippetForEditor(helper, page, snippetName)
		const pageUrl = await createPageWithShortcode(snippetId, snippetName)

		await verifyShortcodeRendersCorrectly(helper, page, pageUrl)
	})

	test.afterEach(async () => {
		await helper.cleanupSnippet(snippetName)
	})
})
