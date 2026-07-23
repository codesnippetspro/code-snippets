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

	test('Expands list rows into accessible snippet details', async ({ page }) => {
		await switchSnippetView(page, 'Table view')

		const table = page.locator('.snippets-list-view .wp-list-table')
		const heading = table.locator('thead .column-name')
		const footerHeading = table.locator('tfoot .column-name')
		const row = table.locator('tbody tr.snippet').first()
		const toggle = row.locator('.mobile-row-toggle')

		await expect(toggle).toBeVisible({ timeout: 5000 })
		await expect(toggle).toHaveAccessibleName(/Expand details for/)
		await expect(heading).toContainText('Snippet Name')
		await expect(footerHeading).toContainText('Snippet Name')
		await expect(heading).toHaveCSS('background-color', 'rgb(247, 247, 248)')
		await expect(table.locator('thead .column-type')).toBeHidden()
		await expect(toggle).toHaveAttribute('aria-expanded', 'false')

		const tableBox = await table.boundingBox()
		const collapsedRowBox = await row.boundingBox()
		expect(tableBox?.x).toBeCloseTo(18, 0)
		expect((tableBox?.x ?? 0) + (tableBox?.width ?? 0)).toBeCloseTo(422, 0)
		expect(collapsedRowBox?.height).toBeCloseTo(64, 0)

		await toggle.click()
		await expect(toggle).toHaveAttribute('aria-expanded', 'true')
		await expect(toggle).toHaveAccessibleName(/Collapse details for/)
		await expect(row).toHaveClass(/is-mobile-expanded/)

		for (const [column, label] of [
			['type', 'Type'],
			['desc', 'Description'],
			['tags', 'Tags'],
			['date', 'Modified'],
			['priority', 'Priority']
		]) {
			const cell = row.locator(`.column-${column}`)
			await expect(cell).toBeVisible()
			await expect(cell).toHaveAttribute('data-label', label)
		}

		const typeCell = row.locator('.column-type')
		const typeValue = typeCell.locator('.mobile-cell-value')
		await expect(typeValue).toBeVisible()
		const typeCellBox = await typeCell.boundingBox()
		const typeValueBox = await typeValue.boundingBox()
		expect(typeValueBox?.x).toBeCloseTo((typeCellBox?.x ?? 0) + 86, 0)

		const rowActions = row.locator('.row-actions')
		await expect(rowActions).toBeVisible()
		await expect(rowActions.getByText('Preview', { exact: true })).toBeHidden()
		await expect(rowActions.getByText('Edit', { exact: true })).toBeVisible()
		await expect(rowActions.getByText('Clone', { exact: true })).toBeVisible()
		await expect(rowActions.getByText('Export', { exact: true })).toBeVisible()
		await expect(rowActions.getByText('Trash', { exact: true })).toBeVisible()
		await expect(rowActions.locator('.row-action-clone .row-action-separator'))
			.toHaveCSS('font-size', '13px')

		await toggle.click()
		await expect(row).not.toHaveClass(/is-mobile-expanded/)
		await expect(row.locator('.column-type')).toBeHidden()
	})
})
