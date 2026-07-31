import { expect, test } from '@playwright/test'
import { expectCanonicalCheckbox } from './helpers/checkbox'
import { URLS } from './helpers/constants'

test.describe('Code Snippets admin notice filtering', () => {
	test('Hides foreign notices injected into the manage root', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)

		const container = page.locator('#manage-snippets-container')
		await expect(container).toBeVisible()
		await container.evaluate(element => {
			const notice = document.createElement('div')
			notice.className = 'notice notice-warning'
			notice.dataset.testid = 'foreign-notice'
			notice.textContent = 'Foreign notice'
			element.prepend(notice)
		})

		const foreignNotice = container.locator(':scope > [data-testid="foreign-notice"]')
		await expect(foreignNotice).toHaveCount(1)
		await expect(foreignNotice).toHaveCSS('display', 'none')
	})

	test('Keeps direct plugin notices visible', async ({ page }) => {
		await page.goto(`${URLS.SNIPPETS_ADMIN}&result=deleted`)

		const pluginNotice = page.locator('#manage-snippets-container > .notice')
			.filter({ hasText: 'Snippet deleted.' })

		await expect(pluginNotice).toHaveClass(/code-snippets-notice/)
		await expect(pluginNotice).toBeVisible()
	})

	test('Filters foreign notices from the settings page', async ({ page }) => {
		await page.goto(URLS.SETTINGS_ADMIN)

		const settingsPage = page.locator('#wpbody-content > .wrap')
			.filter({ has: page.locator('.settings-type-nav') })
		await expect(settingsPage).toBeVisible()
		await settingsPage.evaluate(element => {
			const foreignNotice = document.createElement('div')
			foreignNotice.className = 'notice notice-warning'
			foreignNotice.dataset.testid = 'foreign-notice'
			foreignNotice.textContent = 'Foreign settings notice'
			element.prepend(foreignNotice)

			for (const [type, text] of [
				['updated', 'Settings saved.'],
				['notice-error', 'Settings could not be saved.']
			]) {
				const pluginNotice = document.createElement('div')
				pluginNotice.className = `notice ${type} settings-error`
				pluginNotice.textContent = text
				element.prepend(pluginNotice)
			}
		})

		const foreignNotice = settingsPage.locator(':scope > [data-testid="foreign-notice"]')
		await expect(foreignNotice).toHaveCount(1)
		await expect(foreignNotice).toHaveCSS('display', 'none')
		await expect(settingsPage.locator(':scope > .settings-error')).toHaveCount(2)

		for (const pluginNotice of await settingsPage.locator(':scope > .settings-error').all()) {
			await expect(pluginNotice).toBeVisible()
		}

		const checkbox = page.locator('.settings-section:visible input[type="checkbox"]:not(.switch)').first()
		await expect(checkbox).toBeVisible()
		await expectCanonicalCheckbox(checkbox)
	})
})
