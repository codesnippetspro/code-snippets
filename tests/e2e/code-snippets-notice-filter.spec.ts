import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

test.describe('Code Snippets admin notice filtering', () => {
	test('Hides foreign notices injected into the manage root', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)

		const container = page.locator('#manage-snippets-container')
		await expect(container).toBeVisible()
		await container.evaluate(element => {
			const notice = document.createElement('div')
			notice.className = 'notice e-notice e-notice--dismissible e-notice--extended'
			notice.textContent = 'Foreign notice'
			element.prepend(notice)
		})

		await expect(container.locator(':scope > .e-notice')).toBeHidden()
	})

	test('Keeps direct plugin notices visible', async ({ page }) => {
		await page.goto(`${URLS.SNIPPETS_ADMIN}&result=deleted`)

		const pluginNotice = page.locator('#manage-snippets-container > .notice')
			.filter({ hasText: 'Snippet deleted.' })

		await expect(pluginNotice).toHaveClass(/code-snippets-notice/)
		await expect(pluginNotice).toBeVisible()
	})
})
