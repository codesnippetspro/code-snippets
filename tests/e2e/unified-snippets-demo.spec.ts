import { expect, test } from '@playwright/test'
import { TIMEOUTS, URLS } from './helpers/constants'
import { measureCalloutSteps } from './helpers/demoPacing'
import { wpCli } from './helpers/wpCli'

const countSnippets = async (): Promise<number> => {
	const output = await wpCli(['eval', `
		global $wpdb;
		echo (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}snippets" );
	`])

	return Number.parseInt(output.trim(), 10)
}

const forgetDemos = () => wpCli(['option', 'delete', 'code_snippets_demos_seen'])

test.describe('Unified Snippets demo', () => {
	test.beforeEach(forgetDemos)
	test.afterAll(forgetDemos)

	test('the tab is reachable from the toolbar and highlighted as new', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)

		const link = page.locator('.code-snippets-toolbar-lower a.unified-snippets-link')
		await expect(link).toBeVisible()
		await expect(link).toContainText('Unified Snippets')
		await expect(link.locator('.new-chip')).toHaveText('New')

		await link.click()
		await expect(page.getByRole('heading', { level: 1 })).toContainText('Unified Snippets')
		await expect(page.locator('.demo-play')).toBeVisible()
		await expect(page.locator('.unified-snippets-empty')).toBeVisible()
	})

	test('playing the walkthrough scans, imports and ends without touching the site', async ({ page }) => {
		await page.goto(URLS.UNIFIED_SNIPPETS_ADMIN)
		const before = await countSnippets()

		await page.locator('.demo-play').click()

		await expect(page.locator('.unified-snippets-stats')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(page.locator('.unified-snippets-demo-table tbody tr')).toHaveCount(8)

		await expect(page.locator('.demo-upsell')).toBeVisible({ timeout: TIMEOUTS.LONG })
		await expect(page.locator('.unified-snippets-row__imported')).toHaveCount(6)
		await expect(page.locator('.unified-snippets-status-badge.is-view-only')).toHaveCount(2)

		expect(await countSnippets()).toBe(before)
	})

	test('found code can be previewed', async ({ page }) => {
		await page.goto(URLS.UNIFIED_SNIPPETS_ADMIN)

		await page.locator('.demo-play').click()
		await page.getByRole('button', { name: 'Skip animation' }).click()

		await page.locator('.demo-featured-row').getByRole('button', { name: 'Preview' }).click()
		await expect(page.locator('.code-snippets-preview-modal')).toContainText('RewriteEngine On')
	})

	test('watching to the end stops the tab announcing itself as new', async ({ page }) => {
		await page.goto(URLS.UNIFIED_SNIPPETS_ADMIN)

		await page.locator('.demo-play').click()
		await page.getByRole('button', { name: 'Skip animation' }).click()
		await expect(page.locator('.demo-upsell')).toBeVisible()

		await expect.poll(async () => wpCli(['option', 'get', 'code_snippets_demos_seen', '--format=json']))
			.toContain('unified-snippets')

		await page.reload()
		await expect(page.locator('a.unified-snippets-link .demo-chip')).toHaveText('Demo')
	})

	test('the walkthrough holds each step long enough to be read', async ({ page }) => {
		test.setTimeout(TIMEOUTS.VERY_LONG)
		await page.goto(URLS.UNIFIED_SNIPPETS_ADMIN)
		await page.locator('.demo-play').click()

		const steps = await measureCalloutSteps(page)
		expect(steps.length).toBeGreaterThanOrEqual(5)

		for (const step of steps.slice(0, -1)) {
			expect(step.shownFor, `step "${step.title}" was only shown for ${step.shownFor}ms`)
				.toBeGreaterThan(3000)
		}
	})
})
