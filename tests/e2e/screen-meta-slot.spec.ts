import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

const SCREENS = [
	{ name: 'Add Snippet', url: URLS.ADD_SNIPPET_ADMIN },
	{ name: 'Manage Snippets', url: URLS.SNIPPETS_ADMIN },
	{ name: 'Cloud Library', url: URLS.CLOUD_LIBRARY_ADMIN },
	{ name: 'Blueprints', url: URLS.BLUEPRINTS_ADMIN },
	{ name: 'AI Agent', url: URLS.AI_AGENT_ADMIN },
	{ name: 'Insights', url: URLS.INSIGHTS_ADMIN },
	{ name: 'Welcome', url: URLS.WELCOME_ADMIN }
]

test.describe('Screen meta slot', () => {
	for (const screen of SCREENS) {
		test(`renders a slot on the ${screen.name} screen`, async ({ page }) => {
			await page.goto(screen.url)

			const screenMetaSlot = page.locator('#snippets-screen-meta-slot')

			await expect(screenMetaSlot.locator('#screen-meta')).toHaveCount(1)
			await expect(screenMetaSlot.locator('#screen-meta-links')).toHaveCount(1)
		})
	}
})
