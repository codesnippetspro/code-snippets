import { expect, test } from '@playwright/test'
import { TIMEOUTS } from './helpers/constants'
import { measureCalloutSteps } from './helpers/demoPacing'
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

const forgetDemos = () => wpCli(['option', 'delete', 'code_snippets_demos_seen'])

test.describe('AI Agent demo', () => {
	test.beforeEach(forgetDemos)
	test.afterAll(forgetDemos)

	test('the tab is reachable from the toolbar and highlighted as new', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=snippets')

		const link = page.locator('.code-snippets-toolbar-lower a.ai-agent-link')
		await expect(link).toBeVisible()
		await expect(link).toContainText('AI Agent')
		await expect(link.locator('.new-chip')).toHaveText('New')
		await expect(link.locator('.pro-chip')).toHaveCount(0)

		await link.click()
		await expect(page.locator('.ai-agent-demo h1')).toContainText('AI Agent')
		await expect(page.locator('.demo-play')).toBeVisible()
	})

	test('the page opens on the agent\u2019s own layout, with its starter prompts and sidebar', async ({ page }) => {
		await page.goto(DEMO_URL)

		await expect(page.locator('.ai-agent-empty__chip')).toHaveCount(5)
		await expect(page.locator('.ai-agent-layout__main > .ai-agent-prompt')).toBeVisible()

		await page.getByRole('tab', { name: 'Past Conversations' }).click()
		await expect(page.locator('.ai-agent-history__item')).toHaveCount(2)

		await page.getByRole('tab', { name: 'Prompt Actions' }).click()
		await expect(page.locator('.ai-agent-examples__chip')).toHaveCount(5)
		await expect(page.locator('.ai-agent-quota__row')).toHaveCount(2)
	})

	test('playing the walkthrough plans, builds and refines without touching the site', async ({ page }) => {
		await page.goto(DEMO_URL)
		expect(await countDemoSnippets()).toBe(0)

		await page.locator('.demo-play').click()

		// The plan is scripted to arrive before anything is built.
		await expect(page.locator('.ai-agent-plan__title')).toHaveText(
			'Dismissible welcome banner',
			{ timeout: TIMEOUTS.DEFAULT }
		)

		await expect(page.locator('.demo-upsell')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(page.locator('.ai-agent-result__row')).toHaveCount(2)

		// The composer stays on the page throughout, as it does in the real agent.
		await expect(page.locator('.ai-agent-layout__main > .ai-agent-prompt')).toBeVisible()
		await expect(page.locator('.ai-agent-empty')).toHaveCount(0)

		// The refined code names the site, so the walkthrough reads as personal.
		const siteName = (await wpCli(['option', 'get', 'blogname'])).trim()
		await page.locator('.ai-agent-result__row').first().getByRole('button', { name: 'Preview code' }).click()
		await expect(page.locator('.code-snippets-preview-modal')).toContainText(`Welcome to ${siteName}`)
	})

	test('the walkthrough never writes a snippet, however many times it runs', async ({ page }) => {
		await page.goto(DEMO_URL)

		await page.locator('.demo-play').click()
		await page.getByRole('button', { name: 'Skip animation' }).click()
		await expect(page.locator('.demo-upsell')).toBeVisible()

		await page.locator('.demo-upsell').getByRole('button', { name: 'Run demo again' }).click()
		await expect(page.locator('.demo-upsell')).toBeHidden()

		await page.getByRole('button', { name: 'Skip animation' }).click()
		await expect(page.locator('.demo-upsell')).toBeVisible()

		// Replaying leaves nothing behind, so a visitor's library stays theirs.
		expect(await countDemoSnippets()).toBe(0)
	})

	test('the walkthrough ends with the closing panel below the agent, scrolled into view', async ({ page }) => {
		await page.setViewportSize({ width: 1280, height: 800 })
		await page.goto(DEMO_URL)

		await page.locator('.demo-play').click()
		await page.getByRole('button', { name: 'Skip animation' }).click()

		const closing = page.locator('.ai-agent-demo > .ai-agent-demo__closing .demo-upsell')
		await expect(closing).toBeVisible()
		await expect(page.locator('.ai-agent-thread .demo-upsell')).toHaveCount(0)
		await expect(closing).toBeInViewport()
	})

	test('the walkthrough holds each step long enough to be read', async ({ page }) => {
		await page.goto(DEMO_URL)
		await page.locator('.demo-play').click()

		const steps = await measureCalloutSteps(page)
		expect(steps.length).toBeGreaterThanOrEqual(5)

		// The closing step is measured against the upsell appearing, so it is
		// checked separately from the steps that were replaced by the next one.
		for (const step of steps.slice(0, -1)) {
			expect(step.shownFor, `step "${step.title}" was only shown for ${step.shownFor}ms`)
				.toBeGreaterThan(3000)
		}
	})
})
