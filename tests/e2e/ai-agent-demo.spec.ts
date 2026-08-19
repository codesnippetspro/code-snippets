import { expect, test } from '@playwright/test'
import { TIMEOUTS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'

const DEMO_URL = '/wp-admin/admin.php?page=snippets&subpage=ai-agent'
const DEMO_NAMES = "'Welcome banner', 'Welcome banner styles'"

const countDemoSnippets = async (): Promise<number> => {
	const output = await wpCli(['eval', `
		global $wpdb;
		echo (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}snippets WHERE name IN (${DEMO_NAMES})" );
	`])

	return Number.parseInt(output.trim(), 10)
}

const countActiveDemoSnippets = async (): Promise<number> => {
	const output = await wpCli(['eval', `
		global $wpdb;
		echo (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}snippets WHERE active = 1 AND name IN (${DEMO_NAMES})" );
	`])

	return Number.parseInt(output.trim(), 10)
}

const getBannerCode = (): Promise<string> =>
	wpCli(['eval', `
		global $wpdb;
		echo (string) $wpdb->get_var( "SELECT code FROM {$wpdb->prefix}snippets WHERE name = 'Welcome banner'" );
	`])

const deleteDemoSnippets = () =>
	wpCli(['eval', `
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}snippets WHERE name IN (${DEMO_NAMES})" );
	`])

test.describe('AI Agent demo', () => {
	test.beforeEach(deleteDemoSnippets)
	test.afterAll(deleteDemoSnippets)

	test('the tab is reachable from the toolbar and highlighted as new', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=snippets')

		const link = page.locator('.code-snippets-toolbar-lower a.ai-agent-link')
		await expect(link).toBeVisible()
		await expect(link).toContainText('AI Agent')
		await expect(link.locator('.new-chip')).toHaveText('New')
		await expect(link.locator('.pro-chip')).toHaveCount(0)

		await link.click()
		await expect(page.locator('.ai-agent-demo h1')).toContainText('AI Agent')
		await expect(page.locator('.ai-agent-demo-play')).toBeVisible()
	})

	test('playing the walkthrough saves two inactive snippets naming the site', async ({ page }) => {
		await page.goto(DEMO_URL)
		expect(await countDemoSnippets()).toBe(0)

		await page.locator('.ai-agent-demo-play').click()

		// The plan is scripted to arrive before anything is built.
		await expect(page.locator('.ai-agent-plan__title')).toHaveText(
			'Dismissible welcome banner',
			{ timeout: TIMEOUTS.DEFAULT }
		)

		await expect(page.locator('.ai-agent-demo-upsell')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(page.locator('.ai-agent-result__row')).toHaveCount(2)
		await expect(page.locator('.ai-agent-result__link')).toHaveCount(2)

		expect(await countDemoSnippets()).toBe(2)
		expect(await countActiveDemoSnippets()).toBe(0)

		const siteName = (await wpCli(['option', 'get', 'blogname'])).trim()
		expect(await getBannerCode()).toContain(`Welcome to ${siteName}`)
	})

	test('skipping jumps to the end and replaying does not duplicate snippets', async ({ page }) => {
		await page.goto(DEMO_URL)

		await page.locator('.ai-agent-demo-play').click()
		await page.getByRole('button', { name: 'Skip animation' }).click()

		await expect(page.locator('.ai-agent-demo-upsell')).toBeVisible()
		await expect(page.locator('.ai-agent-result__link')).toHaveCount(2, { timeout: TIMEOUTS.DEFAULT })
		expect(await countDemoSnippets()).toBe(2)

		await page.locator('.ai-agent-demo-upsell').getByRole('button', { name: 'Run demo again' }).click()
		await expect(page.locator('.ai-agent-demo-upsell')).toBeHidden()

		await page.getByRole('button', { name: 'Skip animation' }).click()
		await expect(page.locator('.ai-agent-demo-upsell')).toBeVisible()
		await expect(page.locator('.ai-agent-result__link')).toHaveCount(2, { timeout: TIMEOUTS.DEFAULT })

		// The replay updates the snippets it created rather than adding more.
		expect(await countDemoSnippets()).toBe(2)
	})
})
