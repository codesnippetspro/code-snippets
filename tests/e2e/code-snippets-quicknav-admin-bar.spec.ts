import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { wpCli } from './helpers/wpCli'

const QUICKNAV_PREFIX = 'E2E QuickNav'
const QUICKNAV_PER_PAGE = 2
const QUICKNAV_TEST_TIMEOUT_MS = 180000

test.describe('Admin Bar Snippets QuickNav', () => {
	let activeA: string
	let activeB: string
	let activeC: string
	let inactiveB: string
	let inactiveC: string
	let inactiveA: string

	test.beforeAll(async () => {
		test.setTimeout(QUICKNAV_TEST_TIMEOUT_MS)
		await SnippetsTestHelper.setAdminBarQuickNavSettings({ enabled: true, perPage: QUICKNAV_PER_PAGE })
		await SnippetsTestHelper.cleanupSnippetsByPrefix(QUICKNAV_PREFIX)

		activeA = `${QUICKNAV_PREFIX} Active A`
		activeB = `${QUICKNAV_PREFIX} Active B`
		activeC = `${QUICKNAV_PREFIX} Active C`
		inactiveA = `${QUICKNAV_PREFIX} Inactive A`
		inactiveB = `${QUICKNAV_PREFIX} Inactive B`
		inactiveC = `${QUICKNAV_PREFIX} Inactive Z HTML`

		await SnippetsTestHelper.createSnippetViaCli({ name: activeA, active: true, type: 'php' })
		await SnippetsTestHelper.createSnippetViaCli({ name: activeB, active: true, type: 'php' })
		await SnippetsTestHelper.createSnippetViaCli({ name: activeC, active: true, type: 'php' })
		await SnippetsTestHelper.createSnippetViaCli({ name: inactiveA, active: false, type: 'php' })
		await SnippetsTestHelper.createSnippetViaCli({ name: inactiveB, active: false, type: 'php' })
		await SnippetsTestHelper.createSnippetViaCli({ name: inactiveC, active: false, type: 'html' })
	})

	test.afterAll(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(QUICKNAV_PREFIX)
		await SnippetsTestHelper.setAdminBarQuickNavSettings({ enabled: true, perPage: QUICKNAV_PER_PAGE })
	})

	test('Menu structure and pagination works', async ({ page }) => {
		test.setTimeout(QUICKNAV_TEST_TIMEOUT_MS)

		const helper = new SnippetsTestHelper(page)
		await helper.navigateToSnippetsAdmin()

		const root = page.locator('#wp-admin-bar-code-snippets')
		await expect(root).toBeVisible()
		await root.hover()

		await expect(page.locator('#wp-admin-bar-code-snippets-manage')).toBeVisible()
		await expect(page.locator('#wp-admin-bar-code-snippets-add')).toBeVisible()
		await expect(page.locator('#wp-admin-bar-code-snippets-import')).toBeVisible()
		await expect(page.locator('#wp-admin-bar-code-snippets-settings')).toBeVisible()
		await expect(page.locator('#wp-admin-bar-code-snippets-active-snippets')).toBeVisible()
		await expect(page.locator('#wp-admin-bar-code-snippets-inactive-snippets')).toBeVisible()
		await expect(page.locator('#wp-admin-bar-code-snippets-safe-mode-doc')).toBeVisible()

		const safeModeDocLink = page.locator('#wp-admin-bar-code-snippets-safe-mode-doc a').first()
		await expect(safeModeDocLink).toHaveAttribute('href', 'https://snipco.de/safe-mode')
		await expect(safeModeDocLink).toHaveAttribute('target', '_blank')

		// Free vs Pro gating: CSS/JS/COND lead to upgrade when unlicensed, otherwise to the type's add screen.
		const proLicensed = await SnippetsTestHelper.isProLicensed()

		for (const type of ['css', 'js', 'cond']) {
			const node = page.locator(`#wp-admin-bar-code-snippets-add-${type}`)

			if (proLicensed) {
				await expect(node).not.toHaveClass(/code-snippets-disabled/)
				await expect(node.locator('a')).toHaveAttribute('href', new RegExp(`type=${type}`))
			} else {
				await expect(node).toHaveClass(/code-snippets-disabled/)
				await expect(node.locator('a')).toHaveAttribute('href', /page=code_snippets_upgrade/)
			}
		}

		// Pagination: perPage=2 and we created 3 active snippets.
		const activeNode = page.locator('#wp-admin-bar-code-snippets-active-snippets')
		await activeNode.hover()

		const activeControls = activeNode.locator('.code-snippets-pagination-controls[data-status="active"]')
		await expect(activeControls).toBeVisible()
	})

	test('QuickNav loads on the front end without JavaScript errors', async ({ page }) => {
		const errors: string[] = []
		page.on('pageerror', error => errors.push(error.message))

		await page.goto('/')

		// The admin bar bundle is enqueued on the front end as well as in wp-admin,
		// but `wp.i18n`, `wp.url` and `pagenow` are only present in wp-admin unless
		// the script declares them. Loading it here proves those are satisfied.
		await expect(page.locator('#wpadminbar')).toBeVisible()
		await expect(page.locator('#wp-admin-bar-code-snippets')).toHaveCount(1)
		expect(errors).toEqual([])
	})

	test('Manage submenu contains status quick links', async ({ page }) => {
		test.setTimeout(QUICKNAV_TEST_TIMEOUT_MS)

		const helper = new SnippetsTestHelper(page)
		await helper.navigateToSnippetsAdmin()

		const root = page.locator('#wp-admin-bar-code-snippets')
		await expect(root).toBeVisible()
		await root.hover()

		const manageNode = page.locator('#wp-admin-bar-code-snippets-manage')
		await manageNode.hover()

		await expect(page.locator('#wp-admin-bar-code-snippets-status-all a')).toHaveAttribute('href', /page=snippets&status=all/)
		await expect(page.locator('#wp-admin-bar-code-snippets-status-active a')).toHaveAttribute('href', /page=snippets&status=active/)
		await expect(page.locator('#wp-admin-bar-code-snippets-status-inactive a')).toHaveAttribute('href', /page=snippets&status=inactive/)
	})

	test('QuickNav menu can be disabled via setting', async ({ page }) => {
		test.setTimeout(QUICKNAV_TEST_TIMEOUT_MS)

		await SnippetsTestHelper.setAdminBarQuickNavSettings({ enabled: false, perPage: QUICKNAV_PER_PAGE })

		const helper = new SnippetsTestHelper(page)
		await helper.navigateToSnippetsAdmin()

		await expect(page.locator('#wp-admin-bar-code-snippets')).toHaveCount(0)

		await SnippetsTestHelper.setAdminBarQuickNavSettings({ enabled: true, perPage: QUICKNAV_PER_PAGE })
		await helper.navigateToSnippetsAdmin()
		await expect(page.locator('#wp-admin-bar-code-snippets')).toBeVisible()
	})

	test('Safe Mode indicator appears only when Safe Mode is active', async ({ page }) => {
		test.setTimeout(QUICKNAV_TEST_TIMEOUT_MS)
		const safeModeMuPluginPath = 'wp-content/mu-plugins/code-snippets-e2e-safe-mode.php'

		const removeMuPlugin = async () => {
			await wpCli(['eval', `@unlink( ABSPATH . ${JSON.stringify(safeModeMuPluginPath)} );`])
		}

		await removeMuPlugin()

		await page.goto('/wp-admin/admin.php?page=snippets')
		await expect(page.locator('#wp-admin-bar-code-snippets-safe-mode')).toHaveCount(0)

		try {
			// Enable safe mode via a temporary mu-plugin so we don't rely on mutating wp-config.php.
			await wpCli([
				'eval',
				`
					$path = ABSPATH . ${JSON.stringify(safeModeMuPluginPath)};
					wp_mkdir_p( dirname( $path ) );
					file_put_contents(
						$path,
						"<?php\\nif ( ! defined( 'CODE_SNIPPETS_SAFE_MODE' ) ) {\\n\\tdefine( 'CODE_SNIPPETS_SAFE_MODE', true );\\n}\\n"
					);
				`
			])

			await page.goto('/wp-admin/admin.php?page=snippets')
			const safeModeNode = page.locator('#wp-admin-bar-code-snippets-safe-mode')
			await expect(safeModeNode).toBeVisible({ timeout: 30000 })

			const safeModeLink = safeModeNode.locator('a').first()
			await expect(safeModeLink).toHaveAttribute('href', 'https://snipco.de/safe-mode')
			await expect(safeModeLink).toHaveAttribute('target', '_blank')
		} finally {
			await removeMuPlugin()
		}

		await page.goto('/wp-admin/admin.php?page=snippets')
		await expect(page.locator('#wp-admin-bar-code-snippets-safe-mode')).toHaveCount(0)
	})
})
