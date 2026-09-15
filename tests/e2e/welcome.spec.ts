import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

test.describe('What’s New screen', () => {
	test('renders the Resources and Updates screen', async ({ page }) => {
		await page.goto(URLS.WELCOME_ADMIN)

		await expect(page.getByRole('heading', { level: 1, name: 'Resources and Updates' })).toBeVisible()
		await expect(page.locator('.code-snippets-welcome')).toBeVisible()
	})
})
