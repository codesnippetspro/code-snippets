import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'
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

const createPageWithShortcode = async (page: Page, snippetId: string): Promise<string> => {
	const shortcode = `[code_snippet id=${snippetId} format name="${TEST_SNIPPET_NAME}"]`
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

		const pageUrl = (await wpCli(['post', 'url', pageId])).trim()
		return pageUrl
	} catch (error) {
		console.error('Failed to create page via WP-CLI. Falling back to UI/API creation.', error)

		try {
			// Fallback: create a published page via WP REST API from within WP Admin (uses nonce + cookies).
			// This avoids direct Gutenberg UI interactions while still exercising shortcode rendering on the front-end.
			const pageUrl = await page.evaluate(
				async ({ title, content }) => {
					const apiFetch = (window as any)?.wp?.apiFetch
					const nonce = (window as any)?.wpApiSettings?.nonce

					if (apiFetch) {
						const created = await apiFetch({
							path: '/wp/v2/pages',
							method: 'POST',
							data: { title, content, status: 'publish' }
						})
						return created?.link ?? ''
					}

					if (!nonce) {
						throw new Error('Missing wpApiSettings.nonce for REST fallback.')
					}

					const response = await fetch('/wp-json/wp/v2/pages', {
						method: 'POST',
						credentials: 'same-origin',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': nonce
						},
						body: JSON.stringify({ title, content, status: 'publish' })
					})

					if (!response.ok) {
						const text = await response.text().catch(() => '')
						throw new Error(`REST create page failed: ${response.status} ${response.statusText} ${text}`)
					}

					const created = await response.json()
					return created?.link ?? ''
				},
				{ title: 'Test Page for Snippet Shortcode', content: pageContent }
			)

			if (!pageUrl) {
				throw new Error('REST fallback returned empty page URL.')
			}

			return pageUrl
		} catch (fallbackError) {
			console.error('Failed to create page via REST fallback:', fallbackError)
			throw error
		}
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
		const pageUrl = await createPageWithShortcode(page, snippetId)

		await verifyShortcodeRendersCorrectly(helper, page, pageUrl)
	})

	test.afterEach(async () => {
		await helper.cleanupSnippet(TEST_SNIPPET_NAME)
	})
})
