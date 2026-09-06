import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

const SCREENS = [
	{ name: 'Add Snippet', url: URLS.ADD_SNIPPET_ADMIN },
	{ name: 'Cloud Library', url: '/wp-admin/admin.php?page=snippets&subpage=cloud-library' },
	{ name: 'Blueprints', url: '/wp-admin/admin.php?page=snippets&subpage=blueprints' },
	{ name: 'AI Agent', url: '/wp-admin/admin.php?page=snippets&subpage=ai-agent' },
	{ name: 'Insights', url: '/wp-admin/admin.php?page=code-snippets-insights' },
	{ name: 'Welcome', url: URLS.WELCOME_SCREEN_ADMIN }
]

test.describe('Screen meta slot', () => {
	for (const screen of SCREENS) {
		test(`renders a slot on the ${screen.name} screen`, async ({ page }) => {
			await page.goto(screen.url)

			await expect(page.locator('#snippets-screen-meta-slot')).toHaveCount(1)
		})
	}
})
