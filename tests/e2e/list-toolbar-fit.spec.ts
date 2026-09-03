import { expect, test } from '@playwright/test'

const SNIPPETS_URL = '/wp-admin/admin.php?page=snippets'

// The toolbar's end group (pagination plus the view toggle) cannot shrink, so at
// widths where the row does not fit it must wrap rather than spill off the page.
test.describe('Snippets toolbar fit', () => {
	for (const width of [1280, 1360, 1600]) {
		test(`nothing spills out of the toolbar at ${width}px`, async ({ page }) => {
			await page.setViewportSize({ width, height: 900 })
			await page.goto(SNIPPETS_URL)
			await page.waitForSelector('.snippet-view-toggle')

			const fit = await page.evaluate(() => {
				const nav = document.querySelector('.snippets-list-view .tablenav.top')
				const toggle = document.querySelector('.snippet-view-toggle')
				if (!nav || !toggle) {
					return { missing: true }
				}
				const n = nav.getBoundingClientRect()
				const t = toggle.getBoundingClientRect()
				return {
					missing: false,
					pageOverflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
					toggleInsideNav: t.left >= n.left - 1 && t.right <= n.right + 1
				}
			})

			expect(fit.missing).toBe(false)
			expect(fit.pageOverflow).toBeLessThanOrEqual(0)
			expect(fit.toggleInsideNav).toBe(true)
		})
	}
})
