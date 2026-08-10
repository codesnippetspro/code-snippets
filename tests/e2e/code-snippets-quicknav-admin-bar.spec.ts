import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { wpCli } from './helpers/wpCli'
import type { Page } from '@playwright/test'

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

	const openListing = async (page: Page, query: string) => {
		await page.goto(`/wp-admin/admin.php?page=snippets${query}`)

		const root = page.locator('#wp-admin-bar-code-snippets')
		await expect(root).toBeVisible()
		await root.hover()
	}

	const getTotalPagesForListing = async (page: Page, status: 'active' | 'inactive') => {
		const node = page.locator(`#wp-admin-bar-code-snippets-${status}-snippets`)
		await node.hover()

		const controls = node.locator(`.code-snippets-pagination-controls[data-status="${status}"]`).first()
		const totalPagesAttr = await controls.getAttribute('data-total-pages').catch(() => null)
		const parsed = totalPagesAttr ? Number(totalPagesAttr) : NaN
		return Number.isFinite(parsed) && 0 < parsed ? parsed : 1
	}

	const expectSnippetVisibleInListingPages = async (
		page: Page,
		options: { status: 'active' | 'inactive'; queryArg: string; snippetName: string }
	) => {
		await openListing(page, '')

		const totalPages = await getTotalPagesForListing(page, options.status)

		for (let pageNo = 1; pageNo <= totalPages; pageNo++) {
			await openListing(page, `&${options.queryArg}=${pageNo}`)

			const node = page.locator(`#wp-admin-bar-code-snippets-${options.status}-snippets`)
			await node.hover()

			const items = node.locator('li.code-snippets-snippet-item a')
			await items.first().waitFor({ state: 'visible', timeout: 5000 }).catch(() => null)

			const match = items.filter({ hasText: options.snippetName }).first()
			if (await match.isVisible().catch(() => false)) {
				await expect(match).toBeVisible({ timeout: 30000 })
				return
			}
		}

		throw new Error(`Snippet not found in ${options.status} listing after checking ${totalPages} page(s): ${options.snippetName}`)
	}

	test('Menu structure, gating, and pagination work', async ({ page }) => {
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

		await SnippetsTestHelper.setAdminBarQuickNavSettings({ enabled: true, perPage: 999 })
		await helper.navigateToSnippetsAdmin()
		await page.locator('#wp-admin-bar-code-snippets').hover()
		await page.locator('#wp-admin-bar-code-snippets-active-snippets').hover()

		const activeItems = activeNode.locator('li.code-snippets-snippet-item a')
		await expect(activeItems.filter({ hasText: activeA })).toBeVisible()
		await expect(activeItems.filter({ hasText: activeB })).toBeVisible()
		await expect(activeItems.filter({ hasText: activeC })).toBeVisible()

		await expectSnippetVisibleInListingPages(page, { status: 'active', queryArg: 'code_snippets_ab_active_page', snippetName: activeC })

		// Ensure titles are type-prefixed.
		await expect(activeItems.filter({ hasText: activeC })).toContainText('(PHP)')

		// Inactive list exists and includes our inactive snippet.
		const inactiveNode = page.locator('#wp-admin-bar-code-snippets-inactive-snippets')
		await inactiveNode.hover()
		const inactiveControls = inactiveNode.locator('.code-snippets-pagination-controls[data-status="inactive"]')
		await expect(inactiveControls).toBeVisible()

		const inactiveItems = inactiveNode.locator('li.code-snippets-snippet-item a')
		await expect(inactiveItems.first()).toBeVisible({ timeout: 30000 })

		await expectSnippetVisibleInListingPages(page, {
			status: 'inactive',
			queryArg: 'code_snippets_ab_inactive_page',
			snippetName: inactiveA
		})
		await expectSnippetVisibleInListingPages(page, {
			status: 'inactive',
			queryArg: 'code_snippets_ab_inactive_page',
			snippetName: inactiveC
		})
		const inactiveCLink = page
			.locator('#wp-admin-bar-code-snippets-inactive-snippets li.code-snippets-snippet-item a')
			.filter({ hasText: inactiveC })
			.first()
		await expect(inactiveCLink).toContainText('(HTML)')
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
