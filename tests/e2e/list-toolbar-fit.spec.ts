import { expect, test } from '@playwright/test'

const SNIPPETS_URL = '/wp-admin/admin.php?page=snippets'

// The toolbar's end group (pagination plus the view toggle) cannot shrink, so at
// widths where the row does not fit it must wrap rather than spill off the page.
// The row collapses onto two lines up to 1400px, so both sides of that boundary
// are covered; the right-to-left project checks the same screen mirrored.
test.describe('Snippets toolbar fit', () => {
	for (const width of [1280, 1360, 1400, 1401, 1600]) {
		test(`nothing spills out of the toolbar at ${width}px`, async ({ page }) => {
			await page.setViewportSize({ width, height: 900 })
			await page.goto(SNIPPETS_URL)
			await page.waitForSelector('.snippet-view-toggle')

			const fit = await page.evaluate(() => {
				const nav = document.querySelector('.snippets-list-view .tablenav.top')
				const toggle = document.querySelector('.snippet-view-toggle')
				if (!nav || !toggle) {
					throw new Error('The snippets toolbar or its view toggle did not render.')
				}
				const n = nav.getBoundingClientRect()
				const t = toggle.getBoundingClientRect()
				return {
					pageOverflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
					toggleInsideNav: t.left >= n.left - 1 && t.right <= n.right + 1,
					wraps: 'wrap' === getComputedStyle(nav).flexWrap
				}
			})

			expect(fit.wraps, 'the row collapses onto two lines up to 1400px and not above').toBe(1400 >= width)
			expect(fit.pageOverflow).toBeLessThanOrEqual(0)
			expect(fit.toggleInsideNav).toBe(true)
		})
	}
})
