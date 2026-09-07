import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

const EXPECTED_H1_HEADING_COUNT = 1

const SCREENS = [
	{ name: 'Manage Snippets', url: URLS.SNIPPETS_ADMIN },
	{ name: 'Add Snippet', url: URLS.ADD_SNIPPET_ADMIN },
	{ name: 'Community Cloud', url: URLS.COMMUNITY_CLOUD_ADMIN },
	{ name: 'Blueprints', url: URLS.BLUEPRINTS_ADMIN },
	{ name: 'Cloud Library', url: URLS.CLOUD_LIBRARY_ADMIN },
	{ name: 'AI Agent', url: URLS.AI_AGENT_ADMIN },
	{ name: 'Insights', url: URLS.INSIGHTS_ADMIN },
	{ name: 'Import', url: URLS.IMPORT_SNIPPETS_ADMIN },
	{ name: 'Settings', url: URLS.SETTINGS_ADMIN },
	{ name: 'Welcome', url: URLS.WELCOME_SCREEN_ADMIN }
]

test.describe('Admin screen h1 headings', () => {
	for (const screen of SCREENS) {
		test(`${screen.name} renders one page heading`, async ({ page }) => {
			await page.goto(screen.url)

			await expect(page.getByRole('heading', { level: 1 })).toHaveCount(EXPECTED_H1_HEADING_COUNT)
		})
	}
})
