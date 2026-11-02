import { expect, test } from '@playwright/test'
import { TIMEOUTS, URLS } from './helpers/constants'
import { measureCalloutSteps } from './helpers/demoPacing'
import { wpCli } from './helpers/wpCli'

const SAVED_BACKGROUND = 'rgb(219, 234, 254)'
const DRAFT_BACKGROUND = 'rgb(253, 230, 138)'

test.describe('Snippet preview demo', () => {
	test('the editor sidebar opens the preview demo in a new tab', async ({ page, context }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)

		const section = page.getByRole('region', { name: 'Preview Snippet' })
		await expect(section).toBeVisible()

		const link = section.getByRole('link', { name: /Preview/ })
		await expect(link).toHaveAttribute('target', '_blank')
		await expect(link).toHaveAttribute('rel', 'noopener noreferrer')

		const [demo] = await Promise.all([context.waitForEvent('page'), link.click()])
		await demo.waitForLoadState()

		await expect(demo.getByRole('heading', { level: 1 })).toContainText('Snippet Preview')
		await expect(demo.locator('.demo-play')).toBeVisible()
	})

	test('the page loads with the saved colour and changes to the draft colour', async ({ page }) => {
		await page.goto(URLS.PREVIEW_DEMO_ADMIN)

		const site = page.locator('.preview-demo-site')
		await expect(site).toHaveCSS('background-color', SAVED_BACKGROUND)

		const siteName = (await wpCli(['option', 'get', 'blogname'])).trim()
		await expect(page.locator('.preview-demo-site__name')).toHaveText(siteName)

		await page.locator('.demo-play').click()
		await expect(page.locator('.preview-demo-code')).toBeVisible()
		await expect(page.locator('.preview-demo-code__value')).toContainText('#fde68a', { timeout: TIMEOUTS.DEFAULT })

		await expect(site).toHaveCSS('background-color', DRAFT_BACKGROUND, { timeout: TIMEOUTS.DEFAULT })
		await expect(page.locator('.preview-demo-log')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(page.locator('.demo-upsell')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
	})

	test('the walkthrough holds each step long enough to be read', async ({ page }) => {
		test.setTimeout(TIMEOUTS.VERY_LONG)
		await page.goto(URLS.PREVIEW_DEMO_ADMIN)
		await page.locator('.demo-play').click()

		const steps = await measureCalloutSteps(page)
		expect(steps.length).toBeGreaterThanOrEqual(4)

		for (const step of steps.slice(0, -1)) {
			expect(step.shownFor, `step "${step.title}" was only shown for ${step.shownFor}ms`)
				.toBeGreaterThan(3000)
		}
	})
})
