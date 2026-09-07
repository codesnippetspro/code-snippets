import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

const SCREENS = [
	{ name: 'Manage Snippets', url: URLS.SNIPPETS_ADMIN },
	{ name: 'Add Snippet', url: URLS.ADD_SNIPPET_ADMIN },
	{ name: 'Community Cloud', url: URLS.COMMUNITY_CLOUD },
	{ name: 'Blueprints', url: `${URLS.SNIPPETS_ADMIN}&subpage=blueprints` },
	{ name: 'Cloud Library', url: `${URLS.SNIPPETS_ADMIN}&subpage=cloud-library` },
	{ name: 'AI Agent', url: `${URLS.SNIPPETS_ADMIN}&subpage=ai-agent` },
	{ name: 'Insights', url: URLS.INSIGHTS_ADMIN },
	{ name: 'Import', url: URLS.IMPORT_SNIPPETS_ADMIN },
	{ name: 'Settings', url: URLS.SETTINGS_ADMIN },
	{ name: 'Welcome', url: URLS.WELCOME_SCREEN_ADMIN }
]

test.describe('Admin screen h1 headings', () => {
	for (const screen of SCREENS) {
		test(`${screen.name} renders one page heading`, async ({ page }) => {
			await page.goto(screen.url)

			await expect(page.locator('#wpbody-content h1')).toHaveCount(1)
		})
	}
})
