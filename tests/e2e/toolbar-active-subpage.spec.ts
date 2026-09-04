import { expect, test } from '@playwright/test'
import type { Page } from '@playwright/test'

const MANAGE_URL = '/wp-admin/admin.php?page=snippets'

// The manage screen resolves an absent or unrecognised `subpage` to the first
// subpage, so the toolbar must highlight whichever subpage actually rendered.
// Each case names a landmark of the body it expects, so a highlight that
// disagrees with the rendered page fails rather than passing on the query alone.
const CASES = [
	{ name: 'no subpage parameter', query: '', active: 'snippets', body: '.wp-list-table' },
	{ name: 'an unrecognised subpage', query: '&subpage=not-a-subpage', active: 'snippets', body: '.wp-list-table' },
	{ name: 'a named subpage', query: '&subpage=cloud-community', active: 'cloud-community', body: '.community-cloud-nav' },
]

// The Settings tab is highlighted by its own page slug rather than a subpage,
// so it is excluded here.
const activeSubpageLinks = (page: Page) =>
	page.locator('.code-snippets-toolbar-lower a.active-link:not(.settings-link)')

test.describe('Toolbar active subpage', () => {
	for (const { name, query, active, body } of CASES) {
		test(`the highlighted tab matches the rendered page with ${name}`, async ({ page }) => {
			await page.goto(`${MANAGE_URL}${query}`)
			await expect(page.locator(body)).toBeVisible()

			await expect(activeSubpageLinks(page)).toHaveCount(1)
			await expect(activeSubpageLinks(page)).toHaveClass(new RegExp(`(^|\\s)${active}-link(\\s|$)`))
		})
	}

	test('no lower-nav tab is highlighted away from the manage screen', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=snippets-settings')

		await expect(page.locator('.code-snippets-toolbar-lower')).toBeVisible()
		await expect(activeSubpageLinks(page)).toHaveCount(0)
	})
})
