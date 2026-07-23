import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'

const MOBILE_VIEWPORT = { width: 440, height: 1000 }

test.describe('Mobile snippets views', () => {
	test.beforeEach(async ({ page }) => {
		await page.setViewportSize(MOBILE_VIEWPORT)
		await new SnippetsTestHelper(page).navigateToSnippetsAdmin()
	})

	test('Uses the mobile shell at the canonical breakpoint', async ({ page }) => {
		const upperToolbar = page.locator('.code-snippets-toolbar-upper')
		const primaryToolbar = page.locator('.code-snippets-toolbar-lower')
		const activeNavLink = primaryToolbar.locator('.active-link')
		const typeNav = page.locator('.snippet-type-nav')
		const pageHeader = page.locator('.snippets-page-header')
		const title = pageHeader.getByRole('heading')
		const createButton = pageHeader.getByRole('link', { name: 'Create new Snippet' })

		await expect(upperToolbar.locator('nav')).toBeHidden()
		await expect(primaryToolbar.locator('.cloud-library-link')).toBeHidden()
		await expect(primaryToolbar).toHaveCSS('height', '67px')
		await expect(activeNavLink).toHaveCSS('flex-direction', 'column')
		await expect(activeNavLink).toHaveCSS('border-bottom-width', '3px')
		await expect(page.locator('.snippet-type-nav-fade')).toBeVisible()
		await expect(page.locator('.snippets-screen-meta-slot')).toBeHidden()
		await expect(title).toHaveCSS('font-size', '28px')

		const typeNavOverflows = await typeNav.evaluate(
			element => element.scrollWidth > element.clientWidth
		)
		expect(typeNavOverflows).toBe(true)

		const titleBox = await title.boundingBox()
		const createButtonBox = await createButton.boundingBox()
		const pageHeaderBox = await pageHeader.boundingBox()
		expect(titleBox).not.toBeNull()
		expect(createButtonBox).not.toBeNull()
		expect(pageHeaderBox?.x).toBeCloseTo(18, 0)
		expect(createButtonBox?.y).toBeGreaterThan((titleBox?.y ?? 0) + (titleBox?.height ?? 0))
	})
})
