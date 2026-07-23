import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'
import type { Page } from '@playwright/test'

const MOBILE_VIEWPORT = { width: 440, height: 1000 }

const switchSnippetView = async (page: Page, view: 'Card view' | 'Table view') => {
	const saved = page
		.waitForResponse(response =>
			response.url().includes('/snippet-view') && 'GET' !== response.request().method()
		)
		.catch(() => undefined)

	await page.getByRole('button', { name: view }).click()
	await saved
}

test.describe('Mobile snippets views', () => {
	test.beforeEach(async ({ page }) => {
		await page.setViewportSize(MOBILE_VIEWPORT)
		await page.goto(URLS.SNIPPETS_ADMIN)
		await expect(page.locator('.snippets-list-view')).toBeVisible()
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

	test('Stacks snippet cards and uses a mobile preview sheet', async ({ page }) => {
		await switchSnippetView(page, 'Card view')

		try {
			const grid = page.locator('.snippets-card-grid')
			const card = grid.locator('.code-snippets-card').first()
			const title = card.locator('.snippet-card-header h3')
			const footer = card.locator('footer')

			await expect(grid).toHaveCSS('column-gap', '16px')
			await expect(title).toHaveCSS('font-size', '16px')
			await expect(footer).toHaveCSS('background-color', 'rgb(247, 247, 248)')
			await expect(card.locator('.snippet-card-select')).toBeHidden()

			const cardBox = await card.boundingBox()
			expect(cardBox?.x).toBeCloseTo(18, 0)
			expect((cardBox?.x ?? 0) + (cardBox?.width ?? 0)).toBeCloseTo(422, 0)

			await card.getByRole('button', { name: 'Preview' }).click()

			const modal = page.locator('.components-modal__frame.code-snippets-preview-modal')
			await expect(modal).toBeVisible()
			await expect(modal).toHaveCSS('width', '404px')
			await expect(modal).toHaveCSS('height', '964px')

			const modalTitleBox = await modal.locator('.components-modal__header-heading').boundingBox()
			const modalBadgeBox = await modal.locator('.code-snippets-preview-modal__badge').boundingBox()
			expect((modalTitleBox?.x ?? 0) + (modalTitleBox?.width ?? 0))
				.toBeLessThanOrEqual(modalBadgeBox?.x ?? 0)
		} finally {
			await page.keyboard.press('Escape')
			await switchSnippetView(page, 'Table view').catch(() => undefined)
		}
	})
})
