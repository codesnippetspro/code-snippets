import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

const SCREENS_WITH_HELP = [
	{ name: 'Add Snippet', url: URLS.ADD_SNIPPET_ADMIN },
	{ name: 'Manage Snippets', url: URLS.SNIPPETS_ADMIN },
	// { name: 'Cloud Community', url: URLS.CLOUD_COMMUNITY_ADMIN },
	{ name: 'Cloud Library', url: URLS.CLOUD_LIBRARY_ADMIN },
	{ name: 'Blueprints', url: URLS.BLUEPRINTS_ADMIN },
	{ name: 'AI Agent', url: URLS.AI_AGENT_ADMIN },
	{ name: 'Insights', url: URLS.INSIGHTS_ADMIN },
	{ name: 'Import', url: URLS.IMPORT_ADMIN },
	{ name: 'Settings', url: URLS.SETTINGS_ADMIN },
	{ name: 'Welcome', url: URLS.WELCOME_ADMIN }
]

test.describe('Contextual Help', () => {
	for (const screen of SCREENS_WITH_HELP) {
		test(`${screen.name} exposes its Help tabs`, async ({ page }) => {
			await page.goto(screen.url)

			await page.locator('#contextual-help-link').click()
			const help = page.locator('#contextual-help-wrap')
			await expect(help).toBeVisible()
			await expect(help.locator('.contextual-help-tabs li')).not.toHaveCount(0)
		})
	}
})
