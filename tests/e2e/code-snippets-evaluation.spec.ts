import util from 'util'
import { exec } from 'child_process'
import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS } from './helpers/constants'
import type { Page } from '@playwright/test'

const TEST_SNIPPET_NAME = 'E2E Snippet Test'

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

const createPageWithShortcode = async (snippetId: string): Promise<string> => {
	const execAsync = util.promisify(exec)

	const shortcode = `[code_snippet id=${snippetId} format name="${TEST_SNIPPET_NAME}"]`
	const pageContent = `<p>Page content before shortcode.</p>\n\n${shortcode}\n\n<p>Page content after shortcode.</p>`

	try {
		const createPageCmd = [
			'npx wp-env run cli wp post create',
			'--post_type=page',
			'--post_title="Test Page for Snippet Shortcode"',
			`--post_content='${pageContent}'`,
			'--post_status=publish',
			'--porcelain'
		].join(' ')

		const { stdout } = await execAsync(createPageCmd)
		const pageId = stdout.trim()

		const getUrlCmd = `npx wp-env run cli wp post url ${pageId}`
		const { stdout: pageUrl } = await execAsync(getUrlCmd)
		return pageUrl.trim()
	} catch (error) {
		console.error('Failed to create page via WP-CLI:', error)
		throw error
	}
}

const createHtmlSnippetForEditor = async (helper: SnippetsTestHelper, page: Page): Promise<string> => {
	await helper.createAndActivateSnippet({
		name: TEST_SNIPPET_NAME,
		code: '<div class="custom-snippet-content">' +
			'<h3>Custom HTML Content</h3><p>This content was inserted via shortcode!</p></div>',
		type: 'HTML',
		location: 'IN_EDITOR'
	})

	const currentUrl = page.url()
	const urlMatch = /[?&]id=(?<id>\d+)/.exec(currentUrl)
	expect(urlMatch).toBeTruthy()
	return urlMatch?.groups?.id ?? '0'
}

test.describe('Code Snippets Evaluation', () => {
	let helper: SnippetsTestHelper

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page)
		await helper.navigateToSnippetsAdmin()
	})

	test('PHP snippet is evaluating correctly', async () => {
		await helper.createAndActivateSnippet({
			name: TEST_SNIPPET_NAME,
			code: "add_filter('show_admin_bar', '__return_false');"
		})

		await helper.navigateToFrontend()
		await helper.expectElementNotVisible(SELECTORS.ADMIN_BAR)
		await helper.expectElementCount(SELECTORS.ADMIN_BAR, 0)
	})

	test('PHP Snippet runs everywhere', async ({ page }) => {
		await helper.createAndActivateSnippet({
			name: TEST_SNIPPET_NAME,
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
			name: TEST_SNIPPET_NAME,
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
			name: TEST_SNIPPET_NAME,
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
			name: TEST_SNIPPET_NAME,
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
			name: TEST_SNIPPET_NAME,
			code: '<p>Hello World HTML snippet in header!</p>',
			type: 'HTML',
			location: 'SITE_HEADER'
		})

		await helper.navigateToFrontend()
		await helper.expectTextVisible('Hello World HTML snippet in header!')
		await helper.expectElementCount('text=Hello World HTML snippet in header!', 1)
	})

	test('HTML snippet works with shortcode in editor', async ({ page }) => {
		const snippetId = await createHtmlSnippetForEditor(helper, page)
		const pageUrl = await createPageWithShortcode(snippetId)

		await verifyShortcodeRendersCorrectly(helper, page, pageUrl)
	})

	test.afterEach(async () => {
		await helper.cleanupSnippet(TEST_SNIPPET_NAME)
	})
})
