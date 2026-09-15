import { expect, test } from '@playwright/test'
import { DEFAULT_E2E_SNIPPET_BASE_NAME, SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS, URLS } from './helpers/constants'
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

		await page.goto(URLS.WP_ADMIN)
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

		await page.goto(URLS.WP_ADMIN)
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

		await page.goto(URLS.WP_ADMIN)
		await expect(page.locator('body')).not.toHaveClass(/custom-admin-class/)

		await helper.navigateToFrontend()
		await expect(page.locator('body')).toHaveClass(/custom-frontend-class/)
	})

	test('Safe mode query disables front-end snippet execution', async ({ page }) => {
		const safeModeClass = `safe-mode-${Date.now()}`

		await helper.createAndActivateSnippet({
			name: snippetName,
			code: `add_filter('body_class', function($classes) { $classes[] = '${safeModeClass}'; return $classes; });`
		})

		await page.goto(URLS.FRONTEND)
		await expect(page.locator('body')).toHaveClass(new RegExp(safeModeClass))

		await page.goto(`${URLS.FRONTEND}?snippets-safe-mode=1`)
		await expect(page.locator('body')).not.toHaveClass(new RegExp(safeModeClass))

		await page.goto(`${URLS.SNIPPETS_ADMIN}&snippets-safe-mode=1`)
		await page.getByRole('link', { name: 'Add New' }).click()
		await expect(page).toHaveURL(/snippets-safe-mode=1/, { timeout: 5000 })
	})

	test('Safe mode constant disables snippets while keeping the editor accessible', async ({ page }) => {
		const safeModeClass = `safe-mode-constant-${Date.now()}`
		const safeModeMuPluginPath = 'wp-content/mu-plugins/code-snippets-e2e-safe-mode-execution.php'
		const removeMuPlugin = () =>
			wpCli(['eval', `@unlink( ABSPATH . ${JSON.stringify(safeModeMuPluginPath)} );`])
		const enableSafeMode = `
			$path = ABSPATH . ${JSON.stringify(safeModeMuPluginPath)};
			wp_mkdir_p( dirname( $path ) );
			file_put_contents( $path, "<?php\\ndefine( 'CODE_SNIPPETS_SAFE_MODE', true );\\n" );
		`

		await removeMuPlugin()

		try {
			await helper.createAndActivateSnippet({
				name: snippetName,
				code: `add_filter('body_class', function($classes) { $classes[] = '${safeModeClass}'; return $classes; });`
			})
			await page.goto(URLS.FRONTEND)
			await expect(page.locator('body')).toHaveClass(new RegExp(safeModeClass))

			await wpCli(['eval', enableSafeMode])

			await page.goto(URLS.FRONTEND)
			await expect(page.locator('body')).not.toHaveClass(new RegExp(safeModeClass))

			await helper.navigateToSnippetsAdmin()
			const row = page.locator(SELECTORS.SNIPPET_ROW).filter({ hasText: snippetName }).first()
			await row.locator(SELECTORS.SNIPPET_NAME_LINK).click()
			await expect(page.locator('#title')).toBeEnabled()
			await page.getByRole('button', { name: 'Save and Deactivate' }).click()
			await helper.expectSuccessMessage(/Snippet updated/i)

			await helper.navigateToSnippetsAdmin()
			const deactivatedRow = page.locator(SELECTORS.SNIPPET_ROW).filter({ hasText: snippetName }).first()
			await expect(deactivatedRow.getByRole('switch')).not.toBeChecked()
		} finally {
			await removeMuPlugin()
		}
	})

	test('Single-use PHP snippets run once from the list', async ({ page }) => {
		const markerKey = `code_snippets_e2e_single_use_${Date.now()}`

		try {
			await SnippetsTestHelper.createSnippetViaCli({
				name: snippetName,
				active: false,
				scope: 'single-use',
				code: `update_option('${markerKey}', 'ran once');`
			})
			await helper.navigateToSnippetsAdmin()

			const row = page.locator(SELECTORS.SNIPPET_ROW).filter({ hasText: snippetName }).first()
			const runOnce = row.getByRole('link', { name: 'Run Once' })
			await expect(runOnce).toBeVisible()
			await Promise.all([
				page.waitForURL(/result=executed/),
				runOnce.click()
			])

			expect((await wpCli(['option', 'get', markerKey])).trim()).toBe('ran once')
		} finally {
			await wpCli(['eval', `delete_option('${markerKey}');`])
		}
	})

	test('asks for confirmation before running a single-use snippet', async ({ page }) => {
		await SnippetsTestHelper.createSnippetViaCli({
			name: snippetName,
			active: false,
			scope: 'single-use',
			code: '// A harmless Run Once confirmation fixture.'
		})
		await helper.navigateToSnippetsAdmin()

		const row = page.locator(SELECTORS.SNIPPET_ROW).filter({ hasText: snippetName }).first()
		await row.getByRole('link', { name: 'Run Once' }).click()
		await expect(page.getByRole('dialog', { name: /Run Once/ })).toBeVisible({ timeout: 5000 })
	})

	test('PHP snippets execute in priority order', async ({ page }) => {
		const outputPrefix = `snippet-priority-${Date.now()}`
		const highPriorityId = `${outputPrefix}-high`
		const lowPriorityId = `${outputPrefix}-low`
		const highPriorityName = SnippetsTestHelper.makeUniqueSnippetName('High priority snippet')
		const lowPriorityName = SnippetsTestHelper.makeUniqueSnippetName('Low priority snippet')

		try {
			await SnippetsTestHelper.createSnippetViaCli({
				name: highPriorityName,
				active: true,
				priority: 20,
				code: `add_action('wp_footer', function() { echo '<span id="${highPriorityId}"></span>'; });`
			})
			await SnippetsTestHelper.createSnippetViaCli({
				name: lowPriorityName,
				active: true,
				priority: 5,
				code: `add_action('wp_footer', function() { echo '<span id="${lowPriorityId}"></span>'; });`
			})

			await helper.navigateToFrontend()
			await expect(page.locator(`#${lowPriorityId}`)).toBeAttached()
			await expect(page.locator(`#${highPriorityId}`)).toBeAttached()
			expect(await page.locator(`span[id^="${outputPrefix}"]`).evaluateAll(elements =>
				elements.map(({ id }) => id)
			)).toEqual([lowPriorityId, highPriorityId])
		} finally {
			await helper.cleanupSnippet(highPriorityName)
			await helper.cleanupSnippet(lowPriorityName)
		}
	})

	test('CSS snippets load on the front end', async ({ page }) => {
		if (!await SnippetsTestHelper.isProLicensed()) {
			test.skip(true, 'CSS snippets require an active Pro license.')
		}

		const property = `--e2e-css-${Date.now()}`

		await SnippetsTestHelper.createSnippetViaCli({
			name: snippetName,
			active: true,
			type: 'css',
			code: `body { ${property}: loaded; }`
		})

		await page.goto(URLS.FRONTEND)
		await expect.poll(() => page.locator('body').evaluate((body, propertyName) =>
			getComputedStyle(body).getPropertyValue(propertyName).trim(), property)
		).toBe('loaded')
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
