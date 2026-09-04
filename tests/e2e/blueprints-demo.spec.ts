import { expect, test } from '@playwright/test'
import { TIMEOUTS } from './helpers/constants'
import { measureCalloutSteps } from './helpers/demoPacing'
import { wpCli } from './helpers/wpCli'
import type { Page } from '@playwright/test'

const DEMO_URL = '/wp-admin/admin.php?page=snippets&subpage=blueprints'
const SECTIONS = ['General', 'Attributes', 'Output']

const forgetDemos = () => wpCli(['option', 'delete', 'code_snippets_demos_seen'])

const activeTab = (page: Page) =>
	page.locator('.blueprint-form-sidebar__item.is-active')

test.describe('Blueprints demo', () => {
	test.beforeEach(forgetDemos)
	test.afterAll(forgetDemos)

	test('the tab is reachable from the toolbar and highlighted as new', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=snippets')

		const link = page.locator('.code-snippets-toolbar-lower a.blueprints-link')
		await expect(link).toBeVisible()
		await expect(link).toContainText('Blueprints')
		await expect(link.locator('.new-chip')).toHaveText('New')
		await expect(link.locator('.pro-chip')).toHaveCount(0)

		await link.click()
		await expect(page.locator('.blueprints-demo h1')).toContainText('Blueprints')
		await expect(page.locator('.demo-play')).toBeVisible()
	})

	test('the blueprint is shown prepopulated before play', async ({ page }) => {
		await page.goto(DEMO_URL)

		await expect(page.locator('.blueprint-detail__header h3')).toHaveText('Create a Shortcode')
		await expect(page.locator('.blueprint-form-sidebar__item')).toHaveCount(SECTIONS.length)
		await expect(activeTab(page)).toHaveText('General')

		await expect(page.locator('#shortcodeTag')).toHaveValue('staff_profile')
		await expect(page.locator('#functionName')).toHaveValue('render_staff_profile')
		await expect(page.locator('#functionName')).toHaveAttribute('readonly', '')
	})

	test('playing steps through every section and confirms a generated snippet', async ({ page }) => {
		await page.goto(DEMO_URL)
		await page.locator('.demo-play').click()

		for (const section of SECTIONS) {
			await expect(activeTab(page)).toHaveText(section, { timeout: TIMEOUTS.DEFAULT })
		}

		// The generate button is marked as clicked, then released again.
		const generateButton = page.locator('.blueprint-form-sidebar__generate')
		await expect(generateButton).toHaveClass(/demo-click/, { timeout: TIMEOUTS.DEFAULT })

		await expect(page.locator('.demo-upsell')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(generateButton).not.toHaveClass(/demo-click/)

		const generated = page.locator('.blueprints-demo-generated')
		await expect(generated).toBeVisible()
		await expect(generated).toContainText('Snippet “Create a Shortcode” has been generated.')
		await expect(generated.locator('.badge.php-badge')).toBeVisible()
	})

	test('sections become browsable once the walkthrough finishes', async ({ page }) => {
		await page.goto(DEMO_URL)

		await expect(page.locator('.blueprint-form-sidebar__item').first()).toBeDisabled()

		await page.locator('.demo-play').click()
		await page.getByRole('button', { name: 'Skip animation' }).click()

		await expect(page.locator('.demo-upsell')).toBeVisible()
		await expect(activeTab(page)).toHaveText('Output')

		await page.locator('.blueprint-form-sidebar__item', { hasText: 'Attributes' }).click()
		await expect(activeTab(page)).toHaveText('Attributes')
		await expect(page.locator('.blueprint-form-repeater__row')).toHaveCount(2)
	})

	test('every section is the same height', async ({ page }) => {
		await page.setViewportSize({ width: 1600, height: 950 })
		await page.goto(DEMO_URL)

		await page.locator('.demo-play').click()
		await page.getByRole('button', { name: 'Skip animation' }).click()
		await expect(page.locator('.demo-upsell')).toBeVisible()

		const heights: number[] = []

		for (const section of SECTIONS) {
			await page.locator('.blueprint-form-sidebar__item', { hasText: section }).click()
			await expect(activeTab(page)).toHaveText(section)

			const box = await page.locator('.blueprint-form-layout').boundingBox()
			heights.push(Math.round(box?.height ?? 0))
		}

		// Stepping between sections must never resize the card.
		expect(new Set(heights).size).toBe(1)
		expect(heights[0]).toBeGreaterThan(0)
	})

	test('the toolbar badge softens to Demo once the walkthrough has been watched', async ({ page }) => {
		await page.goto(DEMO_URL)
		await expect(page.locator('.code-snippets-toolbar-lower a.blueprints-link .new-chip')).toBeVisible()

		await page.locator('.demo-play').click()
		await page.getByRole('button', { name: 'Skip animation' }).click()
		await expect(page.locator('.demo-upsell')).toBeVisible()

		// The badge only changes on a fresh load, once the visit is recorded.
		await expect.poll(async () =>
			(await wpCli(['option', 'get', 'code_snippets_demos_seen'])).includes('blueprints')).toBe(true)

		await page.goto(DEMO_URL)

		const link = page.locator('.code-snippets-toolbar-lower a.blueprints-link')
		await expect(link.locator('.demo-chip')).toHaveText('Demo')
		await expect(link.locator('.new-chip')).toHaveCount(0)
	})

	test('the demo-reset URL puts the badge back to New', async ({ page }) => {
		await page.goto(DEMO_URL)
		await page.locator('.demo-play').click()
		await page.getByRole('button', { name: 'Skip animation' }).click()
		await expect(page.locator('.demo-upsell')).toBeVisible()

		await expect.poll(async () =>
			(await wpCli(['option', 'get', 'code_snippets_demos_seen'])).includes('blueprints')).toBe(true)

		const link = page.locator('.code-snippets-toolbar-lower a.blueprints-link')
		await page.goto(DEMO_URL)
		await expect(link.locator('.demo-chip')).toBeVisible()

		// The reset is nonce-protected, so the signed address is taken from the
		// page rather than typed.
		const resetUrl = await page.evaluate(() => window.CODE_SNIPPETS?.urls.demoReset)
		await page.goto(String(resetUrl))

		// The reset redirects to a clean address rather than leaving the
		// parameter in place for a refresh to repeat.
		await expect(page).toHaveURL(/page=snippets/)
		await expect(page).not.toHaveURL(/demo-reset/)

		await page.goto(DEMO_URL)
		await expect(link.locator('.new-chip')).toHaveText('New')
		await expect(link.locator('.demo-chip')).toHaveCount(0)
	})

	test('replaying restarts from the first section', async ({ page }) => {
		await page.goto(DEMO_URL)
		await page.locator('.demo-play').click()
		await page.getByRole('button', { name: 'Skip animation' }).click()

		await page.locator('.demo-upsell').getByRole('button', { name: 'Run demo again' }).click()

		await expect(page.locator('.demo-upsell')).toBeHidden()
		await expect(page.locator('.blueprints-demo-generated')).toBeHidden()
		await expect(activeTab(page)).toHaveText('General', { timeout: TIMEOUTS.DEFAULT })
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
