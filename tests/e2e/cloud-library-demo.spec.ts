import { expect, test } from '@playwright/test'
import { TIMEOUTS } from './helpers/constants'
import { measureCalloutSteps } from './helpers/demoPacing'

const DEMO_URL = '/wp-admin/admin.php?page=snippets&subpage=cloud-library'
const FEATURED_ROW = '.cloud-library-snippets tbody tr:first-child'

test.describe('Cloud Library demo', () => {
	test('the tab carries a demo badge rather than announcing itself as new', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=snippets')

		const link = page.locator('.code-snippets-toolbar-lower a.cloud-library-link')
		await expect(link).toBeVisible()
		await expect(link.locator('.demo-chip')).toHaveText('Demo')
		await expect(link.locator('.new-chip')).toHaveCount(0)
		await expect(link.locator('.pro-chip')).toHaveCount(0)

		await link.click()
		await expect(page.locator('.cloud-library-demo h1')).toContainText('Cloud Library')
		await expect(page.locator('.demo-play')).toBeVisible()
	})

	test('the library is shown as a table on its own before play', async ({ page }) => {
		await page.goto(DEMO_URL)

		// Deliberately a short library, so the closing panel is reachable.
		await expect(page.locator('.cloud-library-snippets tbody tr')).toHaveCount(4)

		// The walkthrough is about the table, so the page furniture is not shown.
		await expect(page.locator('.snippet-type-nav-wrapper')).toHaveCount(0)
		await expect(page.locator('#cloud-library-search')).toHaveCount(0)
		await expect(page.locator('.snippet-view-toggle')).toHaveCount(0)

		await expect(page.locator(`${FEATURED_ROW} .cloud-snippet-action-buttons`))
			.toContainText('Download')
	})

	test('playing previews, downloads, and leaves the snippet synced', async ({ page }) => {
		await page.goto(DEMO_URL)
		await page.locator('.demo-play').click()

		// The code preview opens on its own, then closes before the download.
		const modal = page.locator('.code-snippets-preview-modal')
		await expect(modal).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(modal).toContainText('Fix outgoing email sender name')
		await expect(modal).toBeHidden({ timeout: TIMEOUTS.DEFAULT })

		const actions = page.locator(`${FEATURED_ROW} .cloud-snippet-action-buttons`)
		await expect(actions).toContainText('Edit', { timeout: TIMEOUTS.DEFAULT })
		await expect(actions).not.toContainText('Download')

		// Only the featured row changes state.
		await expect(page.locator('.cloud-library-snippets tbody tr:nth-child(2) .cloud-snippet-action-buttons'))
			.toContainText('Download')

		await expect(page.locator('.demo-upsell')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
	})

	test('the preview step keeps its callout in place, over the modal overlay', async ({ page }) => {
		await page.setViewportSize({ width: 1600, height: 900 })
		await page.goto(DEMO_URL)
		await page.locator('.demo-play').click()

		const modal = page.locator('.code-snippets-preview-modal')
		await expect(modal).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		const callout = page.locator('.demo-callout')
		await expect(callout).toBeVisible()

		// Above the modal's own overlay, so the commentary is not dimmed with
		// the rest of the page.
		const zIndexes = await page.evaluate(() => {
			const zIndexOf = (selector: string) => {
				const element = document.querySelector(selector)
				return element ? Number.parseInt(window.getComputedStyle(element).zIndex, 10) : 0
			}

			return {
				callout: zIndexOf('.demo-callout'),
				overlay: zIndexOf('.components-modal__screen-overlay')
			}
		})

		expect(zIndexes.callout).toBeGreaterThan(zIndexes.overlay)

		// And clear of the modal itself, rather than covering what it describes.
		const calloutBox = await callout.boundingBox()
		const modalBox = await modal.boundingBox()
		expect(calloutBox?.x ?? 0).toBeGreaterThanOrEqual((modalBox?.x ?? 0) + (modalBox?.width ?? 0))
	})

	test('each step marks the click and spotlights what it describes', async ({ page }) => {
		await page.goto(DEMO_URL)
		await page.locator('.demo-play').click()

		const spotlight = page.locator('.demo-spotlight')
		await expect(spotlight).toBeVisible()

		// The preview button is marked as clicked while its step runs.
		await expect(page.locator('.demo-preview-button.demo-click')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(page.locator('.demo-action-button.demo-click')).toHaveCount(0)

		// Then the download button takes over.
		await expect(page.locator('.demo-action-button.demo-click')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(page.locator('.demo-preview-button.demo-click')).toHaveCount(0)

		// Only ever one click marker, and only on the featured row.
		await expect(page.locator('.demo-click')).toHaveCount(1)

		// The spotlight clears once the walkthrough is over.
		await expect(page.locator('.demo-upsell')).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await expect(spotlight).toHaveCount(0)
		await expect(page.locator('.demo-click')).toHaveCount(0)
	})

	test('the walkthrough ends scrolled to the closing panel', async ({ page }) => {
		await page.setViewportSize({ width: 1280, height: 800 })
		await page.goto(DEMO_URL)

		await page.locator('.demo-play').click()
		await page.getByRole('button', { name: 'Skip animation' }).click()

		// The panel is brought to the bottom of the viewport, not the bottom of
		// the document: the admin footer still sits below it.
		const closing = page.locator('.demo-upsell')
		await expect(closing).toBeVisible()
		await expect(closing).toBeInViewport({ ratio: 1 })
	})

	test('replaying returns the snippet to its undownloaded state', async ({ page }) => {
		await page.goto(DEMO_URL)
		await page.locator('.demo-play').click()
		await page.getByRole('button', { name: 'Skip animation' }).click()

		await expect(page.locator(`${FEATURED_ROW} .cloud-snippet-action-buttons`)).toContainText('Edit')

		await page.locator('.demo-upsell').getByRole('button', { name: 'Run demo again' }).click()

		await expect(page.locator('.demo-upsell')).toBeHidden()
		await expect(page.locator(`${FEATURED_ROW} .cloud-snippet-action-buttons`)).toContainText('Download')
	})

	test('the walkthrough holds each step long enough to be read', async ({ page }) => {
		await page.goto(DEMO_URL)
		await page.locator('.demo-play').click()

		const steps = await measureCalloutSteps(page)
		expect(steps.length).toBeGreaterThanOrEqual(4)

		// The closing step is measured against the upsell appearing, so it is
		// checked separately from the steps that were replaced by the next one.
		for (const step of steps.slice(0, -1)) {
			expect(step.shownFor, `step "${step.title}" was only shown for ${step.shownFor}ms`)
				.toBeGreaterThan(3000)
		}
	})
})
